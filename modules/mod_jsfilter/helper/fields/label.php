<?php
/*
 * label.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");

jimport('joomla.utilities.arrayhelper');


class JsfilterFieldLabel extends JsfilterField
{
	
	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_LABEL';
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
		$lang	= JSFactory::getLang();
		$query	= "SELECT
						`id` as `value`,
						`".$lang->get('name')."` as `text`
					FROM `#__jshopping_product_labels`
					ORDER BY `".$lang->get('name')."`";
		$db->setQuery($query);

		return $db->loadObjectList();
	}


	// ------------------------------------------------------------------------
	// Формирование списка для фиксированного набора значений
	// ------------------------------------------------------------------------
	// cfg		Конфигурация блока (строка конструктора)
	// params	Дополнительные параметры
	// ------------------------------------------------------------------------
	
	public function getFixedValues (&$cfg, &$params)
	{
		$db		= JFactory::getDbo();
		$lang	= JSFactory::getLang();

		if ( !isset($cfg->values->list) ) return array();

		$cfg->values->list = (array) $cfg->values->list;

		$query	= "SELECT
						`id` as `value`,
						`".$lang->get('name')."` as `text`
					FROM `#__jshopping_product_labels`
					WHERE `id` IN (".join(',', $cfg->values->list).")
					ORDER BY `".$lang->get('name')."`";
		$db->setQuery( $query );

		return $db->loadObjectList();
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
		$db		= JFactory::getDbo();
		$lang	= JSFactory::getLang();
		$manufacturer = (int) $manufacturer;
		$values = array();

		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		// Сортировка для популярных значений
		$needOrder = ($cfg->b_mode == 3) ? true : false;

		$cfg->values->list = (array) $cfg->values->list;

		$query	= "SELECT
						l.`id` as `value`,
						l.`".$lang->get('name')."` as `text`
					FROM `#__jshopping_product_labels` l
					LEFT JOIN `#__jshopping_products` p
							ON ( l.`id` = p.`label_id` )
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ""
					)."
					WHERE
						p.`product_publish` = 1
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ""
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($cfg->values->selection == 0)
							? "AND l.`id` IN (".join(',', $cfg->values->list).")"
							: ''
						)."
					GROUP BY l.`id`
					".( ($needOrder)
						? "ORDER BY COUNT(l.`id`) DESC, c.`product_ordering`"
						: "ORDER BY l.`".$lang->get('name')."`"
					);
		$db->setQuery( $query );

		return $db->loadObjectList();
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

		$query	= "SELECT
						DISTINCT p.`label_id`
					FROM `#__jshopping_products` p
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ""
					)."
					WHERE
						".( ($pids)
							? "p.`product_id` IN (".join(',', $pids).")"
							: "1 != 1"
						)."
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ""
						);
		$db->setQuery( $query );

		return $db->loadColumn();
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

		$query 	= "SELECT
						p.`product_id`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_product_labels` l
						ON (p.`label_id` = l.`id`)
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
						".( ($condition)
							? "AND ( ".$condition." )"
							: ""
						);
		$db->setQuery($query);

		return $db->loadColumn();
	}


	// ------------------------------------------------------------------------
	// Получение имени поля со значениями id (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondID ()
	{
		return "`label_id`";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		return "l.`name`";
	}

}
