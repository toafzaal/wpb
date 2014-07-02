<?php

/**
 * Description of general
 *
 * @author Afzaal
 */
class general {

// ------------------------------------------------------------------------
  /**
   * use to logotu user -
   *
   * @access	public
   * @return	NULL
   */
  function checkLogoutReq() {
    global $genObj;

    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'logout') {
      $_SESSION['user_id'] = '';
      $_SESSION['user_name'] = '';
      $this->redirectMe('login.php');
    }

    $page = $this->getCurPage();
    if((!in_array($page, array('login.php', 'forgot_password.php'))) && $this->isLogin() == 0) {
      echo '<script>location.href = "'.HOME_LINK.'/login.php";</script>';
      exit();
    }
  }

  // ------------------------------------------------------------------------
  /**
   * use to logotu user -
   *
   * @access	public
   * @return	NULL
   */
  public function getCurPage() {
    $page_uri = $_SERVER["REQUEST_URI"];
    $uri_arr = explode('/', $page_uri);
    $page_arr = explode('?', $uri_arr[count($uri_arr) - 1]);
    $page_name = explode('#', $page_arr[0]);
    if (isset($page_name[0]))
      return $page_name[0];
    else
      return 'index';
  }

  // ------------------------------------------------------------------------
  /**
   * use to logotu user -
   *
   * @access	public
   * @return	NULL
   */
  public function isLogin() {
    if (isset($_SESSION['user_id']) && trim($_SESSION['user_id']) && isset($_SESSION['user_name']) && trim($_SESSION['user_name']))
      return $_SESSION['user_id'];

    return 0;
  }

  // ------------------------------------------------------------------------
  /**
   * validate Admin User Login -
   *
   * @access	public
   * @return	NULL
   */
  function validateUserLogin() {

    $error = $user_name = $password = '';
    if (form::validatePostValue('username') && form::validatePostValue('password')) {
      $user_name = form::getPostValue('username');
      $password = form::getPostValue('password');

      if (trim($user_name) && trim($password)) {
        $isExist = $this->isUserExist($user_name, $password);

        if ($isExist && count($isExist) > 0) {
          $_SESSION['user_id'] = $isExist['id'];
          $_SESSION['user_name'] = $isExist['user_name'];
          $this->redirectMe('index.php');
        }
        else
          $error = 'Wrong user name or password';
      }
    }
    return $error;
  }

  // ------------------------------------------------------------------------
  /**
   * is Admin User Exist -
   *
   * @access	public
   * @return	no error on success or error message
   */
  function isUserExist($pname, $ppass) {
    global $db;

    $uname = $db->mySQLSafe($pname, '');
    $md5pass = md5($ppass);
    $tot_row = $db->select("SELECT * FROM " . USER_TABLE . " WHERE user_name ='" . $uname . "' AND (password = '" . $ppass . "' OR password = '" . $md5pass . "') Limit 1;");
    if (isset($tot_row[0]['id']) && $tot_row[0]['id'] != '')
      return $tot_row[0];

    return 0;
  }

  // ------------------------------------------------------------------------
  /**
   * getErrorHTML
   *
   * @access	public
   * @return	html or null
   */
  public function getErrorHTML($error) {
    if (trim($error)) {
      return '<div class="notif_area">
                <div class="error_message">' . $error . '<a onclick="hideNotification()" class="close" href="#">x</a></div>
              </div>';
    }
    return '';
  }

  //-------------------------------------------------------------------------
  /**
   * getSuccessHTML
   * 
   * @access public
   * @param string $success
   * @return html or null
   */
  public function getSuccessHTML($success) {
    if (trim($success)) {
      return '<div class="notif_area">
          <div class="welcome_message">' . $success . '<a href="#" class="close" onclick="hideNotification()">x</a></div>
        </div>';
    }
    return '';
  }

  // ------------------------------------------------------------------------
  /**
   * getErrorHTML
   *
   * @access	public
   * @return	html or null
   */
  public function getWizardNav($active=1) {
    $active_class = array('', '', '');
    $active_class[$active - 1] = 'class="active"';
    
    $products_count = 10; 
    if(! empty($_SESSION['oto3']) && $_SESSION['oto3'] == 1)
      $products_count = 15;

    return '<div id="steps_menu">
              <ul>
                <li ' . $active_class[0] . '>
                  <a href="wizard1.php">
                    <span class="num">1</span>
                    <h2>Name Product <span class="sub">setup site settings</span></h2>
                  </a>
                </li>
                <li ' . $active_class[1] . '>
                  <a href="javascript:;">
                    <span class="num">2</span>
                    <h2>Select Product <span class="sub">'.$products_count.' options to choose from</span></h2>
                  </a>
                </li>
                <li ' . $active_class[2] . '>
                  <a href="javascript:;">
                    <span class="num">3</span>
                    <h2>You\'re Done <span class="sub">It\'s as easy as 1,2,3</span></h2>
                  </a>
                </li>
              </ul>
              <div class="clear"></div>
            </div>';
  }

  //------------------------------------------------------------------------
  /*
   * This function is to save files data to database
   * 
   */
  public function saveAllFilesToDB($pid) {
    global $productObj, $pageObj, $template_pages, $common_public_pages, $common_member_pages;

    $record = $productObj->getProductById($pid);

    if ($record && count($record) > 0) {
      //get the current working directory
      $thisdir = $this->getProjectDirectory();

      $product_dir = $thisdir . "/default/templates/" . $record['product_template'] . "/";
      $common_dir = $thisdir . "/default/commons/";

      /* save templates to db */
      foreach ($template_pages as $tpage) {
        $local_file = $product_dir . $tpage . '.php';
        if (in_array($tpage, array('header', 'footer')))
          $fn_php = $this->getPageInnerContents($local_file, 1);
        else
          $fn_php = $this->getPageInnerContents($local_file);
        if ($fn_php)
          $pageObj->addPage($record, $fn_php, $tpage, 'public');
      }

      /* save common public pages to db */
      foreach ($common_public_pages as $ppage) {
        $local_file = $common_dir . $ppage . '.php';
        $fn_php = $this->getPageInnerContents($local_file);
        if ($fn_php)
          $pageObj->addPage($record, $fn_php, $ppage, 'public');
      }

      /* save common member pages to db */
      foreach ($common_member_pages as $mpage) {
        $local_file = $common_dir . $mpage . '.php';
        $fn_php = $this->getPageInnerContents($local_file);
        if ($fn_php)
          $pageObj->addPage($record, $fn_php, $mpage, 'member');
      }
    }
  }

  //------------------------------------------------------------------------
  /*
   * This function is to generate complete packege ready to upload to ftp
   * 
   */
  public function generateFiles($pid) {
    global $productObj, $pageObj, $template_pages;

    $record = $productObj->getProductById($pid);

    if ($record && count($record) > 0) {
      //get the current working directory
      $thisdir = $this->getProjectDirectory();

      $user_pages = $pageObj->getPagesByProductId($pid);
      if ($user_pages && count($user_pages) > 0) {
        $this->uploadPagesToDir($record, $user_pages);
      }
    }
  }

  //------------------------------------------------------------------------
  /*
   * This function is to get one page from DB and uplaod it to dir
   * 
   */
  public function generatePageFileByPageId($page_id) {
    global $productObj, $pageObj;

    $user_pages = $pageObj->getPageById($page_id);

    if ($user_pages && count($user_pages) > 0) {
      $record = $productObj->getProductById($user_pages[0]['pid']);
      if ($record && count($record) > 0) {
        $this->uploadPagesToDir($record, $user_pages, 0);
      }
    }
  }

  //------------------------------------------------------------------------
  /*
   * This function is to generate complete packege ready to upload to ftp
   * 
   */
  public function uploadPagesToDir($record, $user_pages, $upload_folders=1) {
    global $template_pages;


    if ($record && count($record) > 0 && $user_pages && count($user_pages) > 0) {
      //get the current working directory
      $thisdir = $this->getProjectDirectory();
      $dir = $this->getUserRootProductURL($record['product_url']);
      $product_dir = $thisdir . "/default/templates/" . $record['product_template'] . "/";
      $common_dir = $thisdir . "/default/commons/";

      $this->createDir($dir);
      $dir = $dir . "/";

      $templateHeadHtml = $this->getHeadSection(0, 0, $record['website_title']);
      $commonHeadHtml = $this->getHeadSection(0, 1, $record['website_title']);

      $header = '<?php include_once("header.php");?><?php echo $error_msg; ?><script>function hideNotification() {document.getElementById("notif_area").display= "none";}</script>';
      $footer = '<?php include_once("footer.php");?>';
      $footHtml = '</body></html>';

      unset($template_pages[1]);  /* unset for header */
      unset($template_pages[2]);  /* unset for footer */

      foreach ($user_pages as $page) {
        $extra_head = '';
        $extra_foot = '';

        $page_html = $this->replaceInlineParameters($record, $page);
        if (in_array($page['page_name'], $template_pages)) {
          $extra_head = '<div id="contentbg"><div id="content">';
          $extra_foot = '</div></div>';
          $final = ($page['page_name'] == 'index' ? $this->getHeadSection(1, 0, $record['website_title']) : $templateHeadHtml) . $header . $extra_head . $page_html . $extra_foot . $footer . $footHtml;
        } else if (in_array($page['page_name'], array('header', 'footer')))
          $final = $page_html;
        else
          $final = $commonHeadHtml . $header . $page_html . $footer . $footHtml;

        $this->uploadPageContents($final, $dir . $page['page_name'] . '.php');
      }

      if ($upload_folders == 1) {
        /* move templates folders data */
        $this->recurse_copy($product_dir . "images", $dir . "images");
        $this->recurse_copy($product_dir . "css", $dir . "css");
        $this->recurse_copy($product_dir . "downloads", $dir . "downloads");

        /* move commons folders data */
        $this->recurse_copy($common_dir . "images", $dir . "images");
        $this->recurse_copy($common_dir . "css", $dir . "css");
        $this->recurse_copy($common_dir . "js", $dir . "js");
        $this->recurse_copy($common_dir . "includes", $dir . "includes");
        $this->recurse_copy($common_dir . "lib", $dir . "lib");
        $this->recurse_copy($common_dir . "samples", $dir . "samples");
        $this->recurse_copy($common_dir . "clickbank", $dir . "clickbank");
      }
    }
  }

  // ------------------------------------------------------------------------
  /**
   * get Head Section
   * @access public
   * @return	
   */
  public function getHeadSection($is_index=0, $is_common_template=0, $product_title = '') {
    $head = '<?php require_once "includes/custom_functions.php"; ?>
             <!DOCTYPE HTML>
              <html>
                <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                  <title>' . $product_title . '</title>
                  ' . ($is_common_template == 1 ?
                    '<link rel="stylesheet" type="text/css" media="all" href="css/reset.css" />
                     <link rel="stylesheet" type="text/css" media="all" href="css/960.css" />
                     <link rel="stylesheet" type="text/css" media="all" href="css/validationEngine.jquery.css" />
                     <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
                     <script type="text/javascript" src="js/jquery.validationEngine-en.js" charset="utf-8"></script>
                     <script type="text/javascript" src="js/jquery.validationEngine.js"></script>
                     <script type="text/javascript" src="js/front.js"></script>
                  ' : '') . '
                  <link rel="stylesheet" type="text/css" media="all" href="css/style.css"  />
                  <link rel="stylesheet" type="text/css" media="all" href="css/main.css" />
                  <!--[if lt IE 9]>
                  <script src="js/html5shiv.js"></script>
                  <![endif]-->
                  ' . ($is_index ? '<script type="text/javascript">function MM_swapImgRestore(){var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;}function MM_preloadImages(){var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}}function MM_findObj(n, d){var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length){d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);if(!x && d.getElementById) x=d.getElementById(n); return x;}function MM_swapImage(){var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}}</script>' : '') . '
                </head>
                <body ' . ($is_index ? '' : '') . '>';

    return $head;
  }

  // ------------------------------------------------------------------------
  /**
   * getProjectDirectory
   * @access public
   * @return	
   */
  public function getProjectDirectory() {
    return getcwd();
  }

  // ------------------------------------------------------------------------
  /**
   * uploadPageContents
   * @access public
   * @return	
   */
  public function getRootDirectory() {
    return $_SERVER['DOCUMENT_ROOT'];
  }

  // ------------------------------------------------------------------------
  /**
   * get User Product URL
   *
   * @access	public
   * @return	redirect to a page
   *
   */
  function getUserProductURL() {
    return str_replace(DIRECTORY . '/', '', HOME_LINK) . $_SESSION['user_name'] . '/';
  }

  // ------------------------------------------------------------------------
  /**
   * get User Product URL
   *
   * @access	public
   * @return	redirect to a page
   *
   */
  function getUserRootProductURL($product_url) {
    $user_dir = $this->getRootDirectory() . '/' . $_SESSION['user_name'];
    $this->createDir($user_dir);

    /* if($_SERVER['SERVER_NAME'] == 'localhost') {
      return 'http://localhost/'.$_SESSION['user_name'].'/'.$product_url;
      } */
    return $user_dir . '/' . $product_url;
  }

  // ------------------------------------------------------------------------
  /**
   * uploadPageContents
   * @access public
   * @return	
   */
  public function uploadPageContents($contents, $new_file) {
    if ($contents) {
      $contents = html_entity_decode($contents, ENT_QUOTES);
      $contents = str_replace('"default/templates/Pro-CashCow/images', '"images', $contents);
      $ourFileHandle = fopen($new_file, 'w') or die("can't open file");
      fwrite($ourFileHandle, $contents);
      fclose($ourFileHandle);
    }
  }

  // ------------------------------------------------------------------------
  /**
   * get And Upload Page Contents
   * @access public
   * @return	
   */
  public function getPageInnerContents($local_file, $get_all=0) {
    if (file_exists($local_file)) {
      if ($get_all == 1) {
        return file_get_contents($local_file);
      } else {
        $html = file_get_html($local_file);
        foreach ($html->find('div[id=inner_contents]') as $data)
          return $data->innertext;
      }
    }
    return '';
  }

  // ------------------------------------------------------------------------
  /**
   * create createDir
   * @access public
   * @return	
   */
  public function createDir($dir) {
    if (!(is_dir($dir)))
      @mkdir($dir, 0777);
  }

  // ------------------------------------------------------------------------
  /**
   * To copy entire contents of a directory to another location
   *
   * @access	public
   * @return	
   * 
   */
  public function recurse_copy($src, $dst) {
    $dir = opendir($src);
    $this->createDir($dst);
    while (false !== ( $file = readdir($dir))) {
      if (( $file != '.' ) && ( $file != '..' )) {
        if (is_dir($src . '/' . $file)) {
          $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
        } else {
          copy($src . '/' . $file, $dst . '/' . $file);
        }
      }
    }
    closedir($dir);
  }

  // ------------------------------------------------------------------------
  /**
   * redirectMe - redirects to a page
   *
   * @access	public
   * @return	redirect to a page
   *
   */
  function redirectMe($page='') {
    echo '<script>location.href = "' . HOME_LINK . $page . '";</script>';
    exit();
  }

  /**   * ************************ IMAGE UPLOADING FUNCTIONS ******************** */

  /**
   * uploadImage - upload image and save to local
   *
   * @access	public
   * @return	image name
   */
  function uploadImage($upload_name, $folder_name) {
    $path_info = pathinfo($_FILES[$upload_name]['name']);
    $file_extension = strtolower($path_info["extension"]);

    $file_name = md5(time() . rand(1, 5000)) . '.' . $file_extension;

    $dir = UPLOAD_PATH . $folder_name;
    $this->createDir($dir);

    $path = $dir . '/' . $file_name;
    //Process the file
    if (@move_uploaded_file($_FILES[$upload_name]["tmp_name"], $path))
      return $file_name;

    return '';
  }

  function getUserUploads() {
    $dir = 'uploads/' . $_SESSION['user_name'] . '/';
    $return = array();
    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        $i = 0;
        while (($file = readdir($dh)) !== false) {
          if (is_file($dir . $file)) {
            $return[$i]['name'] = $file;
            $return[$i]['path'] = $dir . $file;
            $i++;
          }
        }
      }
    }
    return $return;
  }

  function getImages($dir) {
    $return = '';
    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
          if (is_file($dir . $file)) {
            $return .= '<div class="image_box"><input type="radio" name="img_selector" class="img_checkbox" val="' . HOME_LINK . $dir . $file . '" /><img src="' . HOME_LINK . $dir . $file . '"/></div>';
          }
        }
      }
    }
    return $return;
  }

  public function get_dir_contents($dir) {
    $ret_arr = array();
    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
          if (is_file($dir . $file)) {
            $ret_arr[] = $file;
          }
        }
      }
    }
    return $ret_arr;
  }

  public function createProductDB($dbname) {
    global $db;
    //Create database
    if (!$db->select_db($dbname)) {
      $ret = array();
      if(DOMAIN == 'localhost') {
        $sql = "CREATE DATABASE ".$dbname;
        $ret[] = $db->misc($sql);
      } else {
        $ret = $this->createDbWithXML($dbname, DB_USER, DB_PASSWORD);
      }
      
      if($ret[0]) {
        if(DOMAIN == 'localhost') {
          $db->select_db($dbname);
        } else {
          $db->select_db(CPANEL_USER.'_'.$dbname);
        }
        

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_members` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `email` varchar(50) NOT NULL,
                `user_name` varchar(50) NOT NULL,
                `password` varchar(50) NOT NULL,
                `member_type` varchar(50) NOT NULL,
                `parent_id` bigint(20) NOT NULL,
                `paypal_email` varchar(50) NULL,
                `registered_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_actions` (
                `id` int(20) NOT NULL AUTO_INCREMENT,
                `location` varchar(100) NULL,
                `ip` varchar(100) NULL,
                `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
              )ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_downloads` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) CHARACTER SET utf8 NOT NULL,
                `icon` varchar(20) CHARACTER SET utf8 NOT NULL,
                `link` varchar(500) CHARACTER SET utf8 NOT NULL,
                `access_level` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
              )ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_product_settings` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `pid` bigint(20) NOT NULL,
                `product_name` varchar(100) NOT NULL,
                `pen_name` varchar(255) NOT NULL,
                `support_email` varchar(50) NULL,
                `support_link` varchar(100) NULL,
                `price` float NOT NULL,
                `affiliate_share` int(11) NOT NULL,
                `paypal_email` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
                `clickbank_id` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
              )ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_transactions` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `tid` varchar(50) CHARACTER SET utf8 NOT NULL,
                  `email` varchar(100) CHARACTER SET utf8 NOT NULL,
                  `status` varchar(50) CHARACTER SET utf8 NOT NULL,
                  `ipn_status` varchar(50) CHARACTER SET utf8 NOT NULL,
                  `price` float NOT NULL,
                  `affid` bigint(20) NOT NULL,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00",
                  PRIMARY KEY (`id`)
              )ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $tables[] = 'CREATE TABLE IF NOT EXISTS `mc_support` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `user_id` bigint(20) NOT NULL,
                  `user_name` varchar(100) CHARACTER SET utf8 NOT NULL,
                  `email` varchar(50) CHARACTER SET utf8 NOT NULL,
                  `subject` varchar(200) CHARACTER SET utf8 NOT NULL,
                  `body` text CHARACTER SET utf8 NOT NULL,
                  `status` varchar(30) CHARACTER SET utf8 NOT NULL,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`)
                )ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        foreach ($tables as $table)
          $db->misc($table);
        $db->select_db(DB_DATBASE_NAME);
      } else {
        return "Error: ".$ret[1];
      }
    } else {
      $db->select_db(DB_DATBASE_NAME);
      return "Error: Database already exists";
    }
  }

  function getCustomDbName($pid = 0, $cpanel = 0)
{
  return 'altaknee_drag';
  //return 'dbasejames';
  if($pid != 0) {
    if($cpanel == 0) {
      return 'product_'.$pid;
    } else {
      return CPANEL_USER.'_product_'.$pid;
    }
  }
}

  public function saveProductSettings($pid) {
    global $db, $productObj;

    $result = $productObj->getProductById($pid);

    $option['pid'] = $pid;
    $option['product_name'] = $result['product_name'];

    $option['pen_name'] = $result['pen_name'];

    $option['support_email'] = $result['support_email'];
    $option['support_link'] = $result['support_link'];
    $option['price'] = $result['price'];
    $option['affiliate_share'] = $result['affiliate_share'];
    $option['paypal_email'] = ($result['payment_processor'] == 'paypal') ? $result['payment_id'] : '';
    $option['clickbank_id'] = ($result['payment_processor'] == 'clickbank') ? $result['payment_id'] : '';

    $db->select_db($this->getCustomDbName($pid, 0));

    $results = $db->select("SELECT * FROM " . PRO_SETTINGS_TABLE . " WHERE pid = " . $pid);
    if ($results[0]['id']) {
      $where = "pid = " . $pid;
      $db->update(PRO_SETTINGS_TABLE, $option, $where);
    } else {
      $db->insert(PRO_SETTINGS_TABLE, $option);
    }
    $db->select_db(DB_DATBASE_NAME);
  }

//---------------------------------------------------------------------------
  /**
   * checkProductDbExists - checks if product db exists
   * 
   * @global db $db
   * @param bigint $pid
   * @return boolean
   */
  public function checkProductDbExists($pid) {
    global $db;

    if ($db->select_db($this->getCustomDbName($pid, 0))) {
      $db->select_db(DB_DATBASE_NAME);
      return TRUE;
    } else {
      return FALSE;
    }
  }

// ------------------------------------------------------------------------
  /**
   * function to create config file
   *
   * @access private
   * @return 
   */
  public function createConfig($pid) {
    $root = $this->getRootDirectory() . '/' . $this->getProductDirectory($pid);
    $f = fopen($root . "/includes/config.php", 'w');
    if (!$f)
      return 'Give write permissions 777 to includes/config.php';

    $string = "<?php \n /*This file is automatically generated. Do not edit.*/\n\n";
    $string .= "/*\n";
    $string .= " * general config file\n";
    $string .= " *\n";
    $string .= " * this file defines all constants or variables that will be used in the whole site\n";
    $string .= " * @the code is properly formatted, please use 'TAB SIZE 2' to view proper indentation\n";
    $string .= " */\n\n";

    $string .= "/*disable it if you want to turn off the warnings and errors*/ \n";
    $string .= "error_reporting(E_ALL); \n";
    $string .= "ini_set('display_errors', 0);\n";
    $string .= "global \$db; \n\n";

    $string .= "/*secure constants if site is in https mode*/\n";
    $string .= "\$secure = 'http://';\n";
    $string .= "if(isset(\$_SERVER['SERVER_PORT']) && \$_SERVER['SERVER_PORT']== 443){\$secure = 'https://';}\n";
    $string .= "define('SECURE', \$secure);\n\n";

    $string .= "/*site general constants*/\n";

    $string .= "define('DOMAIN', '" . DOMAIN . "');\n";
    $string .= "define('SUB_DOMAIN', '" . SUB_DOMAIN . "');\n";
    $string .= "define('DIRECTORY', '" . $this->getProductDirectory($pid) . "');\n";
    $string .= "define('HOME_LINK', SECURE.SUB_DOMAIN.DOMAIN.'/'.DIRECTORY.'/');\n";
    $string .= "define('IMAGES_SRC', HOME_LINK.'images/');\n";
    $string .= "define('ROOT_PATH', dirname(__FILE__).'/../');\n";
    $string .= "define('UPLOAD_PATH', ROOT_PATH.'uploads/');\n";
    $string .= "define('UPLOAD_LINK', HOME_LINK.'uploads/');\n";

    $string .= "\n/*Database Settings*/\n";
    $string .= "define('DB_HOST', '" . DB_HOST . "');\n";
    $string .= "define('DB_USER', '" . DB_USER . "');\n";
    $string .= "define('DB_PASSWORD', '" . DB_PASSWORD . "');\n";
    $string .= "define('DB_DATBASE_NAME', '" . $this->getCustomDbName($pid, 0) . "');\n";

    $string .= "\ndefine('PER_PAGE', 10);\n";

    $string .= "\n//db tables\n";
    $string .= "define('MEMBER_TABLE', 'mc_members');\n";
    $string .= "define('ACTION_TABLE', 'mc_actions');\n";
    $string .= "define('DOWNLOAD_TABLE', 'mc_downloads');\n";
    $string .= "define('PRO_SETTINGS_TABLE', 'mc_product_settings');\n";
    $string .= "define('TRANSACTION_TABLE', 'mc_transactions');\n";
    $string .= "define('SUPPORT_TABLE', 'mc_support');\n\n";

    $string .= "\n//array for member pages (needs login)\n";
    $string .= "\$member_pages= array('account','affiliate_welcome','downloads','member_welcome','support','tools');\n\n";

    $string .= "\nrequire_once(ROOT_PATH.'includes/db.php');\n";
    $string .= "\$db = new db();\n\n";

    fwrite($f, $string . "\n");
    fclose($f);
  }

  //-------------------------------------------------------------------------
  /**
   * gets the product directory
   * 
   * @global type $productObj
   * @param type $pid
   * @return type 
   */
  function getProductDirectory($pid) {
    global $productObj;
    $prod = $productObj->getProductById($pid);
    return $_SESSION['user_name'] . '/' . $prod['product_url'];
  }

  //------------------------------------------------------------------------
  /**
   * replaceInlineParameters - replace parameters in generated html from DB
   * 
   * @param type $product
   * @param type $page
   * @return type 
   */
  function replaceInlineParameters($product, $page) {
    $search = array();
    $replace = array();

    $search[] = '%%PRODUCT_NAME%%';
    $replace[] = $product['product_name'];

    $search[] = '%%SUPPORT_EMAIL%%';
    $replace[] = $product['support_email'];

    $search[] = '%%PEN_NAME%%';
    $replace[] = $product['pen_name'];

    $search[] = '%%COPYRIGHT_INFO%%';
    $replace[] = $product['copyright_info'];

    $search[] = '%%AFFILIATE_USER_URL%%';
    $replace[] = '<?php if($_SESSION[\'isAffiliate\'] == 1) { echo HOME_LINK.\'index.php?affid=\'.$_SESSION[\'user_id\']; } ?>';

    $search[] = '%%DOWNLOADS_CODE%%';
    $replace[] = '<?php foreach ($pre_downloads as $pre_download) { ?>
          <li class="<?php echo $pre_download[\'ext\']; ?>-file"><a href="<?php echo HOME_LINK.$pre_download[\'name\'].\'.\'.$pre_download[\'ext\']; ?>"><?php echo $pre_download[\'name\']; ?></a></li>
            <?php } ?>
          <?php foreach ($downloads as $download) { ?>
          <li class="<?php echo $download[\'icon\']; ?>-file"><a href="<?php echo $download[\'link\']; ?>" target="new"><?php echo $download[\'name\']; ?></a></li>
          <?php } ?>';

    $search[] = '%%TRANSACTION_ID%%';
    $replace[] = '<?php echo (isset($_REQUEST[\'tid\']) && $_REQUEST[\'tid\'] != \'\') ? $_REQUEST[\'tid\'] : \'\' ?>';

    $search[] = '%%PARENT_ID%%';
    $replace[] = '<?php echo (isset($_REQUEST[\'parent_id\']) && $_REQUEST[\'parent_id\'] != \'\') ? $_REQUEST[\'parent_id\'] : \'0\' ?>';

    $search[] = '%%PRODUCT_PRICE%%';
    $replace[] = $product['price'];

    $search[] = 'src="default/templates/' . $product['product_template'] . '/images';
    $replace[] = 'src="images';

    $search[] = '<!-- %%BUY_LINK_START%% -->';
    $replace[] = '<a href="' . (($product['payment_processor'] == 'paypal') ? 'samples/Pay.php<?php echo $params;?>' : 'clickbank/click.php<?php echo $params;?>') . '">';

    $search[] = '<!-- %%BUY_LINK_END%% -->';
    $replace[] = '</a>';

    $search[] = '%%PAYMENT_PROCESSOR%%';
    $replace[] = (($product['payment_processor'] == 'paypal') ? 'Paypal Email' : 'Clickbank ID');

    $search[] = '%%PRODUCT_TITLE%%';
    $replace[] = $product['website_title'];

    $search[] = '%%SUPPORT_URL%%';
    $replace[] = (($product['support_email'] && $product['support_email'] != '') ? 'href="support.php"' : 'href="' . $product['support_link'] . '" target="new"');

    $search[] = '%%MEMBER_AFFILIATE_CODE%%';
    $replace[] = '<?php echo ($_SESSION[\'isMember\'] == 1) ? \'<li><a href="member_welcome.php">Member Welcome</a></li>\' : \'<li><a href="affiliate_welcome.php">Affiliate Home</a></li>\' ?>';
    /*
      $search[] = '';
      $replace[] = '';
     */
    $page_html = $page['page_html'];
    for ($i = 0; $i < count($search); $i++) {
      $page_html = str_replace($search, $replace, $page_html);
    }
    return $page_html;
  }

  //------------------------------------------------------------------------
  /**
   * sendEmail - send email to recipient
   * 
   * @param type $sender
   * @param type $senderEmail
   * @param type $receiverEmail
   * @param type $subject
   * @param type $body
   * @return type 
   */
  function sendEmail($sender, $senderEmail, $receiverEmail, $subject, $body) {
    $to = $receiverEmail;
    $subject = $subject;
    $message = $body;
    $headers = 'From: ' . $sender . '<' . $senderEmail . '>' . "\r\n" .
            'Reply-To: ' . $senderEmail . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
    if (mail($to, $subject, $message, $headers)) {
      return array('', 'Email sent to support, please allow atleast 24 hours to reply.');
    } else {
      return array('Email could not be sent, please try again in a while', '');
    }
  }


  function createDbWithXML($db_name, $db_username, $db_password) 
  {
    $xmlapi = new xmlapi(DOMAIN);
    $xmlapi->set_port(SERVER_PORT);
    $xmlapi->password_auth(CPANEL_USER, CPANEL_PASS);
    $xmlapi->set_debug(0); //output actions in the error log 1 for true and 0 false 

    $cpaneluser = CPANEL_USER;
    $databasename = $db_name;
    $databaseuser = $db_username;
    $databasepass = $db_password;

    //create database    
    $createdb = $xmlapi->api1_query($cpaneluser, "Mysql", "adddb", array($databasename));
    //print_r($createdb);
    if(isset($createdb['error']))
      return array(false, $createdb['error']);

    //create user 
    //$usr = $xmlapi->api1_query($cpaneluser, "Mysql", "adduser", array($databaseuser, $databasepass));
    //print_r($usr);

    //add user 
    $addusr = $xmlapi->api1_query($cpaneluser, "Mysql", "adduserdb", array("" . $databasename . "", "" . $databaseuser . "", 'all'));
    //print_r($addusr);
    if(isset($addusr['error']))
      return array(false, $addusr['error']);
    
    return array(true, '');
  }

}

?>