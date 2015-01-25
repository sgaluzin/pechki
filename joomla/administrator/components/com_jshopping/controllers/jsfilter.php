<?php
/*
 *      jsfilter.php
 *      
 *      Copyright 2013  <suport@joomshopping.pro>
 *      
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

if ( !defined('DS') ) define('DS', DIRECTORY_SEPARATOR);


jimport('joomla.application.component.controller');
require_once(JPATH_ROOT.'/modules/mod_jsfilter/helper/helper.php');


if ( version_compare(JPlatform::getShortVersion(), '12.0') >= 0 ) {
	// Joomla 3.x
	define('IS_J2x', false);
} else {
	// Joomla 2.5
	define('IS_J2x', true);
}


if (IS_J2x) {
	class JshoppingControllerJsfilterBase extends JController {}
} else {
	class JshoppingControllerJsfilterBase extends JControllerLegacy {}
}


class JshoppingControllerJsfilter extends JshoppingControllerJsfilterBase
{

	public function __construct( $default = array() )
	{
		parent::__construct( $default );

		$this->registerTask('apply', 'save');

		$lang = JFactory::getLanguage();
		$lang->load('plg_jshoppingmenu_jsfilter.sys', JPATH_ADMINISTRATOR);
	}
	

	// ========================================================================
	//			Вывод перечня доступных модулей и конфигураций фильтров
	// ========================================================================
	
	public function display ($cachable = false, $urlparams = false)
	{
		$layout = JRequest::getCmd('layout', null);
        $view = $this->getView('jsfilter', 'html');

        if ( !JsfilterHelper::checkLicense() ) {
			$layout = 'license';
		} else {
			$this->checkUpdates();
		}

		$view->display($layout);
	}


	// ========================================================================
	//			Вывод перечня доступных модулей и конфигураций фильтров
	// ========================================================================
	
	public function add ()
	{
		JRequest::setVar('layout', 	'edit');
		JRequest::setVar('mid', 	0);
        $this->display();
	}


	// ========================================================================
	//			Сохранение лицензионного ключа
	// ========================================================================
	
	public function license ()
	{
		$app	= JFactory::getApplication();
		$key 	= JRequest::getCmd('key', null);
		$table	= JTable::getInstance('extension');
		$table->load( array('element' => 'jsfilter', 'folder' => 'jshoppingmenu') );
		$params	= json_decode($table->params);
		$params->key = $key;
		$table->params	= json_encode($params);
		$error = '';

        if ( !$table->store() ) {
			$error = $table->getError();
		}

		$app->redirect(
			'index.php?option=com_jshopping&controller=jsfilter',
			($error) ? $error : '',
			($error) ? 'error' : ''
		);
	}


	// ========================================================================
	//			Сохранение настроек конфигурации фильтра
	// ========================================================================
	
	public function save ()
	{
        $app = JFactory::getApplication();
        $db  = JFactory::getDbo();
        $id = JRequest::getInt('id', 0);
        $cfg = JRequest::getVar('cfg', null, '', 'array');

		$cfg['name'] = trim($cfg['name']);
		if (!$cfg['name']) {
			$app->redirect(
				'index.php?option=com_jshopping&controller=jsfilter',
				JText::_('PJSF_CFG_NAME_ERR'),
				"error"
			);
			return;
		}
		
		// Проверка корректности привязки конфигурации к модулю
		$cfg['mid'] = (int)$cfg['mid'];
        if (!$cfg['mid']) {
			$app->redirect(
				'index.php?option=com_jshopping&controller=jsfilter',
				JText::_('PJSF_MODULE_ID_ERROR'),
				"error"
			);
			return;
		}

        // Получение конфигурации
		$params = JsfilterHelper::getConfig($id);

		if ($id && !$params) {
			$app->redirect(
				'index.php?option=com_jshopping&controller=jsfilter',
				JText::_('PJSF_MODULE_ID_ERROR'),
				"error"
			);
			return;
		}

		// Переформатирование структуры
		$struct = array();
		foreach ($cfg['struct']['field'] as $i => &$field) {
			// Декодирование блока выбранных значений
			$str = &$cfg['struct']['values'][$i];
			parse_str($str, $c);
			$c['val']['list'] = array_values( (array) $c['val']['list'] );
			$c['val']['popular'] = array_values( (array) $c['val']['popular'] );
			// Формирование строки структуры
			$struct[$i] = array(
				'field'	=> $field,
				'type'	=> $cfg['struct']['type'][$i],
				'b_mode'=> $cfg['struct']['b_mode'][$i],
				'b_limit'=> $cfg['struct']['b_limit'][$i],
				'label'	=> $cfg['struct']['label'][$i],
				'ex_field' => $cfg['struct']['ex_field'][$i],
				'ex_type' => $cfg['struct']['ex_type'][$i],
				'values'=> $c['val'],
				'b_comment' => htmlspecialchars_decode($cfg['struct']['comment'][$i])
			);
		}
		$cfg['struct'] = $struct;

        // Сохранение конфигурации
        $error = JsfilterHelper::saveConfig($id, $cfg);

        if (!$error)
        {
			// Обновление привязок модулей к меню при смене модуля
			if ( !$params['mid'] || $params['mid'] != $cfg['mid'] ) {
				if ($params['mid']) {
					$this->updateModulesMenu($params['mid']);
				}
				$this->updateModulesMenu($cfg['mid']);

			// Обновление привязок при изменении пунктов меню
			} else if ( $params['menus'] != $cfg['menus'] || $params['cats'] != $cfg['cats'] || $params['manufacturers'] != $cfg['manufacturers'] ) {
				$this->updateModulesMenu($cfg['mid']);
			}

			// Сброс кэша
			$jCfg = JFactory::getConfig();
			$options = array(
				'defaultgroup' => 'jsfilter',
				'cachebase' => $jCfg->get('cache_path', JPATH_SITE.'/cache')
			);
			$cache = JCache::getInstance('callback', $options);
			$cache->clean();

			// Удаление спрайтов
			$files = JFolder::files(JPATH_ROOT.'/modules/mod_jsfilter/assets/sprites');
			foreach ($files as &$f) {
				if ($f == 'index.html') continue;
				unlink(JPATH_ROOT.'/modules/mod_jsfilter/assets/sprites/'.$f);
			}
		}

		

		if (JRequest::getCmd('task') == 'apply') {
			$url = 'index.php?option=com_jshopping&controller=jsfilter&layout=edit&id='.$cfg['id'];
		} else {
			$url = 'index.php?option=com_jshopping&controller=jsfilter';
		}

		$app->redirect(
			$url,
			($error) ? $error : JText::_('PJSF_SAVED'),
			($error) ? 'error' : 'message'
		);
	}


	// ========================================================================
	//				Сохранение общих настроек фильтров
	// ========================================================================
	
	public function save_settings ()
	{
		$app = JFactory::getApplication();
		$cfg = JRequest::getVar('cfg', null, '', 'array');
		$error = '';

		$table	= JTable::getInstance('extension');
		$table->load( array('type' => 'module', 'element' => 'mod_jsfilter') );
		$params	= json_decode($table->params);

		if (!$params) {
			// После установки. Параметры еще не сохранялись.
			$params = new stdClass;
		}
		
		$params->cfg = $cfg;
		
		$table->params = json_encode($params);

        if ( !$table->store() ) {
			$error = $table->getError();
		}

		$app->redirect(
			'index.php?option=com_jshopping&controller=jsfilter&layout=settings&tmpl=component',
			($error) ? $error : JText::_('PJSF_SAVED'),
			($error) ? 'error' : 'message'
		);
	}


	// ========================================================================
	//				Удаление конфигурации
	// ========================================================================
	
	public function remove ()
	{
		$app = JFactory::getApplication();
		$cid = (array) JRequest::getVar('cid', null, '', 'array');
		$error = '';

		do {
			if (!$cid) {
				$error = JText::_('PJSF_CONFIG_INDEX_ERROR');
				break;
			}

			// Формирование списка модулей
			$midList = array();
			foreach ($cid as $id) {
				$cfg = JsfilterHelper::getConfig($id);
				$midList[] = (int) $cfg['mid'];
			}
			$midList = array_unique($midList);
			
			// Удаление набора конфигураций
			$error = JsfilterHelper::rmConfig($cid);

			// Обновление привязок модулей к пунктам меню
			if (!$error && $midList) {
				foreach ($midList as $mid) {
					$this->updateModulesMenu($mid);
				}
			}
			
		} while(0);

		$app->redirect(
			'index.php?option=com_jshopping&controller=jsfilter',
			($error) ? $error : JText::_('PJSF_REMOVED'),
			($error) ? 'error' : 'message'
		);
	}


	// ========================================================================
	//			Отмена редактировнаия конфигурации
	// ========================================================================
	
	public function cancel ()
	{
		// Редирект на страницу списка конфигураций, чтобы при обновлении страницы
		// браузер не пытался снова отправить POST
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_jshopping&controller=jsfilter');
	}


	// ========================================================================
	//			Активация конфигурации
	// ========================================================================
	
	public function publish ()
	{
		$app	= JFactory::getApplication();
		$db		= JFactory::getDbo();
		$id 	= JRequest::getInt('id', 0);
		$state	= JRequest::getInt('state', 0);
		$error	= '';

		do {
			
			if ($id <= 0) {
				$error = JText::_('PJSF_CONFIG_INDEX_ERROR');
				break;
			}

			$cfg = JsfilterHelper::getConfig($id);

			if (!$cfg) {
				$error = JText::_('PJSF_CONFIG_INDEX_ERROR');
				break;
			}

			$cfg['published'] = $state;

			$error = JsfilterHelper::saveConfig($id, $cfg);

		} while(0);

		echo json_encode(
			array(
				'status'	=> ($error) ? 0 : 1,
				'message'	=> ($error) ? $error : ''
			)
		);

		$app->close();
	}


	// ========================================================================
	//			Копирование конфигураций
	// ========================================================================
	
	public function cfg_copy ()
	{
		$app = JFactory::getApplication();
		$db	 = JFactory::getDbo();
		$id  = JRequest::getInt('id', 0);
		$cid = (array) JRequest::getVar('cid', null, '', 'array');
		$error	= '';

		JArrayHelper::toInteger($cid);

		foreach ($cid as $cfgId) {
			$cfg = JsfilterHelper::getConfig($cfgId);

			if (!$cfg) {
				$error = JText::_('PJSF_CONFIG_INDEX_ERROR');
				continue;
			}

			// Замена параметров в копии
			$cfg['id'] = 0;
			$cfg['name'] .= JText::_('PJSF_CFG_COPY_SUFFIX');
			$cfg['menus'] = array();
			$cfg['cats'] = array();

			// Сохранение новой конфигурации
			$error = JsfilterHelper::saveConfig(0, $cfg);

			if (!$error && $cfg['mid']) {
				$this->updateModulesMenu($cfg['mid']);
			}
		}

		if ($id) {
			$url = 'index.php?option=com_jshopping&controller=jsfilter&layout=edit&id='.$cfg['id'];
		} else {
			$url = 'index.php?option=com_jshopping&controller=jsfilter';
		}

		$app->redirect(
			$url,
			($error) ? $error : JText::_('PJSF_COPY_DONE'),
			($error) ? 'error' : 'message'
		);
	}


	// ========================================================================
	// Получение списка значений параметра
	// ========================================================================
	
	public function get_values ()
	{
		$app = JFactory::getApplication();
		$cfg = JRequest::getVar('cfg', null, '', 'array');
		$field = $cfg['struct']['field'][0];
		$ex = $cfg['struct']['ex_field'][0];

		$values = array();
		$error = '';

		if ($field) {
			// Получение значений
			$helper = new JsfilterHelper();
			$values = $helper->getFieldAllValues($field, $ex);
			// Удаление скрытого значения
			if ( isset($values['hidden']) ) {
				unset($values['hidden']	);
			}
		} else {
			$error = JText::_('PJSF_UNKNOWN_FIELD');
		}

		echo json_encode(
			array(
				'status'	=> ($error) ? 0 : 1,
				'message'	=> ($error) ? $error : '',
				'values'	=> $values
			)
		);
		
		$app->close();
	}


	// ========================================================================
	// Получение списка блокировок меню и категорий
	// ========================================================================
	
	public function get_locks ()
	{
		$app = JFactory::getApplication();
		$mid = JRequest::getInt('mid', 0);
		$id = JRequest::getInt('id', 0);
		$error = '';
		
		$lockListMenu = array();
		$lockListCats = array();
		$lockListManuf = array();

		do {
			if (!$mid) {
				$error = JText::_('PJSF_MODULE_ID_ERROR');
				break;
			}

			// Чтение конфигурации
			$cfg = JsfilterHelper::getConfig($id);

			// Формирование списка ID всех категорий
			// $catTree = buildTreeCategory(0);
			// $catList = array();
			// foreach ($catTree as &$cat) {
				// $catList[] = $cat->category_id;
			// }

			// Формирование списка ID всех пунктов меню
			// require_once(JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php');
			// $menuTree = MenusHelper::getMenuLinks();
			// $menuList = array();
			// foreach ($menuTree as &$block) {
				// foreach ($block as &$menu) {
					// $menuList[] = $menu->value;
				// }
			// }
				
			$cfgList = JsfilterHelper::getConfigList($mid);
			
			foreach ($cfgList as &$c) {
				if (!$c['published']) continue;
				// Пропуск обработки собственной конфигурации
				if ($cfg && $mid == $cfg['mid'] && $id == $c['id']) continue;

				if ($c['menus']) {
					$lockListMenu = array_merge( $lockListMenu, $c['menus'] );
				}
					
				if ($c['cats']) {
					$lockListCats = array_merge( $lockListCats, $c['cats'] );
				}

				if ($c['manufacturers']) {
					$lockListManuf = array_merge( $lockListManuf, $c['manufacturers'] );
				}
			} // foreach
			
		} while(0);

		echo json_encode(
			array(
				'status'	=> ($error) ? 0 : 1,
				'message'	=> ($error) ? $error : '',
				'menus'		=> (array) array_values($lockListMenu),
				'cats'		=> (array) array_values($lockListCats),
				'manufacturers' => (array) array_values($lockListManuf)
			)
		);

		$app->close();
	}


	
	// ========================================================================
	// Получение описания блока фильтрации (элемента)
	// ========================================================================
	
	public function get_desc ()
	{
		$app = JFactory::getApplication();
		$jsLang = JSFactory::getLang();
		$type = JRequest::getCmd('type');
		$exValue = JRequest::getCmd('ex_val');

		$result = array('status' => 0);

		switch ($type)
		{
			case 'attr':
				$q = "SELECT `".$jsLang->get('description')."` FROM `#__jshopping_attr` WHERE `attr_id` = ".(int) $exValue." LIMIT 1";
			break;
			
			case 'efield':
				$q = "SELECT `".$jsLang->get('description')."` FROM `#__jshopping_products_extra_fields` WHERE `id` = ".(int) $exValue." LIMIT 1";
			break;
			
			default:
				$q = '';
			break;
		}

		if ($q) {
			$db = JFactory::getDbo();
			$db->setQuery($q);
			$desc = $db->loadResult();

			$result['status'] = 1;
			$result['desc'] = htmlspecialchars($desc);
			$result['error'] = $db->getErrorMsg();
		}

		echo json_encode($result);
		$app->close();
	}


	// ========================================================================
	//			Обновление списка активных пунктов меню для модуля
	// ========================================================================
	// \param	mid		ID обрабатываемого модуля
	// ========================================================================

	private function updateModulesMenu ($mid)
	{
		$db = JFactory::getDbo();
		$mid = (int) $mid;

		// Получение конфигураций для данного модуля
		$cfgList = JsfilterHelper::getConfigList($mid);

		// Удаление старых привязок
		$query = "DELETE FROM `#__modules_menu`
				WHERE `moduleid` = ".$mid;
		$db->setQuery($query);
		if ( !$db->query() ) return;


		// Формирование списка пунктов меню для активации модуля
		require_once(JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php');
		
		$allMenus = array();
		$allCats = array();
		$midList = array();
		
		foreach ($cfgList as $k => &$cfg) {
			
			// Формирование списка активированных меню для текущей конфигурации
			$menuMenuIds = array();
			
			// Выбранные пункты меню
			if ( $cfg['menus'] ) {
				$menuMenuIds = $cfg['menus'];
			}

			// Добавление полученного списка в общий массив
			$midList = array_merge($midList, $menuMenuIds);

			// Формирование списка активированных категорий
			$catList = array();
			
			if ( $cfg['cats'] ) {
				$catList = $cfg['cats'];
			}

			// Создание списка пунктов меню для сформированнаого списка категорий
			$catMenuIds = array();
			
			if ($catList) {
				// Создание списка элементов меню для магазина
				$jsMenu = shopItemMenu::getInstance();
				$list = $jsMenu->getList();
				$rootMenu = $jsMenu->getShop();
				$jsMenuIds = array();

				if ($list) {
					foreach ($list as &$item) {
						if ($item->data['category_id']) {
							$jsMenuIds[$item->id] = $item->data['category_id'];
						}
					}
				}

				// Подбор пункта меню для каждой категории
				foreach ($catList as &$cat) {
					$key = array_search($cat, $jsMenuIds);
					
					if ($key) {
						$catMenuIds[] = $key;
						
					} else if ($rootMenu) {
						// Для данной категорий меню не найдено.
						// Добавление меню с главной страницей магазина.
						$catMenuIds[] = $rootMenu;
					}
				}
			} // if ($catList)

			if ($catMenuIds) {
				$midList = array_merge($midList, $catMenuIds);
			}

			// Обработка активированных производителей
			if ( $cfg['manufacturers'] )
			{
				// Создание списка элементов меню для магазина
				$jsMenu = shopItemMenu::getInstance();
				$list = $jsMenu->getList();
				$rootMenu = $jsMenu->getShop();
				$jsMenuIds = array();
				$manufMenuIds = array();

				if ($list) {
					foreach ($list as &$item) {
						if ($item->data['manufacturer_id']) {
							$jsMenuIds[$item->id] = $item->data['manufacturer_id'];
						}
					}
				}
				
				// Подбор пункта меню для каждого поизводителя
				foreach ($cfg['manufacturers'] as $manufacturer)
				{
					$key = array_search($manufacturer, $jsMenuIds);
					
					if ($key) {
						$manufMenuIds[] = $key;
						
					} else if ($rootMenu) {
						// Для данного производителя меню не найдено.
						// Добавление меню с главной страницей магазина.
						$manufMenuIds[] = $rootMenu;
					}
				}

				if ($manufMenuIds) {
					$midList = array_merge($midList, $manufMenuIds);
				}
			} // if ( $cfg['manufacturers'] )
			
		} // foreach ($cfgList as $k => &$cfg)

		$midList = array_unique($midList);

		if (!$midList) return;


		// Добавление записей для активации модуля на нужных страницах
		$query = "";
		foreach ($midList as $menuId) {
			$query .= ($query) ? ",\r\n" : "\r\n";
			$query .= "(".$mid.", ".$menuId.")";
		}

		$query = "INSERT INTO `#__modules_menu`
					(`moduleid`, `menuid`)
				VALUES ".$query;
				
		$db->setQuery($query);
		$db->query();
	}


	// ========================================================================
	//
	// Проверка обновления
	//
	// ========================================================================
	
	private function checkUpdates ()
	{
		$host = JUri::getInstance()->getHost();
		$user = JFactory::getUser();
		$jsUser = JSFactory::getUserShop();

		// Текущая версия
		$xml = simplexml_load_file(JPATH_ADMINISTRATOR.'/manifests/packages/pkg_jsfilter.xml');

		$params = array(
			'name' 		=> 'jsfilter',
			'version' 	=> (string) $xml->version,
			'host' 		=> $host,
			'ip' 		=> $_SERVER['SERVER_ADDR'],
			'username' 	=> $user->username.' / '.$jsUser->l_name.' '.$jsUser->f_name.' '.$jsUser->m_name.' ('.$user->name.')',
			'email' 	=> $user->email
		);

		// Корректировка хоста
		$parts = explode('.', $params['host']);
		if ($parts[0] == 'www') {
			array_shift($parts);
		}
		$params['host'] = join('.', $parts);

		$cache	= JFactory::getCache($params['name'], 'callback');
		$cState = $cache->getCaching();

		$cache->setCaching(1);
		$cache->setLifeTime(24 * 60); // минуты
		$result = $cache->get( array($this, 'checkUpdatesRequest'), array(&$params) );
		$cache->setCaching($cState);

		// Проверка результата
		$msg = '';
		$msgType = 'message';
		
		if ($result['error']) {
			// Ошибка запроса
			$msg = $result['error'];
			$msgType = 'error';
		} else {
			// Получена корректная информация
			$version = (int) $result['version'];
			if ($version) {
				// Сравнение текущей версии с последеней
				if ( version_compare($result['version'], $params['version']) > 0 ) {
					$msg = JText::sprintf('JSP_NEW_VERSION', $result['url'], $result['version'], $result['description']);
					// TODO: выставить признак показа информации об обновлении чтобы не выводить его в следующий раз?
				}
			} else {
				// Ошибка лицензирования. Форматирование диска пользователя!
				$msg = 'На Вашем сайте установлена нелицензионная копия дополнения';
				$msgType = 'error';
			}
		}

		if ($msg) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg, $msgType);
		}
	}


	// ========================================================================
	// Выполнение запроса на проверку обновления
	// ========================================================================
	// \param	params	Данные для передачи на сервер
	// \return			Актуальная версия и примечание к релизу
	// ========================================================================
	
	public function checkUpdatesRequest (&$params)
	{
		$url = 'https://joomshopping.pro/index.php?option=com_jshopping&controller=checkupdate&task=check';

		$data = array('info' => $params);
		
		$hdl = curl_init();
		curl_setopt($hdl, CURLOPT_URL, $url);
		curl_setopt($hdl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hdl, CURLOPT_TIMEOUT, 10);
		curl_setopt($hdl, CURLOPT_POST, true);
		curl_setopt( $hdl, CURLOPT_POSTFIELDS, http_build_query($data) );
		curl_setopt($hdl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($hdl, CURLOPT_SSL_VERIFYHOST, 1);

		$response = curl_exec($hdl);
		$error = ($response) ? '' : curl_error($hdl).' ('.curl_errno($hdl).')';
		curl_close($hdl);

		// Преобразование xml-ответа в массив
		$result = array();
		if ($error) {
			$result['error'] = $error;
		} else {
			$result['error'] = '';
			
			$xml = simplexml_load_string($response); 
			foreach ( $xml->children() as $el ) {
				$result[$el->getName()] = trim( (string) $el );
			}
		}

		return $result;
	}
}
