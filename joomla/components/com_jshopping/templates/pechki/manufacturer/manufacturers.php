<?php
/**
* @version      4.3.1 13.08.2013
* @author       MAXXmarketing GmbH
* @package      Jshopping
* @copyright    Copyright (C) 2010 webdesigner-profi.de. All rights reserved.
* @license      GNU/GPL
*/
defined('_JEXEC') or die('Restricted access');
?>
<h1><?=JFactory::getDocument()->getTitle();?></h1>
<?php if ($this->params->get('show_page_heading') && $this->params->get('page_heading')) {?>
<div class="shophead<?php print $this->params->get('pageclass_sfx');?>"><h1><?php print $this->params->get('page_heading')?></h1></div>
<?php }?>
<div class="jshop"  id="comjshop">
<?php print $this->manufacturer->description?>

<?php if (count($this->rows)){?>
<div class="jshop_list_manufacturer grid-brand">
    <?php foreach($this->rows as $k=>$row){?>
        <div class="item-wrap">
            <div class="item">
                <a class="link" href="<?php print $row->link?>">
                    <div class="img-wrap">
                        <img src="<?php print $this->image_manufs_live_path;?>/<?php if ($row->manufacturer_logo) print $row->manufacturer_logo; else print $this->noimage;?>" alt="<?php print htmlspecialchars($row->name);?>">
                    </div>
                    <p class="title"><?php print $row->name?></p>
                </a>
            </div>
        </div>
	 <?php } ?>
</div>
<?php } ?>
</div>