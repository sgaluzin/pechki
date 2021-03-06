<div class="grid-product discount">

<?php foreach($rows as $product){ ?>

	 <div class="item-wrap">
	 	<div class="item">
			<header class="header">
				<p class="title">
					<?php print $product->name?>
				</p>
				<p class="sub-title"><?php print $product->extra_str?></p>
			</header>
<?php 	if ($show_image && $product->image){// option modul  show_image ?>
		<div class="thumb">
<?php 		print $product->_tmp_var_image_block;?>

				<a href="<?php print $product->product_link?>">
					<img class="jshop_img" src="<?php print $product->image ? $product->image : $noimage;?>" alt="<?php print htmlspecialchars($product->name);?>" />
				</a>
		</div>
<?php 	} ?>

<?php	if($allow_review){	// option modul allow_review ?>
		<table class="review_mark"><tr><td><?php print showMarkStar($product->average_rating);?></td></tr></table>
		<div class="count_commentar">
<?php 		print sprintf(_JSHOP_X_COMENTAR, $product->reviews_count);?>
		</div>
<?php 	} ?>

<?php 	print $product->_tmp_var_bottom_foto;?>
		

<?php	if($short_description){	// option modul short_description ?>		
		 <div class="description">
            <?php print $product->short_description?>
        </div>
<?php 	} ?>

<?php 	if ($product->manufacturer->name && $manufacturer_name){// option modul manufacturer_name ?>
        <div class="manufacturer_name"><?php print _JSHOP_MANUFACTURER;?>: <span><?php print $product->manufacturer->name?></span></div>
<?php 	}?>

<?php 	if ($product->product_quantity <=0 && !$jshopConfig->hide_text_product_not_available && $product_quantity){// option modul product_quantity?>
		<div class="not_available"><?php print _JSHOP_PRODUCT_NOT_AVAILABLE;?></div>
<?php 	}?>

<?php	if( $product_old_price){?>
<?php 		if ($product->product_old_price > 0){// option modul product_old_price?>
		<div class="price before"><?php if ($jshopConfig->product_list_show_price_description) print _JSHOP_OLD_PRICE.": ";?><span><?php print formatprice($product->product_old_price)?></span></div>
<?php 		}?>
<?php 	print $product->_tmp_var_bottom_old_price;?>
<?php 	}?>

<?php 	if ($product->product_price_default > 0 && $jshopConfig->product_list_show_price_default && $product_price_default){ // option modul product_price_default?>
        <div class="price"><?php print _JSHOP_DEFAULT_PRICE.": ";?><span><?php print formatprice($product->product_price_default)?></span></div>
<?php 	}?>
		<div class="bottom">
<?php	if($display_price){?>
<?php 		if ($product->_display_price){// option modul display_price?>
		<div class = "price">
            <?if ($product->product_old_price){?>
            <span class="old"><?=formatprice($product->product_old_price)?></span>
            <?}?>
<?php 		if ($jshopConfig->product_list_show_price_description) print _JSHOP_PRICE.": ";?>
<?php 		if ($product->show_price_from) print _JSHOP_FROM." ";?>
			<span class="current"><?php print formatprice($product->product_price);?></span>
		</div>
<?php 		}?>
<?php 	print $product->_tmp_var_bottom_price;?>
<?php 	}?>

<?php 	if ($jshopConfig->show_tax_in_product && $product->tax > 0 && $show_tax_product){// option modul show_tax_product?>
		<span class="taxinfo"><?php print productTaxInfo($product->tax);?></span>
<?php 	}?>

<?php 	if ($jshopConfig->show_plus_shipping_in_product && $show_plus_shipping_in_product){?>
        <span class="plusshippinginfo"><?php print sprintf(_JSHOP_PLUS_SHIPPING, $shippinginfo);?></span>
<?php 	}?>

<?php 	if ($product->basic_price_info['price_show'] && $basic_price_info){// option modul basic_price_info?>
		<div class="base_price"><?php print _JSHOP_BASIC_PRICE?>: <?php if ($product->show_price_from) print _JSHOP_FROM;?> <span><?php print formatprice($product->basic_price_info['basic_price'])?> / <?php print $product->basic_price_info['name'];?></span></div>
<?php 	}?>

<?php 	if ($jshopConfig->product_list_show_weight && $product->product_weight > 0 && $product_weight){// option modul product_weight?>
        <div class="productweight"><?php print _JSHOP_WEIGHT?>: <span><?php print formatweight($product->product_weight)?></span></div>
<?php 	}?>

<?php 	if ($product->delivery_time != '' && $delivery_time){// option modul delivery_time?>
            <div class="deliverytime"><?php print _JSHOP_DELIVERY_TIME?>: <span><?php print $product->delivery_time?></span></div>
<?php 	}?>

<?php 	if (is_array($product->extra_field) && $extra_field){// option modul extra_field?>
		<div class="extra_fields">
<?php 		foreach($product->extra_field as $extra_field){?>
			<div><?php print $extra_field['name'];?>: <?php print $extra_field['value']; ?></div>
<?php 		}?>
		</div>
<?php	}?>

<?php 	if ($product->vendor && $vendor){// option modul vendor?>
        <div class="vendorinfo"><?php print _JSHOP_VENDOR?>: <a href="<?php print $product->vendor->products?>"><?php print $product->vendor->shop_name?></a></div>
<?php 	}?>

<?php 	if ($jshopConfig->product_list_show_qty_stock && $product_list_qty_stock){// option modul product_list_qty_stock?>
            <div class="qty_in_stock"><?php print _JSHOP_QTY_IN_STOCK?>: <span><?php print sprintQtyInStock($product->qty_in_stock)?></span></div>
<?php 	}?>

<?php	if($show_button){?>
<?php 	print $product->_tmp_var_top_buttons;?>

<?php 		if ($show_button_detal){?>
			<a class="pure-button button-primary" href="<?php print $product->product_link?>"><?php print _JSHOP_DETAIL?></a>
<?php		}?>
<?php 		print $product->_tmp_var_buttons;?>

<?php 	print $product->_tmp_var_bottom_buttons;?>
<?php	}?>
		</div>
	</div>
	</div>

<?php print $product->_tmp_var_end?>

<?php } ?>
</div>
