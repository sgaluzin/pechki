<script type = "text/javascript">
function isEmptyValue(value){
    var pattern = /\S/;
    return ret = (pattern.test(value)) ? (true) : (false);
}
</script>
<form name = "searchForm" method = "post" action="<?php print SEFLink("index.php?option=com_jshopping&controller=search&task=result", 1);?>" onsubmit = "return isEmptyValue(jQuery('#jshop_search').val())">
    <input type="hidden" name="setsearchdata" value="1">
    <input type = "hidden" name = "category_id" value = "<?php print $category_id?>" />
    <input type = "hidden" name = "search_type" value = "<?php print $search_type;?>" />
    <div class="search-wrap">
        <input type = "search" class = "inputbox" name = "search" id = "jshop_search" value = "<?php print $search?>" />
        <button class="btn" type="submit"><i class="icon"></i></button>
    </div>
</form>