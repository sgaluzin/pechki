<?php
defined('_JEXEC') or die('Restricted access');
error_reporting(error_reporting() & ~E_NOTICE);

require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');

$db = JFactory::getDBO();
$jshopConfig = JSFactory::getConfig();
$jshopConfig->cur_lang = $jshopConfig->frontend_lang;

?>