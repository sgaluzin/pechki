<?php
/*
 * attr.php
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


class JsfilterFieldAttr extends JsfilterField
{
	// кодовый набор символов для замены в conditions
	private $sep	= '%@#';
	

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_FIELD_ATTR';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		$list = JSFactory::getAllAttributes();
		$html = JHtml::_(
						'select.genericlist',
						$list,
						'cfg[struct][ex_field][]',
						'',
						'attr_id',
						'name',
						'',
						'struct_ex_field'
					);
		return array(JText::_('MJSF_EXT_SETTINGS_ATTR_TIP'), $html);
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
		$aid	= (int) $ex_field;

		// Формирование списка значеий только по какому-то конкретному атрибуту (выбран в настройках)
		if (!$aid) return array();

		// Получение списка всех значений для данного атрибута
		$query 	= "SELECT
						v.`value_id` as `value`,
						v.`image` as `img`,
						v.`".$lang->get('name')."` as `text`
						#a.`cats`
					FROM `#__jshopping_attr_values` v
					#LEFT JOIN `#__jshopping_attr` a
					#	ON ( v.`attr_id` = a.`attr_id` ) 
					WHERE
						v.`attr_id` = ".$aid."
					ORDER BY v.`value_ordering`";
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
		$aid	= (int) $cfg->ex_field;
		$values = array();

		$cfg->values->list = (array) $cfg->values->list;

		// Формирование списка значеий только по какому-то конкретному атрибуту (выбран в настройках)
		if ($aid && $cfg->values->list) {

			// Получение списка всех значений для данного атрибута
			$query 	= "SELECT
							v.`value_id` as `value`,
							v.`image` as `img`,
							v.`".$lang->get('name')."` as `text`
							#a.`cats`
						FROM `#__jshopping_attr_values` v
						#LEFT JOIN `#__jshopping_attr` a
						#	ON ( v.`attr_id` = a.`attr_id` ) 
						WHERE
							v.`attr_id` = ".$aid."
							AND
							v.`value_id` IN (".join(',', $cfg->values->list).")
						ORDER BY v.`value_ordering`";
			$db->setQuery( $query );
			$values = $db->loadObjectList();
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
		$db		= JFactory::getDbo();
		$lang	= JSFactory::getLang();
		$aid	= (int) $cfg->ex_field;
		$manufacturer = (int) $manufacturer;
		$values = array();

		// Формирование списка значеий только по какому-то конкретному атрибуту (выбран в настройках)
		if (!$aid) return $values;
		// Подбор значений только внутри категории
		if (!$cid && !$manufacturer) return $values;
		// Для режима без подбора должен быть сформирован список значений
		if ( $cfg->values->selection == 0 && !isset($cfg->values->list) ) return $values;

		// Сортировка для популярных значений
		$needOrder = ($cfg->b_mode == 3) ? true : false;

		// Определение типа атрибута (зависимый/независимый)
		$query = "SELECT `independent`
					FROM `#__jshopping_attr`
					WHERE `attr_id` = ".$aid."
					LIMIT 1";
		$db->setQuery($query);
		$atype = $db->loadResult();

		if ($cfg->values->selection) {
			// Режим автоматического подбора значений с ограничением значения

			$query = "SELECT
							v.`value_id` as `value`,
							v.`image` as `img`,
							v.`".$lang->get('name')."` as `text`
						FROM `#__jshopping_attr_values` v
						".( ($atype)
							? "LEFT JOIN `#__jshopping_products_attr2` a "
								."ON ( v.`value_id` = a.`attr_value_id` )"
							: "LEFT JOIN `#__jshopping_products_attr` a "
								."ON ( v.`value_id` = a.`attr_".$aid."` )"
						)."
						".( ($cid)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( a.`product_id` = c.`product_id` )"
							: ""
						)."
						".( ($manufacturer)
							? "LEFT JOIN `#__jshopping_products` p "
								."ON ( a.`product_id` = p.`product_id` )"
							: ""
						)."
						WHERE
							v.`attr_id` = ".$aid."
							".( ($cid)
								? "AND c.`category_id` IN (".join(',', $cid).")"
								: ""
							)."
							".( ($manufacturer)
								? "AND p.`product_manufacturer_id` = ".$manufacturer
								: ""
							)."
						GROUP BY v.`value_id`
						".( ($needOrder)
							? "ORDER BY COUNT(v.`value_id`) DESC, c.`product_ordering`"
							: 'ORDER BY v.`value_ordering`'
						);
						
		} else {
			// Режим автоматического подбора из указанного списка

			$cfg->values->list = (array) $cfg->values->list;
			
			$query 	= "SELECT
							v.`value_id` as `value`,
							v.`".$lang->get('name')."` as `text`
						FROM `#__jshopping_attr_values` v
						".( ($atype)
							? "LEFT JOIN `#__jshopping_products_attr2` a "
								."ON ( v.`value_id` = a.`attr_value_id` )"
							: "LEFT JOIN `#__jshopping_products_attr` a "
								."ON ( v.`value_id` = a.`attr_".$aid."` )"
						)."
						".( ($cid)
							? "LEFT JOIN `#__jshopping_products_to_categories` c "
								."ON ( a.`product_id` = c.`product_id` )"
							: ""
						)."
						".( ($manufacturer)
							? "LEFT JOIN `#__jshopping_products` p "
								."ON ( a.`product_id` = p.`product_id` )"
							: ""
						)."
						WHERE
							v.`attr_id` = ".$aid."
							AND
							v.`value_id` IN (".join(',', $cfg->values->list).")
							".( ($cid)
								? "AND c.`category_id` IN (".join(',', $cid).")"
								: ""
							)."
							".( ($manufacturer)
								? "AND p.`product_manufacturer_id` = ".$manufacturer
								: ""
							)."
						GROUP BY v.`value_id`
						".( ($needOrder)
							? "ORDER BY COUNT(v.`value_id`) DESC, c.`product_ordering`"
							: 'ORDER BY v.`value_ordering'
						);
		}

		$db->setQuery( $query );
		$values = $db->loadObjectList();

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
		$db  = JFactory::getDbo();
		$aid = (int) $cfg['ex_field'];

		// При отсутствии товаров в списке вызвращается пустой список значений
		if (!$pids) return array();

		// Определение типа атрибута (зависимый/независимый)
		$query = "SELECT `independent`
					FROM `#__jshopping_attr`
					WHERE `attr_id` = ".$aid."
					LIMIT 1";
		$db->setQuery($query);
		$atype = $db->loadResult();

		$query = "SELECT
						DISTINCT v.`value_id`
					FROM `#__jshopping_attr_values` v
					".( ($atype)
						? "LEFT JOIN `#__jshopping_products_attr2` a "
							."ON ( v.`value_id` = a.`attr_value_id` )"
						: "LEFT JOIN `#__jshopping_products_attr` a "
							."ON ( v.`value_id` = a.`attr_".$aid."` )"
					)."
					".( ($catList)
						? "LEFT JOIN `#__jshopping_products_to_categories` c "
							."ON ( a.`product_id` = c.`product_id` )"
						: ""
					)."
					WHERE
						v.`attr_id` = ".$aid."
						AND a.`product_id` IN (".join(',', $pids).")
						".( ($catList)
							? "AND c.`category_id` IN (".join(',', $catList).")"
							: ""
						)."
						".( (!$atype && $attrList !== null)
								? ( ($attrList)
									? "AND a.`product_attr_id` IN ( ".join(',', $attrList)." )"
									: "AND 1 != 1"
								  )
								: ""
						);

		$db->setQuery( $query );
		$values = $db->loadColumn();

		if ($pids && $attrList && !$atype) {
			if ($values) {
				$query = "SELECT
							a.`product_id`,
							a.`product_attr_id`
						FROM `#__jshopping_products_attr` a
						LEFT JOIN `#__jshopping_attr_values` an
							ON ( a.`attr_".$aid."` = an.`value_id` )
						WHERE
							`product_id` IN (".join(',', $pids).")
							AND
							`product_attr_id` IN ( ".join(',', $attrList)." )
							AND
							an.`value_id` IN ( ".join(',', $values)." )";
			
				$db->setQuery($query);
				$pids = array_unique( $db->loadColumn(0) );
				$attrList = $db->loadColumn(1);
			} else {
				$pids = array();
				$attrList = array();
			}
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
		// Определение ID атрибута
		$aid = (int) $cfg['ex_field'];
		// $cid = ($cfg['values']['mode'] == 2) ? (int) $cid : 0;

		// Проверка корректности ID атрибута
		if (!$aid) return;
		// Проверка наличия cid в режиме динамической фильтрации
		if ($cfg['values']['mode'] == 2 && !$cid && !$manufacturer) return;
		// Проверка отсутствия условий фильтрации (подходит любой товар)
		if (!$conditions) return null;

		// Формирование условия фильтрации в зависимости от выбранной логики
		// Выбор только по логике "Или", если в настройках "И", то доп.фильтрация по кол-ву условий
		$condition = join(" OR ", $conditions);

		// Определение типа атрибута (зависимый/независимый)
		$query 	= "SELECT
						`independent`
					FROM `#__jshopping_attr`
					WHERE
						`attr_id` = ".$aid."
					LIMIT 1";
		$db->setQuery($query);
		$type = $db->loadResult();

		if ($type) {
			// Независимый
			// (поиск в jshopping_products_attr2 по значению поля attr_value_id)

			$condition = str_replace( "__".$this->sep."__", "a.`attr_value_id`", $condition );

			$query 	= "SELECT
							p.`product_id`
							".( ($conditions && $cfg['values']['logic'])
								? ", COUNT(p.`product_id`) as cnt"
								: ""
							)."
						FROM `#__jshopping_products` p
						LEFT JOIN `#__jshopping_products_attr2` a
							ON ( p.`product_id` = a.`product_id` )
						LEFT JOIN `#__jshopping_attr_values` an
							ON ( an.`value_id` = a.`attr_value_id` )
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
			
		} else {
			// Зависимый
			// (поиск в jshopping_products_attr по полю attr_[ID_атрибута])

			// Проверка наличия допустимых значений для фильтрации
			// null - доступны все значения
			if (!$attrList && $attrList !== null) return;

			$condition = str_replace( "__".$this->sep."__", "a.`attr_".$aid."`", $condition );

			$query 	= "SELECT
							p.`product_id`,
							a.`product_attr_id`
							".( ($conditions && $cfg['values']['logic'])
								? ", COUNT(DISTINCT a.`attr_".$aid."`) as cnt"
								: ""
							)."
							#, a.`attr_".$aid."`
							
						FROM `#__jshopping_products` p
						LEFT JOIN `#__jshopping_products_attr` a
							ON ( p.`product_id` = a.`product_id` )
						LEFT JOIN `#__jshopping_attr_values` an
							ON ( a.`attr_".$aid."` = an.`value_id` )
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

		// отбор товаров
		$db->setQuery($query);
		$pids = $db->loadColumn();

		if (!$type) {
			// Формирование списка ID записей зависимых атрибутов (доп.фильтрация)
			// (логика И не работает для разных атрибутов)

			if (!$pids) {
				$attrList = array();
			} else {
				$query = "SELECT
							a.`product_id`,
							a.`product_attr_id`
						FROM `#__jshopping_products_attr` a
						LEFT JOIN `#__jshopping_attr_values` an
							ON ( a.`attr_".$aid."` = an.`value_id` )
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
		}

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
		$jslang = JSFactory::getLang();
		
		return "an.`".$jslang->get('name')."`";
	}

}
