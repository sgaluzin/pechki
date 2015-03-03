<?php
/*
 * cat.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang() и buildTreeCategory()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'functions.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");


class JsfilterFieldCat extends JsfilterField
{
	protected $values = array();

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_CAT';
	}


	// ------------------------------------------------------------------------
	// Получение списка всех доступных значений для текущего поля
	// ------------------------------------------------------------------------
	// ex_field		Значение параметра расширенной конфигурации
	// params		Дополнительные параметры
	// ------------------------------------------------------------------------

	public function getAllValues ($ex_field, &$params)
	{
		if (!$this->values) {
			$tree = buildTreeCategory();

			foreach ($tree as &$cat) {
				$obj		= new stdClass;
				$obj->text	= $cat->name;
				$obj->value	= $cat->category_id;

				$this->values[] = $obj;
			}
		}

		return $this->values;
	}


	// ------------------------------------------------------------------------
	// Формирование списка для фиксированного набора значений
	// ------------------------------------------------------------------------
	// cfg		Конфигурация блока (строка конструктора)
	// params	Дополнительные параметры
	// ------------------------------------------------------------------------
	
	public function getFixedValues (&$cfg, &$params)
	{
		$values = array();

		$cfg->values->list = (array) $cfg->values->list;
		
		if ($cfg->values->list) {
			$tree = buildTreeCategory();

			foreach ($tree as &$cat) {
				if ( !in_array($cat->category_id, $cfg->values->list) ) continue;
				
				$obj		= new stdClass;
				$obj->text	= $cat->name;
				$obj->value	= $cat->category_id;
				$values[] 	= $obj;
			}
		}

		return $values;
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
		$values = array();

		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		if ($manufacturer) {
			$db = JFactory::getDbo();
			$q = "SELECT
					c.`category_id`
				FROM `#__jshopping_products_to_categories` c
				".( ($manufacturer)
						? "LEFT JOIN #__jshopping_products` p "
							."ON ( c.`product_id` = p.`product_id` )"
						: ""
				)."
				WHERE
					1 = 1
					".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
					)."
					".( ($cid)
						? "AND c.`category_id` IN (".join(',', $cid).")"
						: ""
					);
			$db->setQuery($q);
			$cid = $db->loadColumn();
		}

		$tree = buildTreeCategory();
		foreach ($tree as &$cat) {
			if ( in_array($cat->category_id, $cid) ) {
				$obj		= new stdClass;
				$obj->text	= $cat->name;
				$obj->value	= $cat->category_id;
				$values[] 	= $obj;
			}
		}
		
		return $values;
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
		$values = array();

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return $values;

		$q = "SELECT
				DISTINCT `category_id`
			FROM `#__jshopping_products_to_categories`
			WHERE
				`product_id` IN (".join(',', $pids).")
				".( ($catList)
						? "AND `category_id` IN (".join(',', $catList).")"
						: ''
				);

		$db->setQuery($q);
		$values = $db->loadColumn();
		
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
		$condition = join(" OR ", $conditions);

		$query	= "SELECT
						c.`product_id`
						".( ($conditions && $cfg['values']['logic'])
							? ", COUNT(c.`product_id`) as cnt"
							: ""
						)."
					FROM `#__jshopping_products_to_categories` c
					LEFT JOIN `#__jshopping_products` p
						ON ( c.`product_id` = p.`product_id` )
					LEFT JOIN `#__jshopping_categories` cn
						ON ( c.`category_id` = cn.`category_id` )
					WHERE
						p.`product_publish` = 1
						AND
						cn.`category_publish` = 1
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
						)."
					GROUP BY c.`product_id`
						".( ($conditions && $cfg['values']['logic'])
							? "HAVING cnt = ".count($conditions)
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
		return "c.`category_id`";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		$jslang = JSFactory::getLang();
		
		return "cn.`".$jslang->get('name')."`";
	}
	
}
