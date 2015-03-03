<?php
/*
 * default.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */

// no direct access
defined('_JEXEC') or die;


JHtml::_('stylesheet', 'modules/mod_jsfilter/assets/layout/'.$cfg->layout.'/style.css');
?>

<div id="jsfilter_<?php echo $module->id; ?>" class="sf_wrapper">
	<div class="sf_container gradient">
		<div class="sf_header gradient">
			<?php echo $cfg->title; ?>
		</div>

		<div class="msg">
			<?php echo $error; ?>
		</div>

	</div>

</div>
