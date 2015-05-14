<div class="manufactuter_list">
<?php
  foreach($list as $curr){
      $class = "jshop_menu_level_0";
	  if ($curr->manufacturer_id == $manufacturer_id) $class = $class."_a";      
      ?>
      <div class = "<?php print $class?>">
            <a href = "<?php print $curr->link?>"><?php print $curr->name?>
                <?php if ($show_image && $curr->manufacturer_logo){?>
                    <img align = "absmiddle" src = "<?php print $jshopConfig->image_manufs_live_path."/".$curr->manufacturer_logo?>" alt = "<?php print $curr->name?>" />
                <?php } ?>
            </a>
      </div>
<?php } ?>
</div>