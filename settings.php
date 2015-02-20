<?php

/*
* settings.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
*/

// unread_color
// rss_feed
//ini_set('display_errors', 'On');
//error_reporting(E_ALL|E_STRICT);

// include 
$locale=get_locale();
list ($lng, $loc)=preg_split('/\_/', $locale);
$languageFile=dirname(__FILE__).'/language/lang_'. $lng . '.php';
if (file_exists($languageFile)) {  
  require_once($languageFile);
} else {
  require_once(dirname(__FILE__).'/language/lang_en.php');
}

require_once (dirname(__FILE__).'/includes/_functions.php');
require_once(dirname(__FILE__)."/functions.php");
require_once (dirname(__FILE__).'/admin.php');
require_once (dirname(__FILE__).'/captcha_class.php');

if (!isset($_GET)) $_GET=$HTTP_GET_VARS;
if (!isset($_POST)) $_POST=$HTTP_POST_VARS;
if (!isset($_SERVER)) $_SERVER=$HTTP_SERVER_VARS;
if (!isset($_COOKIE)) $_COOKIE=$HTTP_COOKIE_VARS;

global $table_prefix, $wpdb, $wpmuBaseTablePrefix, $wp_redirect;
if (!$table_prefix) $table_prefix=$wpdb->prefix;
if (!$wpmuBaseTablePrefix) $wpmuBaseTablePrefix=$table_prefix;

$wpcAdminMenu=array(
  array('name'=>'Add/Edit Categories','arg'=>'wpcStructure'),
  array('name'=>'Edit/Remove Ads','arg'=>'wpcModify'),
  array('name'=>'Users Admin','arg'=>'wpcUsers'),
  array('name'=>'Utilities','arg'=>'wpcUtilities'),
);

$wpcAdminPages=array(
  array('name'=>'Add/Edit Categories','arg'=>'wpcStructure','prg'=>'adm_structure_process'),
  array('name'=>'Edit/Remove Ads','arg'=>'wpcModify','prg'=>'adm_modify_process'),
  array('name'=>'Users Admin','arg'=>'wpcUsers','prg'=>'adm_users_process'),
  array('name'=>'Utilities','arg'=>'wpcUtilities','prg'=>'adm_utilities_process'),
);


function wpcHeaderLink(){
  global $_GET, $_POST, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $_SERVER, $lang, $wp_rewrite, $wpClassified;
  $pageinfo=$wpClassified->get_pageinfo();
  $page_id=$pageinfo['ID'];
  if($wp_rewrite->using_permalinks()) $delim="?";
  else $delim="&amp;";
  $perm=get_permalink($page_id);
  $main_link=$perm . $delim;

  $wpClassified_settings=get_option('wpClassified_data');
  if (isset($_POST['search_terms'])) {
    $_GET['_action']="search";
  } else {
    $_POST['search_terms']='';
  }
  switch ($_GET['_action']){
    default:
    case "classified":
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a>";
    break;
    case "search":
      $search_title="Searching for: ".$_POST['search_terms'];
      return $search_title;
    break;
    case "vl":
    if (isset($_GET['lid']) && is_numeric($_GET['lid'])) {
        $sql = "SELECT c.name as cName, l.name as lName, l.wpClassified_lists_id FROM {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c 
                WHERE c.categories_id=l.wpClassified_lists_id AND l.lists_id=". (int)$_GET['lid'];
        $lists=$wpdb->get_row($sql, ARRAY_A);
        return wpcPublicLink("index", array("name"=>"Classified"))."  ". $lists["cName"] . " - " . $lists['lName'];
    } else {
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a>";
    }
    break;
    case "pa":
    if (!empty($_GET['lid']) && is_numeric($_GET['lid'])) {
      $sql = "SELECT c.name as cName, l.name as lName, l.lists_id, l.wpClassified_lists_id FROM {$table_prefix}wpClassified_lists l, 
              {$table_prefix}wpClassified_categories c 
              WHERE c.categories_id=l.wpClassified_lists_id AND l.lists_id=". (int)$_GET['lid'];
      $lists=$wpdb->get_row($sql, ARRAY_A);
      return wpcPublicLink("index", array("name"=>"Classified"))." ". $lists["cName"] ."<br></h3>". $lang['_ADDANNONCE'] . "<h3>" . 
             wpcPublicLink("classified", array("name"=>$lists["lName"], "lid"=>$lists['lists_id'])); 
    } else {
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a>";
    }        
    break;
    case "ea":
    if (isset($_GET['asid']) && is_numeric($_GET['asid'])) {
      $sql = "SELECT l.name as lName, c.name as cName, l.lists_id FROM {$table_prefix}wpClassified_ads_subjects a, {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c
              WHERE l.lists_id=a.ads_subjects_list_id AND c.categories_id=l.wpClassified_lists_id AND a.ads_subjects_id=". (int)$_GET['asid'];
      $adsInfo=$wpdb->get_row($sql, ARRAY_A);
      return wpcPublicLink("index", array("name"=>"Classified"))." ". $adsInfo["cName"] ." - ". 
             wpcPublicLink("classified", array("name"=>$adsInfo["lName"], "name"=>$adsInfo["lName"], "lid"=>$adsInfo['lists_id']))."<BR>". $lang['_EDITADS'];
    } else {
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a>";
    }  
    break;
    case "va":
    if (isset($_GET['asid']) && is_numeric($_GET['asid'])) {
      $sql = "SELECT l.name as lName, c.name as cName, l.lists_id FROM {$table_prefix}wpClassified_ads_subjects a, {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c
              WHERE l.lists_id=a.ads_subjects_list_id AND c.categories_id=l.wpClassified_lists_id AND a.ads_subjects_id=". (int)$_GET['asid'];
      $adsInfo=$wpdb->get_row($sql, ARRAY_A);
      return wpcPublicLink("index", array("name"=>"Classified"))." ". $adsInfo["cName"] ." - ". 
             wpcPublicLink("classified", array("name"=>$adsInfo["lName"], "name"=>$adsInfo["lName"], "lid"=>$adsInfo['lists_id']));
    } else {
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a>";
    }  
    break;
  }
}


function wpClassified_adm_page(){
  global $_GET, $_POST, $PHP_SELF, $wpdb, $table_prefix;
  get_currentuserinfo();
  $wpcSettings=get_option('wpClassified_data');
  ?>
  
  <div class="wrap">
    <h2><?php echo __($pagelabel);?></h2>
      <?php
      switch ($_REQUEST['adm_arg']){
        case "wpcOptions":
        default:
          $this->process_settings();
        break;
        case "wpcStructure":
          adm_structure_process();
        break;
        case "wpcModify":
          adm_modify_process();
        break;
        case "wpcUsers":
          adm_users_process();
        break;
        case "wpcUtilities":
          adm_utilities_process();
        break;
      }
    ?>
  </div>
  <?php
}

function wpClassified_process(){
  global $_GET, $_POST, $table_prefix, $wpdb;
  if (!isset($msg)) $msg='';
  $wpcSettings=get_option('wpClassified_data');
  if (!isset($_GET['_action'])) $_GET['_action']='';
  if (is_user_logged_in()) {
    get_currentuserinfo();
  }
  switch ($_GET['_action']){
    default:
    case "classified": wpcIndex('');  break;
    case "search": wpcSearch($_POST['search_terms']); break;
    case "vl": 
      if (!empty($_GET['lid']) && is_numeric($_GET['lid'])) {
        wpcList($msg);
      } else {
        wpcIndex(404);
      } 
      break;
    case "pa":
      if (!empty($_GET['lid']) && is_numeric($_GET['lid'])) {
        wpcAddAd(); 
      } else {
        wpcIndex(404);  
      } 
      break;
    case "ea": 
      if (isset($_GET['asid']) && is_numeric($_GET['asid'])) {
        wpcEditAd();
      } else {
        wpcIndex(404);
      } 
      break;
    case "da":  
      if (isset($_GET['aid']) && is_numeric($_GET['aid'])) {
        wpcDeleteAd();
      } else {
        wpcIndex(404);
      } 
      break;
    case "va": 
      if (isset($_GET['asid']) && is_numeric($_GET['asid'])) {
        wpcDisplayAd();
      } else {
        wpcIndex(404);
      } 
      break;
    case "sndad": wpcSendAd(); break;
    case "mi": wpcModifyImg(); break;
    case "di": wpcDeleteImg($_POST['file']); break;
  }
}

function adm_structure_process(){
  global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $wpClassified;
  print wpcAdminMenu();
  $wpClassified->showCategoryImg();
  $t=$table_prefix.'wpClassified';
  $tab=$wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'");
  if(!$tab) {
    echo "<h3>No wpClassified tables found in database, May be you simply forget to save settings?</h3>";
  }  
  switch ($_GET['adm_action']){
    case "saveCategory":
      if ($_GET['categories_id']==0){
        $position=$wpdb->get_var("SELECT MAX(position) FROM {$table_prefix}wpClassified_categories")+1;
        $wpdb->query("INSERT INTO {$table_prefix}wpClassified_categories (name, photo, position, status) values (
        '".  $wpdb->escape($_POST['wpClassified_data']['name']).  "',
        '". $wpdb->escape($_POST['wpClassified_data']['photo'])."', 
        '".$position."', 'active')");
      } else {
        $wpdb->query("
          UPDATE {$table_prefix}wpClassified_categories 
          SET name='".$wpdb->escape($_POST['wpClassified_data']['name'])."',
            photo='".$wpdb->escape($_POST['wpClassified_data']['photo'])."' WHERE categories_id='".($_GET['categories_id']*1)."'");
      }
      $msg="Classifieds Category Saved!";
    break;
    case "saveList":
      $position=$wpdb->get_var("SELECT MAX(position) FROM {$table_prefix}wpClassified_lists")+1;
         $nameValue=$wpdb->escape($_POST['wpClassified_data']['name']);
         $descriptionValue = $wpdb->escape(stripslashes($_POST['wpClassified_data']['description']));
         $list_id = $_POST['wpClassified_data']['wpClassified_lists_id'];
         if ($_GET['lid']==0){
           if (strlen($nameValue) > 3)
             $wpdb->query("INSERT INTO {$table_prefix}wpClassified_lists (wpClassified_lists_id, name, description, position, status)
              values ('".$list_id."', '".$nameValue."', '".$descriptionValue."', '".$position."', '".$wpdb->escape($_POST['wpClassified_data']['status'])."')");
         } else {
           if (strlen($nameValue) > 3 && $list_id > 0)
             $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET status='".$wpdb->escape($_POST['wpClassified_data']['status'])."',
              wpClassified_lists_id='".$list_id."', name='".$nameValue."', description='".$descriptionValue."' WHERE lists_id=".$_GET['lid']*1);
      }
      $msg="List Saved!";
    break;
    case "deleteCategory":      
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_categories WHERE categories_id='".($_GET['categories_id']*1)."'");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id NOT IN (SELECT categories_id FROM {$table_prefix}wpClassified_categories)");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_ads_subjects_id NOT IN (SELECT lists_id FROM {$table_prefix}wpClassified_lists)");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
    break;
    case "deleteList":
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_lists WHERE lists_id='".($_GET['lid']*1)."'");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE wpClassified_lists_id NOT IN (SELECT lists_id FROM {$table_prefix}wpClassified_lists)");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
      $wpdb->query("DELETE FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
    break;
    case "moveupCategory":
      $ginfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id='".($_GET['categories_id']*1)."'", ARRAY_A);
      $above=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE position < '".$ginfo['position']."' ORDER BY position DESC", ARRAY_A);
      if ($above['categories_id']>0){
        $wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position='".$above['position']."' WHERE categories_id='".($_GET['categories_id']*1)."'");
        $wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position='".$ginfo['position']."' WHERE categories_id='".$above['categories_id']."'");
      }
      $msg="Category Moved Up";
    break;
    case "moveupList":
      $ginfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id='".($_GET['lid']*1)."'", ARRAY_A);
      $above=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id='".$ginfo['lists_id']."' && position < '".$ginfo['position']."' ORDER BY position DESC", ARRAY_A);
      if ($above['lists_id']>0){
        $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position='".$above['position']."' WHERE lists_id='".($_GET['lid']*1)."'");
        $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position='".$ginfo['position']."' WHERE lists_id='".$above['lists_id']."'");
      }
      $msg="List Moved Up";
    break;
    case "movedownCategory":
      $ginfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id='".($_GET['categories_id']*1)."'", ARRAY_A);
      $above=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE position > '".$ginfo['position']."' ORDER BY position ASC", ARRAY_A);
      if ($above['categories_id']>0){
        $wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position='".$above['position']."' WHERE categories_id='".($_GET['categories_id']*1)."'");
        $wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position='".$ginfo['position']."' WHERE categories_id='".$above['categories_id']."'");
      }
      $msg="Category Moved Down";
    break;
    case "movedownList":
      $ginfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id='".($_GET['lid']*1)."'", ARRAY_A);
      $above=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id='".$ginfo['lists_id']."' && position > '".$ginfo['position']."' ORDER BY position ASC", ARRAY_A);
      if ($above['lists_id']>0){
        $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position='".$above['position']."' WHERE lists_id='".($_GET['lid']*1)."'");
        $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position='".$ginfo['position']."' WHERE lists_id='".$above['lists_id']."'");
      }
      $msg="List Moved Down";
    break;
  }
  if ($msg!=''){
    ?>
    <p>
    <b><?php echo $msg; ?></b>
    </p>
    <?php
  }
  $wpcSettings=get_option('wpClassified_data');
  if ($_GET['adm_action']=='editCategory'){
    $categoryinfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id='".($_GET['categories_id']*1)."'", ARRAY_A);
  ?>
  <p>
  <form method="post" id="admCatStructure" name="admCatStructure" action="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveCategory&categories_id=<?php echo $_GET['categories_id'];?>">
    <table border=0 class="editform">
    <tr><th align="right">Category Name</th>
    <td><input type=text size=80 name="wpClassified_data[name]" value="<?php echo $categoryinfo['name'];?>"></td>
    </tr>

  <th align="right" valign="top">Category Photo</th>
  <td>
  <input type=hidden name="wpClassified_data[photo]" value="<?php echo $categoryinfo['photo'];?>">
  <?php

  echo "\n<select name=\"topImage\" onChange=\"showCatimage()\">";
  $rep=$wpClassified->plugin_dir . '/images/';
  $handle=opendir($rep);
  while ($file=readdir($handle)) {
    $filelist[]=$file;
  }
  asort($filelist);
  while (list ($key, $file)=each ($filelist)) {
    
    if (!ereg(".gif|.jpg|.png",$file)) {
      if ($file == "." || $file == "..") $a=1;
    } else {
      if ("images/" . $file == $categoryinfo['photo']) {
        echo "\n<option value=\"images/$file\" selected>images/$file</option>\n";
      } else {
        echo "\n<option value=\"images/$file\">images/$file</option>\n";
      }
    }
  }
  echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". $wpClassified->plugin_url . "/" . $categoryinfo['photo'] ."\" class=\"imgMiddle\"><br />";
  ?>    
  <span class="smallTxt">images from plugins/wp-classified/images directory</span></td>
   </tr>  


  <tr>
    <th></th>
    <td><input type=submit value="Save">&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);"></td>
  </tr>
  </table>
  </form>
  </p>
  <?php
  } elseif ($_GET['adm_action']=='editList'){
    $categories=$wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
    $classifiedinfo=$wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id='".($_GET['lid']*1)."'", ARRAY_A);
  ?>
  <p>
  <form method="post" id="admLstStructure" name="admLstStructure" action="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveList&lid=<?php echo $_GET['lid'];?>">
    <table border=0 class="editform">
      <tr>
        <th align="right">List Name</th>
        <td><input type=text size=80 name="wpClassified_data[name]" value="<?php echo $classifiedinfo['name'];?>"></td>
      </tr>
      <tr>
        <th align="right">List Description</th>
        <td><textarea name="wpClassified_data[description]" rows="3" cols="80"><?php echo $classifiedinfo['description'];?></textarea></td>
      </tr>
      <tr>
        <th align="right">Parent Category</th>
        <td><select name="wpClassified_data[wpClassified_lists_id]">
          <?php
                for ($x=0; $x<count($categories); $x++){
                    $category=$categories[$x];
                    $sel=($category->categories_id==$classifiedinfo['wpClassified_lists_id'])?" selected":"";
                    echo "<option value=\"".$category->categories_id."\"$sel>".$category->name."</option>\n";
                }
              ?>
            </select></td>
      </tr>
      <tr>
        <th align="right">List Status</th>
        <td><select name="wpClassified_data[status]">
          <option value="active">Open</option>
          <option value="inactive" <?php echo ($classifiedinfo['status']=='inactive')?" selected":"";?>>Closed</option>
          <option value="readonly"<?php echo ($classifiedinfo['status']=='readonly')?" selected":"";?>>Read-Only</option>
        </select></td>
      </tr>
      <tr>
        <th></th>
        <td><input type=submit value="Save">&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);"></td>
      </tr>
    </table>
  </form>
  </p>
  <?php
  } else {
    $liststatuses=array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
    $categories=$wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
    $tlists=$wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists ORDER BY position ASC");
    ?>
    <script language=javascript>
    <!--
    function deleteCategory(x, y){
      if (confirm("Are you sure you wish to delete the category:\n"+x)){
        document.location.href=y;
      }
    }
    function deleteList(x, y){
      if (confirm("Are you sure you wish to delete the list:\n"+x)){
        document.location.href=y;
      }
    }
    function deleteclassified(x, y){
      if (confirm("Are you sure you wish to delete the classified:\n"+x)){
        document.location.href=y;
      }
    }
    -->
    </script>
    <div class="wrap">
    <H2>Add/Edit Categories</H2><HR>
    <input type=button value="Add Category" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=0';">
     <input<?php echo (count($categories)<1)?" disabled":"";?> type=button value="Add List" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=0';">
    <?php
    for ($i=0; $i<count($tlists); $i++){
      $lists[$tlists[$i]->wpClassified_lists_id][]=$tlists[$i];
    }
  ?>
  <hr>
  <img src="<?php echo $wpClassified->plugin_url; ?>/images/delete.png"> - delete category, including and all lists within.<p>  
  <table style="width: 100%; background-color:#fafafa; border:1px #C0C0C0 solid; border-spacing:1px;">
  <tr>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" width=60>Delete</th>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" width=100>Move up/down</th>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" >Category/List</th>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" width=150>Number of ads</th>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" width=100>List</th>
    <th style="border:1px #C0C0C0 solid; padding-left:2px" width=100>Views</th>
  </tr>
  <?php
  for ($x=0; $x<count($categories); $x++){
    $category=$categories[$x];
  ?>
    <tr>
    <td style="border:1px #C0C0C0 solid; padding-left:2px"><a style="text-decoration: none;" href="javascript:deleteCategory('<?php echo rawurlencode($category->name);?>', '<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=deleteCategory&categories_id=<?php echo $category->categories_id;?>');"><img border=0 src="<?php echo $wpClassified->plugin_url; ?>/images/delete.png"></a></td>
    <td style="border:1px #C0C0C0 solid; padding-left:2px"><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupCategory&categories_id=<?php echo $category->categories_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownCategory&categories_id=<?php echo $category->categories_id;?>">&darr;</a> </sup></td>
    <td colspan=4 style="border:1px #C0C0C0 solid; padding-left:2px"><a href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=<?php echo $category->categories_id;?>"><?php echo $category->name;?></a></td>
    </tr>
    <?php
    $tfs=$lists[$category->categories_id];
    for ($i=0; $i<count($tfs); $i++){
      ?>
      <tr>
        <td></td>
        <td style="padding-left:2px"><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupList&lid=<?php echo $tfs[$i]->lists_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownList&lid=<?php echo $tfs[$i]->lists_id;?>">&darr;</a></td>
        <td style="padding-left:2px"><a style="text-decoration: none;" href="javascript:deleteList('<?php echo $tfs[$i]->name; ?>', '<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=deleteList&lid=<?php echo $tfs[$i]->lists_id;?>')"><img border=0 src="<?php echo $wpClassified->plugin_url; ?>/images/delete.png"></a>&nbsp;(<?php echo $liststatuses[$tfs[$i]->status];?>) <a href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
          <td style="padding-left:2px"><?php echo $tfs[$i]->ads_status;?></td>
          <td style="padding-left:2px"><?php echo $tfs[$i]->ads;?></td>
          <td style="padding-left:2px"><?php echo $tfs[$i]->ads_views;?></td>
        </tr>
        <?php
      }
    }
    ?>
    </table></div>
    <?php
  }
}

function wpcSearchHighlight($keywords,$post,$bgcolors='yellow'){
  if (is_array($bgcolors)) {
    $no_colors=count($bgcolors);
  } else {
    $temp=$bgcolors;
    unset($bgcolors);
    $bgcolors[0]=$temp;
    $no_colors=1;
  }
  $word_no=0;
  foreach($keywords as $keyword){
    $regex1=">[^<]*(";
    $regex2=")[^<]*<";
    preg_match_all("/".$regex1.$keyword.$regex2."/i", $post, $matches, PREG_PATTERN_ORDER);
    foreach($matches[0] as $match){
      preg_match("/$keyword/i", $match, $out);
      $search_word=$out[0];
      $newtext=str_replace($search_word,"<span style=\"background-color:".$bgcolors[($word_no % $no_colors)].";\">$search_word</span>", $match);
      $post=str_replace($match, $newtext, $post);
    }
    $word_no++;
  }
  return $post;
}



function bb($r) { 
  $r = trim($r); 
  $r = preg_replace("/<a[^>]+\>/i", " ", $r); 
  $r = preg_replace("/<img[^>]+\>/i", " ", $r); 
  $r = str_replace("\r\n","<br>",$r); 
  $r = str_replace("[b]","<b>",$r); 
  $r = str_replace("[/b]","</b>",$r); 
  $r = str_replace("[h3]","<h3>",$r); 
  $r = str_replace("[/h3]","</h3>",$r);
  $r = str_replace("[img]","<img src='",$r); 
  $r = str_replace("[/img]","'>",$r); 
  $r = str_replace("[IMG]","<img src='",$r); 
  $r = str_replace("[/IMG]","'>",$r); 
  $r = str_replace("[s]","<s>",$r); 
  $r = str_replace("[/s]","</s>",$r); 
  $r = str_replace("[ul]","<ul>",$r); 
  $r = str_replace("[/ul]","</ul>",$r); 
  $r = str_replace("[li]","<li>",$r); 
  $r = str_replace("[/li]","</li>",$r); 
  $r = str_replace("[ol]","<ol>",$r); 
  $r = str_replace("[/ol]","</ol>",$r); 
  $r = str_replace("[quote]","<br /><table width='80%' bgcolor='#ffff66' align='center'><tr><td style='border: 1px dotted black'><font color=black><b>Quote:<br></b>",$r); 
  $r = str_replace("[/quote]","</font></td></tr></table>",$r); 
  $r = str_replace("[i]","<i>",$r); 
  $r = str_replace("[/i]","</i>",$r); 
  $r = str_replace("[u]","<u>",$r); 
  $r = str_replace("[/u]","</u>",$r); 
  $r = str_replace("[spoiler]",'[spoiler]<font bgcolor ="#000000" color="#DDDDDD">',$r); 
  $r = str_replace("[/spoiler]","</font>[/spoiler]",$r); 
  $r = str_replace("[link\n=","[link=",$r); 
  $r = preg_replace("/<a[^>]+\>/i", " ", $r); 
  $r = preg_replace("/<img[^>]+\>/i", " ", $r);
  $r = trim($r); 
  return $r; 
} 


function wpcPostHtml($post){
  global $_GET;
  $wpcSettings=get_option('wpClassified_data');
  get_currentuserinfo();
  switch ($wpcSettings["edit_style"]){
    case "plain":
    default:
      $post->post=nl2br(str_replace("<", "&lt;", $post->post));
      break;
    case "tinymce":
         $html=bb($post->post);
      $post->post=nl2br($html);
         //$post->post=nl2br($post->post);
      break;
  }
  if ($wpcSettings['filter_posts']=='y'){
    $post->post=apply_filters('comment_text', nl2br($post->post));
  }
  if (isset($_GET['search_words'])){
    $keyword=explode(" ", $_GET['search_words']);
  } else $keyword='';
  return $post->post;
}


//mohamm
function adm_users_process(){
  global $_GET, $_POST, $wpdb, $table_prefix, $wpmuBaseTablePrefix, $wpClassified;
  $wpcSettings=get_option('wpClassified_data');
  print wpcAdminMenu();
  if ($_GET["adm_action"]=="saveuser"){
    $id=(int)$_GET["id"];
    $update=array();
    // FIXME .. hove to check with the new wp version
    if (isset($_POST["user_info"])){
      foreach ($_POST["user_info"] as $k=>$v){
        $update[]="$k='".$wpdb->escape($v)."'";
      }
      $wpdb->query("update {$table_prefix}wpClassified_user_info set ".implode(", ", $update)." where user_info_user_ID='".$id."'", ARRAY_A);
    }
  }

  switch ($_GET["adm_action"]){
    default:
    case "saveuser":
    case "list":
      $start=(int)$_GET["start"];
      $perpage=((int)$_GET["perpage"])?(int)$_GET["perpage"]:20;
      $searchfields=array(
        ($namefield= $wpClassified->get_user_field()),
        "user_login",
        "user_nicename",
        "user_email",
        "user_url",
      );
      if ($_GET["term"]){
        $where=" WHERE ";
        foreach ($searchfields as $field){
          if ($where!=" WHERE "){
            $where .= " || ";
          }
          $where .= "{$wpmuBaseTablePrefix}users.".$field." like '%".$wpdb->escape($_GET["term"])."%'";
        }
      } else {
        $where="";
      }
      //TODO
      $sql="select * from {$wpmuBaseTablePrefix}users
                LEFT JOIN {$table_prefix}wpClassified_user_info
                ON {$table_prefix}wpClassified_user_info.user_info_user_ID={$wpmuBaseTablePrefix}users.ID
                $where
                ORDER BY {$wpmuBaseTablePrefix}users.".$searchfields[0]." ASC
                LIMIT $start, $perpage";
      $all_users=$wpdb->get_results($sql, ARRAY_A);
      $numusers=$wpdb->get_results("select count(*) as numusers from {$wpmuBaseTablePrefix}users $where ", ARRAY_A);
      $numusers=$numusers[0]["numusers"];
      ?>
      <div class="wrap">
      <h2>Users Admin</h2>
      <form method="get" id="adm_form_get" action="<?php echo $_SERVER["PHP_SELF"];?>">
        <input type="hidden" name="adm_arg" value="<?php echo $_GET["adm_arg"];?>" />
        <input type="hidden" name="page" value="wpClassified" />
        <table width="100%">
          <tr>
            <td>Pages: <?php
            $query_string="perpage=$perpage&adm_arg=".$_GET["adm_arg"]."&page=wpClassified&term=".urlencode($_GET["term"]);

            for ($i=0; $i<($numusers/$perpage); $i++){
              if ($i*$perpage==$start){
                echo " <b>".($i+1)."</b> ";
              } else {
                echo " <a href=\"".$_SERVER["PHP_SELF"]."?".$query_string."&start=".($i*$perpage)."\">".($i+1)."</a> ";
              }
            }
            ?></td>
            <td align="right"><input type="text" size="25" name="term" value="<?php echo $_GET["term"];?>" /><input type="submit" value="Search" />&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);"></td>
          </tr>
        </table>
      </form>
      <table width="100%" cellpadding="3" cellspacing="3" border="0">
        <tr>
          <th align="left">Action</th>
          <th align="left">ID</th>
          <th align="left">Username</th>
          <th align="left">Display Name</th>
          <th align="left">E-mail Address</th>
          <th align="left">URL</th>
        </tr>
        <?php    
        foreach ($all_users as $user){
          $bgcolor=($bgcolor=="#CCCCCC")?"#DDDDDD":"#CCCCCC";
          ?>
          <tr bgcolor="<?php echo $bgcolor;?>">
          <td align="left"><a href="<?php echo $PHP_SELF;?>?page=wpcUsers&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=edit&id=<?php echo $user["ID"];?>&start=<?php echo $start;?>&perpage=<?php echo $perpage;?>&term=<?php echo urlencode($_GET["term"]);?>">Edit</a></td>
          <td><?php echo eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user["ID"]);?></td>
          <td><?php echo eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user["user_login"]);?></td>
          <td><?php echo eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user[$namefield]);?></td>
          <td><?php echo eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user["user_email"]);?></td>
          <td><?php
          if ($user["user_url"]!="" && $user["user_url"]!="http://"){
           echo "<a href=\"".$user["user_url"]."\" target=\"_BLANK\">".eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user["user_url"])."</a>";
          } else {
           echo eregi_replace("(".$_GET["term"].")", "<font color=\"red\"><b>\\1</b></font>", $user["user_url"]);
          }
          ?></td>
          </tr>
          <?php
        }
        ?>
      </table></div>
      <?php
    break;
    case "edit":
      $user=$wpdb->get_results("select * from {$wpmuBaseTablePrefix}users
              LEFT JOIN {$table_prefix}wpClassified_user_info
              ON {$table_prefix}wpClassified_user_info.user_info_user_ID={$wpmuBaseTablePrefix}users.ID
              WHERE {$wpmuBaseTablePrefix}users.ID='".(int)$_GET['id']."'", ARRAY_A);

      $user=$user[0];
      $namefield=$wpClassified->get_user_field();

      $permissions=array("none"=>"User", "moderator"=>"Moderator", "administrator"=>"Administrator");

      ?>
      <form method="post" id="admUser" name="admUser" enctype="multipart/form-data"
       action="<?php echo $_SERVER["PHP_SELF"];?>?page=wpcUsers&adm_arg=<?php echo $_GET["adm_arg"];?>&adm_action=saveuser&id=<?php echo $_GET["id"];?>&start=<?php echo $_GET["start"];?>&perpage=<?php echo $_GET["perpage"];?>&term=<?php echo urlencode($_GET["term"]);?>">
      <table width="100%">
      <tr>
        <td>ID</td>
        <td><?php echo $user["ID"];?></td>
      </tr>
      <tr>
        <td>Username</td>
        <td><?php echo $user["user_login"];?></td>
      </tr>
      <tr>
        <td>Name</td>
        <td><?php echo $user[$namefield];?></td>
      </tr>
      <tr>
        <td>Permission</td>
        <td><select name="wpClassified_user_info[user_info_permission]">
        <?php
        foreach ($permissions as $perm=>$name){
          $sel=($perm==$user["permission"])?" selected=\"selected\"":"";
          echo "<option value=\"$perm\"$sel>$name</option>\n";
        }
        ?>
        </select></td>
      </tr>
      <tr>
        <td>Title</td>
        <td><input type="text" name="wpClassified_user_info[user_info_title]" value="<?php echo str_replace('"', "&quot;", $user["title"]);?>" /></td>
      </tr>
      <tr>
        <td>Ads Count</td>
        <td><input type="text" name="wpClassified_user_info[user_info_post_count]" size="4" value="<?php echo str_replace('"', "&quot;", $user["post_count"]);?>" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" value="Save" />&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);"></td>
      </tr>
      </table>
      <?php
    break;
  }
}

function adm_utilities_process(){
  global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
  $t=$table_prefix.'wpClassified';
  $wpcSettings=get_option('wpClassified_data');
   print wpcAdminMenu();
  $exit=FALSE;
  switch ($_GET["adm_action"]){
    default:
    case "list":
    break;
    case "uninstall":
      $msg .= '<div class="wrap">';
      $msg .= '<h2>Uninstall wpClassified</h2>';
      delete_option('wpClassified_data');
      if($_tables=$wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
        foreach ($_tables as $table){
          $wpdb->query("DROP TABLE $table");
          $msg .= '<font style="color:green;">';
          $msg .= 'Table ' . $table . ' has been deleted.';
          $msg .= '</font><br />';
        }
      }
      $msg .= '</p><p>';
      $wpdb->query("DELETE FROM {$table_prefix}posts WHERE post_title='[[WP_CLASSIFIED]]'");
      $wpdb->query("DELETE FROM {$table_prefix}options WHERE option_name='wpClassified_data'");
      $_table="";
      

      $deactivate_url='plugins.php?action=deactivate&plugin=wp-classified/wpClassified.php';
      if(function_exists('wp_nonce_url')) {
        $deactivate_url=wp_nonce_url($deactivate_url, 'deactivate-plugin_wp-classified/wpClassified.php');
      }
      $msg .= '<h3><strong><a href='.$deactivate_url.'>Click Here</a> To Finish The Uninstallation And wpClassified Will Be Deactivated Automatically.</strong></h3>';
      $msg .= '</div>';
      $exit=TRUE;
    break;
  }

  if ($msg!=''){
    ?>
    <p>
    <b><?php echo $msg; ?></b>
    </p>
    <?php
  }
  if (!$exit) {
    ?>
    <div class="wrap">
    <h2>Uninstall wpClassified</h2>
    <p style="text-align: left;">Deactivating wpClassified plugin does not remove any data, which are created by installation. To completely remove the plugin, you can uninstall it here.</p>
    <p style="text-align: left; color:red">
    <strong>WARNING:</strong><br />Once uninstalled, this cannot be undone. You should use a database backup of WordPress to back up all the classifieds data first.  </p>
    <p style="text-align: left; color:red">
    <strong>The following WordPress Options/Tables will be DELETED:</strong><br />
    </p>
    <table width="70%"  border="0" cellspacing="3" cellpadding="3">
    <tr class="thead">
      <td align="center"><strong>WordPress Tables</strong></td>
    </tr>
    <tr>
    <td valign="top" style="background-color:#eee;">
      <ol>
      <?php
      if($tables=$wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
        foreach ($tables as $table){
          echo '<li>'.$table.'</li>'."\n";
        }
      }
      ?>
      </ol>
    </td>
    </tr>
    </table>
    <p>&nbsp;</p>
    
    <form method="post" id="admUtilities" name="admUtilities"
        action="<?php echo $PHP_SELF;?>?page=wpcUtilities&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=uninstall">
    <p style="text-align: center;">
    <br />
    <input type="submit" name="do" value="UNINSTALL wpClassified" class="button" onclick="return confirm('You Are About To Uninstall wpClassified From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.')" />
      </p>
    </form>
    </div>
    <?php
  }
}


?>
