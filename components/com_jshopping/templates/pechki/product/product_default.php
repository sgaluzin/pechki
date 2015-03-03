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
<?php
$product = $this->product;
?>
<?php include(dirname(__FILE__)."/load.js.php");?>
<div class="jshop productfull product"  id="comjshop">
<form name="product" method="post" action="<?php print $this->action?>" enctype="multipart/form-data" autocomplete="off">
    <h1><?=$this->product->name?><?php include(dirname(__FILE__)."/ratingandhits.php");?></h1>

    <?php print $this->_tmp_product_html_start;?>
    <?php if ($this->config->display_button_print) print printContent();?>

    <div class="info-top">
        <div class="image-block">
            <?php print $this->_tmp_product_html_body_image?>
            <?php foreach($this->images as $k=>$image){?>
                <?if ($k==0){?>
                    <div class="main-image">
                        <div class="wrap" style="background: url(<?=$this->image_product_path.'/'.$image->image_name;?>)">
                            <a class="lightbox" id="main_image_full_<?php print $image->image_id?>" href="<?php print $this->image_product_path?>/<?php print $image->image_full;?>" <?php if ($k!=0){?>style="display:none"<?php }?> title="<?php print htmlspecialchars($image->_title)?>">
                                <img id = "main_image_<?php print $image->image_id?>" src = "<?php print $this->image_product_path?>/<?php print $image->image_name;?>" alt="<?php print htmlspecialchars($image->_title)?>" title="<?php print htmlspecialchars($image->_title)?>" />
                            </a>
                        </div>
                    </div>
                    <ul class="image-list">
                <?} else {?>
                    <li class="item">
                        <a class="lightbox" id="main_image_full_<?php print $image->image_id?>" href="<?php print $this->image_product_path?>/<?php print $image->image_full;?>" title="<?php print htmlspecialchars($image->_title)?>">
                            <img id = "main_image_<?php print $image->image_id?>" src = "<?php print $this->image_product_path?>/<?php print $image->image_name;?>" alt="<?php print htmlspecialchars($image->_title)?>" title="<?php print htmlspecialchars($image->_title)?>" />
                        </a>
                    </li>
                <?php }?>
            <?php }?>
            </ul>
        </div>
        <div class="info-block">
            <div class="top-row">
                <div class="price">Цена: <span class="amount"><?php print formatprice($this->product->getPriceCalculate())?></span> <span class="metric"><?php print $this->product->_tmp_var_price_ext;?></span></div>
                <input type="submit" class="pure-button button-primary button-xlarge" value="Купить" onclick="jQuery('#to').val('cart');" />
            </div>
            <p class="desc">
                <?php
                /*short description*/
                /* print $this->product->description; */
                ?>
            </p>
            <h3>Технические характеристики:</h3>
            <?php if (is_array($this->product->extra_field)){?>
            <table class="features">
                <?php foreach($this->product->extra_field as $extra_field){?>
                <?php if ($extra_field['grshow']){?>
                    <tr><td class='group-title' colspan="2"><?php print $extra_field['groupname']?></td></tr>
                <?php }?>
                    <tr>
                        <td class="name">
                            <span><?php print $extra_field['name'];?>:</span>
                        </td>
                        <td class="feature">
                            <?php if ($extra_field['description']) {?> <span class="extra_fields_description"><?php print $extra_field['description'];?></span><?php } ?> <span class="extra_fields_value"><?php print $extra_field['value'];?></span>
                        </td>
                    </tr>
                    <?php }?>
            </table>
            <?php }?>
        </div>
    </div>
    <div class="text">
        <?php print $this->product->description; ?>
    </div>

<input type="hidden" name="to" id='to' value="cart" />
<input type="hidden" name="product_id" id="product_id" value="<?php print $this->product->product_id?>" />
<input type="hidden" name="category_id" id="category_id" value="<?php print $this->category_id?>" />
</form>

<?php
    print $this->_tmp_product_html_before_related;
    include(dirname(__FILE__)."/related.php");
    print $this->_tmp_product_html_before_review;
    include(dirname(__FILE__)."/review.php");
?>
<?php print $this->_tmp_product_html_end;?>
</div>