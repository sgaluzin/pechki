<?php
/*
 * checkbox.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");


class JsfilterTypeCheckbox extends JsfilterType
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_CHECKBOX';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		// Поле для ввода количества формируемых столбцов
		$html = '<input type="text" id="struct_ex_type" name="cfg[struct][ex_type][]" value="" />';
		return array(JText::_('MJSF_EXT_SETTINGS_CHECKBOX_TIP'), $html);
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
		$columns = (int)$this->params->ex_type;
		if (!$columns) {
			$columns = 1;
		}
		// Определение кол-ва элементов в каждом столбце
		$colCount = ceil( count($this->values) / $columns );
		
		$html = '';
		$count = $colCount;
		foreach ( $this->values as $k => &$opt ) {
			if ($columns > 1 && $count == $colCount) {
				$html .= '<div style="display:inline-block;vertical-align: top;width:'.(int)(100 / $columns).'%">';
			}
			$html .= '<label>'
						.'<input type="checkbox" autocomplete="off"'
							.' value="'.$opt->value.'"'
							.' name="'.$name.'[]"'
							.( ( in_array($opt->value, (array)$val) ) ? ' checked="checked"' : '' )
						.'>'
						.'<span class="sf_text">'
							.$opt->text
						.'</span>'
					.'</label>';
			if ($columns > 1 && --$count == 0) {
				$html .= '</div>';
				$count = $colCount;
			}
		}

		if ($columns > 1 && $count && $count != $colCount) {
			$html .= '</div>';
		}
		
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
		$db		= JFactory::getDbo();
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
