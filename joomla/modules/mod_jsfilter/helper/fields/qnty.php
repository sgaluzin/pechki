<?php
/*
 * qnty.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldQnty extends JsfilterField
{
	// Поле для определения спец.полей, которые фильтруются отдельно
	public $special	= true;

	// Отфильтрованные значения
	private $values	= null;

	
	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_QNTY';
	}


	// ------------------------------------------------------------------------
	// Получение списка всех доступных значений для текущего поля
	// ------------------------------------------------------------------------
	// ex_field		Значение параметра расширенной конфигурации
	// params		Дополнительные параметры
	// ------------------------------------------------------------------------

	public function getAllValues ($ex_field, &$params)
	{
		$db = JFactory::getDbo();

		$query = "SELECT
						MIN(p.`product_quantity`) as `min`,
						MAX(p.`product_quantity`) as `max`
					FROM `#__jshopping_products` as p
					WHERE
						p.`product_publish` = 1";
		$db->setQuery( $query );
		$limits = $db->loadObject();

		$min		= new stdClass;
		$min->value	= round($limits->min, 2);
		$min->text	= $min->value;
		
		$max		= new stdClass;
		$max->value	= round($limits->max, 2);
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

		if (!$cid && !$manufacturer) return $values;

		// Подсчет пределов количества товаров
		$query = "SELECT
						MIN(p.`product_quantity`) as `min`,
						MAX(p.`product_quantity`) as `max`,
						MIN(a.`count`) as `amin`,
						MAX(a.`count`) as `amax`
					FROM `#__jshopping_products` as p
					LEFT JOIN `#__jshopping_products_attr` as a
						ON (p.`product_id` = a.`product_id`)
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
						);
		$db->setQuery( $query );
		$limits = $db->loadObject();

		$min		= new stdClass;
		$min->value	= ($limits->amin < $limits->min) ? $limits->amin : $limits->min;
		$min->value	= round($min->value, 2);
		$min->text	= $min->value;
		
		$max		= new stdClass;
		$max->value	= ($limits->amax > $limits->max) ? $limits->amax : $limits->max;
		$max->value	= round($max->value, 2);
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
		$db = JFactory::getDbo();

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return array();

		// При пустом списке атрибутов (пустой массив) возвращаются нулевые значения (нет данных)
		if ( $attrList !== null && !count($attrList) ) {
			return array(0, 0);
		}

		// Подсчет пределов количества товаров
		$query = "SELECT
						MIN(p.`product_quantity`) as `min`,
						MAX(p.`product_quantity`) as `max`,
						MIN(a.`count`) as `amin`,
						MAX(a.`count`) as `amax`
					FROM `#__jshopping_products` as p
					LEFT JOIN `#__jshopping_products_attr` as a
						ON (p.`product_id` = a.`product_id`)
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						)."
						".( ($attrList)
							? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
							: ""
						);
		$db->setQuery( $query );
		$limits = $db->loadObject();

		if ($attrList) {
			$min = $limits->amin;
			$max = $limits->amax;
		} else {
			$min = ($limits->amin < $limits->min) ? $limits->amin : $limits->min;
			$max = ($limits->amax > $limits->max) ? $limits->amax : $limits->max;
		}
		
		$min = round($min, 2);
		$max = round($max, 2);

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
						".( ($this->values)
								? "AND `count` BETWEEN ".$this->values[0]." AND ".$this->values[1]
								: ""
						);
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
		$db = JFactory::getDbo();

		// Проверка наличия допустимых значений для фильтрации
		// null - доступны все значения
		if (!$attrList && $attrList !== null) return;

		// Формирование условия фильтрации в зависимости от выбранной логики
		$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );

		// Замена поля количества при фильтрации по зависимым атрибутам
		if ($attrList) {
			$condition = str_replace($this->getCondID(), "a.`count`", $condition);
		}
		
		$query 	= "SELECT
					p.`product_id`
					".( ($attrList)
						? ", a.`count`"
						: ""
					)."
				FROM `#__jshopping_products` as p
				".( ($cid)
					? "LEFT JOIN `#__jshopping_products_to_categories` c "
						."ON ( p.`product_id` = c.`product_id` )"
					: ''
				)."
				".( ($attrList)
					? "LEFT JOIN `#__jshopping_products_attr` a "
						."ON (p.`product_id` = a.`product_id`)"
					: ""
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
					".( ($attrList)
						? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
						: ""
					)."
					".( ($condition)
						? "AND ( ".$condition." )"
						: ""
				);
		$db->setQuery( $query );
		$pids = $db->loadColumn();
		
		if ($attrList) {
			// Сохранение отфильтрованных значений
			$data = $db->loadColumn(1);

			$this->values = ($data) ? array( min($data), max($data) ) : array(0, 0);

			// Формирование списка ID записей зависимых атрибутов (доп.фильтрация)
			if (!$pids) {
				$attrList = array();
			} else {
				$query = "SELECT
							a.`product_id`,
							a.`product_attr_id`
						FROM `#__jshopping_products_attr` a
						WHERE
							`product_id` IN (".join(',', $pids).")
							".( ($attrList)
								? "AND `product_attr_id` IN ( ".join(',', $attrList)." )"
								: ""
							)."
							".( ($condition)
								? "AND ( ".$condition." )"
									: ""
							);
				$db->setQuery($query);
				$pids = array_unique( $db->loadColumn(0) );
				$attrList = $db->loadColumn(1);
			}
		} else {
			$this->values = null;
		}

		return $pids;
	}


	// ------------------------------------------------------------------------
	// Получение имени поля со значениями id (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondID ()
	{
		return "p.`product_quantity`";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		return "p.`product_quantity`";
	}


}
