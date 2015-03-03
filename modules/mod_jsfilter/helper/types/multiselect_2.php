<?php
/*
 * multiselect_2.php
 * 
 * Copyright 2014 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");
require_once( dirname(__FILE__).DS."multiselect.php");


class JsfilterTypeMultiselect_2 extends JsfilterTypeMultiselect
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_MULTISELECT_2';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		return array();
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
		JHtml::_('stylesheet', JUri::root().'components/com_jshopping/css/jquery.multiple-select.min.css');
		JHtml::_('script', JUri::root().'components/com_jshopping/js/jquery/jquery.multiple.select.min.js' );
		$html = parent::render($name, $val);

		// Добавление класса для идентификации селекта
		$html = str_replace('multiple="multiple"', 'class="multicheck" multiple="multiple"', $html);

		return $html;
	}

}
