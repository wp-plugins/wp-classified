<?php

/**
 * functions.php
 *
 **/


function add_ads_subject(){
	global $_GET, $_POST, $userdata, $wpc_user_info, $user_ID, $table_prefix, $wpdb, $quicktags;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();

	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lid'])."'", ARRAY_A);

	$displayform = true;

	if ($_POST['add_ads_subject']=='yes'){
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
			die("You can't post without logging in.");
		} else {
			$addPost = true;

			if (str_replace(" ", "", $_POST['wpClassified_data'][author_name])=='' && !_is_usr_loggedin()){
				$msg = "You must provide a posting name!";
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][subject])==''){
				$msg = "You must provide a subject!";
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][email])==''){
				$msg = "You must provide a e-mail!";
				$addPost = false;
			}

			if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$", $_POST['wpClassified_data'][email])){
				$msg = "Please enter a valid e-mail!";
				$addPost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data'][post])==''){
				$msg = "You must provide a comment!";
				$addPost = false;
			}

			if ($_FILES['image_file']!=''){
				$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
				if ($ok==true){
					$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
					if ($imginfo[0]>(int)$wpcSettings["image_width"]  ||
						$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0){
						 echo "<h2>Invalid image size. Image must be ".(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]." pixels or less. Your image was: ".$imginfo[0]."x".$imginfo[1] . "</h2>";
						$addPost=false;	
					} else {
						$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
						$content = @fread($fp, $_FILES['image_file']['size']);
						@fclose($fp);
						$fp = fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
						@fwrite($fp, $content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
						$setImage = (int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'];
					}
				}
			}
			if ($addPost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data']['author_name']), '', stripslashes($_POST['wpClassified_data'][subject]), stripslashes($_POST['wpClassified_data']['post']), $user_ID);

				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads_subjects
					(ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip, email) VALUES
					('".($_GET['lid']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."' , '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."' , 0, 0 , 'n' , '".(($isSpam)?"deleted":"open")."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', '".getenv('REMOTE_ADDR')."',
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][web]))."',
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."',
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data'][email]))."')");

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
				get_wpc_list("New AD Saved ".$out);
			} else {
				$displayform = true;
			}
		}
	}

	if ($displayform==true){
		wpc_header();
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
			?>
			<br><br><?php echo __("Sorry, you must be registered and logged in to post in these classifieds.");?><br><br>
			<a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo __("Register Here");?></a><br><br>- <?php echo __("OR");?> -<br><br>
			<a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("Login Here");?></a>
			<?php
		} else {
			echo $quicktags;
			?>
			<?php
			if ($msg){echo "<h3>".__($msg)."</h3>";}
			?>
			<table width=100% class="editform">
			<form method="post" id="ead_form" name="ead_form" enctype="multipart/form-data"
			onsubmit="this.sub.disabled=true;this.sub.value='Posting Ads...';" action="<?php echo create_public_link("paForm", array("lid"=>$_GET['lid'], "name"=>$lists["name"]));?>">
			<input type="hidden" name="add_ads_subject" value="yes">
<tr>
<td align=right valign=top><?php echo __("Posting Name:");?> </td>
<td><?php
if (!_is_usr_loggedin()){
?>
<input type=text size=15 name="wpClassified_data[author_name]" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['author_name']));?>"><br>
(<?php echo __("You are not logged in and posting as a guest, click");?> <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("here");?></a> <?php echo __("to log in");?>.)
<?php
} else {
echo "<b>".$userdata->$userfield."</b>";
} 
?></td>
</tr><tr>
<td align=right valign=top><?php echo __("Ad Header:");?> </td>
<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data'][subject]));?>"></td></tr>
<tr>
<td align=right valign=top><?php echo __("Image File: ");?></td>
<td><input type=file name="image_file"><br /><small><?php echo __("Picture should be less than (".(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"] . " pixel ");?>)</small></td></tr>
<tr>
<tr>
<td align=right><?php echo __("Email:");?> </td>
<td><input type=text size=30 name="wpClassified_data[email]" id="wpClassified_data_email" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['email']));?>"></td></tr>
<td align=right><?php echo __("Website:");?> </td>
<td><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['web']));?>"><small><?php echo __("Optional")?></small></td></tr>
<td align=right><?php echo __("Phone:");?> </td>
<td><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['phone']));?>"><small><?php echo __("Optional")?></small></td></tr>
<td valign=top align=right><?php echo __("Ad Description:");?> </td>
<td><?php create_ads_input($_POST['wpClassified_data']['post']); ?></td>
</tr>
<tr>
<td></td>
<td><input type=submit value="<?php echo __("Post the Ad");?>" id="sub"></td>
</tr>
</form>
</table>

<?php
	}
		wpc_footer();
	}
}


function create_public_link($action, $vars){
	global $wpdb, $table_prefix, $wp_rewrite;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	// fix me
	//$rewrite = ($wp_rewrite->get_page_permastruct()=="")?false:true;
	$starts = (((int)$vars["start"])?"(".$vars["start"].")/":"");
	if (!$vars['post_jump']) {
		$lastAds = ($action=="lastAds")?"#lastpost":"";
	} else {
		$lastAds = ($action=="lastAds")?"#".$vars['post_jump']:"";
	}
	$action = ($action=="lastAds")?"ads_subject":$action;
	switch ($action){
		case "index":

			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=classified\">".$vars["name"]."</a> ";
		break;
		case "classified":
			
			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/vl/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".$starts."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=vl&lid=".$vars['lid']."&start=".(int)$vars['start']."\">".$vars["name"]."</a> ";
		break;
		case "pa":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/pa/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
		break;
		case "paForm":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/pa/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']:get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=pa&lid=".$vars['lid'];
		break;
		case "ads_subject":			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/va/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".$starts."?search_words=".ereg_replace("[^[:alnum:]]", "+", $vars["search_words"]).$lastAds."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&lid=".$vars['lid']."&asid=".$vars['asid']."&pstart=".((int)$vars["start"])."&search_words=".$vars['search_words'].$lastAds."\">".$vars['name']."</a>";
		break;
		case "ea":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/ea/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".((int)$vars['aid'])."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid'])."\">".$vars['name']."</a> ";
		break;
		case "eaform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/ea/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['asid']."/".((int)$vars['aid']):get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=ea&lid=".$vars['lid']."&asid=".$vars['asid']."&aid=".((int)$vars['aid']);
		break;
		case "searchform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/search/":get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=search";
		break;
	}
}


function wpClassified_permission_denied(){
	echo __("Sorry, it seems that you do not have permission to perform the requested action.");
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
		$out .= $post->author_name." (guest)";
		if ($wpcSettings['display_unregistered_ip']=='y'){
			$out .= " - ".wpClassified_last_octet($post->author_ip);
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
	$email_sent = array();
	$email = wp_mail($mailto, $mailsubject, $mailtext);
	if ($email == false) {
		$email_sent[0] = false;
		$email_sent[1] = __('Email Notification Failed', "sforum");
	} else {
		$email_sent[0] = true;
		$email_sent[1] = __('Email Notification Sent', "sforum");
	}
	return $email_sent;
}


# NOTIFICATION EMAILS 
function _email_notifications($userId, $author_name, $listId, $subject, $post, $image) {
	global $wpdb, $table_prefix;

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
		$msg.= sprintf(__('New classified post on your site %s:', "wpClassified"), get_option('blogname')).$eol.$eol;
		$msg.= sprintf(__('From: %s', "wpClassified"), $subject).$eol;
		$msg.= sprintf(__('List: %s', "wpClassified"), $listName).$eol;
		$msg.= $url.$eol.$eol;
		$msg.= __('Post:', "wpClassified").$eol.$post_content.$eol.$eol;
		$msg.= sprintf(__('There are currently %s Ad(s) in %s List(s) Awaiting Review', 'wpClassified'), $subject, $listName).$eol;
		$msg.= '<a href="'.get_bloginfo('wpurl'). '/index.php?pagename=classified">' . __('Review All Ads', 'wpClassified'). "</a>".$eol;
		$adminStruct = get_userdata($ADMINID);
		$email_sent = _send_email(get_option('admin_email'), sprintf(__('[%s] New Forum Post', "wpClassified"), get_bloginfo('name')), $msg);
		$check = $email_status[1];
		if($email_status[0] == true) {
			$out = '- '.__('Notified: Administrator', "wpClassified");
		} else {
			$out = '- '.__('Not Notified', "wpClassified");
			return $check;
		}
	}
	return $out;
}

?>
