<?php
/*
 * price.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldPrice extends JsfilterField
{
	// Поле для определения спец.полей, которые фильтруются отдельно
	public $special	= true;

	// Отфильтрованные значения
	private $values	= null;
	
	// Кодовый набор символов для замены в conditions
	private $sep	= '%@#';


	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_PRICE';

		// Загрузка актуальной валюты
		$jsCfg = JSFactory::getConfig();   
		$jsCfg->loadCurrencyValue();
	}


	// ------------------------------------------------------------------------
	// Получение списка всех доступных значений для текущего поля
	// ------------------------------------------------------------------------
	// ex_field	Значение параметра расширенной конфигурации
	// params	Дополнительные параметры
	// ------------------------------------------------------------------------

	public function getAllValues ($ex_field, &$params)
	{
		$db = JFactory::getDbo();

		// Определение min и max цены
		// из таблицы товаров
		$query	= "SELECT
						MIN(
							IF (
								p.`different_prices`,
								p.`min_price`,
								p.`product_price`
							) / c.`currency_value`
						) as `min`,
						MAX(
							IF (
								p.`different_prices`,
								# будет выбрана из атрибутов
								0,
								p.`product_price`
							) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` ) 
					WHERE
						p.`product_publish` = 1";
		$db->setQuery( $query );
		$plimits = $db->loadObject();

		// из зависимых атрибутов
		$query	= "SELECT
						MIN( a.`price` / c.`currency_value` ) as `min`,
						MAX( a.`price` / c.`currency_value` ) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` ) 
					WHERE
						p.`product_publish` = 1";
		$db->setQuery( $query );
		$alimits = $db->loadObject();

		// из независимых атрибутов
		$query	= "SELECT
						MIN(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `min`,
						MAX(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products_attr2` a2
					LEFT JOIN `#__jshopping_products` p
						ON ( a2.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_products_attr` a
						ON ( a2.`product_id` = a.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` ) 
					WHERE
						p.`product_publish` = 1";
		$db->setQuery( $query );
		$a2limits = $db->loadObject();

		// Из дополнительных цен (определение только максимальной цены)
		$query	= "SELECT
						p.`product_price` * (100 - MIN(pr.`discount`)) / 100 / c.`currency_value` as `max`
					FROM `#__jshopping_products_prices` pr
					LEFT JOIN `#__jshopping_products` p
						ON ( pr.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					WHERE
						p.`product_publish` = 1";
		$db->setQuery( $query );
		$prlimits = $db->loadObject();

		if ($prlimits) {
			$prlimits->min = $prlimits->max;
		}

		$jsCfg = JSFactory::getConfig();
		$factor = pow(10, (int)$jsCfg->decimal_count);

		// Определение общего min и max
		$min		= new stdClass;
		$min->value	= ($plimits->min > 0) ? $plimits->min : null;
		$min->value	= ($alimits->min > 0 && $min->value > $alimits->min) ? $alimits->min : $min->value;
		$min->value	= ($a2limits->min > 0 && $min->value > $a2limits->min) ? $a2limits->min : $min->value;
		$min->value	= ($prlimits->min > 0 && $min->value > $prlimits->min) ? $prlimits->min : $min->value;
		// Конвертация значения из основной валюты в текущую
		$min->value *= $jsCfg->currency_value;
		// Округление в меньшую сторону до N знаков после запятой
		$min->value	*= $factor;
		$min->value = floor($min->value);
		$min->value /= $factor;
		$min->text	= $min->value;
		
		$max		= new stdClass;
		$max->value	= ($plimits->max > 0) ? $plimits->max : null;
		$max->value	= ($alimits->max > 0 && $max->value < $alimits->max) ? $alimits->max : $max->value;
		$max->value	= ($a2limits->max > 0 && $max->value < $a2limits->max) ? $a2limits->max : $max->value;
		$max->value	= ($prlimits->max > 0 && $max->value < $prlimits->max) ? $prlimits->max : $max->value;
		// Конвертация значения из основной валюты в текущую
		$max->value *= $jsCfg->currency_value;
		// Округление в большую сторону до N знаков после запятой
		$max->value	*= $factor;
		$max->value = ceil($max->value);
		$max->value /= $factor;
		$max->text	= $max->value;

		return array($min, $max);
	}


	// ------------------------------------------------------------------------
	// Формирование списка для фиксированного набора значений
	// ------------------------------------------------------------------------
	// cfg		Конфигурация блока (строка конструктора)
	// params	Дополнительные параметры
	// ------------------------------------------------------------------------
	
	public function getFixedValues (&$cfg, &$params)
	{
		if ( !isset($cfg->values->list) ) return array();

		// Фиксированный список для цен не имеет смысла, т.к. используются только пределы значений.
		// Поэтому возвращаются оба значения из общего списка.
		return $this->getAllValues();
	}


	// ------------------------------------------------------------------------
	// Формирование списка для динамического набора значений
	// ------------------------------------------------------------------------
	// cid		Категории, для которых осуществляется выбор значений
	// cfg		Конфигурация блока (строка конструктора)
	// params	Дополнительные параметры
	// ------------------------------------------------------------------------
	
	public function getDynamicValues (&$cid, $manufacturer, &$cfg, &$params)
	{
		$db 	= JFactory::getDbo();
		$manufacturer = (int) $manufacturer;
		$values = array();

		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений (сам список игнорируется)
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		// Сортировка пределов не имеет смысла
		// $needOrder = ($cfg->b_mode == 3) ? true : false;

		// Определение min и max цены
		// из таблицы товаров
		$query	= "SELECT
						MIN(
							IF (
								p.`different_prices`,
								p.`min_price`,
								p.`product_price`
							) / c.`currency_value`
						) as `min`,
						MAX(
							IF (
								p.`different_prices`,
								# будет выбрана из атрибутов
								0,
								p.`product_price`
							) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						);
		$db->setQuery( $query );
		$plimits = $db->loadObject();

		// из зависимых атрибутов
		$query	= "SELECT
						MIN( a.`price` / c.`currency_value` ) as `min`,
						MAX( a.`price` / c.`currency_value` ) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						);
		$db->setQuery( $query );
		$alimits = $db->loadObject();

		// из независимых атрибутов
		$query	= "SELECT
						MIN(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `min`,
						MAX(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products_attr2` a2
					LEFT JOIN `#__jshopping_products` p
						ON ( a2.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_products_attr` a
						ON ( a2.`product_id` = a.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						);
		$db->setQuery( $query );
		$a2limits = $db->loadObject();

		// Из дополнительных цен (определенеи только максимальной цены)
		$query	= "SELECT
						p.`product_price` * (100 - MIN(pr.`discount`)) / 100 / c.`currency_value` as `max`
					FROM `#__jshopping_products_prices` pr
					LEFT JOIN `#__jshopping_products` p
						ON ( pr.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						);
		$db->setQuery( $query );
		$prlimits = $db->loadObject();

		$jsCfg = JSFactory::getConfig();
		$factor = pow(10, (int)$jsCfg->decimal_count);

		// Определение общего min и max
		$min		= new stdClass;
		$min->value	= ($plimits->min > 0) ? $plimits->min : null;
		$min->value	= ($alimits->min > 0 && $min->value > $alimits->min) ? $alimits->min : $min->value;
		$min->value	= ($a2limits->min > 0 && $min->value > $a2limits->min) ? $a2limits->min : $min->value;
		$min->value	= ($prlimits->max > 0 && $min->value > $prlimits->max) ? $prlimits->max : $min->value;
		// Конвертация значения из основной валюты в текущую
		$min->value *= $jsCfg->currency_value;
		// Округление в меньшую сторону до N знаков после запятой
		$min->value	*= $factor;
		$min->value = floor($min->value);
		$min->value /= $factor;
		$min->text	= $min->value;
		
		$max		= new stdClass;
		$max->value	= ($plimits->max > 0) ? $plimits->max : null;
		$max->value	= ($alimits->max > 0 && $max->value < $alimits->max) ? $alimits->max : $max->value;
		$max->value	= ($a2limits->max > 0 && $max->value < $a2limits->max) ? $a2limits->max : $max->value;
		$max->value	= ($prlimits->max > 0 && $max->value < $prlimits->max) ? $prlimits->max : $max->value;
		// Конвертация значения из основной валюты в текущую
		$max->value *= $jsCfg->currency_value;
		// Округление в большую сторону до N знаков после запятой
		$max->value	*= $factor;
		$max->value = ceil($max->value);
		$max->value /= $factor;
		$max->text	= $max->value;

		return array($min, $max);
	}


	// ------------------------------------------------------------------------
	// Формирование списка доступных значений для указанных товаров
	// ------------------------------------------------------------------------
	// pids		Список ID товаров
	// catList	Список категорий для поиска
	// cfg		Конфигурация
	// attrList	Дополнительный список фильтрации (зависимые атрибуты)
	// valType	Тип значений: ID - false, названия - true
	// \return	Массив ID актуальных значений параметра
	// ------------------------------------------------------------------------

	public function getActiveValues (&$pids, &$catList, &$cfg, &$attrList, $valType)
	{
		$db 	= JFactory::getDbo();
		$values = array();

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return array();

		if (!$this->values && $this->values !== null) return array();
		
		// Определение min и max цены
		// из таблицы товаров
		$query	= "SELECT
						MIN(
							IF (
								p.`different_prices`,
								# будет выбрана из атрибутов
								NULL,
								p.`product_price`
							) / c.`currency_value`
						) as `min`,
						MAX(
							IF (
								p.`different_prices`,
								# будет выбрана из атрибутов
								NULL,
								p.`product_price`
							) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND cat.`category_id` IN (".join(',', $catList).")"
							: ''
						);
		$db->setQuery( $query );
		$plimits = $db->loadObject();

		// из зависимых атрибутов
		$query	= "SELECT
						MIN( a.`price` / c.`currency_value` ) as `min`,
						MAX( a.`price` / c.`currency_value` ) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND cat.`category_id` IN (".join(',', $catList).")"
							: ''
						)."
						".( ($this->values)
								? "AND a.`price` BETWEEN ".$this->values[0]." AND ".$this->values[1]
								: ""
						)."
						".( ($attrList !== null)
								? ( ($attrList)
									? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
									: "AND 1 != 1"
								  )
								: ""
						);
		$db->setQuery( $query );
		$alimits = $db->loadObject();

		// из независимых атрибутов
		$query	= "SELECT
						MIN(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `min`,
						MAX(
							(CASE a2.`price_mod`
								WHEN '+'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
								WHEN '-'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
								WHEN '*'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
								WHEN '/'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
								WHEN '%'
									THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
								ELSE
									p.`product_price`
							END) / c.`currency_value`
						) as `max`
					FROM `#__jshopping_products_attr2` a2
					LEFT JOIN `#__jshopping_products` p
						ON ( a2.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_products_attr` a
						ON ( a2.`product_id` = a.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND cat.`category_id` IN (".join(',', $catList).")"
							: ''
						);
		$db->setQuery( $query );
		$a2limits = $db->loadObject();

		// Дополнительные цены
		$query	= "SELECT
						p.`product_price` * (100 - MAX(pr.`discount`)) / 100 / c.`currency_value` as `min`,
						p.`product_price` * (100 - MIN(pr.`discount`)) / 100 / c.`currency_value` as `max`
					FROM `#__jshopping_products_prices` pr
					LEFT JOIN `#__jshopping_products` p
						ON ( pr.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
						ON ( p.`currency_id` = c.`currency_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND cat.`category_id` IN (".join(',', $catList).")"
							: ''
						);
		$db->setQuery( $query );
		$prlimits = $db->loadObject();

		$jsCfg = JSFactory::getConfig();
		$factor = pow(10, (int)$jsCfg->decimal_count);

		// Определение общего min и max
		$min = ($plimits->min > 0) ? $plimits->min : null;
		$min = ($alimits->min > 0 && ($min > $alimits->min || $min === null) ) ? $alimits->min : $min;
		$min = ($a2limits->min > 0 && ($min > $a2limits->min || $min === null) ) ? $a2limits->min : $min;
		// $min = ($prlimits->max > 0 && $min > $prlimits->max) ? $prlimits->max : $min;
		$min = ($prlimits->min > 0 && ($min > $prlimits->min || $min === null) ) ? $prlimits->min : $min;
		if ($min !== null) {
			// Конвертация значения из основной валюты в текущую
			$min *= $jsCfg->currency_value;
			// Округление в меньшую сторону до N знаков после запятой
			$min *= $factor;
			$min = floor($min);
			$min /= $factor;
		}
		
		$max = ($plimits->max > 0) ? $plimits->max : null;
		$max = ($alimits->max > 0 && ($max < $alimits->max || $max === null) ) ? $alimits->max : $max;
		$max = ($a2limits->max > 0 && ($max < $a2limits->max || $max === null) ) ? $a2limits->max : $max;
		$max = ($prlimits->max > 0 && ($max < $prlimits->max || $max === null) ) ? $prlimits->max : $max;
		if ($max !== null) {
			// Конвертация значения из основной валюты в текущую
			$max *= $jsCfg->currency_value;
			// Округление в большую сторону до N знаков после запятой
			$max *= $factor;
			$max = ceil($max);
			$max /= $factor;
		}

		if ($min === null) $min = $max;
		if ($max === null) $max = $min;

		// Корректировка списка зависимых атрибутов (т.к. special = true)
		if ($attrList) {
			$query = "SELECT
						`product_id`,
						`product_attr_id`
					FROM `#__jshopping_products_attr`
					WHERE
						`product_id` IN (".join(',', $pids).")
						AND
						`product_attr_id` IN ( ".join(',', $attrList)." )
						AND
						`price` BETWEEN ".(float)$min." AND ".(float)$max;
			$db->setQuery($query);
			$pids = array_unique( $db->loadColumn(0) );
			$attrList = $db->loadColumn(1);
		}

		return array($min, $max);
	}


	// ------------------------------------------------------------------------
	// Выполнение фильтрации данных
	// ------------------------------------------------------------------------
	// conditions	Условия для выбора даннных из БД (where)
	// cfg			Конфигурация блока (строка конструктора)
	// cid			Категории, в которых выполняется поиск товаров
	// attrList		Дополнительный список фильтрации (зависимые атрибуты)
	// \return		Массив ID отфильтрованных товаров
	// ------------------------------------------------------------------------

	public function doFilter ($conditions, $manufacturer, &$cfg, &$cid, &$attrList)
	{
		$db		= JFactory::getDbo();
		$jsCfg  = JSFactory::getConfig();
		$pids	= array();

		// Формирование условия фильтрации в зависимости от выбранной логики
		$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );
		
		// Поиск в таблице товаров
		$pcond	= str_replace(
								"__".$this->sep."__",
								'IF(
									p.`min_price`,
									p.`min_price`,
									p.`product_price`
								) / c.`currency_value` * '.$jsCfg->currency_value,
								$condition
							);
		$query	= "SELECT
						p.`product_id`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_currencies` c
							ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($pcond)
							? "AND ( ".$pcond." )"
							: ""
						);
		$db->setQuery( $query );
		$pids = array_merge( $pids, $db->loadColumn() );

		// Поиск в таблице зависимых атрибутов
		$acond	= str_replace(
								"__".$this->sep."__",
								'a.`price` / c.`currency_value` * '.$jsCfg->currency_value,
								$condition
							);
		$query	= "SELECT
						p.`product_id`,
						a.`price`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
							ON ( a.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
							ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($acond)
							? "AND ( ".$acond." )"
							: ""
						)."
						".( ($attrList !== null)
								? ( ($attrList)
									? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
									: "AND 1 != 1"
								  )
								: ""
						);
		$db->setQuery( $query );
		$pids = array_merge( $pids, $db->loadColumn() );

		// Сохранение отфильтрованных значений (для зависимых атрибутов)
		$attrPriceList = $db->loadColumn(1);
		if ($attrPriceList) {
			$this->values = array( min($attrPriceList), max($attrPriceList) );
		} else {
			$this->values = array(0, 0);
		}


		// Поиск в таблице независимых атрибутов
		$a2cond	= str_replace(
								"__".$this->sep."__",
								"CASE a2.`price_mod`
									WHEN '+'
										THEN IF (p.`different_prices`, a.`price`, p.`product_price`) + a2.`addprice`
									WHEN '-'
										THEN IF (p.`different_prices`, a.`price`, p.`product_price`) - a2.`addprice`
									WHEN '*'
										THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice`
									WHEN '/'
										THEN IF (p.`different_prices`, a.`price`, p.`product_price`) / a2.`addprice`
									WHEN '%'
										THEN IF (p.`different_prices`, a.`price`, p.`product_price`) * a2.`addprice` / 100
									ELSE
										p.`product_price`
								END
								/ c.`currency_value` * ".$jsCfg->currency_value,
								$condition
							);
		$query	= "SELECT
						a2.`product_id`
					FROM `#__jshopping_products_attr2` a2
					LEFT JOIN `#__jshopping_products` p
							ON ( a2.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_products_attr` a
						ON ( a2.`product_id` = a.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
							ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( a2.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($a2cond)
							? "AND ( ".$a2cond." )"
							: ""
						);
		$db->setQuery( $query );
		$pids = array_merge( $pids, $db->loadColumn() );

		// Поиск по дополнительным ценам
		$prcond	= str_replace(
								"__".$this->sep."__",
								"(p.`product_price` * (100 - pr.`discount`) / 100 / c.`currency_value` * ".$jsCfg->currency_value.")",
								$condition
							);
		$query	= "SELECT
						p.`product_id`
					FROM `#__jshopping_products_prices` pr
					LEFT JOIN `#__jshopping_products` p
							ON ( pr.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_currencies` c
							ON ( p.`currency_id` = c.`currency_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` cat "
							."ON ( p.`product_id` = cat.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND cat.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($prcond)
							? "AND ( ".$prcond." )"
							: ""
						)."
					GROUP BY p.`product_id`";
		$db->setQuery( $query );
		$pids = array_merge( $pids, $db->loadColumn() );

		// Удаление дублирующихся значений
		$pids = array_unique($pids);

		return $pids;
	}


	// ------------------------------------------------------------------------
	// Получение имени поля со значениями id (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondID ()
	{
		return "__".$this->sep."__";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		return "__".$this->sep."__";
	}

}
