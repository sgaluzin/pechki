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
    </div>
    <div class="middle">
        <p class="slogan">Подарите сердце вашему дому</p>
        <div class="contacts">
            <p>Время работы:
            <p>с 10:00 до 22:00
            <p class="email">E-mail: info@pechispb.ru
        </div>
        <div class="cart-wrap">
            <jdoc:include type="modules" name="cart-top" style="well" />
        </div>
    </div>
</div>
<div class="middle-row">
    <div class="container">
        <a class="link" href="<?=JURI::base()?>">
            <div class="logo-name">
                <p class="title">Печи петербурга
                <p class="sub-title">продажа и установка
            </div>
        </a>
        <div class="call-back">
            <a href="#" class="callme_viewform pure-button button-primary button-xlarge">Закажите обратный звонок</a>
            <span class="text">Или звоните <span class="minor">прямо сейчас</span></span>
            <a class="phone link" href="tel:+78129183974">+7 (812) 918 39 74</a>
            <span class="text minor">или</span>
            <a class="phone link minor" href="tel:+78129186974">+7 (812) 918 69 74</a>
        </div>
    </div>
</div>
<div class="bottom">
    <div class="container">
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


