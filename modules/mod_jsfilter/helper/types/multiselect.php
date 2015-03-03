<?php
/*
 * multiselect.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");


class JsfilterTypeMultiselect extends JsfilterType
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_MULTISELECT';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		// Поле для ввода количества видимых строк
		$html = '<input type="text" id="struct_ex_type" name="cfg[struct][ex_type][]" value="" />';
		return array(JText::_('MJSF_EXT_SETTINGS_MULTISELECT_TIP'), $html);
	}


	// ------------------------------------------------------------------------
	// Отрисовка элементов блока
	// ------------------------------------------------------------------------
	// name		Имя элемента формы
	// val		Выбранное значений параметра (из URL)
	// \return	Html код элемента
	// ------------------------------------------------------------------------

	public function render ($name, &$val)
	{
		$html = '';
		$size = (int)$this->params->ex_type;
		if (!$size) {
			$size = 5;
		}

		$html .= '<select name="'.$name.'[]" multiple="multiple" autocomplete="off" '
		.'size="'.$size.'">';

		foreach ( $this->values as &$opt ) {
			if (!$opt->value) continue;
			
			$html .= 	'<option value="'.$opt->value.'"'
						.( ($val && in_array($opt->value, $val)) ? ' selected="selected"' : '')
						.'>'
							.$opt->text
						.'</option>';
		}
		
		$html .= '</select>';

		return $html;
	}

	
	// ------------------------------------------------------------------------
	// Формирование условия для фильтрации данных в БД
	// ------------------------------------------------------------------------
	// fid		Поле со значением ID фильтруемого параметра
	// fname	Поле с названием фильтруемого параметра
	// val		Значение из формы фильтра
	// \return	Набор условий фильтраци (для вставки в запрос к БД)
	//			для каждого выбранного значения в блоке
	// ------------------------------------------------------------------------

	public function getCondition ($fid, $fname, &$val)
	{
		$db 	= JFactory::getDbo();
		$val 	= array_unique($val);
		$cond	= null;

		foreach ($val as $k => &$v) {
			if ( !$v ) {
				unset($val[$k]);
				continue;
			}

			$cond[] = $fid." = ".$db->quote($v);
		}

		return $cond;
	}

}
