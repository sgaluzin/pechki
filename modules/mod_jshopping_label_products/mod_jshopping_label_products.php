<?php
/**
* @version      4.0.4 20.10.2014
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
	
	$document = JFactory::getDocument();
    $jshopConfig = JSFactory::getConfig();
	
	$enable_addon = $params->get('enable_addon', 1);
	if($enable_addon){
		JPluginHelper::importPlugin('jshoppingproducts');
		$dispatcher = JDispatcher::getInstance();
	}
	
	$layout = $params->get('layout', 'default');
	
	$show_old_price = $params->get('show_old_price', 0);
	
    $product = JTable::getInstance('product', 'jshop');
    $label_id = $params->get('label_id'); 
    $count = $params->get('count_products', 4);
    
    $rows = $product->getProductLabel($label_id, $count);   
    
	addLinkToProducts($rows, 0, 1);
	
	if ($enable_addon){
		$dispatcher->trigger('onBeforeDisplayProductList', array(&$rows));
		$view = new stdClass();
		$view->rows = $rows;
		$dispatcher->trigger('onBeforeDisplayProductListView', array(&$view));
		$rows = $view->rows;
	}
	
    $noimage = $jshopConfig->image_product_live_path."/noimage.gif";
	$shippinginfo = SEFLink($jshopConfig->shippinginfourl,1);
	
	// config modul
	$show_image = $params->get('show_image',1);
	$show_image_label = $params->get('show_image_label',0);
	$allow_review = $params->get('allow_review',0);
	$short_description = $params->get('short_description',0);
	$manufacturer_name = $params->get('manufacturer_name',0);
	$product_quantity = $params->get('product_quantity',0);
	$product_old_price = $params->get('product_old_price',0);
	$product_price_default = $params->get('product_price_default',0);
	$display_price = $params->get('display_price',1);
	$show_tax_product = $params->get('show_tax_product',0);
	$show_plus_shipping_in_product = $params->get('show_plus_shipping_in_product',0);
	$basic_price_info = $params->get('basic_price_info',0);
	$product_weight = $params->get('product_weight',0);
	$delivery_time = $params->get('delivery_time',0);
	$extra_field = $params->get('extra_field',0);
	$vendor = $params->get('vendor',0);
	$product_list_qty_stock = $params->get('product_list_qty_stock',0);
	$show_button = $params->get('show_button',1);
	$show_button_buy = $params->get('show_button_buy',0);
	$show_button_detal = $params->get('show_button_detal',1);
	
	require(JModuleHelper::getLayoutPath('mod_jshopping_label_products',$layout));        
?>