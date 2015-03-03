<?php
/**
 * @version      4.8.0 22.10.2014
 * @author       MAXXmarketing GmbH
 * @package      Jshopping
 * @copyright    Copyright (C) 2010 webdesigner-profi.de. All rights reserved.
 * @license      GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');
?>
<?php $countprod = count($this->products); ?>
<?php
foreach ($this->products as $prod) {
    $amountprod += $prod['quantity'];
}
$prod = null;
?>

<h1 class="order-title">Корзина</h1>

<div class="jshop" id="comjshop">
    <form action="<?php print SEFLink('index.php?option=com_jshopping&controller=cart&task=refresh') ?>" method="post"
          name="updateCart">
        <?php print $this->_tmp_ext_html_cart_start ?>
        <?php if ($countprod > 0) { ?>
            <table class="pure-table striped order">
                <tr>
                    <th class="photo">Фото</th>
                    <th class="name">Наименование</th>
                    <th class="value">Количество</th>
                    <th class="price">Сумма</th>
                    <th class="action">Удалить</th>
                </tr>
                <?php
                $i = 1;
                foreach ($this->products as $key_id => $prod) {
                    ?>
                    <tr class="jshop_prod_cart <?php
                    if ($i % 2 == 0)
                        print "even";
                    else
                        print "odd"
                    ?>">
                        <td class="photo">
                            <a href="<?php print $prod['href'] ?>">
                                <img src="<?php print $this->image_product_path ?>/<?php
                                if ($prod['thumb_image'])
                                    print $prod['thumb_image'];
                                else
                                    print $this->no_image;
                                ?>" alt="<?php print htmlspecialchars($prod['product_name']); ?>" class="jshop_img"/>
                            </a>
                        </td>
                        <td class="name">
                            <a href="<?php print $prod['href'] ?>"><?php print $prod['product_name'] ?></a>
                            <?php if ($this->config->show_product_code_in_cart) { ?>
                                <span class="jshop_code_prod">(<?php print $prod['ean'] ?>)</span>
                            <?php } ?>
                            <?php if ($prod['manufacturer'] != '') { ?>
                                <div class="manufacturer"><?php print _JSHOP_MANUFACTURER ?>:
                                    <span><?php print $prod['manufacturer'] ?></span></div>
                            <?php } ?>
                            <?php print sprintAtributeInCart($prod['attributes_value']); ?>
                            <?php print sprintFreeAtributeInCart($prod['free_attributes_value']); ?>
                            <?php print sprintFreeExtraFiledsInCart($prod['extra_fields']); ?>
                            <?php print $prod['_ext_attribute_html'] ?>
                        </td>
                        <td class="value">
                            <input type="text" name="quantity[<?php print $key_id ?>]"
                                   value="<?php print $prod['quantity'] ?>" class="inputbox counter"/>
                            <?php print $prod['_qty_unit']; ?>
                            <span class="cart-reload"><img style="cursor:pointer"
                                                           src="<?php print $this->image_path ?>images/reload.png"
                                                           title="<?php print _JSHOP_UPDATE_CART ?>"
                                                           alt="<?php print _JSHOP_UPDATE_CART ?>"
                                                           onclick="document.updateCart.submit();"/></span>
                        </td>
                        <td class="price">
                            <?php print formatprice($prod['price'] * $prod['quantity']); ?>
                            <?php print $prod['_ext_price_total_html'] ?>
                            <?php if ($this->config->show_tax_product_in_cart && $prod['tax'] > 0) { ?>
                                <span class="taxinfo"><?php print productTaxInfo($prod['tax']); ?></span>
                            <?php } ?>
                        </td>
                        <td class="action">
                            <a class="remove" href="<?php print $prod['href_delete'] ?>"
                               onclick="return confirm('<?php print _JSHOP_CONFIRM_REMOVE ?>')">✘</a>
                        </td>
                    </tr>
                    <?php
                    $i++;

                }
                ?>


                <tr class="amount">
                    <td class="r-align" colspan="2">
                        <!--<div class="left discount">
                            <?php print $this->_tmp_ext_html_before_discount ?>
                            <?php if ($this->use_rabatt && $countprod > 0) { ?>
                                <form name="rabatt" method="post"
                                      action="<?php print SEFLink('index.php?option=com_jshopping&controller=cart&task=discountsave') ?>">
                                    <span class="label"><?php print _JSHOP_RABATT ?></span>
                                    <input type="text" class="inputbox" name="rabatt" value=""/>
                                    <input type="submit" class="button-primary pure-button" value="<?php print _JSHOP_RABATT_ACTIVE ?>"/>
                                </form>
                            <?php } ?>
                        </div>-->
                        <span class="right">ИТОГО:</span>
                    </td>
                    <td class="value"><!--<input class="counter" type="text">--><strong><?php print $amountprod; ?></strong></td>
                    <td class="price">
                        <?php print formatprice($this->fullsumm) ?><?php print $this->_tmp_ext_total ?>
                    </td>
                    <td></td>
                </tr>
            </table>

            <!--<?php if ($this->config->show_weight_order) { ?>
                <div class="weightorder">
                    <?php print _JSHOP_WEIGHT_PRODUCTS ?>: <span><?php print formatweight($this->weight); ?></span>
                </div>
            <?php } ?>

            <?php if ($this->config->summ_null_shipping > 0) { ?>
                <div class="shippingfree">
                    <?php printf(_JSHOP_FROM_PRICE_SHIPPING_FREE, formatprice($this->config->summ_null_shipping, null, 1)); ?>
                </div>
            <?php } ?>

            <div class="cartdescr"><?php print $this->cartdescr ?></div>
            <br/>
            <table class="jshop jshop_subtotal">
                <?php if (!$this->hide_subtotal) { ?>
                    <tr>
                        <td class="name">
                            <?php print _JSHOP_SUBTOTAL ?>
                        </td>
                        <td class="value">
                            <?php print formatprice($this->summ); ?><?php print $this->_tmp_ext_subtotal ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php print $this->_tmp_html_after_subtotal ?>
                <?php if ($this->discount > 0) { ?>
                    <tr>
                        <td class="name">
                            <?php print _JSHOP_RABATT_VALUE ?><?php print $this->_tmp_ext_discount_text ?>
                        </td>
                        <td class="value">
                            <?php print formatprice(-$this->discount); ?><?php print $this->_tmp_ext_discount ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$this->config->hide_tax) { ?>
                    <?php foreach ($this->tax_list as $percent => $value) { ?>
                        <tr>
                            <td class="name">
                                <?php print displayTotalCartTaxName(); ?>
                                <?php if ($this->show_percent_tax) print formattax($percent) . "%" ?>
                            </td>
                            <td class="value">
                                <?php print formatprice($value); ?><?php print $this->_tmp_ext_tax[$percent] ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                <tr class="total">
                    <td class="name">
                        Итого
                    </td>
                    <td class="name">
                        <?php print $amountprod; ?>
                    </td>
                    <td class="value">
                        <?php print formatprice($this->fullsumm) ?><?php print $this->_tmp_ext_total ?>
                    </td>
                </tr>
                <?php print $this->_tmp_html_after_total ?>
                <?php if ($this->config->show_plus_shipping_in_product) { ?>
                    <tr>
                        <td colspan="2" align="right">
                            <span
                                class="plusshippinginfo"><?php print sprintf(_JSHOP_PLUS_SHIPPING, $this->shippinginfo); ?></span>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($this->free_discount > 0) { ?>
                    <tr>
                        <td colspan="2" align="right">
                            <span class="free_discount"><?php print _JSHOP_FREE_DISCOUNT; ?>
                                : <?php print formatprice($this->free_discount); ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </table>-->
        <?php } else { ?>
            
            <div id="system-message-container">
                <div id="system-message">
                    <div class="alert alert-message">
                        <div>
                            <p><?php print _JSHOP_CART_EMPTY ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>


        <?php print $this->_tmp_html_before_buttons ?>
        <!--<table class="jshop" style="margin-top:10px">
            <tr id="checkout">
                <td width="50%" class="td_1">
                    <a href="<?php print $this->href_shop ?>">
                        <img src="<?php print $this->image_path ?>images/arrow_left.gif"
                             alt="<?php print _JSHOP_BACK_TO_SHOP ?>"/>
                        <?php print _JSHOP_BACK_TO_SHOP ?>
                    </a>
                </td>
                <td width="50%" class="td_2">
                    <?php if ($countprod > 0) { ?>
                        <a href="<?php print $this->href_checkout ?>">
                            <?php print _JSHOP_CHECKOUT ?>
                            <img src="<?php print $this->image_path ?>images/arrow_right.gif"
                                 alt="<?php print _JSHOP_CHECKOUT ?>"/>
                        </a>
                    <?php } ?>
                </td>
            </tr>
        </table>-->
        <?php print $this->_tmp_html_after_buttons ?>
    </form>


    <form action="http://pechspb.ru/joomla/katalog/checkout/step2save" method="post">
        <table class="pure-table order info">
            <tr class="info">
                <td>
                    <div class="row personal">
                        <label class="label-wrap">
                            <span class="label">Имя получателя:</span>
                            <input class="w6"  type="text" name="f_name" value="111"/>
                        </label>
                        <label class="label-wrap">
                            <span class="label">E-mail:</span>
                            <input class="w6" type="text" name="email" value="111@asdf.ri"/>
                        </label>
                        <label class="label-wrap phone">
                            <span class="label required">Телефон:</span>
                            +7
                            <input class="w1" type="text" name="phone_code"/>
                            <input class="w3" type="text" required="required" name="phone"
                                                                          value="111"/>
                        </label>
                    </div>
                    <div class="row delivery">
                        <h3>Выберете способ доставки:</h3>
                        <label class="check-block">
                            <input type="radio" name="sh_pr_method_id" checked="checked" value="1"/>
                            <div class="label">
                                <span class="title">Курьером</span>
                                <p>Доставка осуществляется в течении дня удобным для вас способом
                                <p>Стоимость заказа будет рассчитана менеджером <a href="#">Подробнее о доставке.</a>
                            </div>
                        </label>
                        <label class="check-block">
                            <input type="radio" name="sh_pr_method_id" value="2"/>
                            <div class="label">
                                <span class="title">Оплата при получении</span>
                                <p>Доставка осуществляется в течении дня удобным для вас способом
                            </div>
                        </label>
                    </div>
                    <div class="row address">
                        <h3>Адрес доставки (Москва, Санкт-Петербург + близлежащие районы):</h3>
                        <input type="hidden" name="delivery_adress" value="1"/>
                        <div class="inputs">
                            <label class="label-wrap">
                                <span class="label required">Город или населенный пункт:</span>
                                <input class="w2" type="text" required="required" name="d_city" value="111"/>
                            </label>
                            <label class="label-wrap">
                                <span class="label required">Улица:</span>
                                <input class="w2" type="text" required="required" name="d_street" value="111"/>
                            </label>
                            <label class="label-wrap">
                                <span class="label required">Дом:</span>
                                <input class="w1" type="text" required="required" name="d_apartment" value="111"/>
                            </label>
                            <label class="label-wrap">
                                <span class="label">Квартира</span>
                                <input class="w1" type="text" name="d_home" value="111"/>
                            </label>
                        </div>
                    </div>
                    <div class="row delivery">
                        <h3>Выберете способ оплаты:</h3>
                        <label class="check-block">
                            <input type="radio" name="payment_method" checked="checked" value="pm_bank"/>
                            <div class="label">
                                <span class="title">Оплата при получении курьеру</span>
                                <p>Оплата наличными при получении заказа курьеру
                            </div>
                        </label>
                        <label class="check-block">
                            <input type="radio" name="payment_method" value="pm_purchase_order"/>
                            <div class="label">
                                <span class="title">Оплата банковской картой при получении курьеру</span>
                                <p>Оплата банковской картой при получении заказа курьеру
                            </div>
                        </label>
                        <label class="check-block">
                            <input type="radio" name="payment_method" value="pm_epayment"/>
                            <div class="label">
                                <span class="title">Оплата электронными платежными системами</span>
                            </div>
                        </label>
                    </div>
                    <div class="row comment">
                        <h3>Комментарии к заказу:</h3>
                        <textarea name="order_add_info"></textarea>
                    </div>
                    <div class="row">
                        <input class="pure-button button-primary" type="submit" value="Оформить заказ"/>
                    </div>
                </td>
            </tr>
        </table>
    </form>

</div>