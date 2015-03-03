<?php
/*
 *      install.php
 *      
 *      Copyright 2013 Bass <support@joomshopping.pro>
 */
 
// No direct access.
defined('_JEXEC') or die;
if ( !defined('DS') ) define('DS', DIRECTORY_SEPARATOR);


class mod_jsfilterInstallerScript
{
	private $_name = "jsfilter";


	function install(&$ex)
	{
		$lang	= JFactory::getLanguage();
		$inst	= JInstaller::getInstance();
		$desc	= (string) $inst->getManifest()->description;
		$lang->load('mod_'.$this->_name.'.sys', JPATH_ROOT, null, true);
		$inst->set('message', JText::_($desc));

		// Front-end
		$src	= dirname(__FILE__).DS.'component';
		$dest	= JPATH_ROOT.DS.'components'.DS.'com_jshopping';

		if ( !JFolder::exists($dest) ) return false;
		
		if ( JFolder::exists($src) ) {
			foreach ( JFolder::folders($src) as $f ) {
				JFolder::copy( $src.DS.$f, $dest.DS.$f, '', true );
			}
		}

		// Install encoded files
		$path 	= JPATH_ROOT.DS.'modules'.DS.'mod_jsfilter'.DS.'helper'.DS;
		$res	= false;
		
		if ( extension_loaded('ionCube Loader') ) {
			// use ionCube
			$res = true;
	
		} else {
			// check zend loader/optimizer

			$list = get_loaded_extensions();
			foreach ( $list as &$item ) {
				if ( preg_match("/zend.*(loader|optimizer)/i", $item) ) {
					$res	= true;
					break;
				}
			}

			if ( $res ) {
				// Zend was found, detect php version

				
				if ( version_compare(phpversion(), "5.3") >= 0 ) {
					// php > 5.3
					JFile::move('helper_5.3.php', 'helper.php', $path);
				} else {
					JFile::move('helper_5.2.php', 'helper.php', $path);
				}
			}
		}

		if ( JFile::exists($path.'helper_5.2.php') ) {
			JFile::delete($path.'helper_5.2.php');
		}
		if ( JFile::exists($path.'helper_5.3.php') ) {
			JFile::delete($path.'helper_5.3.php');
		}

		// Дополнительные оерации при установке дополнения
		$this->customInstall();

		
		if (!$res) {
			JError::raiseNotice( '500', JText::_('MJSF_ZEND_NOT_LOADED') );
		}

		return true;
	}


	function uninstall( &$ex )
	{
		$src	= JPATH_ROOT.DS.'components'.DS.'com_jshopping';
		$files	= array(
			$src.DS.'controllers'.DS.$this->_name.'.php'
		);

		foreach ($files as &$f) {
			if ( JFile::exists($f) ) {
				JFile::delete($f);
			}
		}

		return true;
	}


	function update( &$ex )
	{
		$this->install($ex);
	}


	function customInstall ()
	{
		$db = JFactory::getDbo();

		// Определение имени БД
		$q = "SELECT DATABASE()";
		$db->setQuery($q);
		$dbName = $db->loadResult();
		
		// Создание индекса (если не существует) для ID товаров в таблице зависимых атрибутов
		$q = "SELECT
				COUNT(*) as `cnt`
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE
				table_schema = '".$dbName."'
				AND
				table_name LIKE '%jshopping_products_attr'
				AND
				index_name='product_id'";
		$db->setQuery($q);
		$exist = $db->loadResult();

		if (!$exist) {
			$q = "ALTER TABLE `#__jshopping_products_attr` ADD INDEX (`product_id`)";
			$db->setQuery($q);
			$db->query();
		}

		// Создание индекса (если не существует) для ID товаров в таблице независимых атрибутов
		$q = "SELECT
				COUNT(*) as `cnt`
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE
				table_schema = '".$dbName."'
				AND
				table_name LIKE '%jshopping_products_attr2'
				AND
				index_name = 'product_id'";
		$db->setQuery($q);
		$exist = $db->loadResult();

		if (!$exist) {
			$q = "ALTER TABLE `#__jshopping_products_attr2` ADD INDEX (`product_id`)";
			$db->setQuery($q);
			$db->query();
		}
	}

}
