<?php
/*
 *      jsfilter.php
 *      
 *      Copyright 2013 Bass <support@joomshopping.pro>
 */
 
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class plgJshoppingmenuJsfilter extends JPlugin
{

	const _name = "jsfilter";


	public function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage('plg_jshoppingmenu_'.self::_name.'.sys', JPATH_ADMINISTRATOR);
	}


	public function onBeforeAdminOptionPanelIcoDisplay (&$menu)
	{

		$menu[self::_name] = array(
			JText::_('PJSF_TITLE'),
			'index.php?option=com_jshopping&controller='.self::_name,
			self::_name.'/icon.png',
			1
		);
	}

}
