<?php

/*
* _function.php
* This file is part of wp-classified
* @author Mohammad Forgani 2012
* Author Website : http://www.forgani.com
* Licence Type   : GPL
* @version 1.4.3
* fixed some security issues 
* update local
*/

if (!isset($_SESSION)) @session_start();


function wpcHeader(){
  global $_GET, $_POST, $table_prefix, $wpdb, $lang, $wpClassified, $wp_rewrite;
  $wpcSettings = get_option('wpClassified_data');

  if($wp_rewrite->using_permalinks()) $delim="?";
  else $delim="&amp;";
  $pageinfo = $wpClassified->get_pageinfo();
  $page_id = $pageinfo['ID'];
  $main_link=get_permalink($page_id) . $delim;

  if ($wpcSettings['count_ads_per_page'] < 1) {
    $wpcSettings['count_ads_per_page'] = 10;
  }

   ?>

  <table width=90% border=0 cellspacing=0 cellpadding=8><tr>
  <?php
  if ($wpcSettings['top_image']!=''){
    $img=preg_replace('/\s+/','',$wpcSettings['top_image']);
    echo '<td valign="top"><a href="'.$main_link.'_action=classified"><img src="'. $wpClassified->plugin_url .'/images/' .$img. '"></a></td>';
  }
  if ($wpcSettings['description']!=''){
    echo '<td valign=middle>'.$wpcSettings['description'] . "</td>";
  }
   ?>
  </tr></table>
  
  <div class="wpc_head">
  <?php
  if (!isset($lnks)) $lnks = '';
  if ($lnks == ''){$lnks = wpcHeaderLink();}
  echo '<h3>' . $lnks. '</h3>';
  ?>
  <div class="wpc_search">
    <form action="<?php echo wpcPublicLink("searchform", array());?>" method="post">
    <input type="text" name="search_terms" VALUE="">
    <input type="submit" value="<?php echo $lang['_SEARCH']; ?>">
    </form>
  </div>
  <?php
  if ($wpcSettings['GADposition'] == 'top' || $wpcSettings['GADposition'] == 'bth') {
    $gAd = wpcGADlink();
    echo '<div class="wpc_googleAd">' . $gAd . '</div>';
  }
  ?>
  </div><!--wpc_head-->
  <?php
  //
  $wpClassified->cleanUp();

  $today = time();
  $sql = "SELECT ads_subjects_id, txt, date FROM {$table_prefix}wpClassified_ads_subjects";
  $rmRecords = $wpdb->get_results($sql);
  foreach ($rmRecords as $rmRecord) { 
    list ($adExpire, $contactBy) = preg_split('/###/', $rmRecord->txt);
    if (!isset($adExpire)) { $adExpire=$wpcSettings[ad_expiration]; };
    if ($adExpire && $adExpire > 0 ) {
      $second = $adExpire*24*60*60; // second
      $l = $today-$second;
      if ($rmRecord->date < $l) {
        $asid = $rmRecord->ads_subjects_id;
        //$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id =" . $asid);
        //$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = ". $asid);
      }
    }
  }  
}


// function to show the Main page
function wpcIndex($id){
  global $_GET, $user_ID, $table_prefix, $wpdb;
  get_currentuserinfo();
  $liststatuses = array('active'=>'Open','inactive'=>'Closed','readonly'=>'Read-Only');
  $categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
  $tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists WHERE status != 'inactive' ORDER BY position ASC");
  if ((int)$user_ID){
    $readtest = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id, {$table_prefix}wpClassified_ads_subjects.status, {$table_prefix}wpClassified_read.read_ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects
    LEFT JOIN {$table_prefix}wpClassified_read ON
    {$table_prefix}wpClassified_read.read_user_id = '".$user_ID."' &&
    {$table_prefix}wpClassified_read.read_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id");
  }

  for ($i=0; $i<count($tlists); $i++){
    $lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
  }

  for ($i=0; $i<count($readtest); $i++){
    if ($readtest[$i]->read_ads_subjects_id<1 && $readtest[$i]->status=='open'){
      $rlists[$readtest[$i]->ads_subjects_list_id] = 'y';
    } 
  }

  if (isset($id) && $id == 404)
    echo "<div style=\"margin:20px 0;\"><h2 style=\"color:red;\">Oops, 404: Page not found</h2></div>";

  include(dirname(__FILE__)."/main_tpl.php");
}


// function to list all ads already exist under a defined category
function wpcList($msg){
  global $_GET, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $user_ID, $wpClassified;
  $listId = get_query_var("lid");
  get_currentuserinfo();
  $wpcSettings = get_option('wpClassified_data');
  if ($wpcSettings['count_ads_per_page'] < 1) { 
    $wpcSettings['count_ads_per_page'] = 10;
  }
  $userfield = $wpClassified->get_user_field();
  $liststatuses = array('active'=>'Open','inactive'=>'Closed','readonly'=>'Read-Only');
  $lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
    LEFT JOIN {$table_prefix}wpClassified_categories ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id WHERE {$table_prefix}wpClassified_lists.lists_id = '".($listId)."'", ARRAY_A);
  
  $read = ($wpClassified->is_usr_loggedin())?$wpdb->get_col("SELECT read_ads_subjects_id FROM {$table_prefix}wpClassified_read WHERE read_user_id = ".$user_ID):array();

  $sql = "SELECT {$table_prefix}wpClassified_ads_subjects.*, {$wpmuBaseTablePrefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author  LEFT JOIN {$wpmuBaseTablePrefix}users AS lu ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author LEFT JOIN {$table_prefix}wpClassified_ads ON {$table_prefix}wpClassified_ads.ads_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id  WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lid'])."' AND {$table_prefix}wpClassified_ads_subjects.status != 'deleted' AND {$table_prefix}wpClassified_ads.status='active' GROUP BY ads_subjects_id ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,{$table_prefix}wpClassified_ads_subjects.date DESC";

  $ads = $wpdb->get_results($sql);  
  $numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lid'])."' && status != 'deleted'");

  include(dirname(__FILE__)."/listAds_tpl.php");
}

function wpcReadNotAllowed(){
  global $user_level;
  get_currentuserinfo();
}

function wpcFooter(){
  global $wpClassified, $table_prefix, $wpdb, $lang;

  $wpcSettings = get_option('wpClassified_data');
  $wpcSettings['credit_line'] = 'wpClassified plugins (Version '.$wpClassified->version.') powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';
  if ($wpcSettings['GADposition'] == 'btn' || $wpcSettings['GADposition'] == 'bth') {
    $gAd = wpcGADlink();
    echo '<div class="wpc_googleAd">' . $gAd . '</div>';
  }
  echo "<div class=\"wpc_footer\">";
  echo "<h3>" . $lang['_LAST'] . ' ' . $wpcSettings['count_last_ads'] . ' ' . $lang['_ADS'] . "...</h3>";
  echo wpcLastAds(false);
  echo '<HR class="wpc_footer_hr">';
  if(isset($wpcSettings['rss_feed']) && $wpcSettings['rss_feed']=='y'){
    $filename = $wpClassified->plugin_url . '/cache/wpclassified.xml';
    ?>
    <div class="rssIcon">
    <a href="<?php echo $filename; ?>" target="_blank" onclick="return pop('<?php echo $filename; ?>','<?php echo $wpcSettings['slug'] ?>');"><?php echo $wpcSettings['slug'] ?> RSS</a></div>
    <?php
    }
    if ($wpcSettings['show_credits']=='y') echo "<div class=\"smallTxt\">" .stripslashes($wpcSettings['credit_line']) . "</div>";
    if($wpcSettings['fb_link']=='y') echo wpcFbLike(''); 
  echo "</div>";
}

function wpcRssFilter($text){echo convert_chars(ent2ncr($text));} 

function wpcRssLink($vars) {
  global $wpdb, $table_prefix, $wp_rewrite, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $pageinfo = $wpClassified->get_pageinfo();

  $page_id = $pageinfo['ID'];
  if($wp_rewrite->using_permalinks()) $delim = "?";
  else $delim = "&amp;";
  $perm = get_permalink($page_id);
  $main_link = $perm . $delim;
  
  $mail_link .= "_action=va";
  if (isset($vars['lid']))
    $mail_link .= "&amp;lid=" . (int)$vars['lid'];
  if (isset($vars['asid']))
    $mail_link .= "&amp;asid=" . (int)$vars['asid'];
  return $main_link;
}

function wpcDeleteAd(){
  global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF, $lang, $user_ID, $wpClassified;

  if (!$_GET['aid']) $_GET['aid']=$_POST['YesOrNo'];
  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id =" .(int)$_GET['aid'];

  $postinfos = $wpdb->get_results($sql, ARRAY_A);

  $postinfo = $postinfos[0];
  $permission=false;
  if (($wpClassified->is_usr_loggedin() && $user_ID==$postinfo['author']) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
    $permission=true;
  }
  
  if (!$permission) if (getenv('REMOTE_ADDR')==$postinfo['author_ip']) $permission=true;
  if (!$permission) {
    wpcPermissionDenied();
    return;
  }

  $pageinfo = $wpClassified->get_pageinfo();
  $_link = "?page_id=".$pageinfo["ID"]."&_action=da";
  if (isset($_GET['lid']))
    $_link .= "&amp;lid=" . (int)$_GET['lid'];
  if (isset($_GET['asid']))
    $_link .= "&amp;asid=" . (int)$_GET['asid'];
	
  $link_del = get_bloginfo('wpurl'). $_link;

  if ($_POST['YesOrNo']>0){
    $sql = "DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$_GET['asid'])."'";
    $wpdb->query($sql);
    $sql = "DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".((int)$_GET['asid'])."'";
    $wpdb->query($sql);
    wpcList($lang['_ANNDEL']);
    return true;
  } else {
  ?>
  <h3 style= "margin:20px 0"><?php echo $lang['_CONFDEL'];?></h3>
  <form method="post" id="delete_ad_conform" name="delete_ad_conform" action="<?php echo $link_del;?>">
  <strong>
    <input type="hidden" name="YesOrNo" value="<?php echo $_GET['aid'];?>">
    <?php echo $lang['_SURDELANN'];?><br />
    <p><input type=submit value="<?php echo $lang['_YES'];?>"> <input type=button value="<?php echo $lang['_NO'];?>" onclick="history.go(-1);"></p>
  </strong>
  </form>
  <?php
  return false;
  }
}

// edit post function
function wpcEditAd(){

  global $_GET, $_POST, $user_ID, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $quicktags, $lang, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  get_currentuserinfo();
  /*
  $lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
   LEFT JOIN {$table_prefix}wpClassified_categories
   ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
   WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);
  */
  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'";
  $adsInfo = $wpdb->get_row($sql, ARRAY_A);
  
  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id = '".(int)$_GET['aid']."'";
  $postinfos = $wpdb->get_results($sql, ARRAY_A);
  // asid, aid, lid
  if ($adsInfo == null || $postinfos=0) 
    wpcIndex(404);

  $postinfo = $postinfos[0];
  if (isset($_POST['wpClassified_data'])) {
    $web = stripslashes(trim($_POST['wpClassified_data']['web']));
    $email = stripslashes(trim($_POST['wpClassified_data']['email']));
    $email = strtolower($email);
    $phone = stripslashes(trim($_POST['wpClassified_data']['phone']));
    $subject = stripslashes(trim($_POST['wpClassified_data']['subject']));
    $description = $_POST['description'];
    $author_name = $_POST['wpClassified_data']['author_name'];
  }
  $permission=false;
  if (($wpClassified->is_usr_loggedin() && $user_ID==$postinfo['author']) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
    $permission=true;
   }
  if (!$permission) if (getenv('REMOTE_ADDR')==$postinfo['author_ip']) $permission=true;
  if (!$permission) {
    wpcPermissionDenied();
    return;
  }
  if (isset( $adsInfo["txt"])) list($adExpire, $contactBy)=preg_split('/###/', $adsInfo["txt"]);
  $displayform = true;
  
  if (isset($_POST['edit_ad']) and $_POST['edit_ad']=='yes'){
    $addPost = true;
    if (str_replace(" ", "", $author_name)=='' && !$wpClassified->is_usr_loggedin()){
      $msg .= $lang['_INVALIDNAME'];
      $addPost = false;
    }

    if (str_replace(" ", "", $subject)==''){
      $msg .= $lang['_INVALIDSUBJECT'] . '<br />';
      $addPost = false;
    }

    if ( !isset($email) ) {
        $msg .= '-' . $lang['_INVALIDEMAIL'] . '<br />';
        $addPost = false;
    }
    if (isset($email) && !is_email($email)) {
      $msg .= '-' . $lang['_INVALIDEMAIL2'] . '<br />';;
      $addPost = false;
    }
    if ( strlen($web) > 1 ) {
      if ( !wpcCheckUrl($web) ) {
        $msg .= '-' . $lang['_INVALIDURL'] . '(optional)<br />';
        $addPost = false;
      } else {
        $web = wpcCheckUrl($web);
      }
    }
    if (isset($phone) && !preg_match('/^\s*$/',$phone) ) {
      str_replace('/^\s+/',"",$phone);
      str_replace('/\s+$/',"",$phone);
      if ( strlen($phone) > 1 && !wpcValidatePhone($phone)) {
        $msg .= '-' . $lang['_INVALIDPHONE'] . '(optional)<br />';
        $addPost = false;
      }
    }
    if(isset($wpcSettings['confirmation_code']) && $wpcSettings['confirmation_code']=='y'){ 
      if (! wpcCaptcha::Validate($_POST['wpClassified_data']['confirmCode'])) {
         $msg .= '-' . $lang['_INVALIDCONFIRM'] . '<br />';
        $addPost = false;
        }
    }
    if ( empty($description) || str_replace(" ","",$description)==''){
      $msg .= '-' . $lang['_INVALIDCOMMENT'] . '<br />';
      $addPost = false;
    }

    if ($_FILES['image_file']!=''){
      $ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
      if ($ok==true){
        $imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
        if ($imginfo[0] && 
            ($imginfo[0]>(int)$wpcSettings["image_width"] ||
            $imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0))  {
           $msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
          $addPost=false;  
        } else {
          $fp = @fopen($_FILES['image_file']['tmp_name'], "r");
          $content = @fread($fp, $_FILES['image_file']['size']);
          @fclose($fp);
          $fp = fopen( $wpClassified->public_dir."/".(int)$user_ID."-".$_FILES['image_file']['name'], "w");
          @fwrite($fp, $content);
          @fclose($fp);
          @chmod( $wpClassified->public_dir . "/".(int)$user_ID."-".$_FILES['image_file']['name'], 0777);
          $setImage = (int)$user_ID."-".$_FILES['image_file']['name'];
        }
      }
    }
    if ($addPost==true) {
      $displayform = false;

      $_FILES['image_file'] = $id."-".$_FILES['image_file']['name'];
      $sql = "update {$table_prefix}wpClassified_ads
        set subject='".$wpdb->escape($subject)."',";
      if ($_FILES['image_file'] =='') {
        $sql .= "image_file='".$wpdb->escape(stripslashes($setImage))."',";
      }

      if($wpcSettings['edit_style'] != 'tinymce') $description = $wpClassified->html2Text($description);
      $sql .= "post='".$description."'
        WHERE ads_id='".(int)$_GET['aid']."' ";
      $wpdb->query($sql);

      $sql = "update {$table_prefix}wpClassified_ads_subjects
      set subject='".$wpdb->escape($subject)."',
      email='".$wpdb->escape($email)."',
      web='".$web."',
      phone='".$wpdb->escape(stripslashes($phone))."',
      txt='".(int)$wpdb->escape(stripslashes($_POST['wpClassified_data']['ad_expiration'])).'###'.$_POST['wpClassified_data']['contactBy']."'WHERE ads_subjects_id='".(int)$_GET['asid']."'";

      $wpdb->query($sql);
      wpcList($lang['_UPDATE']);
    } else {
      $displayform = true;
    }
  } 
  if ($displayform==true){
    $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE ads_id = '".(int)$_GET['aid']."'";
    $postinfos = $wpdb->get_results($sql);
    $postinfo = $postinfos[0];
    include(dirname(__FILE__)."/editAd_tpl.php");
  }
}



function wpcPrintAd($file, $aid) {
  global $_GET, $_POST, $wpdb, $table_prefix, $wpmuBaseTablePrefix, $PHP_SELF, $lang, $postinfo, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $filename = $wpClassified->cache_dir . $file . '.html';
  
  $userfield = $wpClassified->get_user_field();
  $pageinfo = $wpClassified->get_pageinfo();

  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" . $aid;
  $post = $wpdb->get_row($sql);

  $message=$post->post;
  $subject=$post->subject;
  $displayform = true;
  $phone = $post->phone;
  $photo = $post->image_file;
  $web = $post->web;

  $array = preg_split('/\#\#\#/', $post->image_file);
  $submitter = $postinfo['author'];

  $fp = fopen($filename, 'w');
  ob_start(); 
  include(dirname(__FILE__)."/printAd_tpl.php");
  $contents = ob_get_clean();
  fwrite($fp, $contents);
}


function wpcSendAd(){
  global $_GET, $_POST, $wpdb, $table_prefix, $wpmuBaseTablePrefix, $PHP_SELF, $lang, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();
  $pageinfo = $wpClassified->get_pageinfo();
  $aid = (int)$_GET['aid'];

  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" . $aid;

  $post = $wpdb->get_row($sql);

  $link_snd = wpcPublicLink("sndform", array("aid"=>$_GET['aid']));
  //$link_snd = get_bloginfo('wpurl')."?page_id=".$pageinfo["ID"]."&_action=sndad&aid=".$_GET['aid'];

  $msg=$post->post;
  $subject=$post->subject;
  $displayform = true;
  if (isset($_POST['send_ad']) && $_POST['send_ad']=='yes'){
    $sendAd = true;
    $yourname=$_POST['wpClassified_data']['yourname'];
    $mailfrom=$_POST['wpClassified_data']['mailfrom'];
    $mailto=$_POST['wpClassified_data']['mailto'];

    if (!preg_match('/^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$/',  $_POST['wpClassified_data']['mailto'])) {
      $msg = $lang['_INVALIDEMAIL2'];
      $sendAd = false;
    }
    if($wpcSettings['confirmation_code']=='y'){ 
      if (! wpcCaptcha::Validate($_POST['wpClassified_data']['confirmCode'])) {
        $msg = $lang['_INVALIDCONFIRM'];
        $sendAd = false;
      }
    }
    if ($sendAd == true) {
      $displayform = false;
      $message = "Dear " .$_POST['wpClassified_data']['fname']. "<br>";
      $message .= "your friend " . $yourname . " send you this interesting advertisement about " . $subject . "<br><br>";
      $message .= $lang['_ADDETAIL']. "<BR>" . $msg . "<BR><BR>";
      $message .= $lang['_FRIENDBTN1'];
      $message .= get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."<BR><BR><BR>";
      $message .= $yourname . $lang['_FRIENDBTN2'];
      // todo
        $txt = $wpClassified->html2Text($message);
      $from = "From: ". $yourname . "<" .$mailfrom. ">";
      //$from .= "Content-Type: text/html";
      $sub = "your friend " . $yourname . " sent you an interesting advertisement";

      $status = array();
      $email = wp_mail($mailto, $sub, $txt, $from);
      if ($email == false) {
        $status[0] = false;
        $msg = $lang['_SENDERR'];
        $displayform = true;
        $sendAd = false;
      } else {
        $status[0] = true;
        $msg = $lang['_SEND'];
        wpcList($msg);
      }
    }
  } else {
    $displayform = true;
  }
  if ($displayform==true){
    include(dirname(__FILE__)."/sendAd_tpl.php");
  }
}

// function to display advertisement information
function wpcDisplayAd(){
  global $_GET, $user_ID, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();
  
  if ($wpClassified->is_usr_loggedin()){
    $readposts = $wpdb->get_col("SELECT read_ads_id FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id = '".(int)$_GET['asid']."' && read_ads_user_id = '".(int)$user_ID."'");
  } else {
    $readposts = array();
  }
  $wpClassified->update_ads_views($_GET['asid']);
  if ($wpClassified->is_usr_loggedin()){
    $wpdb->query("REPLACE INTO {$table_prefix}wpClassified_read (read_user_id, read_ads_subjects_id) VALUES ('".(int)$user_ID."', '".(int)$_GET['asid']."')");
  }
  $lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
     LEFT JOIN {$table_prefix}wpClassified_categories
     ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
     WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);
  
  if ($lists== null || !is_numeric($_GET['lid']))
    wpcIndex(404);
	
  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'";
  $adsInfo = $wpdb->get_row($sql, ARRAY_A);

  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author LEFT JOIN {$table_prefix}wpClassified_user_info ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$wpmuBaseTablePrefix}users.ID WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".(int)$_GET['asid']."' && {$table_prefix}wpClassified_ads.status = 'active' ORDER BY {$table_prefix}wpClassified_ads.date ASC";
  $posts = $wpdb->get_results($sql);

  
  if (count($posts)>$wpcSettings['count_ads_per_page']){
    $hm = $wpcSettings['count_ads_per_page'];
  } else {
    $hm = count($posts);
  }
  if ($hm>count($posts)){
    $hm = count($posts);
  }
  
  for ($i=0; $i<$hm; $i++){
    $post = $posts[$i];

    $permission=false;
    if (($wpClassified->is_usr_loggedin() && $user_ID==$post->author) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
      $permission=true;
    }
    if (!$permission) {
      if (getenv('REMOTE_ADDR')==$post->author_ip) $permission=true;
    }  
    
    if ($permission){
      $editlink = " ".wpcPublicLink("ea", array("name"=>$lang['_EDITDESC'], "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>$lang['_EDITDESC'], "aid"=>$post->ads_id))." ";
      $deletelink = " ".wpcPublicLink("da", array("name"=>  $lang['_DELETE'], "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>$lang['_DELETE'], "aid"=>$post->ads_id))." ";
    } else {
      $editlink = "";
    }
    if (!@in_array($post->ads_id, $readposts) && $wpClassified->is_usr_loggedin()){
      $xbefred = "<font color=\"".$wpcSettings['unread_color']."\">";
      $xafred = "</font>";
      $setasread[] = "('".(int)$user_ID."', '".$_GET['asid']."', '".$post->ads_id."')";
    } else {
      $xbefred = "";
      $xafred = "";
    }
    include(dirname(__FILE__)."/showAd_tpl.php");
  }

  if (isset($setasread) && count($setasread)>0){
    $wpdb->query("INSERT INTO {$table_prefix}wpClassified_read_ads (read_ads_user_id, read_ads_ads_subjects_id, read_ads_id) VALUES ".@implode(", ", $setasread));
  }
}



function wpcSearch($term){
  global $_GET, $_POST, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $wpClassified;
  get_currentuserinfo();
  $userfield = $wpClassified->get_user_field();

  #
  # fixed 05-OCT-2012
  #
  $term = strtolower($term);
  $sql="SELECT L.lists_id,L.name, A.subject, A.post,S.ads_subjects_id, U.display_name, A.date, A.ads_id, A.ads_ads_subjects_id, S.views 
        FROM {$table_prefix}wpClassified_lists as L, {$table_prefix}wpClassified_ads_subjects as S, {$table_prefix}wpClassified_ads as A
        LEFT JOIN {$table_prefix}users as U ON U.id = A.author
        WHERE L.lists_id = S.ads_subjects_list_id AND 
        S.ads_subjects_id = A.ads_ads_subjects_id AND 
        (lower(S.subject) like '%".$wpdb->escape($term)."%' OR lower(A.post) like '%".$wpdb->escape($term)."%') 
        ORDER BY L.name, A.date DESC";

  $results = $wpdb->get_results($sql);
  include(dirname(__FILE__)."/searchRes_tpl.php");
}


function wpcGADlink() {
  $wpcSettings = get_option('wpClassified_data');
  $key_code = $wpcSettings['googleID']; 
  if ( $wpcSettings['GADproduct']=='link' )  {
    $format = $wpcSettings['GADLformat'] . '_0ads_al'; // _0ads_al_s  5 Ads Per Unit
    list($width,$height) = preg_split('/[x]/',$wpcSettings['GADLformat']);
  } else {
    $format = $wpcSettings['GADformat'] . '_as';
    list($width,$height,$null) = preg_split('/[x]/',$wpcSettings['GADformat']);
  }

  $code = "\n" . '<script type="text/javascript"><!--' . "\n";
  $code.= 'google_ad_client="' . $key_code . '"; ' . "\n";
  $code.= 'google_ad_width="' . $width . '"; ' . "\n";
  $code.= 'google_ad_height="' . $height . '"; ' . "\n";
  $code.= 'google_ad_format="' . $format . '"; ' . "\n";
  if(isset($settings['alternate_url']) && $settings['alternate_url']!=''){ 
    $code.= 'google_alternate_ad_url="' . $settings['alternate_url'] . '"; ' . "\n";
  } else {
    if(isset($settings['alternate_color']) && $settings['alternate_color']!='') { 
      $code.= 'google_alternate_color="' . $settings['alternate_color'] . '"; ' . "\n";
    }
  }        
  //Default to Ads
  if($wpcSettings['GADproduct']!=='link') { 
    $code.= 'google_ad_type="' . $wpcSettings['GADtype'] . '"; ' . "\n"; 
    $code.= 'google_ui_features="rc:6"' . ";\n";
    // '0' => 'Square corners' 
    // '6' => 'Slightly rounded corners'
    // '10' => 'Very rounded corners'
  }
  $code.= 'google_color_border="' . $wpcSettings['GADcolor_border'] . '"' . ";\n";
  $code.= 'google_color_bg="' . $wpcSettings['GADcolor_bg'] . '"' . ";\n";
  $code.= 'google_color_link="' . $wpcSettings['GADcolor_link'] . '"' . ";\n";
  $code.= 'google_color_text="' . $wpcSettings['GADcolor_text'] . '"' . ";\n";
  $code.= 'google_color_url="' . $wpcSettings['GADcolor_url'] . '"' . ";\n";
  $code.= '//--></script>' . "\n";
  $code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";
  return $code;
}


function wpcFilterHtml($content){
  return addslashes (wp_kses(stripslashes($content), array()));
}

function wpcFilterContent($content, $searchvalue) {
  $content = apply_filters('sf_show_post_content', $content);
  $content = convert_smilies($content);
  if(empty($searchvalue)) {
    return $content."\n";
  }
  $searchvalue=urldecode($searchvalue);
  return $content."\n";
}

function wpcLastAds($format) {
  global $table_prefix, $wpdb, $lang, $wpClassified, $wp_rewrite;
  $wpcSettings = get_option('wpClassified_data');
  if (!$wpcSettings['count_last_ads']) $wpcSettings['count_last_ads'] = 5;

  $start = 0;
  $out ='';

  $sql ="SELECT ADS.*, L.name as l_name, C.name as c_name, L.lists_id as lists_id FROM {$table_prefix}wpClassified_ads_subjects ADS, 
         {$table_prefix}wpClassified_ads AD, {$table_prefix}wpClassified_lists L, 
         {$table_prefix}wpClassified_categories C WHERE ADS.ads_subjects_list_id = L.lists_id  AND C.categories_id=L.wpClassified_lists_id AND AD.ads_ads_subjects_id=ADS.ads_subjects_id AND AD.status='active' ORDER BY ADS.ads_subjects_id DESC, ADS.date DESC LIMIT ".($start).", ".($wpcSettings['count_last_ads']);

   $lastAds = $wpdb->get_results($sql);

  foreach ($lastAds as $lastAd) {
    $link= wpcPublicLink("ads_subject", array("name"=>$lastAd->subject, "lid"=>$lastAd->ads_subjects_list_id, "asid"=>$lastAd->ads_subjects_id));
    $out .= '<li>'.$link;
    $sql = "SELECT * FROM {$table_prefix}wpClassified_ads WHERE status='active' and ads_ads_subjects_id=" .$lastAd->ads_subjects_id;
    $post = $wpdb->get_row($sql);
    $img = false;
    preg_replace(array('/\s/'), '', $post->image_file);
    if ( !empty($post->image_file) ) {
      $array = preg_split('/\#\#\#/', $post->image_file);
      $img = $array[0];
    }
    
    if (!$format) {
      if (isset($img) && $img !='') {
        include (dirname(__FILE__).'/js/viewer.js.php');
        $out .= "&nbsp;<a href=\"". $wpClassified->public_url ."/" . $img . "\" rel=\"thumbnail\"><img  src=\"". $wpClassified->plugin_url."/images/camera.gif"."\"></a>";
      }
      $pageinfo = $wpClassified->get_pageinfo();
      $page_id = $pageinfo['ID'];
      if($wp_rewrite->using_permalinks()) $delim = "?";
      else $delim = "&amp;";
      $perm = get_permalink($page_id);
      $main_link = $perm . $delim;
	    $main_link .= "_action=vl";
      if (isset($lastAd->lists_id))
        $main_link .= "&amp;lid=" . (int)$lastAd->lists_id;
      $out .= " (". $lastAd->c_name . " - <a href=\"". $main_link . "\">". $lastAd->l_name . "</a>)";
	  $out .= "<span class='smallTxt'>&nbsp;- @" . $lastAd->author_name ." ". @date($wpcSettings['date_format'],$lastAd->date)."<span>";
    }
    $out .= "</li>\n";
  }  
  return $out;
}


function wpcValidatePhone($phone){
  $phoneregexp ='/^(\+[1-9][0-9]*(\([0-9]*\)|-[0-9]*-))?[0]?[1-9][0-9\- ]*$/';
  $phonevalid = false;
  if (preg_match($phoneregexp, $phone)) {
    $phonevalid = true;
  }
  return $phonevalid;
}



function wpcFbLike($id) {
  global $wpClassified;
  $layout = 'standard'; // button_count standard
  $show_faces = 'false'; // TODO
  $font = 'arial';
  $colorscheme = 'light'; // dark
  $action = 'like'; //  recommend
  $width = '450';
  $height = '';
  $pageinfo = $wpClassified->get_pageinfo();
  $url = get_bloginfo('wpurl').'/?page_id=' . $pageinfo["ID"];
  $permalink = urlencode($url);
  $output = '<div style="margin:5px 0">';
  $output .= '<iframe src="http://www.facebook.com/plugins/like.php?href='.str_replace('&', '&amp;', $url).'&amp;layout='.$layout.'&amp;show_faces='.$show_faces.'&amp;width='.$width.'&amp;action='.$action.'&amp;font='.$font.'&amp;colorscheme='.$colorscheme.'" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:'.$width.'px; height:'.$height.'px"></iframe>';
  return $output . '</div>';
}

?>