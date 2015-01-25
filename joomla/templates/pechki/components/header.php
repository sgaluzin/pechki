<?php ?>
<div class="container">
    <div class="logo"></div>
    <div class="top">
        <?php if ($this->countModules('search-top')) : ?>
            <div class="search-form right">
                <jdoc:include type="modules" name="search-top" style="well" />
            </div>
        <?php endif; ?>
        <?php if ($this->countModules('header-menu-top')) : ?>
            <nav class="nav-main right">
                <jdoc:include type="modules" name="header-menu-top" style="well" />
            </nav>
        <?php endif; ?>
        <!--
        <div class="search-form right">
            <div class="search-wrap">
                <input type="search" placeholder="Поиск">
                <button class="btn" type="submit"><i class="icon"></i></button>
            </div>
        </div>
        <nav class="nav-main right">
            <ul class="horiz-menu">
                <li class="item"><a class="link" href="#">Монтаж</a></li>
                <li class="item"><a class="link" href="#">Доставка и оплата</a></li>
                <li class="item"><a class="link" href="#">О компании</a></li>
                <li class="item"><a class="link" href="#">Отзывы</a></li>
                <li class="item"><a class="link" href="#">Контакты</a></li>
            </ul>
        </nav>-->
    </div>
    <div class="middle">
        <p class="slogan">Подарите сердце вашему дому</p>
        <div class="contacts">
            <p>Время работы:
            <p>с 10:00 до 22:00
            <p class="email">E-mail: info@pechispb.ru
        </div>
        <div class="cart-wrap">
            <div class="cart">
                <span class="number">0</span>
            </div>
            <div class="cart-button-wrap">
                <p class="text">Товаров в корзине</p>
                <div class="pure-button button-primary">Оформить заказ</div>
            </div>
        </div>
    </div>
</div>
<div class="middle-row">
    <div class="container">
        <div class="logo-name">
            <p class="title">Печи петербурга
            <p class="sub-title">продажа и установка
        </div>
        <div class="call-back">
            <div class="pure-button button-primary button-xlarge"><a href="#" class="callme_viewform">Закажите обратный звонок</a></div>
            <span class="text">Или звоните <span class="minor">прямо сейчас</span></span>
            <span class="phone">+7 (812) 918 39 74</span>
            <span class="text minor">или</span>
            <span class="phone minor">+7 (812) 918 69 74</span>
        </div>
    </div>
</div>
<div class="bottom">
    <div class="container">
        <!--<ul class="horiz-menu">
            <li class="item">
                <a class="link" href="/joomla/index.php/pechi-kaminy">
                    <div class="thumb"></div>
                    <div class="label"><span>Печи-камины</span></div>
                </a>
            </li>
            <li class="item">
                <a class="link" href="/joomla/index.php/bannye-pechi">
                    <div class="thumb"></div>
                    <div class="label"><span>Банные печи</span></div>
                </a>
            </li>
            <li class="item">
                <a class="link" href="/joomla/index.php/kaminnye-topki">
                    <div class="thumb"></div>
                    <div class="label"><span>Каменные топки</span></div>
                </a>
            </li>
            <li class="item">
                <a class="link" href="/joomla/index.php/oblitsovka-kaminov">
                    <div class="thumb"></div>
                    <div class="label"><span>Облицовка каминов</span></div>
                </a>
            </li>
            <li class="item">
                <a class="link" href="/joomla/index.php/otopitelnye-kotly">
                    <div class="thumb"></div>
                    <div class="label"><span>Отопительные котлы</span></div>
                </a>
            </li>
            <li class="item">
                <a class="link" href="/joomla/index.php/dymokhody">
                    <div class="thumb"></div>
                    <div class="label"><span>Дымоходы</span></div>
                </a>
            </li>
        </ul>-->
        <?php if ($this->countModules('header-menu-bottom')) : ?>
            <jdoc:include type="modules" name="header-menu-bottom" style="well" />
        <?php endif; ?>
    </div>
</div>
<div class="social">
    <div class="tw item"><a class="link" href="#"></a></div>
    <div class="vk item"><a class="link" href="#"></a></div>
    <div class="fb item"><a class="link" href="#"></a></div>
</div>


