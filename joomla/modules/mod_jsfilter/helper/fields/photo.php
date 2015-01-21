<?php
/*
 * photo.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldPhoto extends JsfilterField
{
	
	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_PHOTO';
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
		$item->text		= JText::_('MJSF_ONLY_WITH_PHOTO');
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
		$manufacturer = (int) $manufacturer;
		
		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		return $this->getAllValues(null);
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

		$query 	= "SELECT
						COUNT(*)
					FROM `#__jshopping_products_images` i
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( i.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						i.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						);
		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count) {
			$values = array('true');
		} else {
			$values = array();
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

		// Формирование условия фильтрации в зависимости от выбранной логики
		$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );

		$withImg = ( preg_match("/'true'/", $condition) ) ? true : false;

		$query 	= "SELECT
						p.`product_id`
					FROM `#__jshopping_products` p
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						`product_publish` = 1
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($withImg)
							? "AND p.`product_id` IN (
								SELECT
									i.`product_id`
								FROM `#__jshopping_products_images` i
								GROUP BY i.`product_id`
								HAVING
									COUNT(*) > 0
								)"
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
