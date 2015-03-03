<?php
/*
 * jsfilter.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
// no direct access
defined('_JEXEC') or die;
if ( !defined('DS') ) define('DS', DIRECTORY_SEPARATOR);

if ( version_compare(JPlatform::getShortVersion(), '12.0') >= 0 ) {
	// Joomla 3.x
	define('IS_J2x', false);
} else {
	// Joomla 2.5
	define('IS_J2x', true);
}


jimport('joomla.application.component.controller');

require_once( JPATH_ROOT.DS.'modules'.DS.'mod_jsfilter'.DS.'helper'.DS.'helper.php' );


if (IS_J2x) {
	class JshoppingControllerJsfilterBase extends JController {}
} else {
	class JshoppingControllerJsfilterBase extends JControllerLegacy {}
}

class JshoppingControllerJsfilter extends JshoppingControllerJsfilterBase
{
    
    // фильтрация товаров через helper
    // и вывод данных через view search
    function display()
    {
		$app		= JFactory::getApplication(); 
		$db			= JFactory::getDbo();
		$id			= JRequest::getUint('id', 0);
		$jsCfg		= JSFactory::getConfig();
		$jslang		= JSFactory::getLang();
		$dispatcher = JDispatcher::getInstance();
		$sf			= JRequest::getVar('sf', null, '', 'array');

		// Подготовка хелпера к фильтрации
		$cfg = JsfilterHelper::getConfig($id);
		$helper	= new JsfilterHelper();
		$helper->loadConfig($cfg);
		// Передача параметров фильтрации в хелпер
		// и получение набора ID товаров, удовлетворяющих данным условиям
		$pids = $helper->doFilter($sf);

		
		if ( !count($pids) ) {
			// нет результатов
            $view_name = "search";
            $view_config = array("template_path" => JPATH_COMPONENT."/templates/".$jsCfg->template."/".$view_name);
            $view = &$this->getView($view_name, getDocumentType(), '', $view_config);

            $view->setLayout("noresult");
            $view->display();
            
            return 0;
        }

		$this->_show_result($pids, $helper);
	}


	// ------------------------------------------------------------------------
	//					Обработчик AJAX-запросов фильтра
	// ------------------------------------------------------------------------
	
	public function request ()
	{
		$app		= JFactory::getApplication(); 
		$db			= JFactory::getDbo();
		$jsCfg		= JSFactory::getConfig();
		$jslang		= JSFactory::getLang();
		$id			= JRequest::getUint('id', 0);
		$pre 		= JRequest::getInt('pre', 0);
		$sf			= JRequest::getVar('sf', null, '', 'array');

		// Подготовка хелпера к фильтрации
		$cfg = JsfilterHelper::getConfig($id);
		$baseCfg = JsfilterHelper::getBaseCfg();
		$helper	= new JsfilterHelper();
		$helper->loadConfig($cfg);

		// Передача параметров фильтрации в хелпер
		// и получение набора ID товаров, удовлетворяющих данным условиям
		$pids = $helper->doFilter($sf);

		if ($pre) {
			// Предзапрос. Вывод подсказки с количеством найденых товаров
			$result = array(
				'status'	=> 1,
				'count'		=> count($pids)
			);

			// Добавление списка актуальных значений
			if ( $cfg['deactivate_values'] ) {
				$values = $helper->getActiveValues($pids);
				$result['values'] = $values;
			}

			echo json_encode($result);

		} else {

			// Вывод (части) страницы с результатом поиска

			// Получение актуальных значений для элементов фильтра и вывод результата в скрытом блоке
			if ( $cfg['deactivate_values'] ) {
				$values = $helper->getActiveValues($pids);
				echo '<div id="sf_actual_values" style="display:none">'.json_encode($values).'</div>';
			}

			// Сохранение текущих параметров фильтрации
			$disallowed = JRequest::getInt('sf_dontstore');
			if ( (!isset($baseCfg->remember_params) || $baseCfg->remember_params) && !$disallowed ) {
				$saveData = array();
				
				foreach ($sf as $index => &$block) {
					foreach ($block as $fname => &$v1) {
						foreach ($v1 as $tname => &$v2) {
							$name = 'sf['.$index.']['.$fname.']['.$tname.']';
							if ($tname == 'limits') {
								// From
								$saveData[] = array(
									'name' => $name.'[f]',
									'type' => $tname,
									'values' => $v2['f']
								);
								$saveData[] = array(
									'name' => $name.'[t]',
									'type' => $tname,
									'values' => $v2['t']
								);
								continue;
								
							} else if ($tname != 'slider') {
								$name .= '[]';
							}
							
							$saveData[] = array(
								'name' => $name,
								'type' => $tname,
								'values' => ( ($tname == 'slider') ? array($v2['min'], $v2['max']) : $v2 )
							);
						}
					}
				}
				// Сохранение обработанных данных
				$sess = JFactory::getSession();
				$sess->set( 'jsfilter.params.cid',  $cfg['id'] );
				$sess->set( 'jsfilter.params.data', serialize($saveData) );
				$sess->set( 'jsfilter.params.url', 	JRequest::getString('url') );
			}
			
			$this->_show_result($pids, $helper);
		}

		$app->close();
	}


	// ------------------------------------------------------------------------
	//					Обработчик сброса параметров фильтрации
	// ------------------------------------------------------------------------
	
	public function reset ()
	{
		$sess = JFactory::getSession();
		$sess->clear('jsfilter.params.cid');
		$sess->clear('jsfilter.params.data');
		$sess->clear('jsfilter.params.url');
		$result = array('status' => 1);

		// Загрузка актуальных значений при активации элемента доп.фильтрации по наличию товара
		// (значение "только в наличии")
		$cfgId = JRequest::getUint('id', 0);
		$cfg = JsfilterHelper::getConfig($cfgId);
		$baseCfg = JsfilterHelper::getBaseCfg();

		if ($baseCfg->stock_state == 1 && $cfg['deactivate_values']) {
			// Фильтрация товаров только по наличию

			$helper	= new JsfilterHelper();
			$helper->loadConfig($cfg);
			
			$sf = array();
			$sf[999]['stock']['checkbox'][] = 'true';
			$pids = $helper->doFilter($sf);
			// Определение актуальных значений после искусственной фильтрации
			$activeValues = $helper->getActiveValues($pids);

			$result['values'] = $activeValues;
		}

		echo json_encode($result);

		$app = JFactory::getApplication();
		$app->close();
	}


	// ------------------------------------------------------------------------
	//					Формирование списка найденных товаров
	// ------------------------------------------------------------------------

	private function _show_result (&$pids, &$helper)
	{
		$app		= JFactory::getApplication(); 
		$db			= JFactory::getDbo();
		$mid		= $helper->getModuleId();
		$jsCfg		= JSFactory::getConfig();
		$jslang		= JSFactory::getLang();
		$dispatcher = JDispatcher::getInstance();
		$cid		= JRequest::getInt('category_id');

		// выбор из БД данных, необходимых для отрисовки каталога
		$context	= "jshoping.searclist.front.product";
		$orderby 	= $app->getUserStateFromRequest($context.'orderby', 'sf_orderby', 	$jsCfg->product_sorting_direction,	'cmd');
        $order		= $app->getUserStateFromRequest($context.'order', 	'sf_order', 	$jsCfg->product_sorting, 			'cmd');
        $limit 		= $app->getUserStateFromRequest($context.'limit', 	'limit', 		$jsCfg->count_products_to_page, 	'int');
        $limit		= ($limit) ? $limit : $jsCfg->count_products_to_page;
        $dontStore  = JRequest::getInt('sf_dontstore');
        
		$adv_query	= "";
		$adv_from 	= "";

		if ($dontStore) {
			$limitstart	= $app->getUserState($context.'sf_start', 0);
		} else {
			$limitstart	= $app->getUserStateFromRequest($context.'sf_start', 'sf_start', 0, 'int');
		}
		
		$filters	= ($cid && $pids === null) ? array( 'categorys' => $helper->buildCatList($cid) ) : array();
		$product	= JTable::getInstance('product', 'jshop');
		$adv_result = $product->getBuildQueryListProductDefaultResult();
		$product->getBuildQueryListProduct("search", "list", $filters, $adv_query, $adv_from, $adv_result);

		switch ( $orderby ) {
			case "name":
				$order_query = "prod.`".$jslang->get('name')."`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			case "price":
				$order_query = "IF (
									prod.`min_price`,
									prod.`min_price`,
									prod.`product_price`
									) / cr.`currency_value`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			case "hits":
				$order_query = "prod.`hits`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			case "date":
				$order_query = "prod.`date_modify`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			case "mnf":
				$order_query = "mnf.`".$jslang->get('name')."`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			case "qnty":
				$order_query = "prod.`unlimited`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';

				$order_query .= ", prod.`product_quantity`";
				$order_query .= ( $order == 'desc' ) ? ' DESC' : '';
			break;

			default:
				$order_query = "prod.`product_id`";
			break;
		}
		

		// prod.`product_id` = prod.`product_id` - для универсальности при добалении доп.условий фильтрации
		$query 	= 	"SELECT SQL_CALC_FOUND_ROWS
						$adv_result,
						prod.`product_price` as pr,
						cr.`currency_value` as curr,
						prod.`product_price` / cr.`currency_value` as price
					FROM `#__jshopping_products` AS prod
					LEFT JOIN `#__jshopping_products_to_categories` AS pr_cat
						ON pr_cat.product_id = prod.product_id
					LEFT JOIN `#__jshopping_categories` AS cat
						ON pr_cat.category_id = cat.category_id
					LEFT JOIN `#__jshopping_manufacturers` AS mnf
						ON ( prod.`product_manufacturer_id` = mnf.`manufacturer_id` )
					LEFT JOIN `#__jshopping_currencies` AS cr
						ON ( prod.`currency_id` = cr.`currency_id` )
					$adv_from
					WHERE
						prod.`product_id` = prod.`product_id`
					".( ($pids)
							? "AND prod.product_id IN ( ".join(',', $pids).")"
							: ( ($pids === null) ? '' : "AND 1 != 1" )
					)."
					".( ( $adv_query )
							? $adv_query
							: ""
					)."
					GROUP BY prod.`product_id`
					ORDER BY ".$order_query;

        $db->setQuery($query, $limitstart, $limit);
        $rows = $db->loadObjectList();

        $db->setQuery("SELECT FOUND_ROWS()");
        $total = $db->loadResult();

        $rows = listProductUpdateData($rows);
        addLinkToProducts($rows, 0, 1);


		$_review = JTable::getInstance('review', 'jshop');
        $allow_review = $_review->getAllowReview();
        
        $action = xhtmlUrl($_SERVER['REQUEST_URI']);
        
        $dispatcher->trigger( 'onBeforeDisplayProductList', array(&$rows) );

        jimport('joomla.html.pagination');
        $pagination = new JPagination($total, $limitstart, $limit, 'sf_');
        $pagenav 	= $pagination->getPagesLinks();

        // панель сортировки
        $doc	= JFactory::getDocument();
        $panid	= 'sf_panel_'.$mid;
        $doc->addScriptDeclaration("
			jQuery(function() {
				var sample = jQuery('#".$panid."');

				if (sample) {
					var panel = sample.clone(true, true);
					var wrap = sample.parentNode;

					panel.appendTo(wrap);
				}
			});
        ");
        JHtml::script( 'modules/mod_jsfilter/assets/jsfilter.js', true );

		$table	= JTable::getInstance('extension');
		$table->load( array('type' => 'module', 'element' => 'mod_jsfilter') );
		$paramsData	= json_decode($table->params);
		$params	= &$paramsData->cfg;

		// Панель фильтрации
		$sortFields = $helper->getAllSortFields();
		$panelContent = '';
		foreach ($params->sort as $fname) {
			if (!$fname) continue;
			
			$f = &$sortFields[$fname];
			$panelContent .= '<div class="item" rel="'.$f['name'].'"'
							.' onclick="sf_doSort(\'smart_filter_'.$mid.'\', this);"'
							.'>'
							.$f['label']
							.'<span class="sort'
							.( ($orderby == $f['name'] && $order == 'asc' ) ? ' asc' : '' )
							.( ($orderby == $f['name'] && $order == 'desc' ) ? ' desc' : '' )
							.'"></span>'
						.'</div>';
		}

		// Вывод элемента фильтрации результата по наличию
		$baseCfg = JsfilterHelper::getBaseCfg();
		if ($baseCfg->stock_state) {
			$sfData = JRequest::getVar('sf', null, '', 'array');
			
			if ( isset($sfData[999]['stock']['checkbox']) ) {
				$stockVal = ( $sfData[999]['stock']['checkbox'][0] ) ? 1 : 0;
			} else {
				$stockVal = -1;
			}
			
			$panelContent .= '<div class="stock">'
							.'<select name="sf_dummy[stock]" onchange="sf_doPostFilter(\'smart_filter_'.$mid.'\', this);">'
								.'<option value="0" '.( (!$stockVal || ($stockVal == -1 && !$cfg->stock_state)) ? 'selected="selected"' : '' ).'>'
									.JText::_('MJSF_STOCK_INACTIVE_NAME')
								.'</option>'
								.'<option value="1"'.( ($stockVal == 1 || ($stockVal == -1 && $cfg->stock_state)) ? 'selected="selected"' : '' ).'>'
									.JText::_('MJSF_STOCK_ACTIVE_NAME')
								.'</option>'
							.'</select>'
						.'</div>';
		}

		$panel = "<style>.box_products_sorting {display:none;}</style>"; // костыль для скрытия стандартной панели фильтрации
		if ($panelContent) {
			$panel	.= '<div id="'.$panid.'" class="sf_panel">'
						.'<div class="title">'.JText::_('MJSF_SORT_PANEL_TITLE').'</div>';
			$panel .= $panelContent;
			$panel .= '</div>';
		}


        $view_name 		= "search";
        $view_config 	= array("template_path"=>JPATH_COMPONENT."/templates/".$jsCfg->template."/".$view_name);
        $view 			= &$this->getView($view_name, getDocumentType(), '', $view_config);
        
        if ($rows) {
			$view->setLayout("products");
		} else {
			$view->setLayout("noresult");
		}

		// Заглушка
		$image_sort_dir = 'arrow_down.gif';

        // Принудительное включение вывода блока сортировки (для замены своей панелью)
        $jsCfg->show_sort_product = 1;

        // search - текстовая строка поискового запроса
        // $view->assign('search', 						$search);
        $view->assignRef('total', 						$total);
        $view->assignRef('config', 						$jsCfg);
        $view->assign('template_block_list_product',	"list_products/list_products.php");
        $view->assign('template_block_form_filter', 	"list_products/form_filters.php");
        $view->assign('template_block_pagination', 		"list_products/block_pagination.php");
        $view->assign('path_image_sorting_dir', 		$jsCfg->live_path.'images/'.$image_sort_dir);
        $view->assign('filter_show', 					0);
        $view->assign('filter_show_category', 			0);
        $view->assign('filter_show_manufacturer',		$jsCfg->product_list_show_manufacturer);
        $view->assignRef('pagination', 					$pagenav);
        $view->assign('display_pagination', 			$pagenav!="");
        $view->assign('product_count', 					$total);
        $view->assign('sorting', 						$panel);
        $view->assign('count_product_to_row', 			$jsCfg->count_products_to_row);
        $view->assignRef('rows',						$rows);
        $view->assign('allow_review', 					$allow_review);
        $view->assignRef('shippinginfo',				SEFLink('index.php?option=com_jshopping&controller=content&task=view&page=shipping',1));
        
        $dispatcher->trigger('onBeforeDisplayProductListView', array(&$view) );
        
        $view->display();
	}
}
?>
