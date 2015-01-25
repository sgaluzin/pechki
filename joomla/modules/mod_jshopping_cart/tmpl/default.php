<<<<<<< HEAD
<div class="cart">
    <span class="number"><?php print $cart->count_product?></span>
</div>
<div class="cart-button-wrap">
    <p class="text">Товаров в корзине</p>
    <a class="pure-button button-primary" href = "<?php print SEFLink('index.php?option=com_jshopping&controller=cart&task=view', 1)?>">Оформить заказ</a>
=======
<div id = "jshop_module_cart">
<table width = "100%" >
<tr>
    <td>
      <span id = "jshop_quantity_products"><?php print $cart->count_product?></span>&nbsp;<?php print JText::_('PRODUCTS')?>
    </td>
    <td>-</td>
    <td>
      <span id = "jshop_summ_product"><?php print formatprice($cart->getSum(0,1))?></span>
    </td>
</tr>
<tr>
    <td colspan="3" align="right">
      <a href = "<?php print SEFLink('index.php?option=com_jshopping&controller=cart&task=view', 1)?>"><?php print JText::_('GO_TO_CART')?></a>
    </td>
</tr>
</table>
>>>>>>> c48fde4942579cd605ff11eaa1acde97f3324cdc
</div>