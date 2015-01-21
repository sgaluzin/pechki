<?php
/*
 * slider.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");


class JsfilterTypeSlider extends JsfilterType
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_SLIDER';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		// Поле для ввода максимального количества шагов слайдера (точность установки)
		$html = '<input type="text" id="struct_ex_type" name="cfg[struct][ex_type][]" value="" />';
		return array(JText::_('MJSF_EXT_SETTINGS_SLIDER_TIP'), $html);
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
		if (!$this->values) {
			return JText::_('MJSF_SLIDER_NO_DATA_ERR');
		}

		// Обработка входных данных
		$min = null;
		$max = null;
		foreach ($this->values as &$item) {
			if ( isset($item->text) && !(is_numeric($item->text) || (int)$item->text) ) {
				return JText::_('MJSF_SLIDER_DATA_ERR');
			}

			$value = (float) $item->text;
			if ($min === null) $min = $value;
			if ($max === null) $max = $value;
			
			$min = ($value < $min) ? $value : $min;
			$max = ($value > $max) ? $value : $max;
		}

		$this->params->ex_type = trim($this->params->ex_type);
		if (substr($this->params->ex_type, 0, 1) == '+') {
			// Указана величина шага
			$step = (float) str_replace(',', '.', $this->params->ex_type);
		} else {
			// Указано количество шагов
			$maxSteps = (int) $this->params->ex_type;
			$maxSteps = ($maxSteps > 0) ? $maxSteps : 10;
			$step = ($max - $min) / $maxSteps;
		}

		$step = round($step, 3);

		// Корректировка максимального значения для получения целого количества шагов
		if ($step && floor($max / $step) * $step < $max) {
			$max = floor( (floor($max / $step) + 1) * $step );
		}

		$params = array(
			'range'	=> true,
			'min'	=> $min,
			'max'	=> $max,
			'step'	=> $step,
			'values'=> ($val) ? $val : array($min, $max),
			'name'	=> $name
		);

		// Элементы для отрисовки слайдера
		JHtml::_('stylesheet', JUri::root().'components/com_jshopping/css/jquery-ui-slider.min.css');
		JHtml::_('script', JUri::root().'components/com_jshopping/js/jquery/jquery-ui-slider.min.js' );
		
		$html = '<div id="sf_slider_wrap" rel=\''.json_encode($params).'\'>'
					.'<div class="sf_slider_digits">'
						.'<div id="sf_slider_min"></div>'
						.'<div id="sf_slider_max"></div>'
					.'</div>'
					.'<div id="sf_slider"></div>'
				.'</div>';

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
		$cond	= '';

		$val['min']	= str_replace(',', '.', $val['min']);
		$val['max']	= str_replace(',', '.', $val['max']);

		$val['min']	= (float) $val['min'];
		$val['max']	= (float) $val['max'];
		
		$cond 	.= $fname." >= ".$val['min'];
		$cond 	.= ' AND ';
		$cond 	.= $fname." <= ".$val['max'];

		return array($cond);
	}

}
