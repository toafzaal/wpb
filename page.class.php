<?php
/**
 * Description of page
 *
 * @author Afzaal
 */
class page {
  
  // ------------------------------------------------------------------------
  /**
   * 
   * get Pages By Product Id -
   * 
   * @global type $db
   * @param type $pid
   * @return type 
  */
  public function getPagesByProductId($pid = 0, $limit=0)
  {
    if($pid != 0)
    {
  		global $db;
		
      $results = $db->select("SELECT * FROM ".PAGE_TABLE." WHERE pid = ".$pid.($limit > 0 ? " LIMIT ".$limit : ""));
				
      return $results;
    }
  }
  // ------------------------------------------------------------------------
  /**
   * 
   * get Pages By ProductId And Type -
   * 
   * @global type $db
   * @param type $pid
   * @return type 
  */
  public function getPagesByProductIdAndType($pid = 0, $page_type = 'public', $limit=0)
  {
    if($pid != 0)
    {
  		global $db;
		
      $results = $db->select("SELECT * FROM ".PAGE_TABLE." WHERE pid = ".$pid." AND page_type = '".$page_type."' ".($limit > 0 ? " LIMIT ".$limit : ""));
				
      return $results;
    }
  }
  /**
   *
   * @global type $db
   * @param type $id
   * @return type 
   */
  public function deletePageById($id) {
    if($id != 0)
    {
  		global $db, $genObj, $productObj;
      
      $record = $this->getPageById($id);
      $prod = $productObj->getProductById($record[0]['pid']);
      $path = '';
      
      if(DOMAIN == 'localhost')
        $path = $genObj->getUserRootProductURL($prod['product_url']).'/'.$record[0]['page_name'].'.php';
      else
        $path = $genObj->getUserProductURL().$prod['product_url'].'/'.$record[0]['page_name'].'.php';
      if(unlink($path)) {
        $results = $db->delete(PAGE_TABLE, "id = ".$id, 1);
        if($results)	
          return "Page is deleted successfully.";
      } else {
        return "Page could not be deleted.";
      }
    }
  }
  public function getPageById($id = 0) {
    if($id != 0)
    {
  		global $db;
		
      $results = $db->select("SELECT * FROM ".PAGE_TABLE." WHERE id = ".$id);
				
      return $results;
    }
  }
  // ------------------------------------------------------------------------
	/**
	 * addPage -
	 *
	 * @access	public
	 * @return	bool
	*/	
	
	function addPage($record, $fn_php, $page_name, $page_type, $page_category = 'default')
	{
		global $db;
    
    $page['pid'] = $record['id'];
    $page['page_name'] = $page_name;
    $page['page_html'] = $fn_php;
    $page['page_type'] = $page_type;
    $page['page_category'] = $page_category;
    $db->insert(PAGE_TABLE, $page);
    $pageid = $db->insertid();
    return $pageid;
	}
  /**
   *
   * @global type $db
   * @param type $id
   * @return type 
   */
  public function isPageExist($id) {
    if($id != 0)
    {
  		global $db;
		
      $results = $db->select("SELECT count(id) FROM ".PAGE_TABLE." WHERE id = ".$id);
				
      return $results;
    }
  }
  /**
   *
   * @global type $db 
   */
  public function savePage() {
    global $db;
    $page_id = 0;
      $record['id'] = form::getPostValue('pid');
      $fn_php = form::getPostValue('html_data', 0);
      $fn_php = str_replace('"default/templates/Pro-CashCow/images', '"images', $fn_php);
      $page_name = form::getPostValue('pagename');
      $page_type = form::getPostValue('pagetype');
      $page_category = form::getPostValue('page_category');
    if($this->isPageExist(form::getPostValue('page_id')) > 0) {
      $option['page_name'] = $page_name;
      //$option['page_type'] = $page_type;
      $option['page_html'] = $fn_php;
      $option['page_category'] = $page_category;
      $page_id = form::getPostValue('page_id');
      $where = "id = '".form::getPostValue('page_id')."' AND pid = '".form::getPostValue('pid')."'";
      $db->update(PAGE_TABLE, $option, $where);
    } else {
      $page_id = $this->addPage($record, $fn_php, $page_name, $page_type, $page_category);
    }
    $genObj = new general();
    $genObj->generatePageFileByPageId($page_id);
  }
}

?>
