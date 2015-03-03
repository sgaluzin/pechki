<?php 
/**
* @version      4.8.0 18.12.2014
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
<div class="jshop" id="comjshop">
<?php print $this->category->description?>

<div class="jshop_list_category grid-brand">
<?php if (count($this->categories)){?>
    <?php foreach($this->categories as $k=>$category){?>
        <div class="item-wrap">
            <div class="item">
                <a class="link" href="<?php print $category->category_link;?>">
                    <div class="img-wrap">
                        <img class = "jshop_img" src = "<?php print $this->image_category_path;?>/<?php if ($category->category_image) print $category->category_image; else print $this->noimage;?>" alt="<?php print htmlspecialchars($category->name);?>" title="<?php print htmlspecialchars($category->name);?>" />
                    </div>
                    <p class="title"><?php print $category->name?></p>
                </a>
            </div>
        </div>
    <?php } ?>
<?php } ?>
</div>
</div>