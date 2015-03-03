<?php
/*
 * limits.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");



class JsfilterTypeLimits  extends JsfilterType
{

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_LIMITS';
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

	public function render ($name, &$val)
	{

		$min	= (isset($val['f']) && $val['f']) ? $val['f'] : '';
		$min	= str_replace( ',', '.', trim($min) );
		if ( $min ) {
			$min	= (float) $min;
		}
		
		$max	= (isset($val['t']) && $val['t']) ? $val['t'] : '';
		$max	= str_replace( ',', '.', trim($max) );
		if ( $max ) {
			$max	= (float) $max;
		}


		$html = '<div class="sf_price_wrap">'
					.'<span class="sf_text">'
						.JText::_('MJSF_TYPE_LIMITS_FROM')
					.'</span>'
					.'<input type="text" name="'.$name.'[f]" value="'.$min.'" autocomplete="off" />'
					.'<span class="sf_text">'
						.JText::_('MJSF_TYPE_LIMITS_TO')
					.'</span>'
					.'<input type="text" name="'.$name.'[t]" value="'.$max.'" autocomplete="off" />'
				.'</div>';

		return $html;
	}

	
	// ------------------------------------------------------------------------
	// Формирование условия для фильтрации данных в БД
	// ------------------------------------------------------------------------
	// fid		Поле со значением ID фильтруемого параметра
	// fname	Поле с названием фильтруемого параметра
	// val		Значение из формы фильтра
	// ------------------------------------------------------------------------

	public function getCondition ($fid, $fname, &$val)
	{
		$db		= JFactory::getDbo();
		$cond	= null;

		$val['f'] = str_replace( ',', '.', trim($val['f']) );
		
		if ($val['f']) {
			$val['f'] = (float) $val['f'];
			$cond 	.= $fname." >= ".$val['f'];
		}
		
		$val['t'] = str_replace( ',', '.', trim($val['t']) );
		
		if ($val['t']) {
			$val['t'] = (float) $val['t'];
			$cond 	.= ($cond) ? ' AND ' : '' ;
			$cond 	.= $fname." <= ".$val['t'];
		}

		return ($cond) ? array($cond) : null;
	}

}
