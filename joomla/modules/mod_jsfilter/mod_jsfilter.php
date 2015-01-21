<?php
/*
 * mod_jsfilter.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;
if ( !defined('DS') ) define('DS', DIRECTORY_SEPARATOR);


require_once( JPATH_ROOT.DS.'components'.DS.'com_jshopping'.DS.'lib'.DS.'factory.php' );
require_once( dirname(__FILE__).DS.'helper'.DS.'helper.php' );


if ( version_compare(JPlatform::getShortVersion(), '12.0') >= 0 ) {
	// Joomla 3.x
	define('IS_J2x', false);
} else {
	// Joomla 2.5
	define('IS_J2x', true);
}


$jsCfg = JSFactory::getConfig();
// Стили магазина, для корректного отображения списка товаров
JHtml::_('stylesheet', JURI::root().'components/com_jshopping/css/'.$jsCfg->template.'.css');

// Загрузка jquery на страницах вне магазина
if (IS_J2x) {
	JHtml::_('script', JURI::root().'components/com_jshopping/js/jquery/jquery-'.$jsCfg->load_jquery_version.'.min.js' );
	JHtml::_('script', JURI::root().'components/com_jshopping/js/jquery/jquery-noconflict.js' );
}


// Определение параметров текущей страницы
$menu = JFactory::getApplication()->getMenu();
$currMenu = $menu->getActive();
$currMenuId = ($currMenu) ? $currMenu->id : 0;
$currCatId = JRequest::getInt('category_id');
$currManufacturerId = JRequest::getInt('manufacturer_id');
$pid = JRequest::getInt('product_id');

$helper	= new JsfilterHelper();
$baseCfg = JsfilterHelper::getBaseCfg();
$configs = JsfilterHelper::getConfigList($module->id);

// Определение конфигурации для текущей страницы
$pCfgByMenu = null;
$pCfgByCat  = null;
$pCfgByManuf  = null;

foreach ($configs as &$c) {
	// Поиск конфигураций для текущей страницы
	if (!$c['published']) {
		// Конфигурация не опубликована
		continue;
	}

	// Проверка страницы товара и разрешения вывода в ней
	if ($pid && !$c['in_prod']) continue;

	$conditionMenu = ( $currMenuId && $c['menus'] && in_array($currMenuId, $c['menus']) );
	$conditionCats = ( $currCatId != 0 && $c['cats'] && in_array($currCatId, $c['cats']) );
	$conditionManuf = ( $currManufacturerId != 0 && $c['manufacturers'] && in_array($currManufacturerId, $c['manufacturers']) );

	// Обработка привязки к производителю
	if ($conditionManuf) {
		$pCfgByManuf = ($pCfgByManuf) ? $pCfgByManuf : $c;
	}
	// Обработка привязки к пункту меню
	else if ($conditionMenu) {
		$pCfgByMenu = ($pCfgByMenu) ? $pCfgByMenu : $c;
	}
	// Обработка привязки к категории
	else if ($conditionCats) {
		$pCfgByCat = ($pCfgByCat) ? $pCfgByCat : $c;
	}

	// Индексы уже определены. Завершение поиска конфигурации
	if ($pCfgByManuf && $pCfgByMenu && $pCfgByCat) break;
}


// Определение конфигурации для вывода
$cfg = null;
if ($pCfgByManuf) {
	$cfg = $pCfgByManuf;
} else if ($pCfgByCat) {
	$cfg = $pCfgByCat;
} else if ($pCfgByMenu) {
	$cfg = $pCfgByMenu;
}


if ($cfg) {
	// Формирование формы фильтра
	$helper->loadConfig($cfg);
	$html	= $helper->buildForm();
	$error	= $helper->getErrorMsg();

	// Сохраненные значения параметров фильтрации
	$sess = JFactory::getSession();
	$sessCid = $sess->get('jsfilter.params.cid');
	if ($sessCid == $cfg['id']) {
		$sessData = $sess->get('jsfilter.params.data');
		$storedParams = ($sessData) ? unserialize($sessData) : array();
		$storedUrl = $sess->get('jsfilter.params.url');
	} else {
		$storedParams = array();
		$storedUrl = '';
	}


	// Вывод данных в соответствующем шаблоне
	if ($error) {
		require JModuleHelper::getLayoutPath('mod_jsfilter', 'error');
	} else if ($html) {
		require JModuleHelper::getLayoutPath('mod_jsfilter', 'default');
	}
}
