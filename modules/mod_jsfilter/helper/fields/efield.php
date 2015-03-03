<?php
/*
 * efield.php
 * 
 * Copyright 2012 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

// для JSFactory::getLang()
require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS."..".DS."field.php");

jimport('joomla.utilities.arrayhelper');


class JsfilterFieldEfield extends JsfilterField
{
	// Маркер поля в condition
	private $sep	= '%@#';

	
	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_EFIELD';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		$fields	= JSFactory::getAllProductExtraField();
		$html = JHtml::_(
					'select.genericlist',
					$fields,
					'cfg[struct][ex_field][]',
					'',
					'id',
					'name',
					'',
					'struct_ex_field'
				);
		return array(JText::_('MJSF_EXT_SETTINGS_EFIELD_TIP'), $html);
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
		$fid 	= (int) $ex_field;

		// Проверка корректности значения хар-ки
		if (!$fid) return;
		
		// Определение типа хар-ки (текст/список)
		$query 	= "SELECT
						`type`
					FROM `#__jshopping_products_extra_fields`
					WHERE
						`id` = ".$fid."
					LIMIT 1";
		$db->setQuery( $query );
		$ftype	= $db->loadResult();
			
		if ( $ftype ) {
			// Текст

			$query 	= "SELECT
							`extra_field_".$fid."` as `value`,
							`extra_field_".$fid."` as `text`
						FROM `#__jshopping_products`
						WHERE
							`extra_field_".$fid."` != ''
						GROUP BY `extra_field_".$fid."`
						ORDER BY `extra_field_".$fid."`";
		} else {
			// Список

			$query 	= "SELECT
							`id` as `value`,
							`".$lang->get('name')."` as `text`
						FROM `#__jshopping_products_extra_field_values`
						WHERE `field_id` = ".$fid."
						ORDER BY `ordering`";
		}

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
		$db		= JFactory::getDbo();
		$lang	= JSFactory::getLang();
		$fid 	= (int) $cfg->ex_field;

		// Проверка корректности значения хар-ки
		if (!$fid) return;
		// Проверка заполнения списка значений
		if ( !isset($cfg->values->list) ) return array();

		// Определение типа хар-ки (текст/список)
		$query 	= "SELECT
						`type`
					FROM `#__jshopping_products_extra_fields`
					WHERE
						`id` = ".$fid."
					LIMIT 1";
		$db->setQuery( $query );
		$ftype	= $db->loadResult();
			
		if ( $ftype ) {
			// Текст

			// Т.к. в текстовых характеристиках и название и значение одинаковы,
			// то нет смысла делать запрос к БД
			$values = array();
			foreach ($cfg->values->list as &$v) {
				$item = new stdClass();
				$item->value = $v;
				$item->text  = $v;
				$values[] = $item;
			}

			return $values;

		} else {
			// Список

			$cfg->values->list = (array) $cfg->values->list;

			$query 	= "SELECT
							`id` as `value`,
							`".$lang->get('name')."` as `text`
						FROM `#__jshopping_products_extra_field_values`
						WHERE
							`field_id` = ".$fid."
							AND
							`id` IN (".join(',', $cfg->values->list).")
						ORDER BY `ordering`";
			$db->setQuery( $query );

			return $db->loadObjectList();
		}
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
		$fid 	= (int) $cfg->ex_field;
		$values = array();

		// Проверка корректности значения хар-ки
		if (!$fid) return $values;
		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		// Определение необходимости сортировки по популярности
		$needOrder = ($cfg->b_mode == 3) ? true : false;

		// Определение типа хар-ки (текст/список)
		$query 	= "SELECT
						`type`
					FROM `#__jshopping_products_extra_fields`
					WHERE
						`id` = ".$fid."
					LIMIT 1";
		$db->setQuery( $query );
		$ftype	= $db->loadResult();
			
		if ( $ftype ) {
			// Текст

			// Обработка значений в списке перед запросом
			$cfg->values->list = (array) $cfg->values->list;
			
			foreach ($cfg->values->list as $k => &$v) {
				if ($v) {
					$cfg->values->list[$k] = $db->Quote($v);
				} else {
					unset($cfg->values->list[$k]);
				}
			}

			$query 	= "SELECT
							p.`extra_field_".$fid."` as `value`,
							p.`extra_field_".$fid."` as `text`
						FROM `#__jshopping_products` p
						".( ($cid)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( p.`product_id` = c.`product_id` )"
							: ""
						)."
						WHERE
							p.`extra_field_".$fid."` != ''
							AND p.`product_publish` = 1
							".( ($cid)
								? "AND c.`category_id` IN (".join(',', $cid).")"
								: ""
							)."
							".( ($manufacturer)
								? "AND p.`product_manufacturer_id` = ".$manufacturer
								: ""
							)."
							".( ($cfg->values->selection == 0)
								? "AND p.`extra_field_".$fid."` IN (".join(',', $cfg->values->list).")"
								: ''
							)."
						GROUP BY p.`extra_field_".$fid."`
						".( ($needOrder)
							? "ORDER BY COUNT(p.`extra_field_".$fid."`) DESC, p.`extra_field_".$fid."`"
							: "ORDER BY p.`extra_field_".$fid."`"
						);
		} else {
			// Список

			$cfg->values->list = (array) $cfg->values->list;

			$query 	= "SELECT
							v.`id` as `value`,
							v.`".$lang->get('name')."` as `text`
						FROM `#__jshopping_products_extra_field_values` v
						LEFT JOIN `#__jshopping_products` p
							ON FIND_IN_SET( v.`id`, p.`extra_field_".$fid."` )
						".( ($cid)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( p.`product_id` = c.`product_id` )"
							: ""
						)."
						WHERE
							v.`field_id` = ".$fid."
							AND p.`product_publish` = 1
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
							? "ORDER BY COUNT(v.`id`) DESC, v.`ordering`"
							: 'ORDER BY v.`ordering`'
						);
		}

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
		$lang = JSFactory::getLang();
		$fid = ( is_array($cfg) ) ? $cfg['ex_field'] : $cfg->ex_field;
		$fid = (int) $fid;

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return array();

		// Определение типа хар-ки (текст/список)
		$query 	= "SELECT
						`type`
					FROM `#__jshopping_products_extra_fields`
					WHERE
						`id` = ".$fid."
					LIMIT 1";
		$db->setQuery( $query );
		$ftype	= $db->loadResult();

		if ( $ftype ) {
			// Текст

			$query 	= "SELECT
							DISTINCT p.`extra_field_".$fid."`
						FROM `#__jshopping_products` p
						".( ($catList)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( p.`product_id` = c.`product_id` )"
							: ""
						)."
						WHERE
							p.`product_id` IN (".join(',', $pids).")
							AND
							p.`extra_field_".$fid."` != ''
							".( ($catList)
								? "AND c.`category_id` IN (".join(',', $catList).")"
								: ""
							);
		} else {
			// Список

			$query 	= "SELECT
						".( ($valType)
							? "DISTINCT v.`".$lang->get('name')."`"
							: "DISTINCT v.`id`"
						)."
							
						FROM `#__jshopping_products_extra_field_values` v
						LEFT JOIN `#__jshopping_products` p
							ON FIND_IN_SET( v.`id`, p.`extra_field_".$fid."` )
						".( ($catList)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( p.`product_id` = c.`product_id` )"
							: ""
						)."
						WHERE
							v.`field_id` = ".$fid."
							AND
							p.`product_id` IN (".join(',', $pids).")
							".( ($catList)
								? "AND c.`category_id` IN (".join(',', $catList).")"
								: ""
							);
		}

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
		$fid = (int) $cfg['ex_field'];		// ID характеристики

		// Проверка корректности ID характеристики
		if (!$fid) return;
		// Проверка наличия cid в режиме динамической фильтрации
		if ($cfg['values']['mode'] == 2 && !$cid && !$manufacturer) return;
		// Проверка отсутствия условий фильтрации (подходит любой товар)
		if (!$conditions) return null;

		// Определение типа поля
		$query = "SELECT `type` FROM `#__jshopping_products_extra_fields` WHERE `id` = ".$fid." LIMIT 1";
		$db->setQuery($query);
		$type = $db->loadResult();

		if ($type) {
			// Текст
			// В таблице товаров хранится текст

			// Формирование условия фильтрации в зависимости от выбранной логики
			$condition = join( (($cfg['values']['logic']) ? " AND " : " OR "), $conditions );
			
			// Замена кодов в condition на название поля
			$condition = str_replace("__".$this->sep."__", "`extra_field_".$fid."`", $condition);
			$condition = str_replace("--".$this->sep."--", "`extra_field_".$fid."`", $condition);

			$query	= "SELECT
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
						".( ($condition)
							? "AND ( ".$condition." )"
							: ""
						);
		} else {
			//Список
			// В таблице товаров хранится ID значений

			// Формирование условия фильтрации в зависимости от выбранной логики
			// Выбор только по логике "Или", если в настройках "И", то доп.фильтрация по кол-ву условий
			$condition = join(" OR ", $conditions);

			// Замена кодов в condition на название поля
			$jslang = JSFactory::getLang();
			$condition = str_replace("__".$this->sep."__", "v.`id`", $condition);
			$condition = str_replace("--".$this->sep."--", "v.`".$jslang->get('name')."`", $condition);

			$query	= "SELECT
						p.`product_id`
						".( ($conditions && $cfg['values']['logic'])
							? ", COUNT(p.`product_id`) as cnt"
							: ""
						)."
					FROM `#__jshopping_products_extra_field_values` v
					LEFT JOIN `#__jshopping_products` p
						ON FIND_IN_SET( v.`id`, p.`extra_field_".$fid."` )
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
						)."
					GROUP BY p.`product_id`
						".( ($conditions && $cfg['values']['logic'])
							? "HAVING cnt = ".count($conditions)
							: ""
						);
		}

		$db->setQuery($query);

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
		return "--".$this->sep."--";
	}

}
