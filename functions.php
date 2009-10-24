<?php

/*
* $Id: *
* functions.php
*
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* Licence Type   : GPL
* @version 1.3.1-a
*/

require_once('captcha_class.php');

function _add_ad(){
	global $_GET,$_POST,$userdata,$user_ID,$table_prefix,$wpdb,$quicktags,$lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lid'])."'",ARRAY_A);
	$displayform = true;
	if (isset($_POST['add_ad']) && $_POST['add_ad']=='yes') {
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()) {
			die($lang['_MUSTLOGIN']);
		} else {
			$addPost = true;
			if (!$_POST['wpClassified_data']['term']) {
				$msg = $lang['_INVALIDTERM'];
				$addPost = false;
			}
			if (str_replace(" ","",$_POST['wpClassified_data']['author_name'])=='' && !_is_usr_loggedin()){
				$msg = $lang['_INVALIDNAME'];
				$addPost = false;
			}
			if (str_replace(" ","",$_POST['wpClassified_data']['subject'])==''){
				$msg = $lang['_INVALIDSUBJECT'];
				$addPost = false;
			}
			if (!isset($_POST['wpClassified_data']['email']) ||
				str_replace(" ","",$_POST['wpClassified_data']['email'])==''){
				$msg = $lang['_INVALIDEMAIL'];
				$addPost = false;
			}
			if (isset($_POST['wpClassified_data']['email']) &&
				!preg_match('/^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$/',
				$_POST['wpClassified_data']['email'])) {
				$msg = $lang['_INVALIDEMAIL'];
				$addPost = false;
			}
			if (isset($_POST['wpClassified_data']['web'])) {
				if (!checkUrl($_POST['wpClassified_data']['web'])){
					$msg = $lang['_INVALIDURL'];
					$addPost = false;
				}
			}
			if (isset($_POST['wpClassified_data']['phone'])) {
				if (!validate_phone($_POST['wpClassified_data']['phone'])) {
					$msg = $lang['_INVALIDPHONE'];
					$addPost = false;
				}
			}
			$_POST['wpClassified_data']['subject'] = preg_replace("/(\<)(.*?)(\>)/mi","",$_POST['wpClassified_data']['subject']);
			if (! checkInput($_POST['wpClassified_data']['subject'])){
				$msg = $lang['_INVALIDTITLE'];
			}
			if(isset($wpcSettings['confirmation_code']) && $wpcSettings['confirmation_code']=='y'){ 
				if (! _captcha::Validate($_POST['wpClassified_data']['confirmCode'])) {
   					$msg = $lang['_INVALIDCONFIRM'];
					$addPost = false;
  				}
			}
			if (!isset($_POST['wpClassified_data']['post']) ||
				str_replace(" ","",$_POST['wpClassified_data']['post'])==''){
				$msg = $lang['_INVALIDCOMMENT'];
				$addPost = false;
			}
			if (isset($_POST['wpClassified_data']['count_ads_max']) && 
				$_POST['wpClassified_data']['count_ads_max'] > $wpcSettings['count_ads_max_limit']){
				$msg = "Classified Text must be less than or equal to ". $wpcSettings['count_ads_max_limit'] . " characters in length";
				$addPost = false;
			}
			if ($_FILES['image_file']!=''){
				$ok = (substr($_FILES['image_file']['type'],0,5)=="image")?true:false;
				if ($ok==true){
					$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
					if ($imginfo[0] && 
						($imginfo[0]>(int)$wpcSettings["image_width"] ||
						$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0))	{
						$msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
						$addPost=false;
					} else {
						$fp = @fopen($_FILES['image_file']['tmp_name'],"r");
						$content = @fread($fp,$_FILES['image_file']['size']);
						@fclose($fp);
						$fp = @fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$user_ID."-".$_FILES['image_file']['name'],"w");
						@fwrite($fp,$content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$user_ID."-".$_FILES['image_file']['name'],0777);
						$setImage = (int)$user_ID."-".$_FILES['image_file']['name'];
					}
				}
			} else {
				$addPost==false;
			}
			if ($addPost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data'][author_name]),'',stripslashes($_POST['wpClassified_data'][subject]),stripslashes($_POST['wpClassified_data'][post]),$user_ID);
				$web = $_POST['wpClassified_data'][web];
				if ($wpcSettings['approve']=='y'){
					$status = 'inactive';
				} else {$status = 'active';}
				$sql = "SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE author_ip = '" . getenv('REMOTE_ADDR') . "' AND subject = '" . $wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."'";
				$checkPost = $wpdb->get_results($sql);
				if ( $checkPost[0]->count > 0){
					$message = $lang['_APPROVEREPLY'];
				} else {
	$sql = "INSERT INTO {$table_prefix}wpClassified_ads_subjects
	(ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip, web, phone, txt, email) VALUES
	('".($_GET['lid']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][author_name]))."' , '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."' , 0, 0 , 'n' , '".(($isSpam)?"deleted":"open")."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][author_name]))."', '".getenv('REMOTE_ADDR')."',
	'".$web."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."',	'".(int)$wpdb->escape(stripslashes($_POST['wpClassified_data'][adExpire])).'###'.$wpdb->escape(stripslashes($_POST['wpClassified_data'][contactBy]))."','".$wpdb->escape(stripslashes($_POST['wpClassified_data'][email]))."')";
					$wpdb->query($sql);
					$tid = $wpdb->get_var("SELECT last_insert_id()");
	$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads
	(ads_ads_subjects_id, date, author, author_name, author_ip, status, subject, image_file, post) VALUES
	('".$tid."', '".time()."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][author_name]))."', 
	'".getenv('REMOTE_ADDR')."', '" . $status . "', 
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',
	'".$wpdb->escape(stripslashes($setImage))."',
	'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][post]))."')");
					do_action('wpClassified_new_ads',$tid);
					$out = _email_notifications($user_ID,$_POST['wpClassified_data'][author_name],
					$_GET['lid'],$_POST['wpClassified_data'][subject],$_POST['wpClassified_data'][post],$setImage,$tid);
					$pid = $wpdb->get_var("select last_insert_id()");
					if (!$isSpam){
						update_user_post_count($user_ID);
						update_ads($_GET['lid']);
					}
					$_GET['asid'] = $tid;
					$message = $lang['_SAVEADINFO']."<br>".$lang['_THANKS'];
					if ($wpcSettings['approve']=='y'){$message = $lang['_APPROVE'];}
				}
				get_wpc_list($message);
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


function _modify_img() {
	global $_GET,$_POST,$userdata,$user_ID,$table_prefix,$wpdb,$quicktags,$lang;
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
						$fp = @fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$user_ID."-".$_FILES['addImage']['name'],"w");
						@fwrite($fp,$content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$user_ID."-".$_FILES['addImage']['name'],0777);
						$setImage = (int)$user_ID."-".$_FILES['addImage']['name'];
					}
				} else {
					$msg = "The file type is invalid";
					$addPost=false;	
				}
			} else {
				$addPost==false;
			}
			if ($addPost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data'][author_name]),'',stripslashes($_POST['wpClassified_data'][subject]),stripslashes($_POST['wpClassified_data'][post]),$user_ID);
				$array = preg_split('/\#\#\#/',$post->image_file);
				$curcount = count ($array);
				if ( $setImage !='' && $curcount < $wpcSettings['number_of_image']) {
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
				$displayform = false;
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
	global $_GET,$_POST,$userdata,$user_ID,$table_prefix,$wpmuBaseTablePrefix,$wpdb,$lang;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	$userfield = get_wpc_user_field();
	get_currentuserinfo();

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" .(int)$_GET['aid'];
	 $postinfo = $wpdb->get_results($sql,ARRAY_A);

	$post = $postinfo[0];
	$permission=false;
	if ((_is_usr_loggedin() && $user_ID==$post['author']) || _is_usr_admin() || _is_usr_mod()){
		$permission=true;
        }
	if (!$permission) {
		if (getenv('REMOTE_ADDR')==$post['author_ip']) $permission=true;
	}	
	if (!$permission) {
		wpClassified_permission_denied();
		return;
	}

	$link_del = get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=di&aid=".$_GET['aid']. "&file=".$_GET[file];
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

		$file = ABSPATH."wp-content/plugins/wp-classified/images/" . $_GET[file];
		unlink($file);
		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
		$post = $postinfo[0];
		if (!file_exists(ABSPATH . INC . "/modifyImg_tpl.php")){ 
			include(dirname(__FILE__)."/includes/modifyImg_tpl.php");
		} else {
			include(ABSPATH . INC . "/modifyImg_tpl.php");
		}
	} else {
	?>
	<h3><?php echo $lang['_CONFDEL'];?></h3>
	<form method="post" id="delete_img_conform" name="delete_img_conform" action="<?php echo $link_del;?>">
	<strong>
		<input type="hidden" name="YesOrNo" value="<?php echo $_GET['aid'];?>">
		<?php echo $lang['_DELETESURE']; ?><br />
		<input type=submit value="<?php echo $lang['_YES'];?>"> <input type=button value="<?php echo $lang['_NO'];?>" onclick="history.go(-1);">
	</strong>
	</form>
	<?php
	return false;
	}
}


function create_public_link($action,$vars){
	global $wpdb,$table_prefix,$lang;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	if (!isset($vars['post_jump'])) {
		$lastAd = ($action=="lastAd")?"#lastpost":"";
	} else {
		$lastAd = ($action=="lastAd")?"#".$vars['post_jump']:"";
	}
	$action = ($action=="lastAd")?"ads_subject":$action;
	switch ($action){
		case "index":
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=classified\">" . $lang['_MAIN'] . "</a><img src=\"" .get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/topic/arrow.gif\" class=\"imgMiddle\">";
		break;
		case "classified":
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=vl&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
		break;
		case "pa":
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
		break;
		case "paform":
			return get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid'];
		break;
		case "ads_subject":			
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&lid=".$vars['lid']."&asid=".$vars['asid'].$lastAd."\">".$vars['name']."</a>";
		break;
		case "ea":
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid'])."\">".$vars['name']."</a> ";
		break;
		case "da":
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=da&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid'])."\">".$vars['name']."</a> ";
		break;
		case "eaform":
			return 
			get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid']);
		break;
		case "searchform":
			return get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=search";
		break;
		case "mi": //modify Images
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=mi&aid=".$vars['aid']."\">".$vars["name"]."</a> ";
		break;
		case "miform":
			return get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=mi&aid="."&aid=".((int)$vars['aid']);
		break;
		case "di": //delete Images
			return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=di&aid=".$vars['aid']."&file=".$vars["file"]."\">".$vars["name"]."</a> ";
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
	global $table_prefix,$wpdb;
	if ($id*1==0)return;
	$test = $wpdb->get_var("SELECT user_info_user_ID FROM {$table_prefix}wpClassified_user_info WHERE user_info_user_ID = '".$id."'");
	if ($test>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_user_info SET user_info_post_count = user_info_post_count+1 WHERE user_info_user_ID = '".$id."'");
	} else {
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_user_info (user_info_user_ID,user_info_post_count) values ('".$id."','1')");
	}
}

function wpClassified_commment_quote($post){
	$wpcSettings = get_option('wpClassified_data');
	$txt = $post->post;
	$txt = nl2br($txt);
	$wpClassified_ads_charset = get_option('blog_charset');
	$txt = addslashes(htmlspecialchars($txt,ENT_COMPAT,$wpClassified_ads_charset));		
	$txt = str_replace(chr(13),"",$txt);
	$txt = str_replace(chr(10),"",$txt);
	$txt = str_replace("&lt;br /&gt;","\n",$txt);
	$txt = str_replace("&lt;","<",$txt);
	$txt = str_replace("&lt;","<",$txt);
	$txt = str_replace("&gt;",">",$txt);
	$txt = str_replace("&gt;",">",$txt);
	$txt = str_replace("&gt;",">",$txt);
	$txt = str_replace("&","&",$txt);
	if ($wpcSettings["wpc_edit_style"]=="plain"){
		$txt = str_replace("<p>","",$txt);
		$txt = str_replace("</p>","\r",$txt);
	}
	if ($wpcSettings["wpc_edit_style"]=="bbcode"){
		$txt = str_replace("<p>","",$txt);
		$txt = str_replace("</p>","\r",$txt);
	}
		if ($wpcSettings["wpc_edit_style"]=="html"){
		$txt = str_replace("<p>","",$txt);
		$txt = str_replace("</p>","\r",$txt);
	}
		if ($wpcSettings["wpc_edit_style"]=="quicktags"){
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
function _send_email($mailto,$mailsubject,$mailtext) {
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
function _email_notifications($userId,$author_name,$listId,$subject,$post,$image,$asid) {
	global $_GET,$_POST,$wpdb,$table_prefix,$lang,$PHP_SELF;
	$pageinfo = get_wpClassified_pageinfo();
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
		$post_content = _filter_content($post_content,'');
		$post_content = _filter_nohtml_kses($post_content);
		$post_content = stripslashes($post_content);
		# admin message

		$msg .= ("This e-mail has been sent to notify you that an Ad has been posted at your site '" .get_option('blogname'). "',and is pending review/approval.");
		$msg .= $eol . $eol . "The Ad and user information is as follows: " . $eol;
		$msg.= $lang['_FROM'] . ': ' . $author_name . $eol;
		$msg.= 'Title: ' . $subject . $eol;
		$msg.= $lang['_LIST'] . ': ' . $listName . $eol;
		$msg.= 'WebSite: ' . $url.$eol.$eol;
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
		$email_status = _send_email(get_option('admin_email'),get_bloginfo('name') . ': ' . $lang['_NEWPOST'],$msg);
		return $email_status;
	}
	return $out;
}

function checkInput($input){ 
	if (!checkLength($input,30)) return false;
	if (preg_match('/[^A-Za-z0-9]/',$input)) {
		return true;
	} 
	return false;
}

/*
function checkUrl($url)	{
	if (!checkLength($url,30)) return false;
	$res = (($ftest = @fopen($url,‘r’)) === false) ? false : @fclose($ftest);
	return ($res == TRUE) ? 1:0 ;
	$online = exec("ping $url -c 1");  
	if (eregi("unbekannter host",$online) || eregi("unknown host",$online)) {
		$res == FLASE
	} else {
		$res == TRUE
   	}
}
*/

function pregtrim($str) {
   return preg_replace("/[^\x20-\xFF]/","",@strval($str));
}
function checkUrl($url) {
   global $_POST, $PHP_SELF;
   $url=trim(pregtrim($url));
   if (strlen($url)==0) return 1;
   if (!preg_match("~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}".
	   "(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|".
	   "org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{2})|(?!0)(?:(?".
	   "!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&".
	   "?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i",$url,$ok)) return false;
   if (!strstr($url,"://")) $url="http://".$url;
   $url=preg_replace("~^[a-z]+~ie","strtolower('\\0')",$url);
   preg_replace("~^[a-z]+~ie","strtolower('\\0')",$url);
   $_POST['wpClassified_data'][web]=$url;
   return true;
}



function checkLength($data,$max){
	$len = strlen($data);
	$rmax = true;
	if($len > $max) $rmax = false;
	return $rmax;
}


?>
