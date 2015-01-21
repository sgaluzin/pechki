<?php
/*
 *      install.php
 *      
 *      Copyright 2013 Bass <support@joomshopping.pro>
 */
 
// No direct access.
defined('_JEXEC') or die;
if ( !defined('DS') ) define('DS', DIRECTORY_SEPARATOR);

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');


class plgJshoppingmenuJsfilterInstallerScript
{

	private $_name = "jsfilter";
	

	function install (&$plg)
	{
		$src	= dirname(__FILE__).DS.'component';
		$dest	= JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jshopping';

		$lang	= &JFactory::getLanguage();
		$inst	= &JInstaller::getInstance();
		$desc	= (string) $inst->getManifest()->description;
		$lang->load('plg_jshoppingmenu_'.$this->_name.'.sys', JPATH_ADMINISTRATOR, null, true);
		$inst->set('message', JText::_($desc));
		
		if ( !JFolder::exists($dest) ) return false;
		
		if ( JFolder::exists($src) ) {
			foreach ( JFolder::folders($src) as $f ) {
				JFolder::copy( $src.DS.$f, $dest.DS.$f, '', true );
			}
		}

		// Дополнительное конфигурирование
		$this->jshoppingUpdate();

		// Очистка кэша
		$jCfg = JFactory::getConfig();
		$options = array(
			'defaultgroup' => $this->_name,
			'cachebase' => $jCfg->get('cache_path', JPATH_ADMINISTRATOR.'/cache')
		);
		$cache = JCache::getInstance('callback', $options);
		$cache->clean();

		return true;
	}



	function uninstall (&$plg)
	{
		$src	= JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jshopping';
		$files	= array(
			'controllers'.DS.$this->_name.'.php',
			'css'.DS.$this->_name.'.css',
			'js'.DS.$this->_name.'.js'
		);
		$dirs	= array(
			$src.DS.'views'.DS.$this->_name,
			$src.DS.'images'.DS.$this->_name
		);

		foreach ($files as &$f) {
			if (JFile::exists( $src.DS.$f )) {
				JFile::delete( $src.DS.$f );
			}
		}

		foreach ($dirs as &$d) {
			if ( JFolder::exists($d) ) {
				JFolder::delete( $d );
			}
		}

		// Дополнительное конфигурирование
		$this->jshoppingUpdate(false);

		return true;

	}


	function update (&$plg)
	{
		$this->install($plg);
	}


	private function jshoppingUpdate ($isInstall = true)
	{
		if ($isInstall) {
			// -------------------------------------------
			// 					Установка
			// -------------------------------------------

			// Включение плагина
			$table = JTable::getInstance('extension');
			$table->load( array('element' => $this->_name, 'folder' => 'jshoppingmenu') );
			$table->publish();
			
		} else {
			// -------------------------------------------
			// 					Удаление
			// -------------------------------------------

			// Пока нет необходимости выполнения дополнительных операций
		}
		
	}
		

}
