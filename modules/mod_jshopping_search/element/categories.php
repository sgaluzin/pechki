<?php
class JFormFieldCategories extends JFormField {

  public $type = 'Categories';
  
  protected function getInput(){
        require_once (JPATH_SITE.'/modules/mod_jshopping_search/helper.php'); 
        $tmp = new stdClass();
        $tmp->category_id = "";
        $tmp->name = JText::_('JALL');  
        $categories_1  = array($tmp);
        $categories_select =array_merge($categories_1 , buildTreeCategory(0)); 
        $ctrl  =  $this->name ;   
        $value        = empty($this->value) ? '' : $this->value;  

        return JHTML::_('select.genericlist', $categories_select,$ctrl,'class="inputbox" id = "category_ordering"','category_id','name', $value );
  }
}
?>