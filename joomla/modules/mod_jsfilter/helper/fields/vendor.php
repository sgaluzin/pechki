<?php
/*
 * vendor.php
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


class JsfilterFieldVendor extends JsfilterField
{
	// Кодовый набор символов для замены в conditions
	private $sep	= '%@#';
	

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_VENDOR';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		$html = '<select id="struct_ex_field" name="cfg[struct][ex_field][]">'
					.'<option value="0">'.JText::_('MJSF_VENDOR_FORMAT_NAME').'</option>'
					.'<option value="1">'.JText::_('MJSF_VENDOR_FORMAT_NAME_LNAME').'</option>'
					.'<option value="2">'.JText::_('MJSF_VENDOR_FORMAT_SHOP').'</option>'
					.'<option value="3">'.JText::_('MJSF_VENDOR_FORMAT_COMPANY').'</option>'
				.'</select>';
		
		return array(JText::_('MJSF_EXT_SETTINGS_VENDOR_TIP'), $html);
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

		switch ((int)$ex_field) {
			default:
			case 0: $format = "`f_name`"; break;
			case 1: $format = "CONCAT_WS(' ', `f_name`, `l_name`)"; break;
			case 2: $format = "`shop_name`"; break;
			case 3: $format = "`company_name`"; break;
		}

		$query = "SELECT
						".$format." as `text`,
						`id` as `value`
					FROM `#__jshopping_vendors`
					WHERE
						`publish` = 1
					ORDER BY `text`";

		$db->setQuery( $query );

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
		$db = JFactory::getDbo();

		// Проверка заполнения списка значений
		if ( !isset($cfg->values->list) ) return array();

		switch ((int)$cfg->ex_field) {
			default:
			case 0: $format = "`f_name`"; break;
			case 1: $format = "CONCAT_WS(' ', `f_name`, `l_name`)"; break;
			case 2: $format = "`shop_name`"; break;
			case 3: $format = "`company_name`"; break;
		}

		$cfg->values->list = (array) $cfg->values->list;

		$query = "SELECT
						".$format." as `text`,
						`id` as `value`
					FROM `#__jshopping_vendors`
					WHERE
						`publish` = 1
						AND
						`id` IN (".join(',', $cfg->values->list).")
					ORDER BY `text`";

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
		$db = JFactory::getDbo();
		$manufacturer = (int) $manufacturer;
		$values = array();

		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		// Сортировка для популярных значений
		$needOrder = ($cfg->b_mode == 3) ? true : false;

		switch ((int)$cfg->ex_field) {
			default:
			case 0: $format = "v.`f_name`"; break;
			case 1: $format = "CONCAT_WS(' ', v.`f_name`, v.`l_name`)"; break;
			case 2: $format = "v.`shop_name`"; break;
			case 3: $format = "v.`company_name`"; break;
		}

		$cfg->values->list = (array) $cfg->values->list;

		$query = "SELECT
						".$format." as `text`,
						v.`id` as `value`
					FROM `#__jshopping_vendors` v
					LEFT JOIN `#__jshopping_products` p
						ON ( v.`id` = p.`vendor_id` )
					LEFT JOIN `#__jshopping_products_to_categories` c
						ON ( p.`product_id` = c.`product_id` )
					WHERE
						v.`publish` = 1
						AND
						p.`product_publish` = 1
						AND
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ""
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($cfg->values->selection == 0)
							? "AND v.`id` IN (".join(',', $cfg->values->list).")"
							: ''
						)."
					GROUP BY v.`id`
					".( ($needOrder)
						? "ORDER BY COUNT(v.`id`) DESC, c.`product_ordering`"
						: ''
					);
		$db->setQuery($query);

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

		$query = "SELECT
						DISTINCT v.`id`
					FROM `#__jshopping_vendors` v
					LEFT JOIN `#__jshopping_products` p
						ON ( v.`id` = p.`vendor_id` )
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						v.`publish` = 1
						AND
						p.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ''
						);
		$db->setQuery($query);

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

		switch ((int)$cfg->ex_field) {
			default:
			case 0: $format = "v.`f_name`"; break;
			case 1: $format = "CONCAT_WS(' ', v.`f_name`, v.`l_name`)"; break;
			case 2: $format = "v.`shop_name`"; break;
			case 3: $format = "v.`company_name`"; break;
		}

		// Формирование условия фильтрации в зависимости от выбранной логики
		$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );
		$condition = str_replace(
								"__".$this->sep."__",
								$format,
								$condition
							);

		$query = "SELECT
						`product_id`
					FROM `#__jshopping_products` p
					LEFT JOIN `#__jshopping_vendors` v
						ON (p.`vendor_id` = v.`id`)
					".( ($cid)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( p.`product_id` = c.`product_id` )"
						: ''
					)."
					WHERE
						p.`product_publish` = 1
						AND v.`publish` = 1
						".( ($cid)
							? "AND c.`category_id` IN (".join(',', $cid).")"
							: ''
						)."
						".( ($manufacturer)
							? "AND p.`product_manufacturer_id` = ".$manufacturer
							: ""
						)."
						".( ($condition)
							? " AND ( ".$condition." )"
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
		return "p.`vendor_id`";
	}
	

	// ------------------------------------------------------------------------
	// Получение имени поля со значениями названий (см. doFilter())
	// ------------------------------------------------------------------------

	public function getCondName ()
	{
		return "__".$this->sep."__";
	}

}
