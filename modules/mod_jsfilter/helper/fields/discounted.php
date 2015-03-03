<?php
/*
 * price.php
 * 
 * Copyright 2014 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldDiscounted extends JsfilterField
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
		$this->title= 'MJSF_FIELD_DISCOUNTED';
	}


	// ------------------------------------------------------------------------
	// Получение списка всех доступных значений для текущего поля
	// ------------------------------------------------------------------------
	// ex_field		Значение параметра расширенной конфигурации
	// params		Дополнительные параметры
	// ------------------------------------------------------------------------

	public function getAllValues ($ex_field, &$params)
	{
		$item			= new stdClass;
		$item->text		= JText::_('MJSF_DISCOUNTED');
		$item->value	= 'true';

		return array($item);
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
		
		return $this->getAllValues(null);
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
		// Подбор значений только внутри категории
		if (!$cid) return array();
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		return $this->getAllValues(null, $dummy);
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
		
		$query = "SELECT
						COUNT(*)
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_products_attr` as a
						ON (p.`product_id` = a.`product_id`)
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_id` IN (".join(',', $pids).")
						AND
						(
							(p.`product_old_price` > 0 AND p.`product_price` < p.`product_old_price`)
							OR
							(a.`old_price` > 0 AND a.`price` < a.`old_price`)
						)
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						)."
						".( ($attrList !== null)
								? ( ($attrList)
									? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
									: "AND 1 != 1"
								  )
								: ""
						);
		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count) {
			$values = array('true');
		} else {
			$values = array();
		}

		// Корректировка списка зависимых атрибутов (т.к. special = true)
		if ($attrList && $this->values) {
			$query = "SELECT
						`product_id`,
						`product_attr_id`
					FROM `#__jshopping_products_attr`
					WHERE
						`product_id` IN (".join(',', $pids).")
						AND
						`product_attr_id` IN ( ".join(',', $attrList)." )
						".( ($this->values)
							? "AND (`old_price` > 0 AND `price` < `old_price`)"
							: ""
						);
			$db->setQuery($query);
			$pids = array_unique( $db->loadColumn(0) );
			$attrList = $db->loadColumn(1);
		}

		return $values;
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
		$onlyDiscounted = ( preg_match("/'true'/", $condition) ) ? true : false;

		// Отфильтрованные значения
		$this->values = $onlyDiscounted;
		
		$query 	= "SELECT
						p.`product_id`
					FROM `#__jshopping_products` p
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
						)."
						".( ($attrList)
							? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
							: ""
						)."
						".( ($onlyDiscounted)
							? "AND (
								(p.`product_old_price` > 0 AND p.`product_price` < p.`product_old_price`)
								OR
								(a.`old_price` > 0 AND a.`price` < a.`old_price`)
							  )"
							: ""
						)."
					GROUP BY p.`product_id`";
		$db->setQuery( $query );
		$pids = $db->loadColumn();

		if ($attrList) {
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
							".( ($onlyDiscounted)
								? ( ($attrList)
									? "AND (a.`old_price` > 0 AND a.`price` < a.`old_price`)"
									: "AND (p.`product_old_price` > 0 AND p.`product_price` < p.`product_old_price`)"
								)
								: ""
							);
				$db->setQuery($query);
				$pids = array_unique( $db->loadColumn(0) );
				$attrList = $db->loadColumn(1);
			}
		}

		return $pids;
	}


	// ------------------------------------------------------------------------
	// Получение имени поля со значениями id (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondID ()
	{
		return "``";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		return "``";
	}

}
