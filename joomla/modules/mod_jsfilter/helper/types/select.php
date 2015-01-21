<?php
/*
 * select.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");


class JsfilterTypeSelect extends JsfilterType
{

	
	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_SELECT';
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

		$html .= '<select name="'.$name.'[]" autocomplete="off">';
		// Значение по умолчанию
		$html .= '<option value=""'.( (!$val) ? ' selected="selected"' : '' ).'>'
					.'- '.JText::_('MJSF_ALL').' -'
				.'</option>';

		foreach ( $this->values as &$opt ) {
			$html .= 	'<option value="'.$opt->value.'"'
						.( ($val == $opt->value) ? ' selected="selected"' : '' )
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
		$db = JFactory::getDbo();

		// Select позволяет выбрать только одно значение
		if (count($val) > 1) {
			$val = array_slice($val, 0, 1);
		}

		$v = trim($val[0]);
		return ($v)
					? array( $fid." = ".$db->quote($v) )
					: null;
	}
	
}
