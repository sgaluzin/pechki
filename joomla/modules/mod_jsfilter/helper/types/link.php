<?php
/*
 * link.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");


class JsfilterTypeLink extends JsfilterType
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_LINK';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		// Поле для ввода названия ссылки
		return array(
			JText::_('MJSF_EXT_SETTINGS_LINK_TIP'),
			'<input type="text" id="struct_ex_type" name="cfg[struct][ex_type][]" value="" />'
		);
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
		$html = '<a href="javascript:void(0);" onclick="sf_linkHandler(this);">'
					.$this->params->ex_type;

		foreach ($this->values as &$v) {
			if (!$v->value) continue;
			$html .= '<input type="hidden" name="'.$name.'[]" value="'.$v->value.'" disabled="disabled" autocomplete="off" />';
		}
					
		$html .= '</a>';
				
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
