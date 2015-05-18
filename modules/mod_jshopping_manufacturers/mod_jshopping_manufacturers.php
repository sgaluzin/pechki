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

if (!file_exists(JPATH_SITE . '/components/com_jshopping/jshopping.php')) {
    JError::raiseError(500, "Please install component \"joomshopping\"");
}

require_once (JPATH_SITE . '/components/com_jshopping/lib/factory.php');
require_once (JPATH_SITE . '/components/com_jshopping/lib/functions.php');
JSFactory::loadCssFiles();
JSFactory::loadLanguageFile();
$jshopConfig = JSFactory::getConfig();

$order = $params->get('sort', 'id');
$direction = $params->get('ordering', 'asc');
$show_image = $params->get('show_image', 1);

$manufacturer_id = JRequest::getInt('manufacturer_id');

$manufacturer = JTable::getInstance('manufacturer', 'jshop');

function getManufacturersByCat($category_id, $publish = 0, $order = "ordering", $dir = "asc") {
    $category_id = (int)$category_id;
    if (!$category_id) {
        return;
    }
    $lang = JSFactory::getLang();
    $db = JFactory::getDBO();
    if ($order == "id")
        $orderby = "manufacturer_id";
    if ($order == "name")
        $orderby = "name";
    if ($order == "ordering")
        $orderby = "ordering";
    if (!$orderby)
        $orderby = "ordering";
    
    $query_where = ($publish) ? ("WHERE manufacturer_publish = '1'") : ("");
    
    $ids = "SELECT product_manufacturer_id FROM #__jshopping_products pr, #__jshopping_products_to_categories prc WHERE pr.product_id=prc.product_id AND category_id={$category_id}";
    $db->setQuery($ids);
    $ids = $db->loadColumn();
    
    if (sizeof($ids) > 0) {
        if ($query_where) {
            $query_where .= " AND ";
        }
        $query_where .= " manufacturer_id IN (" . implode(',', $ids) . ")";
    }
    
    $query = "SELECT manufacturer_id, manufacturer_url, manufacturer_logo, manufacturer_publish, `" . $lang->get('name') . "` as name, `" . $lang->get('description') . "` as description,  `" . $lang->get('short_description') . "` as short_description
				  FROM `#__jshopping_manufacturers` $query_where ORDER BY " . $orderby . " " . $dir;
    $db->setQuery($query);
    $list = $db->loadObjectList();

    foreach ($list as $key => $value) {
        $list[$key]->link = SEFLink('index.php?option=com_jshopping&controller=manufacturer&task=view&manufacturer_id=' . $list[$key]->manufacturer_id);
        $sql = "SELECT COUNT(*) cnt FROM #__jshopping_products WHERE product_manufacturer_id={$list[$key]->manufacturer_id} AND  product_id IN (SELECT product_id FROM #__jshopping_products_to_categories WHERE category_id={$category_id})";
        $db->setQuery($sql);
        $amount = $db->loadResult();
        $list[$key]->amount = $amount;
    }
    return $list;
}
$category_id = (int)$_REQUEST['category_id'];
$list = getManufacturersByCat($category_id, 1, $order, $direction);
foreach ($list as $key => $value) {
    $list[$key]->link = SEFLink('index.php?option=com_jshopping&controller=manufacturer&task=view&manufacturer_id=' . $list[$key]->manufacturer_id, 2);
}

require(JModuleHelper::getLayoutPath('mod_jshopping_manufacturers'));
?>