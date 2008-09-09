<?php

/*
* $Id: *
* functions.php
*
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : forgani.com
* Licence Type   : GPL
* @version 1.2.0-e
*/

require('captcha_class.php');

function _add_ad(){
	global $_GET, $_POST, $userdata, $wpc_user_info, $user_ID, $table_prefix, $wpdb, $quicktags, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lid'])."'", ARRAY_A);

	$displayform = true;

	if ($_POST['add_ad']=='yes'){
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
			die($lang['_MUSTLOGIN']);
		} else {
			$addPost = true;

			if (!$_POST['wpClassified_data'][term]){
				$msg = $lang['_INVALIDTERM'];
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][author_name])=='' && !_is_usr_loggedin()){
				$msg = $lang['_INVALIDNAME'];
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][subject])==''){
				$msg = $lang['_INVALIDSUBJECT'];
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][email])==''){
				$msg = $lang['_INVALIDEMAIL'];
				$addPost = false;
			}

			if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$", $_POST['wpClassified_data'][email])) {
				$msg = $lang['_INVALIDEMAIL'];
				$addPost = false;
			}

			if($wpcSettings['confirmation_code']=='y'){ 
				if (! _captcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   					$msg = $lang['_INVALIDCONFIRM'];
					$addPost = false;
  				}
			}
			if (str_replace(" ", "", $_POST['wpClassified_data'][post])==''){
				$msg = $lang['_INVALIDCOMMENT'];
				$addPost = false;
			}

			if ($_POST['wpClassified_data'][count_ads_max] > $wpcSettings['count_ads_max_limit']){
				$msg = "Classified Text must be less than or equal to ". $wpcSettings['count_ads_max_limit'] . " characters in length";
				$addPost = false;
			}

			if ($_FILES['image_file']!=''){
				$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
				if ($ok==true){
					$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
					if ($imginfo[0]>(int)$wpcSettings["image_width"]  ||
						$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0){
						 $msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
						$addPost=false;	
					} else {
						$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
						$content = @fread($fp, $_FILES['image_file']['size']);
						@fclose($fp);
						$fp = @fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
						@fwrite($fp, $content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
						$setImage = (int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'];
					}
				}
			} else {
				$addPost==false;
			}
			if ($addPost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data']['author_name']), '', stripslashes($_POST['wpClassified_data'][subject]), stripslashes($_POST['wpClassified_data']['post']), $user_ID);

	$sql = "INSERT INTO {$table_prefix}wpClassified_ads_subjects
	(ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip, web, phone, txt, email) VALUES
	('".($_GET['lid']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."' , '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."' , 0, 0 , 'n' , '".(($isSpam)?"deleted":"open")."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', '".getenv('REMOTE_ADDR')."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][web]))."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][adExpire])).'###'.$wpdb->escape(stripslashes($_POST['wpClassified_data'][contactBy]))."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][email]))."')";
//echo "-->" . $sql;
	$wpdb->query($sql);

	$tid = $wpdb->get_var("SELECT last_insert_id()");
	$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads
		(ads_ads_subjects_id, date, author, author_name, author_ip, status, subject, image_file, post) VALUES
		('".$tid."', '".time()."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', 
		'".getenv('REMOTE_ADDR')."', 'active', 
		'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',
		'".$wpdb->escape(stripslashes($setImage))."',
		'".$wpdb->escape(stripslashes($_POST['wpClassified_data']['post']))."')");
		do_action('wpClassified_new_ads', $tid);
		$out = _email_notifications($user_ID, $_POST['wpClassified_data']['author_name'], 
		$_GET['lid'], $_POST['wpClassified_data'][subject], $_POST['wpClassified_data']['post'], $setImage);
		echo $out;
		$pid = $wpdb->get_var("select last_insert_id()");
		if (!$isSpam){
			update_user_post_count($user_ID);
			update_ads($_GET['lid']);
		}
		$_GET['asid'] = $tid;
		get_wpc_list($lang['_SAVEADINFO'].$out."<br>".$lang['_THANKS']);
			} else {
				$displayform = true;
			}
		}
	}
	
	if ($addPost==true){
		$displayform = false;
	}
	if ($displayform==true){
		if (!file_exists(ABSPATH . INC . "/newAd_tpl.php")){ 
			include(dirname(__FILE__)."/includes/newAd_tpl.php");
		} else {
			include(ABSPATH . INC . "/newAd_tpl.php");
		}
	}
}


function _modify_img() {
	global $_GET, $_POST, $userdata, $wpc_user_info, $user_ID, $table_prefix, $wpdb, $quicktags, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();
	
	$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
	$post = $postinfo[0];
	$displayform = true;

	if ($_POST['add_img']=='yes'){
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
			die($lang['_MUSTLOGIN']);
		} else {
			$addPost = true;
			if ($_FILES['addImage']!=''){
				$ok = (substr($_FILES['addImage']['type'], 0, 5)=="image")?true:false;
				if ($ok==true){
					$imginfo = @getimagesize($_FILES['addImage']['tmp_name']);
					if ($imginfo[0]>(int)$wpcSettings["image_width"]  ||
						$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0){
						$msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
						$addPost=false;	
					} else {
						$fp = @fopen($_FILES['addImage']['tmp_name'], "r");
						$content = @fread($fp, $_FILES['addImage']['size']);
						@fclose($fp);
						$fp = @fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpc_user_info["ID"]."-".$_FILES['addImage']['name'], "w");
						@fwrite($fp, $content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$wpc_user_info["ID"]."-".$_FILES['addImage']['name'], 0777);
						$setImage = (int)$wpc_user_info["ID"]."-".$_FILES['addImage']['name'];
					}
				}
			} else {
				$addPost==false;
			}
			if ($addPost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data']['author_name']), '', stripslashes($_POST['wpClassified_data'][subject]), stripslashes($_POST['wpClassified_data']['post']), $user_ID);
				$array = split('###', $post->image_file);
				$curcount = count ($array);
				if ( $setImage !='' && $curcount < 3) {
					if  ($post->image_file !=''){
						$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file = '". $post->image_file . "###" . $wpdb->escape(stripslashes($setImage)) . "' WHERE ads_id=$post->ads_id ");
					} else {
						$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file ='" . $wpdb->escape(stripslashes($setImage)) . "' WHERE ads_id=$post->ads_id ");
					}
				} 
				$addPost = false;
				$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
				$post = $postinfo[0];
				if (!file_exists(ABSPATH . INC . "/modifyImg_tpl.php")){ 
					include(dirname(__FILE__)."/includes/modifyImg_tpl.php");
				} else {
					include(ABSPATH . INC . "/modifyImg_tpl.php");
				}
			} else {
				$displayform = true;
			}
		}
	}
	if ($addPost==true){
		$displayform = false;
	}
	if ($displayform==true){
		if (!file_exists(ABSPATH . INC . "/modifyImg_tpl.php")){ 
			include(dirname(__FILE__)."/includes/modifyImg_tpl.php");
		} else {
			include(ABSPATH . INC . "/modifyImg_tpl.php");
		}
	}
}


function _delete_img() {
	global $_GET, $_POST, $userdata, $wpc_user_info, $user_ID, $table_prefix, $wpdb, $quicktags, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();
	
	$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
	$post = $postinfo[0];
	$displayform = true;
}


function create_public_link($action, $vars){
	global $wpdb, $table_prefix, $wp_rewrite;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	// fix me
	//$rewrite = ($wp_rewrite->get_page_permastruct()=="")?false:true;
	$starts = (((int)$vars["start"])?"(".$vars["start"].")/":"");
	if (!$vars['post_jump']) {
		$lastAd = ($action=="lastAd")?"#lastpost":"";
	} else {
		$lastAd = ($action=="lastAd")?"#".$vars['post_jump']:"";
	}
	$action = ($action=="lastAd")?"ads_subject":$action;
	switch ($action){
		case "index":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=classified\">Main</a><img src=\"" .get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/topic/arrow.gif\" class=\"imgMiddle\">";
		break;
		case "classified":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/vl/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".$starts."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=vl&lid=".$vars['lid']."&start=".(int)$vars['start']."\">".$vars["name"]."</a> ";
		break;
		case "pa":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/pa/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
		break;
		case "paform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/pa/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']:get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid'];
		break;
		case "ads_subject":			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/va/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".$starts."?search_words=".ereg_replace("[^[:alnum:]]", "+", $vars["search_words"]).$lastAd."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&lid=".$vars['lid']."&asid=".$vars['asid']."&pstart=".((int)$vars["start"])."&search_words=".$vars['search_words'].$lastAd."\">".$vars['name']."</a>";
		break;
		case "ea":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/ea/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".((int)$vars['aid'])."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid'])."\">".$vars['name']."</a> ";
		break;
		case "da":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/da/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".((int)$vars['aid'])."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=da&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid'])."\">".$vars['name']."</a> ";
		break;
		case "eaform":
			return 
			($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/ea/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".((int)$vars['aid']):get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid']);
		break;
		case "searchform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/search/":get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=search";
		break;
		case "mi": //modify Images
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/mi/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['aid']."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=mi&aid=".$vars['aid']."\">".$vars["name"]."</a> ";
		break;
		case "miform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/mi/":get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=mi&aid="."&aid=".((int)$vars['aid']);
		break;
		case "di": //delete Images
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/di/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['aid']."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=di&aid=".$vars['aid']."\">".$vars["name"]."</a> ";
		break;
	}
}


function wpClassified_permission_denied(){
	global $lang;
	echo $lang['_SORRY'];
	return;

}

function create_ads_author($ad){
	$userfield = get_wpc_user_field();
	$out = "";
	if ($ad->author==0){
		$out .= $ad->author_name;
	} else {
		$out .= $ad->$userfield;
	}
	return $out;
}

function get_post_author($post){
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	$out = "";
	if ($post->author==0){
		$out .= $post->author_name."(guest)";
		if ($wpcSettings['display_unregistered_ip']=='y'){
			$out .= "-".wpClassified_last_octet($post->author_ip);
		}
		$out .= "";
	} else {
		$out .= $post->$userfield;
	}
	return $out;
}

function update_user_post_count($id){
	global $table_prefix, $wpdb;
	if ($id*1==0)return;
	$test = $wpdb->get_var("SELECT user_info_user_ID FROM {$table_prefix}wpClassified_user_info WHERE user_info_user_ID = '".$id."'");
	if ($test>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_user_info SET user_info_post_count = user_info_post_count+1 WHERE user_info_user_ID = '".$id."'");
	} else {
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_user_info (user_info_user_ID, user_info_post_count) values ('".$id."', '1')");
	}
}

function wpClassified_commment_quote($post){
	$wpcSettings = get_option('wpClassified_data');
	$txt = $post->post;
	$txt = nl2br($txt);
	$wpClassified_ads_charset = get_option('blog_charset');
	$txt = addslashes(htmlspecialchars($txt, ENT_COMPAT, $wpClassified_ads_charset));		
	$txt = str_replace(chr(13), "", $txt);
	$txt = str_replace(chr(10), "", $txt);
	$txt = str_replace("&lt;br /&gt;", "\n", $txt);
	$txt = str_replace("&lt;", "<", $txt);
	$txt = str_replace("&lt;", "<", $txt);
	$txt = str_replace("&gt;", ">", $txt);
	$txt = str_replace("&gt;", ">", $txt);
	$txt = str_replace("&gt;", ">", $txt);
	$txt = str_replace("&", "&", $txt);
	if ($wpcSettings["wpc_edit_style"]=="plain"){
		$txt = str_replace("<p>", "", $txt);
		$txt = str_replace("</p>", "\r", $txt);
	}
	if ($wpcSettings["wpc_edit_style"]=="bbcode"){
		$txt = str_replace("<p>", "", $txt);
		$txt = str_replace("</p>", "\r", $txt);
	}
		if ($wpcSettings["wpc_edit_style"]=="html"){
		$txt = str_replace("<p>", "", $txt);
		$txt = str_replace("</p>", "\r", $txt);
	}
		if ($wpcSettings["wpc_edit_style"]=="quicktags"){
		$txt = str_replace("<p>", "", $txt);
		$txt = str_replace("</p>", "\r", $txt);
	}
	$txt = trim($txt);
	$txt = preg_replace("/ *\n */", "\n", $txt);
	$txt = preg_replace("/\s{3,}/", "\n\n", $txt);
	$txt = str_replace("\n", "\\n", $txt);
	return $txt;
}



# EMAIL ROUTINE 
function _send_email($mailto, $mailsubject, $mailtext) {
	global $lang;
	$email_sent = array();
	$email = wp_mail($mailto, $mailsubject, $mailtext);
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
function _email_notifications($userId, $author_name, $listId, $subject, $post, $image) {
	global $wpdb, $table_prefix, $lang;

	$wpcSettings = get_option('wpClassified_data');
	$lst = $wpdb->get_results("SELECT name FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id=" .$listId);
	$listName = $lst[0]->name;
	
	$out = '';
	$email_status = array();
	$eol = "\r\n";
	
	# notify admin?
	if ( ($wpcSettings["notify"]) ) {
		$msg='';
		# clean up the content for the plain text email
		$post_content = html_entity_decode($post, ENT_QUOTES);
		$post_content = _filter_content($post_content, '');
		$post_content = _filter_nohtml_kses($post_content);
		$post_content = stripslashes($post_content);
		# admin message
		$msg.= sprintf(__('New classified post on your site %s:'), get_option('blogname')).$eol.$eol;
		$msg.= $lang['_FROM'] . ': ' . $subject . $eol;
		$msg.= $lang['_LIST'] . ': ' . $listName . $eol;
		$msg.= $url.$eol.$eol;
		$msg.= $lang['_CLASSIFIED_AD'].$eol.$post_content.$eol.$eol;
		$msg.= sprintf(__('There are currently %s Ad(s) in %s List(s) Awaiting Review'), $subject, $listName).$eol;
		$msg.= '<a href="'.get_bloginfo('wpurl'). '/index.php?pagename=classified">' .$lang['_VIEWALLADS']. "</a>".$eol;
		$adminStruct = get_userdata($ADMINID);
		$email_sent = _send_email(get_option('admin_email'), get_bloginfo('name') . ' ' . $lang['_NEWPOST'], $msg);
		$check = $email_status[1];
		if($email_status[0] == true) {
			$out = '- '. $lang['_NOTIFYADMIN'];
		} else {
			$out = '- '. $lang['_NOTNOTIFY'];
			return $check;
		}
	}
	return $out;
}

?>
