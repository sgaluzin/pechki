<?php
/**
 * @package Joomla.JoomShopping.Products.List
 * @version 2.2.1
 * @author Linfuby (Meling Vadim)
 * @website http://dell3r.ru/
 * @email support@dell3r.ru
 * @copyright Copyright by Linfuby. All rights reserved.
 * @license The MIT License (MIT); See \components\com_jshopping\addons\jshopping_to_parent_category\license.txt
 */
defined('_JEXEC') or die;

	jimport('joomla.filesystem.folder');
	jimport('joomla.filesystem.file');

	$AddonAlias		= "to_parent_category";
	$PluginDirs		= array("products");

	$DataBase = JFactory::getDBO();
	foreach($PluginDirs as $Plugin){
		$Query = $DataBase->getQuery(true);
		$Query->delete("#__extensions");
		$Query->where("element = '".$AddonAlias."'");
		$Query->where("folder = 'jshopping".$Plugin."'");
		$Query = (string)$Query;
		$DataBase->setQuery($Query);
		$DataBase->query();
		JFolder::Delete(JPATH_ROOT."/plugins/jshopping".$Plugin."/extended_menu");
	}
	JFolder::Delete(JPATH_ROOT."/components/com_jshopping/addons/jshopping_".$AddonAlias);