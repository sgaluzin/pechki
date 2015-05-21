<?php
/**
 * @package Joomla.JoomShopping.Products.List
 * @version 2.3
 * @author Linfuby (Meling Vadim)
 * @website http://dell3r.ru/
 * @email support@dell3r.ru
 * @copyright Copyright by Linfuby. All rights reserved.
 * @license The MIT License (MIT); See \components\com_jshopping\addons\jshopping_to_parent_category\license.txt
 */
defined('_JEXEC') or die('Restricted access');
class plgJshoppingProductsTo_Parent_Category extends JPlugin{

	function onBeforeQueryGetProductList($category, &$adv_result, &$adv_from, &$adv_query, &$order_query, $filters)
	{
		if ($category == "category"){
			$tmp1 = $adv_result;
			$tmp2 = $adv_from;
			$tmp3 = $filters;
			$tmp4 = $order_query;
			unset($tmp1);
			unset($tmp2);
			unset($tmp3);
			unset($tmp4);
			$Add_query = $adv_query;
			$JInput = JFactory::getApplication()->input;
			$category_id = $JInput->getInt("category_id", $_GET["category_id"]);
			$Table_Category = JTable::getInstance("Category", "JShop");
			$AllCategories = $Table_Category->getAllCategories();
			$Query = array($category_id);
			$Categories = array();
			foreach($AllCategories as $Category){
				if ($Category->category_parent_id == $category_id){
					$Categories[] = $Category;
					$Query[] = $Category->category_id;
				}
			}
			plgJshoppingProductsTo_Parent_Category::getResortCategoryTree($Categories, $AllCategories, $Query);
			$adv_query = $Add_query." OR (pr_cat.category_id IN (".implode(",", $Query).") AND prod.product_publish = '1' ".$Add_query.") GROUP BY prod.product_id";
		}
	}

	function onBeforeDisplayProductList(&$products)
	{
		addLinkToProducts($products);
	}

	function onBeforeQueryCountProductList($category, &$adv_result, &$adv_from, &$adv_query, $filters)
	{
		if ($category == "category"){
			if ($adv_result == "count(*)"){
				$adv_result = "count(distinct prod.product_id)";
			}
			$tmp1 = $adv_from;
			$tmp2 = $filters;
			unset($tmp1);
			unset($tmp2);
			$Add_query = $adv_query;
			$JInput = JFactory::getApplication()->input;
			$category_id = $JInput->getInt("category_id", $_GET["category_id"]);
			$Table_Category = JTable::getInstance("Category", "JShop");
			$AllCategories = $Table_Category->getAllCategories();
			$Query = array($category_id);
			$Categories = array();
			foreach($AllCategories as $Category){
				if ($Category->category_parent_id == $category_id){
					$Categories[] = $Category;
					$Query[] = $Category->category_id;
				}
			}
			plgJshoppingProductsTo_Parent_Category::getResortCategoryTree($Categories, $AllCategories, $Query);
			$adv_query = $Add_query." OR (pr_cat.category_id IN (".implode(",", $Query).") AND prod.product_publish = '1' ".$Add_query.")";
		}
	}

	function onBeforeDisplayProductListView(&$view)
	{
		$jshopConfig = JSFactory::getConfig();
		if ($jshopConfig->show_product_list_filters){
			$view->manufacuturers_sel = plgJshoppingProductsTo_Parent_Category::getManufacturers($view->filters);
		}
	}

	static function getResortCategoryTree($Categories, $AllCategories, &$Query)
	{
		foreach($Categories as $Category){
			$SubCategories = _getCategoryParent($AllCategories, $Category->category_id);
			foreach($SubCategories as $SubCategory)
				$Query[] = $SubCategory->category_id;
			if (count($SubCategories))
				plgJshoppingProductsTo_Parent_Category::getResortCategoryTree($SubCategories, $AllCategories, $Query);
		}
	}

	static function getManufacturers($filters)
	{
		$JInput = JFactory::getApplication()->input;
		$category_id = $JInput->getInt("category_id", $_GET["category_id"]);
		if(!$category_id)
		{
			$category_id = 0;
		}
		$Table_Category = JTable::getInstance("Category", "JShop");
		$AllCategories = $Table_Category->getAllCategories();
		$Query = array($category_id);
		$Categories = array();
		foreach($AllCategories as $Category){
			if ($Category->category_parent_id == $category_id){
				$Categories[] = $Category;
				$Query[] = $Category->category_id;
			}
		}
		plgJshoppingProductsTo_Parent_Category::getResortCategoryTree($Categories, $AllCategories, $Query);
		$jshopConfig = JSFactory::getConfig();
		$user = JFactory::getUser();
		$lang = JSFactory::getLang();
		$adv_query = "";
		$groups = implode(',', $user->getAuthorisedViewLevels());
		$adv_query .=' AND prod.access IN ('.$groups.')';
		if ($jshopConfig->hide_product_not_avaible_stock){
			$adv_query .= " AND prod.product_quantity > 0";
		}
		if ($jshopConfig->manufacturer_sorting==2){
			$order = 'name';
		}else{
			$order = 'man.ordering';
		}
		$db = JFactory::getDbo();
		$query = "SELECT distinct man.manufacturer_id as id, man.`".$lang->get('name')."` as name FROM `#__jshopping_products` AS prod
				LEFT JOIN `#__jshopping_products_to_categories` AS categ USING (product_id)
				LEFT JOIN `#__jshopping_manufacturers` as man on prod.product_manufacturer_id=man.manufacturer_id
				WHERE categ.category_id IN (".Implode(",", $Query).") AND prod.product_publish = '1' AND prod.product_manufacturer_id!=0 ".$adv_query." order by ".$order;
		$db->setQuery($query);
		$list = $db->loadObjectList();
		$first_manufacturer = array();
		$first_manufacturer[] = JHTML::_('select.option', 0, _JSHOP_ALL, 'id', 'name' );
		$manufacuturers_sel = JHTML::_('select.genericlist', array_merge($first_manufacturer, $list), 'manufacturers[]', 'class = "inputbox" onchange = "submitListProductFilters()"','id', 'name', $filters['manufacturers'][0]);
		return $manufacuturers_sel;
	}
}