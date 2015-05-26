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
<?if ($_GET["layout"]=='grid' || $_GET['layout'] == null){?>
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