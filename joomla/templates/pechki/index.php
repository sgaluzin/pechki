<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.pechki 
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$app             = JFactory::getApplication();
$doc             = JFactory::getDocument();
$user            = JFactory::getUser();
$this->language  = $doc->language;
$this->direction = $doc->direction;
$components_url = 'templates/'.$this->template.'/components/';

// Getting params from template
$params = $app->getTemplate(true)->params;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->get('sitename');

if($task == "edit" || $layout == "form" )
{
	$fullWidth = 1;
}
else
{
	$fullWidth = 0;
}

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');
$doc->addScript('templates/' . $this->template . '/js/template.js');

//$doc->addScript('templates/' . $this->template . '/js/less.js');


// Add Stylesheets
//$doc->addStyleSheet('templates/' . $this->template . '/css/template.css');
//$doc->addStyleSheet('templates/' . $this->template . '/css/main.css');

// Load optional RTL Bootstrap CSS
JHtml::_('bootstrap.loadCss', false, $this->direction);

// Adjusting content width
if ($this->countModules('position-7') && $this->countModules('position-8'))
{
	$span = "span6";
}
elseif ($this->countModules('position-7') && !$this->countModules('position-8'))
{
	$span = "span9";
}
elseif (!$this->countModules('position-7') && $this->countModules('position-8'))
{
	$span = "span9";
}
else
{
	$span = "span12";
}

// Logo file or site title param
if ($this->params->get('logoFile'))
{
	$logo = '<img src="' . JUri::root() . $this->params->get('logoFile') . '" alt="' . $sitename . '" />';
}
elseif ($this->params->get('sitetitle'))
{
	$logo = '<span class="site-title" title="' . $sitename . '">' . htmlspecialchars($this->params->get('sitetitle')) . '</span>';
}
else
{
	$logo = '<span class="site-title" title="' . $sitename . '">' . $sitename . '</span>';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet/less" href="/joomla/templates/pechki/css/main.less" type="text/css">
	<jdoc:include type="head" />
	<?php // Use of Google Font ?>
	<?php if ($this->params->get('googleFont')) : ?>
		<link href='//fonts.googleapis.com/css?family=<?php echo $this->params->get('googleFontName'); ?>' rel='stylesheet' type='text/css' />
	<?php endif; ?>
	<?php // Template color ?>
	<?php if ($this->params->get('templateColor')) : ?>
	<?php endif; ?>
	<!--[if lt IE 9]>
		<script src="<?php echo $this->baseurl; ?>/media/jui/js/html5.js"></script>
	<![endif]-->

</head>

<body class="site <?php echo $option
	. ' view-' . $view
	. ($layout ? ' layout-' . $layout : ' no-layout')
	. ($task ? ' task-' . $task : ' no-task')
	. ($itemid ? ' itemid-' . $itemid : '')
	. ($params->get('fluidContainer') ? ' fluid' : '');
?>">
	<!-- Body -->
	<div class="wrap-main">
        <?php ?>
        <header id="header" class="header-main">
            <?php include_once($components_url.'header.php'); ?>
        </header>
        <main class="main container">
            <div id="aside" class="aside">
                <?php include_once($components_url.'aside.php'); ?>
            </div>
            <div class="content">
                <div class="container<?php echo ($params->get('fluidContainer') ? '-fluid' : ''); ?>">
                    <header class="header" role="banner">
                        <jdoc:include type="modules" name="bread-crumbs" style="no" />
                        <jdoc:include type="modules" name="filter" style="no" />
                        </div>
                    </header>
                    <jdoc:include type="modules" name="banner" style="xhtml" />
                        <!-- Begin Content -->
                        <jdoc:include type="modules" name="slider" style="no" />
                        <jdoc:include type="message" />
                        <jdoc:include type="component" />
                        <jdoc:include type="modules" name="after-component" style="well" />
                        <!-- End Content -->
                    </div> 
		    </div><!-- End 'content' -->
        </main>
	</div><!-- End 'wrap-main' -->
	<!-- Footer -->
	<footer class="footer-main" role="contentinfo">
        <?php include_once($components_url.'footer.php'); ?>
	</footer>
	<jdoc:include type="modules" name="debug" style="none" />

    <script>less = { env: 'development'};</script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/2.2.0/less.min.js"></script>
    <!--<script>less.watch();</script>-->
    <script type="text/javascript" charset="utf-8" src="/joomla/callme/js/callme.js"></script>
</body>
</html>
