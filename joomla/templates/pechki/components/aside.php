    <!--
    <div class="block">
        <header class="header">Клиентам</header>
        <ul class="menu">
            <li class="item stock"><a class="link" href="#">Акции</a></li>
            <li class="item discount"><a class="link" href="#">Скидки<i class="icon"></i></a></li>
            <li class="item"><a class="link" href="#">Гарантия</a></li>
            <li class="item"><a class="link" href="#">Вопрос-ответ</a></li>
            <li class="item"><a class="link" href="#">Примеры работ</a></li>
        </ul>
    </div>
    <div class="block minor">
        <header class="header">Информация</header>
        <ul class="menu">
            <li class="item"><a class="link" href="#">Фото</a></li>
            <li class="item"><a class="link" href="#">Видео</a></li>
            <li class="item"><a class="link" href="#">Статьи</a></li>
            <li class="item"><a class="link" href="#">Рекомендации</a></li>
        </ul>
    </div>
    -->
    <?php if ($this->countModules('position-7')) : ?>
        <div class="block">
            <jdoc:include type="modules" name="aside-first" style="well" />
        </div>
        <div class="block minor">
            <jdoc:include type="modules" name="aside-second" style="well" />
        </div>
    <?php endif; ?>

