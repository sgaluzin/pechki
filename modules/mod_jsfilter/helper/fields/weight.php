<?php
/*
 * weight.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldWeight extends JsfilterField
{

	// кодовый набор символов для замены в conditions
	private $sep	= '%@#';


	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_WEIGHT';
	}


	// ------------------------------------------------------------------------
	// Получение списка всех доступных значений для текущего поля
	// ------------------------------------------------------------------------
	// ex_field		Значение параметра расширенной конфигурации
	// params		Дополнительные параметры
	// ------------------------------------------------------------------------

	public function getAllValues ($ex_field, &$params)
	{
		$db		= JFactory::getDbo();

		$query = "SELECT
						MIN(`product_weight`) as `min`,
						MAX(`product_weight`) as `max`
					FROM `#__jshopping_products`
					WHERE
						`product_publish` = 1
						AND
						`product_weight` > 0
					UNION
					SELECT
						MIN(a.`weight`) as `min`,
						MAX(a.`weight`) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
					 ON ( a.`product_id` = p.`product_id` )
					WHERE
						p.`product_publish` = 1
						AND
						a.`weight` > 0";
		$db->setQuery( $query );
		$list = $db->loadObjectList();

		$min = null;
		$max = null;
		foreach ($list as &$item) {
			if ( $min === null && isset($item->min) ) $min = $item->min;
			if ( $max === null && isset($item->max) ) $max = $item->max;
			
			$min = (isset($item->min) && $item->min < $min) ? $item->min : $min;
			$max = (isset($item->max) && $item->max > $max) ? $item->max : $max;
		}

		$minObj = new stdClass;
		$minObj->value = $min;
		$minObj->text = $min;

		$maxObj = new stdClass;
		$maxObj->value = $max;
		$maxObj->text = $max;

		return array($minObj, $maxObj);
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

		// Фиксированный список не имеет смысла, т.к. используются только пределы значений.
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
		$db = JFactory::getDbo();
		$manufacturer = (int) $manufacturer;

		$query = "SELECT
						MIN(p.`product_weight`) as `min`,
						MAX(p.`product_weight`) as `max`
					FROM `#__jshopping_products` p
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						AND
						p.`product_weight` > 0
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
					UNION
					SELECT
						MIN(a.`weight`) as `min`,
						MAX(a.`weight`) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						AND
						a.`weight` > 0
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						);
		$db->setQuery( $query );
		$list = $db->loadObjectList();

		$min = null;
		$max = null;
		foreach ($list as &$item) {
			if ( $min === null && isset($item->min) ) $min = $item->min;
			if ( $max === null && isset($item->max) ) $max = $item->max;
			
			$min = (isset($item->min) && $item->min < $min) ? $item->min : $min;
			$max = (isset($item->max) && $item->max > $max) ? $item->max : $max;
		}

		$minObj = new stdClass;
		$minObj->value = $min;
		$minObj->text = $min;

		$maxObj = new stdClass;
		$maxObj->value = $max;
		$maxObj->text = $max;

		return array($minObj, $maxObj);
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
		$db = JFactory::getDbo();

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return array();

		// При пустом списке атрибутов (пустой массив) возвращаются нулевые значения (нет данных)
		if ( $attrList !== null && !count($attrList) ) {
			return array(0, 0);
		}

		$query = "SELECT
						MIN(p.`product_weight`) as `min`,
						MAX(p.`product_weight`) as `max`
					FROM `#__jshopping_products` p
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						AND
						p.`product_weight` > 0
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						)."
					UNION
					SELECT
						MIN(a.`weight`) as `min`,
						MAX(a.`weight`) as `max`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						AND
						a.`weight` > 0
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						)."
						".( ($attrList)
							? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
							: ""
						);
		$db->setQuery( $query );
		$list = $db->loadObjectList();

		$min = null;
		$max = null;
		foreach ($list as &$item) {
			if ( $min === null && isset($item->min) ) $min = $item->min;
			if ( $max === null && isset($item->max) ) $max = $item->max;
			
			$min = (isset($item->min) && $item->min < $min) ? $item->min : $min;
			$max = (isset($item->max) && $item->max > $max) ? $item->max : $max;
		}

		return array( (float)$min, (float)$max );
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
		$db = JFactory::getDbo();

		// Формирование условия фильтрации в зависимости от выбранной логики
		$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );

		$cond1	= str_replace("__".$this->sep."__", 'p.`product_weight`', $condition);
		$cond2	= str_replace("__".$this->sep."__", 'a.`weight`', $condition );

		$query = "SELECT
						p.`product_id`
					FROM `#__jshopping_products` p
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($cond1)
							? "AND ( ".$cond1." )"
							: ""
						)."
					UNION
					SELECT
						a.`product_id`
					FROM `#__jshopping_products_attr` a
					LEFT JOIN `#__jshopping_products` p
						ON ( a.`product_id` = p.`product_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($cond2)
							? "AND ( ".$cond2." )"
							: ""
						);

		$db->setQuery( $query );

		return $db->loadColumn();
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
