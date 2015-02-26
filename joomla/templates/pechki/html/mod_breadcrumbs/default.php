<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

?>

<ul class="breadcrumb<?php echo $moduleclass_sfx; ?>">
	<?php


	// Get rid of duplicated entries on trail including home page when using multilanguage
	for ($i = 0; $i < $count; $i++)
	{
		if ($i == 1 && !empty($list[$i]->link) && !empty($list[$i - 1]->link) && $list[$i]->link == $list[$i - 1]->link)
		{
			unset($list[$i]);
		}
	}

	// Find last and penultimate items in breadcrumbs list
	end($list);
	$last_item_key = key($list);
	prev($list);
	$penult_item_key = key($list);

	// Make a link if not the last item in the breadcrumbs
	$show_last = $params->get('showLast', 1);

	// Generate the trail
	foreach ($list as $key => $item) :
	if ($key != $last_item_key)
	{
		// Render all but last item - along with separator
		echo '<li class="item">';
		if (!empty($item->link))
		{
			echo '<a href="' . $item->link . '" class="link"><span class="label">' . $item->name . '</span></a>';
		}
		else
		{
			echo '<span class="label">' . $item->name . '</span>';
		}

		echo '</li>';
	}
	elseif ($show_last)
	{
		// Render last item if reqd.
		echo '<li class="item active">';
		echo '<span class="label">' . $item->name . '</span>';
		echo '</li>';
	}
	endforeach; ?>
</ul>
