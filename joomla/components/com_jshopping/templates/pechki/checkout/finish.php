<?php 
/**
* @version      4.3.1 13.08.2013
* @author       MAXXmarketing GmbH
* @package      Jshopping
* @copyright    Copyright (C) 2010 webdesigner-profi.de. All rights reserved.
* @license      GNU/GPL
*/
defined('_JEXEC') or die('Restricted access');
?>
<?php if (!empty($this->text)){?>
<?php echo $this->text;?>
<?php }else{?>

<div id="system-message-container">
    <div id="system-message">
        <div class="alert alert-message">
            <h4 class="alert-heading">Сообщение</h4>
            <div>
                <p><?php print _JSHOP_THANK_YOU_ORDER?></p>
            </div>
        </div>
    </div>
</div>

<?php }?>