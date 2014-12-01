<?php

/*
* $Id: *
* functions.php
*
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* Licence Type   : GPL
* @version 1.4.3
* fixed some security issues 
*/

// require_once('captcha_class.php');

function wpcAddAd(){
  global $_GET,$_POST, $_FILES, $HTTP_POST_FILES,
    $userdata, $user_ID, $table_prefix,$wpdb, $quicktags, $lang, $wpClassified;

  if(!isset($_FILES) && isset($HTTP_POST_FILES)) $_FILES = $HTTP_POST_FILES;
  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();
  get_currentuserinfo();
  $lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
         LEFT JOIN {$table_prefix}wpClassified_categories
         ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
         WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lid'])."'",ARRAY_A);
  if ($lists == null || !is_numeric($_GET['lid'])) 
    wpcIndex(404);

  $displayform = true;

  $web = stripslashes(trim($_POST['wpClassified_data']['web']));
  $email = stripslashes(trim($_POST['wpClassified_data']['email']));
  $email = strtolower($email);
  $phone = stripslashes(trim($_POST['wpClassified_data']['phone']));
  $subject = stripslashes(trim($_POST['wpClassified_data']['subject']));
  $description = $_POST['description'];
  $author_name = $_POST['wpClassified_data']['author_name'];
  if (isset($_POST['add_ad']) && $_POST['add_ad']=='yes') {
    if ($wpcSettings['must_registered_user']=='y' && !$wpClassified->is_usr_loggedin()) {
      die($lang['_MUSTLOGIN']);
    } else {
      $addPost = true;
      if (!$_POST['wpClassified_data']['term']) {
        $msg = '-' . $lang['_INVALIDTERM'] . '<br />';
        $addPost = false;
      }
      if (str_replace(" ","",$author_name)=='' && !$wpClassified->is_usr_loggedin()){
        $msg .= '-' . $lang['_INVALIDNAME'] . '<br />';
        $addPost = false;
      }
      if (str_replace(" ","",$subject)==''){
        $msg .= '-' . $lang['_INVALIDSUBJECT'] . '<br />';
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
      if (isset($_FILES['image_file']) && $_FILES['image_file']!='') {
        $ok = (substr($_FILES['image_file']['type'],0,5)=="image")?true:false;
        if ($ok==true){
          $imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
          if ($imginfo[0] && 
            ($imginfo[0]>(int)$wpcSettings["image_width"] ||
            $imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0))  {
            $msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
            $addPost=false;
          } else {
            $fp = @fopen($_FILES['image_file']['tmp_name'],"r");
            $content = @fread($fp,$_FILES['image_file']['size']);
            @fclose($fp);
            $fp = @fopen( $wpClassified->public_dir . "/".(int)$user_ID."-".$_FILES['image_file']['name'],"w");
            @fwrite($fp,$content);
            @fclose($fp);
            @chmod( $wpClassified->public_dir . "/".(int)$user_ID."-".$_FILES['image_file']['name'],0777);
            $setImage = (int)$user_ID."-".$_FILES['image_file']['name'];
          }
        }
      }
      if ($addPost==true){
        $displayform = false;
        $isSpam = wpcSpamFilter(stripslashes($author_name),'',$subject, $description, $user_ID);
        if ($wpcSettings['approve']=='y'){ $status = 'inactive';} else {$status = 'active';}
        $sql = "SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE author_ip = '" . 
            getenv('REMOTE_ADDR') . "' AND subject = '" . $wpdb->escape($subject)."'";
        $checkPost = $wpdb->get_results($sql);
        if($wpcSettings['edit_style'] != 'tinymce') $description = $wpClassified->html2Text($description);

        if ( $checkPost[0]->count > 0){
          $message = $lang['_APPROVEREPLY'];
        } else {
          $sql = "INSERT INTO {$table_prefix}wpClassified_ads_subjects
          (ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip, web, phone, txt, email) VALUES
          ('".($_GET['lid']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape($author_name)."' , 
          '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape($subject)."' , 0, 0 , 'n' , 
          '".(($isSpam)?"deleted":"open")."', '".$user_ID."', 
          '".$wpdb->escape($author_name)."', '".getenv('REMOTE_ADDR')."',
          '".$web."',
          '".$wpdb->escape($phone)."',
          '".(int)$wpdb->escape(stripslashes($_POST['wpClassified_data']['ad_expiration'])).'###'.$wpdb->escape(stripslashes($_POST['wpClassified_data']['contactBy']))."', '".$wpdb->escape($email)."')";
          $wpdb->query($sql);
          $tid = $wpdb->get_var("SELECT last_insert_id()");
          $wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads
          (ads_ads_subjects_id, date, author, author_name, author_ip, status, subject, image_file, post) VALUES
          ('".$tid."', '".time()."', '".$user_ID."', '".$wpdb->escape($author_name)."', 
          '".getenv('REMOTE_ADDR')."', '" . $status . "', 
          '".$wpdb->escape($subject)."',
          '".$wpdb->escape(stripslashes($setImage))."',
          '".$description."')");
          do_action('wpClassified_new_ads',$tid);
          $out = wpcEmailNotifications($user_ID, $author_name, $_GET['lid'], $subject, $description, $setImage, $tid, $web);
          $pid = $wpdb->get_var("select last_insert_id()");
          if (!$isSpam){
            wpcUpdatePostCount($user_ID);
            $wpClassified->update_ads($_GET['lid']);
          }
          $_GET['asid'] = $tid;
          $message = $lang['_SAVEADINFO']."<br>".$lang['_THANKS'];
          if ($wpcSettings['approve']=='y'){$message = $lang['_APPROVE'];}
        }
        wpcList($message);
      } else {
        $displayform = true;
      }
    }
  }
  
  if (isset($addPost) && $addPost==true){
    $displayform = false;
  }
  if ($displayform==true){
    include(dirname(__FILE__)."/includes/newAd_tpl.php");
  }
}



function wpcModifyImg() {
  global $_GET, $_POST, $userdata, $user_ID, $table_prefix, $wpdb, $quicktags, $lang, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();
  get_currentuserinfo();
  
  $postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
  //if ($postinfo->num_rows == 0)
  //  wpcIndex(404);
  $post = $postinfo[0];
  $displayform = true;
  if (isset($_POST['add_img']) && $_POST['add_img']=='yes') {
    if ($wpcSettings['must_registered_user']=='y' && !$wpClassified->is_usr_loggedin()){
      die($lang['_MUSTLOGIN']);
    } else {
      $addPost = true;
      if ($_FILES['addImage']!=''){
        $ok = (substr($_FILES['addImage']['type'],0,5)=="image")?true:false;
        if ($ok==true){
          $imginfo = @getimagesize($_FILES['addImage']['tmp_name']);
          if ($imginfo[0]>(int)$wpcSettings["image_width"]  ||
            $imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0){
            $msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
            $addPost=false;  
          } else {
            $fp = @fopen($_FILES['addImage']['tmp_name'],"r");
            $content = @fread($fp,$_FILES['addImage']['size']);
            @fclose($fp);
            $fp = @fopen( $wpClassified->public_dir . "/".(int)$user_ID."-".$_FILES['addImage']['name'],"w");
            @fwrite($fp,$content);
            @fclose($fp);
            @chmod( $wpClassified->public_dir . "/".(int)$user_ID."-".$_FILES['addImage']['name'],0777);
            $setImage = (int)$user_ID."-".$_FILES['addImage']['name'];
          }
        } else {
          $msg = "The file type is invalid";
          $addPost=false;  
        }
      } else {
        $addPost==false;
      }
      if ($addPost==true) {
        $displayform = false;
        $isSpam = wpcSpamFilter(stripslashes($_POST['wpClassified_data'][author_name]),'',
            stripslashes($_POST['wpClassified_data'][subject]),
            stripslashes($_POST['wpClassified_data'][post]),$user_ID);
        preg_replace(array('/\s/'), '', $post->image_file);
        if (!empty($post->image_file) ) {
          $array = preg_split('/###/', $post->image_file);
          $curcount = count ($array);
        }
        if ($setImage !='' && $curcount < $wpcSettings['number_of_image']) {
          if ($post->image_file !=''){
            $wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file = '". $post->image_file . "###" . $wpdb->escape(stripslashes($setImage)) . "' WHERE ads_id=$post->ads_id ");
          } else {
            $wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file ='" . $wpdb->escape(stripslashes($setImage)) . "' WHERE ads_id=$post->ads_id ");
          }
        }
        $addPost = false;
        $postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
        $post = $postinfo[0];
        include(dirname(__FILE__)."/includes/modifyImg_tpl.php");
        $displayform = false;
      } else {
        $displayform = true;
      }
    }
  }
  if (isset($addPost) && $addPost==true){
    $displayform = false;
  }
  if ($displayform==true) include(dirname(__FILE__)."/includes/modifyImg_tpl.php");
}


function wpcDeleteImg() {
  global $_GET,$_POST,$userdata,$user_ID,$table_prefix, $wpmuBaseTablePrefix, $wpdb, $wpClassified, $lang;
  $wpcSettings = get_option('wpClassified_data');
  $pageinfo = $wpClassified->get_pageinfo();
  $userfield = $wpClassified->get_user_field();
  get_currentuserinfo();

  $sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" .(int)$_GET['aid'];
  $postinfo = $wpdb->get_results($sql,ARRAY_A);
  if ($postinfo->num_rows == 0) 
    wpcIndex(404);
  $post = $postinfo[0];
  $permission=false;
  if (($wpClassified->is_usr_loggedin() && $user_ID==$post['author']) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
    $permission=true;
  }
  if (!$permission) {
    if (getenv('REMOTE_ADDR')==$post['author_ip']) $permission=true;
  }  
  if (!$permission) {
    wpcPermissionDenied();
    return;
  }

  $_link .= "&_action=di";
  if (isset($_GET['aid']))
    $_link .= "&amp;lid=" . (int)$_GET['aid'];
  if (isset($_GET[file]))
    $_link .= "&amp;file=" . $_GET[file];
  $link_del = get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]. $_link;
  if ($_POST['YesOrNo']>0){
    $postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
    $rec = $postinfo[0];
    $array = preg_split('/\#\#\#/',$rec->image_file);

    foreach($array as $f) {
      if ($f == $_GET[file]){
      } else {
        $txt .= $f . '###';
      }
    }
    $newstring = substr($txt,0,-3);
    $wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file ='" . $wpdb->escape(stripslashes($newstring)) . "' WHERE ads_id=" . $_GET['aid'] );

    $file = $wpClassified->public_dir . "/" . $_GET[file];
    if (!isset($file)) unlink($file);
    $postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
    $post = $postinfo[0];
    if (!file_exists(ABSPATH . "wp-content/plugins/wp-classified/includes/modifyImg_tpl.php")){ 
      include(dirname(__FILE__)."wp-content/plugins/wp-classified/includes/modifyImg_tpl.php");
    } else {
      include(ABSPATH . "wp-content/plugins/wp-classified/includes/modifyImg_tpl.php");
    }
  } else {
  ?>
  <h3 style= "margin:20px 0"><?php echo $lang['_CONFDEL'];?></h3>
  <form method="post" id="delete_img_conform" name="delete_img_conform" action="<?php echo $link_del;?>">
  <strong>
    <input type="hidden" name="YesOrNo" value="<?php echo $_GET['aid'];?>">
    <?php echo $lang['_DELETESURE']; ?><br />
    <p><input type=submit value="<?php echo $lang['_YES'];?>"> <input type=button value="<?php echo $lang['_NO'];?>" onclick="history.go(-1);"></p>
  </strong>
  </form>
  <?php
  return false;
  }
}


function wpcPublicLink($action,$vars){
  global $wpdb, $table_prefix, $lang, $wp_rewrite, $wpClassified;
  
  $wpcSettings = get_option('wpClassified_data');
  $pageinfo = $wpClassified->get_pageinfo();
  
  $page_id = $pageinfo['ID'];
  if($wp_rewrite->using_permalinks()) $delim = "?";
  else $delim = "&amp;";
  $perm = get_permalink($page_id);
  
  $main_link = $perm . $delim;
  
  if (!isset($vars['post_jump'])) {
    $lastAd = ($action=="lastAd")?"#lastpost":"";
  } else {
    $lastAd = ($action=="lastAd")?"#".$vars['post_jump']:"";
  }
  $action = ($action=="lastAd")?"ads_subject":$action;
  switch ($action){
    case "index":
      return "<a href=\"".$main_link."_action=classified\">".$lang['_MAIN']."</a><img class=\"imgMiddle\" border=0 src=\"".$wpClassified->plugin_url."/images/arrow.gif\">";
    break;
    case "classified":
      $main_link .= "_action=vl";
      if (isset($vars['lid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      return "<a href=\"".$main_link. "\">".$vars["name"]."</a> ";
    break;
    case "pa":
      $main_link .= "_action=pa";
      if (isset($vars['lid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      return "<a href=\"".$main_link."\">".$vars["name"]."</a> ";
    break;
    case "paform":
      $main_link .= "_action=pa";
      if (isset($vars['lid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      return $main_link;
    break;
    case "ads_subject":
      $main_link .= "_action=va";
      if (isset($vars['lid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      if (isset($vars['asid']))
        $main_link .= "&amp;asid=" . (int)$vars['asid'];
      return "<a href=\"".$main_link.$lastAd."\">".$vars['name']."</a>";
    break;
    case "ea":
      $main_link .= "_action=ea";
      if (isset($vars['lid']))
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      if (isset($vars['asid']))
        $main_link .= "&amp;asid=" . (int)$vars['asid'];
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return "<a href=\"".$main_link."\">".$vars['name']."</a> ";
    break;
    case "da":
      $main_link .= "_action=da";
      if (isset($vars['lid']))
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      if (isset($vars['asid']))
        $main_link .= "&amp;asid=" . (int)$vars['asid'];
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return "<a href=\"".$main_link."\">".$vars['name']."</a> ";
    break;
    case "eaform":
      $main_link .= "_action=ea";
      if (isset($vars['lid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;lid=" . (int)$vars['lid'];
      if (isset($vars['asid']))
        $main_link .= "&amp;asid=" . (int)$vars['asid'];
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return $main_link;
    break;
    case "sndform":
      $main_link .= "_action=sndad";
      if (isset($vars['aid']) && (int)$vars['lid'] > 0)
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return $main_link;
    break;
    case "searchform":
      return $main_link."_action=search";
    break;
    case "mi": //modify Images
      $main_link .= "_action=mi";
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return "<a href=\"".$main_link."\">".$vars["name"]."</a> ";
    break;
    case "miform":
      $main_link .= "_action=mi";
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      return $main_link;
    break;
    case "di": //delete Images
      if (isset($vars['aid']))
        $main_link .= "&amp;aid=" . (int)$vars['aid'];
      if (isset($vars['file']))
        $main_link .= "&amp;file=" . $vars['file'];
      return "<a href=\"".$main_link."\">".$vars["name"]."</a> ";
    break;
  }
}


function wpcPermissionDenied(){
  global $lang;
  echo $lang['_SORRY'];
  return;
}

function wpcAdAuthor($ad){
  global $wpClassified;
  $userfield = $wpClassified->get_user_field();
  $out = "";
  if ($ad->author==0){
    $out .= $ad->author_name;
  } else {
    $out .= $ad->$userfield;
  }
  return $out;
}

function wpcPostAuthor($post){
  global $wpClassified;

  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();
  $out = "";
  if ($post->author==0){
    $out .= $post->author_name."(guest)";
    if ($wpcSettings['display_unregistered_ip']=='y'){
      $out .= "-".$wpClassified->last_octet($post->author_ip);
    }
    $out .= "";
  } else {
    $out .= $post->$userfield;
  }
  return $out;
}

function wpcUpdatePostCount($id){
  global $table_prefix,$wpdb;
  if ($id*1==0)return;
  $test = $wpdb->get_var("SELECT user_info_user_ID FROM {$table_prefix}wpClassified_user_info WHERE user_info_user_ID = '".$id."'");
  if ($test>0){
    $wpdb->query("UPDATE {$table_prefix}wpClassified_user_info SET user_info_post_count = user_info_post_count+1 WHERE user_info_user_ID = '".$id."'");
  } else {
    $wpdb->query("INSERT INTO {$table_prefix}wpClassified_user_info (user_info_user_ID,user_info_post_count) values ('".$id."','1')");
  }
}

function wpcCommmentQuote($post){
  $wpcSettings = get_option('wpClassified_data');
  $txt = $post->post;
  $txt = nl2br($txt);
  $wpClassified_ads_charset = get_option('blog_charset');
  $txt = addslashes(htmlspecialchars($txt,ENT_COMPAT,$wpClassified_ads_charset));    
  $txt = str_replace(chr(13),"",$txt);
  $txt = str_replace(chr(10),"",$txt);
  $txt = str_replace("&lt;br /&gt;","",$txt);
  $txt = str_replace("&lt;br&gt;","",$txt);
  $txt = str_replace("&lt;","<",$txt);
  $txt = str_replace("&lt;","<",$txt);
  $txt = str_replace("&gt;",">",$txt);
  $txt = str_replace("&gt;",">",$txt);
  $txt = str_replace("&gt;",">",$txt);
  $txt = str_replace("&","&",$txt);
  if ($wpcSettings["edit_style"]=="plain"){
    $txt = str_replace("<p>","",$txt);
    $txt = str_replace("</p>","\r",$txt);
  }
  if ($wpcSettings["edit_style"]=="bbcode"){
    $txt = str_replace("<p>","",$txt);
    $txt = str_replace("</p>","\r",$txt);
  }
    if ($wpcSettings["edit_style"]=="html"){
    $txt = str_replace("<p>","",$txt);
    $txt = str_replace("</p>","\r",$txt);
  }
    if ($wpcSettings["edit_style"]=="quicktags"){
    $txt = str_replace("<p>","",$txt);
    $txt = str_replace("</p>","\r",$txt);
  }
  $txt = trim($txt);
  $txt = preg_replace("/ *\n */","\n",$txt);
  $txt = preg_replace("/\s{3,}/","\n\n",$txt);
  $txt = str_replace("\n","\\n",$txt);
  return $txt;
}


# EMAIL ROUTINE 
function wpcSendEmail($mailto,$mailsubject,$mailtext) {
  global $lang;
  $email_sent = array();
  $email = wp_mail($mailto,$mailsubject,$mailtext);
  if ($email == false) {
    $email_sent[0] = false;
    $email_sent[1] = $lang['_SENDNOTFAIL'];
  } else {
    $email_sent[0] = true;
    $email_sent[1] = $lang['_SENDNOT'];
  }
  return $email_sent;
}


# NOTIFICATION EMAILS 
function wpcEmailNotifications($userId, $author_name, $listId, $subject, $post, $image,$asid, $web='') {
  global $_GET, $_POST, $wpdb, $table_prefix, $lang, $PHP_SELF, $wpClassified;
  $pageinfo = $wpClassified->get_pageinfo();
  $wpcSettings = get_option('wpClassified_data');
  $lst = $wpdb->get_results("SELECT name FROM {$table_prefix}wpClassified_lists WHERE lists_id=" .$listId);
  $listName = $lst[0]->name;
  
  $out = '';
  $eol = "\r\n";
  
  # notify admin?
  if ( ($wpcSettings["notify"]) ) {
    $msg='';
    # clean up the content for the plain text email
    $post_content = html_entity_decode($post,ENT_QUOTES);
    $post_content = wpcFilterContent($post_content,'');
    $post_content = wpcFilterHtml($post_content);
    $post_content = stripslashes($post_content);
    # admin message

    $msg .= ("This e-mail has been sent to notify you that an Ad has been posted at your site '" .get_option('blogname'). "',and is pending review/approval.");
    $msg .= $eol . $eol . "The Ad and user information is as follows: " . $eol;
    $msg.= $lang['_FROM'] . ': ' . $author_name . $eol;
    $msg.= 'Title: ' . $subject . $eol;
    $msg.= $lang['_LIST'] . ': ' . $listName . $eol;
    $msg.= 'WebSite: ' . $web.$eol.$eol;
    $msg.= $lang['_CLASSIFIED_AD'] . ': ' .$eol.$post_content.$eol.$eol;
    $Vlnk = get_bloginfo('wpurl'). "/?page_id=" . $pageinfo["ID"] . "&_action=va&lid=" . $listId . "&asid=". $asid;
    $Alnk = get_bloginfo('wpurl'). "/wp-admin/admin.php?page=wpcModify&adm_arg=&lid=" . $listId;
    if ($wpcSettings['approve']=='y'){
      $msg.= $eol .  "Approve: " . $Alnk;
    } else {
      $msg.= $eol .  "Visit: " . $Vlnk;
    }

    $msg.= $eol . $lang['_VIEWALLADS'] . ': '. get_bloginfo('wpurl'). '/index.php?pagename=classified';

    $adminStruct = get_userdata($ADMINID);
    $email_status = wpcSendEmail(get_option('admin_email'),get_bloginfo('name') . ': ' . $lang['_NEWPOST'],$msg);
    return $email_status;
  }
  return $out;
}


function wpcCheckLength($data,$max){
  $len = strlen($data);
  $rmax = true;
  if($len > $max) $rmax = false;
  return $rmax;
}

function wpcCheckInput($input){ 
  if (!wpcCheckLength($input,30)) return false;
  if (preg_match('/[^A-Za-z0-9]/',$input)) {
    return true;
  } 
  return false;
}


function wpcPregtrim($str) {
   return preg_replace("/[^\x20-\xFF]/","",@strval($str));
}

function wpcCheckUrl($url) {
   $url=trim(wpcPregtrim($url));
   if (strlen($url)==0) return 1;
   if (!preg_match("~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}".
     "(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|".
     "org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{2})|(?!0)(?:(?".
     "!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&".
     "?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i",$url,$ok)) return false;
   if (!strstr($url,"://")) $url="http://".$url;
   $url=preg_replace("~^[a-z]+~ie","strtolower('\\0')",$url);
   preg_replace("~^[a-z]+~ie","strtolower('\\0')",$url);
   return $url;
}


function wpcLastAdSubject(){
  global $wpdb, $table_prefix, $wpmuBaseTablePrefix, $wpClassified;
  $wpcSettings = get_option('wpClassified_data');
  $userfield = $wpClassified->get_user_field();

  $ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$wpmuBaseTablePrefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
       LEFT JOIN {$wpmuBaseTablePrefix}users
       ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
       LEFT JOIN {$wpmuBaseTablePrefix}users AS lu
       ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
       WHERE {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
       ORDER BY {$table_prefix}wpClassified_ads_subjects.date DESC
       LIMIT 0, ".((int)$wpcSettings['last_ads_subject_num'])." ");

  $htmlout = "<ul>";
  if (is_array($ads)){
    foreach ($ads as $ad){  
      $pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$ad->ads_subjects_id."'", ARRAY_A);

      $pstart = $pstart['count']/$wpcSettings['count_ads_per_page'];
      $pstart = (ceil($pstart)*$wpcSettings['count_ads_per_page'])-$wpcSettings['count_ads_per_page'];

      $name = $wpdb->get_row("SELECT name FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".$ad->ads_subjects_list_id."'", ARRAY_A);

      $htmlout .= "<li>".wpcPublicLink("lastAd", array(
          "name" => $ad->subject,
          "lid"=> $ad->ads_subjects_list_id,
          "name" => $name['name'],
          "asid"=> $ad->ads_subjects_id,
          "start" => $pstart,
      ));
      if ($wpcSettings['last_ads_subjects_author']=='y'){
        $wpcSettings['description'] = '';
        if   ($ad->last_author>0){
          $htmlout .= "<br />".$ad->lastuser;
        } else {
          $htmlout .= "<br />".rawurldecode($ad->last_author_name)." (Guest)";
        }
      }
      $htmlout .= "</li>";
    }
  }
  $htmlout .= "</ul>";
  return $htmlout;
}


// newadd.tpl
// echo htmlentities($content,ENT_NOQUOTES,get_bloginfo('charset'));
// function that echo's the textarea/whatever for post input 
function wpcAdInput($content=""){
  global $wpdb, $table_prefix, $wp_filesystem, $lang;
  $wpcSettings = get_option('wpClassified_data');
  echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/includes/js/jquery.limit.js"></script>';
  ?>
<script type='text/javascript'>
/* <![CDATA[ */
var intMaxLength="<?php echo $wpcSettings['maxchars_limit'] ?>";
$(document).ready(function() {
  $('#description').keyup(function() {
    var len = this.value.length;
    if (len >= intMaxLength) {
    this.value = this.value.substring(0, intMaxLength);
    }
    $('#charLeft').text(len);
  });
});
/* ]]> */

</script>
  <?php
  switch ($wpcSettings['edit_style']){
    case "plain":
      default:
         echo "<tr><td class='wpc_label_right'>" . $lang['_DESC'] . "</td><td>";
      echo "<textarea name='description' id='description' cols='50' rows='20'>".str_replace("<", "&lt;", $content)."</textarea><br />";
      echo '<span class ="smallTxt" id="msgCounter">(<span id="charLeft"> </span>&nbsp;' . $lang['_CHARS_LEFT'] . '). ' . $lang['_CHAR_MAX_OF'] . $wpcSettings['maxchars_limit'] . ' ' . $lang['CHAR_MAX_ALLOWED'];'</SPAN><BR/></td></tr>'; 
    break;
    case "tinymce":
      //echo '<textarea class="theEditor" name="description" id="description" rows="8" cols="50">'. htmlentities($content) .'</textarea><br />';
      //echo '<SPAN class="smallTxt" id="msgCounter">Maximum of ' . $wpcSettings['maxchars_limit'] . ' characters allowed</SPAN><BR/>';    
      ?>
         <tr>
      <td class="wpc_label_right"><?php echo $lang['_DESC']; ?></td>
      <td class="wpc_label_left"><textarea id="description" name="description" style="width:320px; height: 200px;"><?php echo $content; // htmlentities($content) ?></textarea>
         <span class ="smallTxt" id="msgCounter">(<span id="charLeft"></span>&nbsp; <?php echo $lang['_CHARS_LEFT'] . '). ' . $lang['_CHAR_MAX_OF'] . $wpcSettings['maxchars_limit'] . ' ' . $lang['_CHAR_MAX_ALLOWED'];?></SPAN><BR/>
         </td>
         </tr>
         <tr>
         <td class="wpc_label_right"><?php echo $lang['_PREVIEW'];?>: </td>
         <td class="wpc_label_left"><div id="preview" style="width: 320px; height: 200px; border:#c7ceeb 1px solid; padding: 3px"></div></td>
         </tr>
         <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
         <script src='<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/js/jquery.bbcode.js' type='text/javascript'></script>
         <script type="text/javascript">
            $(document).ready(function(){
            $("#description").bbcode(
            {tag_bold:true,tag_italic:true,tag_underline:true,tag_h3:true,button_image:true,image_url:'<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/js/bbimage/'}
            );
            process();
            });
            var bbcode="";
            function process(){
            if (bbcode != $("#description").val()){
            bbcode = $("#description").val();
            $.get('<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/js/bbParser.php',{bbcode: bbcode},
            function(txt){$("#preview").html(txt);})}
            setTimeout("process()", 2000);
            }
         </script>
      <?php
    break;
  }
}


function wpcSpamFilter($name, $email, $subject, $post, $userID){
  global $ksd_api_host, $ksd_api_port;

  $spamcheck = array(
    "user_ip"=> $_SERVER['REMOTE_ADDR'],
    "user_agent"=> $_SERVER['HTTP_USER_AGENT'],
    "referrer"=> $_SERVER['HTTP_REFERER'],
    "blog"=> get_option('home'),
    "comment_author"=> rawurlencode($name),
    "comment_author_email"=> rawurlencode($email),
    "comment_author_url"=> "http://",
    "comment_content"=> str_replace("%20", "+", rawurlencode($subject))."+".str_replace("%20", "+", rawurlencode($post)),
    "comment_type"=> "",
    "user_ID"=> $userID
    );

  $query_string = '';
  foreach ($spamcheck as $k=>$v){
    $query_string .= $k.'='.urlencode(stripslashes($v)).'&';
  }

  // into akismet's spam protection
  if (function_exists('ksd_http_post')){
    $response = ksd_http_post($query_string, $ksd_api_host, '/1.1/comment-check', $ksd_api_port);
    if ('true' == $response[1]){
      return true;
    }
  } 
  return false;
}


// TODO
function getTheHtml($str){
  $bb[] = "#\[b\](.*?)\[/b\]#si";
  $html[] = "<b>\\1</b>";
  $bb[] = "#\[i\](.*?)\[/i\]#si";
  $html[] = "<i>\\1</i>";
  $bb[] = "#\[u\](.*?)\[/u\]#si";
  $html[] = "<u>\\1</u>";
  $bb[] = "#\[h3\](.*?)\[/h3\]#si";
  $html[] = "<h3>\\1</h3>";
  $bb[] = "#\[hr\]#si";
  $html[] = "<hr>";
  $str = preg_replace ($bb, $html, $str);
  $patern="#\[url href=([^\]]*)\]([^\[]*)\[/url\]#i";
  $replace=''; // <a href="\\1" target="_blank" rel="nofollow">\\2</a>
  $str=preg_replace($patern, $replace, $str); 
  $patern="#\[img\]([^\[]*)\[/img\]#i";
  $replace=''; // <img src="\\1" alt=""/>
  $str=preg_replace($patern, $replace, $str);  
  $str=nl2br($str);
  return $str;
}

?>
