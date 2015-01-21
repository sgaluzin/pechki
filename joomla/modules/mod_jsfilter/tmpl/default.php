<?php
/*
 * default.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */

// no direct access
defined('_JEXEC') or die;


JHtml::_('stylesheet', JURI::root().'modules/mod_jsfilter/assets/layout/'.$cfg['layout'].'/style.css');
JHtml::_('script', JURI::root().'modules/mod_jsfilter/assets/jsfilter.js' );

?>

<script type="text/javascript">
	<?php
	echo "MJSF_TARGETS[".$module->id."] = '".(($baseCfg->content_selector) ? $baseCfg->content_selector : 'div.jshop')."';\n";
	echo "MJSF_SETTINGS[".$module->id."] = {"
		."'show_tip': ".(int)$cfg['show_tip'].","
		."'dir': ".(int)$cfg['mod_direction'].","
		."'onload_code': '".str_replace(array("\n", "\r"), '\n', $baseCfg->onload_code)."',"
		."'params': ".json_encode($storedParams).","
		."'storedUrl': '".$storedUrl."'"
	."};\n";
	echo "MJSF_STRINGS = {};\n";
	echo "MJSF_STRINGS.selectAll = '".JText::_('MJSF_SELECT_ALL')."'\n";
	echo "MJSF_STRINGS.allSelected = '".JText::_('MJSF_ALL_SELECTED')."'\n";
	echo "MJSF_STRINGS.multicheckPlaceholder = '".JText::_('MJSF_MULTICHECK_PLACEHOLDER')."'\n";
	?>
	
	jQuery(document).ready(function() {
		// Инициализация модуля
		sf_init('<?php echo $module->id; ?>');
		<?php
		// Загрузка актуальных значений при отсутствии сохраненных параметров фильтрации
		// и активации элемента доп.фильтрации по наличию товара (значение "только в наличии")
		if (!$storedParams && $baseCfg->stock_state == 1 && $cfg['deactivate_values']) {
			// Фильтрация товаров только по наличию
			$sf = array();
			$sf[999]['stock']['checkbox'][] = 'true';
			$helper->loadConfig($cfg);
			$pids = $helper->doFilter($sf);
			// Определение актуальных значений после искусственной фильтрации
			$activeValues = $helper->getActiveValues($pids);
		?>
		var form = jQuery('#smart_filter_<?php echo $module->id; ?>');
		var jsonData = <?php echo json_encode($activeValues); ?>;
		sf_updateValues(form, jsonData);
		<?php
		}
		?>
	});
	
</script>


<div id="jsfilter_ajax_sample" style="display:none;">
	<div class="">
		<div class="loader">
			<img src="<?php echo JUri::root(); ?>media/system/images/modal/spinner.gif" />
		</div>

		<div class="msg">
			<?php echo JText::_('MJSF_LOADER_MSG'); ?>
		</div>

	</div>
</div>

<div style="position: relative;">
	<div id="sf_tip" style="display: none; position: absolute;">
		<a href="javascript:void(0);">
			<?php echo JText::_('MJSF_FOUND_TIP'); ?>
			[<span id="sf_tip_count"></span>]
		</a>
	</div>
</div>


<div id="jsfilter_<?php echo $module->id; ?>" class="sf_wrapper">

	<div class="sf_container<?php echo ( (int)$cfg['mod_direction'] ) ? ' sf_inline' : ''; ?>">
		<?php
		if ( $cfg['title'] ) {
		?>
		<div class="sf_header">
			<?php echo $cfg['title']; ?>
		</div>
		<?php
		}
		?>

		<form id="smart_filter_<?php echo $module->id; ?>" name="smart_filter_<?php echo $module->id; ?>" class="sf_form" action="<?php echo JURI::base(); ?>index.php?option=com_jshopping&controller=jsfilter" method="post" onsubmit="return sf_load(this);">
			
			<?php echo $html; ?>

			<?php if (!isset($cfg['show_buttons']) || $cfg['show_buttons']) { ?>
			<div class="sf_buttons">		
				<input type="submit" value="Показать" class="sf_submit" />
				<input type="reset" value="Сбросить" class="sf_reset" />
			</div>
			<?php } ?>
			<input id="sf_orderby" type="hidden" name="sf_orderby" value="" />
			<input id="sf_order" type="hidden" name="sf_order" value="" />
			<input id="mid" type="hidden" name="mid" value="<?php echo $module->id; ?>" />
			<input id="id" type="hidden" name="id" value="<?php echo $cfg['id']; ?>" />
			<input type="hidden" name="category_id" value="<?php echo $currCatId; ?>" />
			<input type="hidden" name="manufacturer_id" value="<?php echo $currManufacturerId; ?>" />
			<input id="sf_dontstore" type="hidden" name="sf_dontstore" value="" />
			<?php if ($baseCfg->stock_state) { ?>
				<input id="stock_state" type="hidden" name="sf[999][stock][checkbox][]" value="<?php echo ($baseCfg->stock_state == 1) ? 'true' : ''; ?>" />
			<?php } ?>

		</form>

	</div>

</div>
