<?php
defined('_JEXEC') or die;

?>

<jdoc:include type="modules" name="filter" style="no" />
<?php if ($this->countModules('aside-first')) : ?>
    <div class="block">
    <jdoc:include type="modules" name="aside-first" style="well" />
</div>
<?php endif; ?>
<?php if ($this->countModules('aside-second')) : ?>
    <div class="block minor">
        <jdoc:include type="modules" name="aside-second" style="well" />
    </div>
<?php endif; ?>

