<?php 
/**
* @version      4.3.1 13.08.2013
* @author       MAXXmarketing GmbH
* @package      Jshopping
* @copyright    Copyright (C) 2010 webdesigner-profi.de. All rights reserved.
* @license      GNU/GPL
*/
defined('_JEXEC') or die('Restricted access');

$uri = JFactory::getURI();
$absolute_url = $uri->toString();
$url_without_layout_param = preg_replace('/&?layout=[^&]*/', '', $absolute_url);
if(isset($_GET) && !empty($_GET) && count($_GET)>1){
    $url_without_layout_param .= '&';
} else{
    $url_without_layout_param .= '?';
}

?>
<?if (!($_GET["option"]=='com_jshopping'
    && $_GET["controller"]=='jsfilter'
    && $_GET["task"]=='request')) { ?>
    <div class="layout-trigger">
        <div class="left">
            <a href="<?=$url_without_layout_param?>layout=grid" class="link<?=(($_GET["layout"]=='grid' || $_GET['layout'] == null))? ' current' : ''?>">
                <i class="icon icon-grid"></i>
                <span class="label">В виде галереи</span>
            </a>
            <a href="<?=$url_without_layout_param?>layout=list" class="link<?=($_GET["layout"]=='list')? ' current' : ''?>">
                <i class="icon icon-list"></i>
                <span class="label">В виде списка</span>
            </a>
        </div>
        <form class="right sorting" action="<?php print $this->action;?>" method="post" name="sort_count" id="sort_count">
                <?php if ($this->config->show_sort_product || $this->config->show_count_select_products){?>
                    <div class="block_sorting_count_to_page">
                        <?php if ($this->config->show_sort_product){?>
                            <span class="box_products_sorting"><?php print _JSHOP_ORDER_BY.": ".$this->sorting?>
                                <img class="arrow" src="<?php print $this->path_image_sorting_dir?>" alt="orderby" onclick="submitListProductFilterSortDirection()" />
                            </span>
                        <?php }?>
                        <?php if ($this->config->show_count_select_products){?>
                            <span class="box_products_count_to_page"><?php print _JSHOP_DISPLAY_NUMBER.": ".$this->product_count?></span>
                        <?php }?>
                    </div>
                <?php }?>

                <?php if ($this->config->show_product_list_filters && $this->filter_show){?>
                    <?php if ($this->config->show_sort_product || $this->config->show_count_select_products){?>
                        <div class="margin_filter"></div>
                    <?php }?>

                    <div class="jshop filters">
                        <?php if ($this->filter_show_category){?>
                            <span class="box_category"><?php print _JSHOP_CATEGORY.": ".$this->categorys_sel?></span>
                        <?php }?>
                        <?php if ($this->filter_show_manufacturer){?>
                            <span class="box_manufacrurer"><?php print _JSHOP_MANUFACTURER.": ".$this->manufacuturers_sel?></span>
                        <?php }?>
                        <?php print $this->_tmp_ext_filter_box;?>

                        <?php if (getDisplayPriceShop()){?>
                            <span class="filter_price"><?php print _JSHOP_PRICE?>:
            <span class="box_price_from"><?php print _JSHOP_FROM?> <input type="text" class="inputbox" name="fprice_from" id="price_from" size="7" value="<?php if ($this->filters['price_from']>0) print $this->filters['price_from']?>" /></span>
            <span class="box_price_to"><?php print _JSHOP_TO?> <input type="text" class="inputbox" name="fprice_to"  id="price_to" size="7" value="<?php if ($this->filters['price_to']>0) print $this->filters['price_to']?>" /></span>
                                <?php print $this->config->currency_code?>
        </span>
                        <?php }?>

                        <?php print $this->_tmp_ext_filter;?>
                        <input type="button" class="button" value="<?php print _JSHOP_GO?>" onclick="submitListProductFilters();" />
                        <span class="clear_filter"><a href="#" onclick="clearProductListFilter();return false;"><?php print _JSHOP_CLEAR_FILTERS?></a></span>
                    </div>
                <?php }?>
                <input type="hidden" name="orderby" id="orderby" value="<?php print $this->orderby?>" />
                <input type="hidden" name="limitstart" value="0" />
            </form>
    </div>

<?} ?>


