<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of product
 *
 * @author Afzaal
 */
class product {

  // ------------------------------------------------------------------------
  /**
   * create a funnel - it will create a new funnel
   *
   * @access	public
   * @return	newly inserted id of the funnel
   */
  function createProduct($data) {
    global $db;

    $db->insert(PRODUCT_TABLE, $data);
    $newID = $db->insertid();
    return $newID;
  }

  // ------------------------------------------------------------------------
  /**
   * check Edit Product with same name -
   *
   * @access	public
   * @return	bool
   */
  function checkProductWithSameName($pname) {
    global $db;

    $tot_row = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE product_name = '" . $pname . "' AND uid = '" . $_SESSION['user_id'] . "' LIMIT 1");
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return false;
  }

  // ------------------------------------------------------------------------
  /**
   * check Edit Product with same name with id -
   *
   * @access	public
   * @return	bool
   */
  function checkProductWithSameNameWithId($pname, $pid) {
    global $db;

    $tot_row = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE product_name = '" . $pname . "' AND uid = '" . $_SESSION['user_id'] . "' AND id <> '" . $pid . "' LIMIT 1");
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return false;
  }

  // ------------------------------------------------------------------------
  /**
   * product Add Edit -
   *
   * @access	public
   * @return	bool
   */
  function productAddEdit() {
    global $db, $genObj;

    $msg = '';

    if (form::validatePostValue('product_name') && form::getPostValue('product_name') && form::validatePostValue('product_url') && form::getPostValue('product_url') && form::validatePostValue('website_title') && form::getPostValue('website_title')) {
      $option['product_url'] = form::getPostValue('product_url');
      $option['product_name'] = form::getPostValue('product_name');
      $option['uid'] = $_SESSION['user_id'];

      $option['website_title'] = form::getPostValue('website_title');
      //$option['support_email'] = form::getPostValue('support_email');
      //$option['support_link'] = form::getPostValue('support_link');
      //$option['pen_name'] = form::getPostValue('pen_name');
      //$option['copyright_info'] = form::getPostValue('copyright_info');
      //$option['payment_processor'] = form::getPostValue('payment_processor');
      //if ($option['payment_processor'] == 'paypal')
        //$option['payment_id'] = form::getPostValue('payment_id1');
      //else
        //$option['payment_id'] = form::getPostValue('payment_id2');
      //$option['price'] = form::getPostValue('price');
      //$option['affiliate_share'] = form::getPostValue('affiliate_share');

      if (form::validatePostValue('pid') && form::getPostValue('pid')) {
        $pid = form::getPostValue('pid');
        $isExist = $this->getProductById($pid);
        if (!$isExist)
          $genObj->redirectMe('products.php');
        else {
          if ($option['product_url'] != $isExist['product_url']) {
            if (is_dir($genObj->getUserRootProductURL($option['product_url'])))
              return 'This product URL already exist';
          }
        }

        $isPrExist = $this->checkProductWithSameNameWithId($option['product_name'], $pid);
        if ($isPrExist)
          return 'Product with same name already exist';

        $where = "id = '" . form::getPostValue('pid') . "' AND uid = '" . $_SESSION['user_id'] . "'";
        $db->update(PRODUCT_TABLE, $option, $where);
        if ($genObj->checkProductDbExists(form::getPostValue('pid', 0))) {
          $genObj->saveProductSettings(form::getPostValue('pid', 0));
          $genObj->createConfig($pid);
        }
      } else {
        if (is_dir($genObj->getUserRootProductURL($option['product_url'])))
          return 'This product URL already exist';

        $isPrExist = $this->checkProductWithSameName($option['product_name']);
        if ($isPrExist)
          return 'Product with same name already exist';

        $db->insert(PRODUCT_TABLE, $option);
        $pid = $db->insertid();
      }

      if (isset($_POST['save_quit']))
        $genObj->redirectMe('products.php');
      else if (isset($_POST['next_step']))
        $genObj->redirectMe('wizard2.php?pid=' . $pid);
    }

    return $msg;
  }

  // ------------------------------------------------------------------------
  /**
   * product Add Edit -
   *
   * @access	public
   * @return	bool
   */
  function saveProductWizard2() {
    global $db, $genObj, $pageObj;

    $msg = '';

    if (form::validatePostValue('pid') && form::getPostValue('pid') && form::validatePostValue('product_template') && form::getPostValue('product_template') && form::validatePostValue('next_step') && form::getPostValue('next_step')) {
      $next_step = form::getPostValue('next_step');
      $pid = form::getPostValue('pid');

      $isPrExist = $this->getProductByIdAndUser($pid);
      if (!$isPrExist)
        $genObj->redirectMe('products.php');

      $option['product_template'] = form::getPostValue('product_template');
      $where = "id = '" . $pid . "' AND uid = '" . $_SESSION['user_id'] . "'";
      $db->update(PRODUCT_TABLE, $option, $where);

      /* copy them to db only if they not exist */
      $is_page_exist = $pageObj->getPagesByProductId($pid, 1);
      if (!$is_page_exist) {
        $genObj->saveAllFilesToDB($pid); /* this will copy all templates to db */
        $msg = $genObj->createProductDB($genObj->getCustomDbName($pid, 0)); /* this will create database for user. */
        $genObj->saveProductSettings($pid); /* saves product price and payment details in Product Settings table */
      }
      /* copy all db data to the prodult url directory */
      $genObj->generateFiles($pid);
      $genObj->createConfig($pid);

      if ($next_step == 'save_quit')
        $genObj->redirectMe('products.php');
      else if ($next_step == 'next_step')
        $genObj->redirectMe('wizard3.php?pid=' . $pid);
    }

    return $msg;
  }

  // ------------------------------------------------------------------------
  /**
   * get Funnel By Name --
   *
   * @access	public
   * @return	return funnel array or false
   */
  public function getProductByName($name) {
    global $db;

    $tot_row = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE product_name = '" . $name . "'");
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return false;
  }

  // ------------------------------------------------------------------------
  /**
   * get Funnel By Name --
   *
   * @access	public
   * @return	return funnel array or false
   */
  public function getProductById($id) {
    global $db;

    $tot_row = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE id = " . $id);
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return false;
  }

  // ------------------------------------------------------------------------
  /**
   * get Funnel By Name --
   *
   * @access	public
   * @return	return funnel array or false
   */
  public function getProductByIdAndUser($id) {
    global $db;

    $tot_row = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE id = " . $id . " AND uid = '" . $_SESSION['user_id'] . "'");
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return false;
  }

  // ------------------------------------------------------------------------
  /**
   * get Products List -
   *
   * @access	public
   * @return	no error on success or error message
   */
  function getProducts($uid = 0, $orderby = 'id', $order = 'ASC') {
    if ($uid != 0) {
      global $db;

      list($page, $limit, $offset) = $this->getPaginationData();

      $results = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE uid = " . $uid . " ORDER BY " . $orderby . " " . $order . " LIMIT " . $offset . "," . $limit);

      return $results;
    }
  }

  // ------------------------------------------------------------------------
  /**
   * get pagination data -
   *
   * @access	public
   * @return	array of pagination basic fields
   */
  function getPaginationData() {
    $page = 1;
    if (form::validateGetValue('page'))
      $page = form::getGetValue('page', 0);

    $offset = ($page - 1) * PER_PAGE;
    return array($page, PER_PAGE, $offset);
  }

  // ------------------------------------------------------------------------
  /**
   * get Product Pagi -
   *
   * @access	public
   * @return	html
   */
  function getProductPagi($uid = 0) {
    $total = $this->getTotalProducts($uid);

    return $this->getPaginationHtml($total, 'products');
  }

  // ------------------------------------------------------------------------
  /**
   * get Total Admin Users -
   *
   * @access	public
   * @return total records
   */
  function getTotalProducts($uid = 0) {
    global $db;

    $tot_row = $db->select("SELECT count(id) as cnt FROM " . PRODUCT_TABLE . " WHERE uid = " . $uid);
    if (isset($tot_row[0]['cnt']) && $tot_row[0]['cnt'] != '')
      return $tot_row[0]['cnt'];

    return 0;
  }

  // ------------------------------------------------------------------------
  /**
   * get Pagination Html -
   *
   * @access	public
   * @return	html
   */
  function getPaginationHtml($total, $page_name) {
    list($page, $limit, $offset) = $this->getPaginationData();

    return getPagination($page, $total, $limit, $page_name) . '<div style="clear:both"></div>';
  }

  // ------------------------------------------------------------------------
  /**
   * delete Product -
   *
   * @access	public
   * @return	no error on success or error message
   */
  function deleteProduct() {
    global $db;

    if (form::validateGetValue('delete_id')) {
      $delete_id = form::getGetValue('delete_id', 0);
      if ($delete_id) {
        $query = "DELETE FROM " . PRODUCT_TABLE . " WHERE id = " . $delete_id;
        return $db->misc($query);
      }
    }
  }

  /**
   * get statistics of Latest Product for Dashboard
   * 
   * @global DB_Object $db
   * @param type $uid
   * @return type mixed array
   */
  function getLatestProductStats($uid, $pid = 0) {
    global $db, $genObj;
    
    $id = $db->select("SELECT id, payment_processor FROM " . PRODUCT_TABLE . " WHERE uid = " . $uid . (($pid != 0 ) ? " AND id = ".$pid : "") ." ORDER BY created_at DESC LIMIT 1");
    
    if ($db->select_db($genObj->getCustomDbName($id[0]['id'], 0))) {

      $members_count = $db->select("SELECT count(id) as cnt, member_type FROM " . MEMBER_TABLE . " GROUP BY member_type");
      $members = 0;
      $affiliates = 0;
      if ($members_count) {
        foreach ($members_count as $mem_cnt) {
          if ($mem_cnt['member_type'] == 'Affiliate') {
            $affiliates = $mem_cnt['cnt'];
          } else {
            $members += $mem_cnt['cnt'];
          }
        }
      }
      $total_sales = $db->select("SELECT count(id) as cnt FROM " . TRANSACTION_TABLE . " WHERE ipn_status = 'Confirmed' AND status = 'Paid'");
      $sales = $total_sales[0]['cnt'];

      $visitors = $db->select("SELECT count(id) as cnt, ip FROM " . ACTION_TABLE . " GROUP BY ip");
      $total_visitors = 0;
      $unique_visitors = 0;
      if ($visitors) {
        foreach ($visitors as $visitor) {
          $total_visitors += $visitor['cnt'];
          $unique_visitors++;
        }
      }
      $aff_sales = $db->select("SELECT count(id) as cnt FROM " . MEMBER_TABLE . " WHERE parent_id != 0 AND member_type != 'Affiliate'");
      $top_affs = $db->select("SELECT count(id) as cnt, parent_id FROM " . MEMBER_TABLE . " WHERE parent_id != 0 AND member_type != 'Affiliate' GROUP BY parent_id ORDER BY count(id) DESC LIMIT 1");
      $top_affiliate = '--';
      if ($top_affs) {
        $top_aff = $db->select("SELECT user_name FROM " . MEMBER_TABLE . " WHERE id =" . $top_affs[0]['parent_id']);
        if ($top_aff) {
          $top_affiliate = $top_aff[0]['user_name'];
        }
      }

      //$monthly_charts = $db->select("SELECT count(".MEMBER_TABLE.".id) as members, count(".TRANSACTION_TABLE.".id) as sales, MONTH(".MEMBER_TABLE.".registered_date) as month FROM ".MEMBER_TABLE.", ".TRANSACTION_TABLE." GROUP BY MONTH(".MEMBER_TABLE.".registered_date), MONTH(".TRANSACTION_TABLE.".created_at) ORDER BY MONTH(".MEMBER_TABLE.".registered_date) DESC LIMIT 12");
      $monthly_members = $db->select("SELECT count(id) as members, MONTH(registered_date)as month, YEAR(registered_date) as year FROM " . MEMBER_TABLE . " GROUP BY MONTH(registered_date), YEAR(registered_date) ORDER BY MONTH(registered_date) DESC LIMIT 12");
      $monthly_sales = $db->select("SELECT count(id) as sales, MONTH(created_at) as month, YEAR(created_at) as year FROM " . TRANSACTION_TABLE . " GROUP BY MONTH(created_at) ORDER BY MONTH(created_at) DESC LIMIT 12");
      $i = $j = 0;
      $monthly_charts = array();
      if (isset($monthly_members[0]['members'])) {
        foreach ($monthly_members as $member) {
          if (isset($monthly_sales[$i]['month']) && $monthly_sales[$i]['month'] == $member['month'] && $monthly_sales[$i]['year'] == $member['year']) {
            $i++;
            $monthly_charts[$j]['month'] = $member['month'];
            $monthly_charts[$j]['sales'] = $monthly_sales[$i]['sales'];
            $monthly_charts[$j]['members'] = $member['members'];
          } else {
            $monthly_charts[$j]['month'] = $member['month'];
            $monthly_charts[$j]['members'] = $member['members'];
            $monthly_charts[$j]['sales'] = 0;
          }
          $j++;
        }
      }
      $db->select_db(DB_DATBASE_NAME);
      $conv_rate = 0;
      if ($unique_visitors > 0) {
        $conv_rate = number_format(($sales / $unique_visitors) * 100);
      }
      return array(
          'payment_processor' => $id[0]['payment_processor'],
          'total_members' => $members,
          'total_affiliates' => $affiliates,
          'total_visitors' => $total_visitors,
          'unique_visitors' => $unique_visitors,
          'total_sales' => $sales,
          'affiliate_sales' => $aff_sales[0]['cnt'],
          'top_affiliate' => $top_affiliate,
          'monthly_chart' => $monthly_charts,
          'conversion_rate' => $conv_rate);
    }
    return array(
        'payment_processor' => '',
        'total_members' => 0,
        'total_affiliates' => 0,
        'total_visitors' => 0,
        'unique_visitors' => 0,
        'total_sales' => 0,
        'affiliate_sales' => 0,
        'top_affiliate' => 0,
        'monthly_chart' => 0,
        'conversion_rate' => 0);
  }

  /**
   * get 5 latest products names
   * 
   * @global DB_Object $db
   * @param type $uid
   * @return type 
   */
  function getLatestProducts($uid, $limit = 5) {
    global $db;

    $products = $db->select("SELECT id, product_name FROM " . PRODUCT_TABLE . " WHERE uid = " . $uid . " ORDER BY created_at DESC LIMIT " . $limit);

    return $products;
  }

  function getTotalProductsStats() {
    $total_visitors = 0;
    $total_unique_visitors = 0;
    $total_sales = 0;
    $total_affiliate_sales = 0;

    global $db, $genObj;
    $products = $db->select("SELECT * FROM " . PRODUCT_TABLE . " WHERE uid = " . $_SESSION['user_id']);
    if(is_array($products)) {
    foreach ($products as $product) {
      if ($db->select_db($genObj->getCustomDbName($product['id'], 0))) {

        $pr_sales = $db->select("SELECT count(id) as cnt FROM " . TRANSACTION_TABLE . " WHERE ipn_status = 'Confirmed' AND status = 'Paid'");
        $total_sales += $pr_sales[0]['cnt'];

        $pr_aff_sales = $db->select("SELECT count(id) as cnt FROM " . TRANSACTION_TABLE . " WHERE ipn_status = 'Confirmed' AND status = 'Paid' AND affid != 0");
        $total_affiliate_sales += $total_sales[0]['cnt'];

        $visitors = $db->select("SELECT count(id) as cnt, ip FROM " . ACTION_TABLE . " GROUP BY ip");
        $total_pr_visitors = 0;
        $unique_pr_visitors = 0;
        if ($visitors) {
          foreach ($visitors as $visitor) {
            $total_pr_visitors += $visitor['cnt'];
            $unique_pr_visitors++;
          }
        }
        $total_visitors += $total_pr_visitors;
        $total_unique_visitors += $unique_pr_visitors;
      }
    }
    
    $db->select_db(DB_DATBASE_NAME);
    return array(
        'total_visitors' => $total_visitors,
        'total_unique_visitors' => $total_unique_visitors,
        'total_sales' => $total_sales,
        'total_affiliate_sales' => $total_affiliate_sales
    );
    } else {
      return array(
        'total_visitors' => 0,
        'total_unique_visitors' => 0,
        'total_sales' => 0,
        'total_affiliate_sales' => 0
          );
    }
  }
  function getProductUrl($pid)
  {
    global $db;
    
    $results = $db->select("SELECT * FROM ".PRODUCT_TABLE." WHERE id = ".$pid." LIMIT 1");
    if(isset($results[0]['product_url'])) {
      return $results[0]['product_url'];
    } else {
      return '';
    }
  }
  function getAllProducts()
  {
    global $db;
    $results = $db->select("SELECT count(p.id) as count FROM ".PRODUCT_TABLE." p, product_paypal q WHERE p.id = q.pid AND (q.status = 'new' OR q.status = '') ");
    if(isset($results[0]['count'])) {
      return $results[0]['count'];
    } else {
      return '';
    }
  }
  function paypalfixing($pid = 0, $nop = 0) {
    global $db, $genObj;
    if ($pid != 0 && $nop != 0) {
      $result = $db->select("SELECT p.id as id, p.uid as uid, u.user_name as user_name, p.product_url as product_url FROM " . PRODUCT_TABLE . " p, " . USER_TABLE . " u WHERE p.id > " . ($pid - 1) . " AND u.id = p.uid ORDER BY p.id ASC LIMIT " . $nop);
      foreach ($result as $key => $res) {
        //echo 'ID => '.$res['id'].', USERNMAE => '.$res['user_name'].', URL => '.$res['product_url'].'<br />';
        $root = $genObj->getRootDirectory();
        //if ($res['user_name'] == 'rmatakajr') {
          $user_dir = $root . '/' . $res['user_name'];
          $product_dir = $user_dir . '/' . $res['product_url'];
          echo $key.' - '.$res['id'].' - <a href="'.SECURE.DOMAIN.'/'.$res['user_name'].'/'.$res['product_url'] . '/">'.$res['product_url'].'</a><br />';
          
          if ($this->rrmdir($product_dir . '/samples')) {
            $thisdir = $genObj->getProjectDirectory();
            $src_dir = $thisdir . "/default/replace/";
            $genObj->recurse_copy($src_dir, $product_dir);
            $this->updateProduct($res['id'], 'Success');
          } else {
            $this->updateProduct($res['id'], 'Failure');
          }
        //}
      }
    }
  }

  function updateProduct($pid = 0, $status = 'Failure') {
    if ($pid != 0) {
      global $db, $genObj;

      $option['status'] = $status;
      $where = 'pid = ' . $pid;
      $result = $db->update('product_paypal', $option, $where);
    }
  }
    //-------------------------------------------------------------------------
  /**
   *  removes a directory and its contents
   * @param type $dir 
   */
  function rrmdir($dir) {
    if (is_dir($dir)) {
      foreach (glob($dir . '/*') as $file) {
        if (is_dir($file)) {
          
          $this->rrmdir($file); 
        } else {
          
          unlink($file);
        }
      }
      rmdir($dir);
      return true;
    }
    return false;
  }
}

?>
