<?php 
/**
* @version      4.8.0 18.12.2014
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
<div class="layout-trigger">
    <a href="<?=$url_without_layout_param?>layout=list" class="link<?=($_GET["layout"]!='grid')? ' current' : ''?>">
        <i class="icon icon-list"></i>
        <span class="label">В виде списка</span>
    </a>
    <a href="<?=$url_without_layout_param?>layout=grid" class="link<?=($_GET["layout"]=='grid')? ' current' : ''?>">
        <i class="icon icon-grid"></i>
        <span class="label">В виде галереи</span>
    </a>
</div>
<?if ($_GET["layout"]=='grid'){?>
<div class="grid-product category">
    <?php foreach ($this->rows as $k=>$product){?>
        <?php include(dirname(__FILE__)."/".$product->template_block_product);?>
    <?php }?>
</div>
<?} else {?>
    <table class="jshop list_product pure-table striped category"  id="comjshop_list_product">
        <tr>
            <th class="photo">Фото</th>
            <th class="producer">Производитель</th>
            <th class="model">Модель</th>
            <th class="volume">Объем помещения</th>
            <th class="weight">Вес (кг)</th>
            <th class="size">Размеры (ГxШxВ)</th>
            <th class="price">Цена</th>
            <th class="action"></th>
        </tr>
        <?php foreach ($this->rows as $k=>$product){?>
            <?php include(dirname(__FILE__)."/".$product->template_block_product);?>
        <?php }?>
    </table>
<?}?>