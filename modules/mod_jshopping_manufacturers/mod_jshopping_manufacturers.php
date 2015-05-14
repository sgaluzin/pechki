<?php
/**
* @version      4.0.0 12.10.2012
* @author       MAXXmarketing GmbH
* @package      Jshopping
* @copyright    Copyright (C) 2010 webdesigner-profi.de. All rights reserved.
* @license      GNU/GPL
*/

    defined('_JEXEC') or die('Restricted access');
    error_reporting(error_reporting() & ~E_NOTICE);
    
    if (!file_exists(JPATH_SITE.'/components/com_jshopping/jshopping.php')){
        JError::raiseError(500,"Please install component \"joomshopping\"");
    } 

    require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php'); 
    require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');        
    JSFactory::loadCssFiles();
    JSFactory::loadLanguageFile();
    $jshopConfig = JSFactory::getConfig(); 
    
    $order = $params->get('sort', 'id');
    $direction = $params->get('ordering', 'asc');
    $show_image = $params->get('show_image', 1);
        
    $manufacturer_id = JRequest::getInt('manufacturer_id');
    
    $manufacturer = JTable::getInstance('manufacturer', 'jshop');    
    $list = $manufacturer->getAllManufacturers(1, $order, $direction);
    foreach ($list as $key => $value){
        $list[$key]->link = SEFLink('index.php?option=com_jshopping&controller=manufacturer&task=view&manufacturer_id='.$list[$key]->manufacturer_id, 2);
    }    

    require(JModuleHelper::getLayoutPath('mod_jshopping_manufacturers'));
?>