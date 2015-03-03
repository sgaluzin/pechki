<div class="cart">
    <span class="number"><?php print $cart->count_product?></span>
</div>
<div class="cart-button-wrap">
    <p class="text">Товаров в корзине</p>
    <a class="pure-button button-primary" href = "<?php print SEFLink('index.php?option=com_jshopping&controller=cart&task=view', 1)?>">Оформить заказ</a>
</div>