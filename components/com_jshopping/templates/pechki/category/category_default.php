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
<div class="jshop" id="comjshop">
    <h1><?php print $this->category->name?></h1>
    <?php print $this->category->description?>
<div class="grid-brand">
<?php if (count($this->categories)){ ?>
    <?php foreach($this->categories as $k=>$category){?>
      <div class="item-wrap">
        <div class="item">
            <div class="img-wrap">
                <a href = "<?php print $category->category_link;?>"><img class="jshop_img" src="<?php print $this->image_category_path;?>/<?php if ($category->category_image) print $category->category_image; else print $this->noimage;?>" alt="<?php print htmlspecialchars($category->name)?>" title="<?php print htmlspecialchars($category->name)?>" /></a>
            </div>
            <div class="info">
                <a class = "title" href = "<?php print $category->category_link?>">
                    <?php print $category->name?>
                </a>
                <span class="count">(<?php print $category->cnt?>)</span>
            </div>
        </div>
       </div>
    <?php } ?>
<?php }?>
</div>
<?php include(dirname(__FILE__)."/products.php");?>
</div>