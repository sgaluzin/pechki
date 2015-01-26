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
<?php print $product->_tmp_var_start?>
<tr>
    <td class="photo">
        <?php if ($product->image){?>
			<?php print $product->_tmp_var_image_block;?>
            <?php if ($product->label_id){?>
                <div class="product_label">
                    <?php if ($product->_label_image){?>
                        <img src="<?php print $product->_label_image?>" alt="<?php print htmlspecialchars($product->_label_name)?>" />
                    <?php }else{?>
                        <span class="label_name"><?php print $product->_label_name;?></span>
                    <?php }?>
                </div>
            <?php }?>
            <a href="<?php print $product->product_link?>">
                <img class="jshop_img" src="<?php print $product->image?>" alt="<?php print htmlspecialchars($product->name);?>" title="<?php print htmlspecialchars($product->name);?>" />
            </a>
        <?php }?>
    </td>
    <td class="producer">
        <?=$product->manufacturer->name; ?>
    </td>
    <td class="model">
        <span class="top"><?=$product->category_id?></span>
        <span class="title"><?=$product->name?></span>

        <div class="bottom">
            <div class="rating r-<?=(int)$product->average_rating?>">
                <span>☆</span><span>☆</span><span>☆</span><span>☆</span><span>☆</span>
            </div>
            <?php if ($this->allow_review){?>
                <span>отзывов: <span class="coutn"><?=$product->reviews_count?></span></span>
            <?php }?>
        </div>
    </td>
    <td class="volume">до <?=$product->reviews_count?> куб.м
        <? var_dump($product);?>
    </td>
    <td class="weight">0</td>
    <td class="size">0</td>
    <td class="price">
        <?php if ($product->_display_price){?>
            <div class = "jshop_price">
                <?php if ($this->config->product_list_show_price_description) print _JSHOP_PRICE.": ";?>
                <?php if ($product->show_price_from) print _JSHOP_FROM." ";?>
                <span><?php print formatprice($product->product_price);?><?php print $product->_tmp_var_price_ext;?></span>
            </div>
        <?php }?>
    </td>
    <td class="action">
        <a href="<?php print $product->product_link?>"><div class="pure-button button-primary">Подробнее</div></a>
    </td>
</tr>
<?php print $product->_tmp_var_end?>