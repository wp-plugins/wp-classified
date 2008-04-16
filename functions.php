<?php

/**
 * functions.php
 *
 **/


function wpClassified_ads_subject(){
	global $_GET, $_POST, $user_login, $userdata, $wpClassified_user_info, $fckhtml, $user_level, 
		$user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $quicktags;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	get_currentuserinfo();

	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lists_id'])."'", ARRAY_A);

	$displayform = true;

	if ($_POST['wpClassified_ads_subject']=='yes'){
		if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
			die("You can't post without logging in.");
		} else {
			$makepost = true;

			if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !_is_usr_loggedin()){
				$msg = "You must provide a posting name!";
				$makepost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data']['subject'])==''){
				$msg = "You must provide a subject!";
				$makepost = false;
			}

			if (str_replace(" ", "", $_POST['wpClassified_data']['post'])==''){
				$msg = "You must provide a comment!";
				$makepost = false;
			}

			if ($_FILES['image_file']!=''){
				$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
				if ($ok==true){
					$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
					if ($imginfo[0]>(int)$wpcSettings["wpClassified_image_width"]  ||
						$imginfo[1]>(int)$wpcSettings["wpClassified_image_height"] || $imginfo[0] == 0){
						 echo "<h2>Invalid image size. Image must be ".(int)$wpcSettings["wpClassified_image_width"]."x".(int)$wpcSettings["wpClassified_image_height"]." pixels or less. Your image was: ".$imginfo[0]."x".$imginfo[1] . "</h2>";
						$makepost=false;	
					} else {
						$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
						$content = @fread($fp, $_FILES['image_file']['size']);
						@fclose($fp);
						$fp = fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
						@fwrite($fp, $content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
						$setImage = (int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'];
					}
				}
			}
			if ($makepost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data']['author_name']), '', stripslashes($_POST['wpClassified_data']['subject']), stripslashes($_POST['wpClassified_data']['post']), $user_ID);

				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads_subjects
					(ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip) VALUES
					('".($_GET['lists_id']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."' , '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['subject']))."' , 0, 0 , 'n' , '".(($isSpam)?"deleted":"open")."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', '".getenv('REMOTE_ADDR')."')");

				$tid = $wpdb->get_var("SELECT last_insert_id()");
				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads
					(ads_ads_subjects_id, date, author, author_name, author_ip, status, subject, image_file, post) VALUES
					('".$tid."', '".time()."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', 
					'".getenv('REMOTE_ADDR')."', 'active', 
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data']['subject']))."',
					'".$wpdb->escape(stripslashes($setImage))."',
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data']['post']))."')");
				do_action('wpClassified_new_ads', $tid);
				$pid = $wpdb->get_var("select last_insert_id()");
				if (!$isSpam){
					update_user_post_count($user_ID);
					update_ads($_GET['lists_id']);
				}
				$_GET['ads_subjects_id'] = $tid;
				wpClassified_display_ads_subject();
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
				<form method="post" id="cat_form_post" name="cat_form_post" enctype="multipart/form-data"
			onsubmit="this.sub.disabled=true;this.sub.value='Posting Ads...';" action="<?php echo create_wpClassified_link("postAdsForm", array("lists_id"=>$_GET["lists_id"], "name"=>$lists["name"]));?>">
				<input type="hidden" name="wpClassified_ads_subject" value="yes">
				<tr>
					<td align=right><?php echo __("Posting Name:");?> </td>
					<td><?php
						if (!_is_usr_loggedin()){
						?>
							<input type=text size=15 name="wpClassified_data[author_name]" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['author_name']));?>"><br>
							<span style="font-size: 10px;">(<?php echo __("You are not logged in and posting as a guest, click");?> <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("here");?></a> <?php echo __("to log in");?>.)</span>
						<?php
						} else {
							echo "<b>".$userdata->$userfield."</b>";
						}

						?></td>
				</tr>
				<tr>
					<td align=right><?php echo __("Subject:");?> </td>
					<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['subject']));?>"></td>
				</tr>
		<tr>
			<td align=right><?php echo __("Image File: ");?></td>
			<td><input type=file name="image_file"><br /><small><?php echo __("(maximum:" . (int)$wpcSettings["wpClassified_image_width"]."x".(int)$wpcSettings["wpClassified_image_height"] . " pixel ");?>)</small></td>
		</tr>
				<tr>
					<td valign=top align=right><?php echo __("Comment:");?> </td>
					<td><?php create_ads_input($_POST['wpClassified_data']['post']); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value="<?php echo __("Post Ads");?>" id="sub"></td>
				</tr>
				</form>
			</table>

			<?php

		}
		wpc_footer();
	}
}


function create_wpClassified_link($action, $vars){
	global $wpdb, $table_prefix, $wp_rewrite;
		
	$pageinfo = get_wpClassified_pageinfo();
	$rewrite = ($wp_rewrite->get_page_permastruct()=="")?false:true;
	$starts = (((int)$vars["start"])?"(".$vars["start"].")/":"");
	if (!$vars['post_jump']) {
		$lastAds = ($action=="lastAds")?"#lastpost":"";
	} else {
		$lastAds = ($action=="lastAds")?"#".$vars['post_jump']:"";
	}
	$action = ($action=="lastAds")?"ads_subject":$action;
	switch ($action){
		case "index":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=classified\">".$vars["name"]."</a> ";
		break;
		case "classified":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/viewList/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".$starts."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=viewList&amp;lists_id=".$vars["lists_id"]."&amp;start=".(int)$vars['start']."\">".$vars["name"]."</a> ";
		break;
		case "postAds":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/postAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=postAds&amp;lists_id=".$vars["lists_id"]."\">".$vars["name"]."</a> ";
		break;
		case "postAdsForm":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/postAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]:get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=postAds&amp;lists_id=".$vars["lists_id"];
		break;
		case "ads_subject":			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/viewAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".$starts."?search_words=".ereg_replace("[^[:alnum:]]", "+", $vars["search_words"]).$lastAds."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=viewAds&amp;lists_id=".$vars['lists_id']."&amp;ads_subjects_id=".$vars['ads_subjects_id']."&amp;pstart=".((int)$vars["start"])."&amp;search_words=".$vars['search_words'].$lastAds."\">".$vars['name']."</a>";
		break;
		case "editAds":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/editAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".((int)$vars["ads_id"])."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=editAds&amp;lists_id=".$vars['lists_id']."&amp;ads_subjects_id=".$vars['ads_subjects_id']."&amp;ads_id=".((int)$vars["ads_id"])."\">".$vars['name']."</a> ";
		break;
		case "editAdsform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/editAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".((int)$vars["ads_id"]):get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=editAds&amp;lists_id=".$vars['lists_id']."&amp;ads_subjects_id=".$vars['ads_subjects_id']."&amp;ads_id=".((int)$vars["ads_id"]);
		break;
		case "searchform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/search/":get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&amp;wpClassified_action=search";
		break;
	}
}


function wpClassified_permission_denied(){
	echo __("Sorry, it seems that you do not have permission to perform the requested action.");
	return;

}

function create_ads_author($ad){
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	$out = "";
	if ($ad->author==0){
		$out .= $ad->author_name;
	} else {
		$out .= $ad->$userfield;
	}
	return $out;
}

function wpClassified_last_octet($ip){
	$ip = explode(".", $ip);
	$ip[count($ip)-1] = "***";
	return @implode(".", $ip);
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
	$wpClassified_ads_text = $post->post;
	$wpClassified_ads_text = nl2br($wpClassified_ads_text);
	$wpClassified_ads_charset = get_option('blog_charset');
	$wpClassified_ads_text = addslashes(htmlspecialchars($wpClassified_ads_text, ENT_COMPAT, $wpClassified_ads_charset));		
	$wpClassified_ads_text = str_replace(chr(13), "", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace(chr(10), "", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&lt;br /&gt;", "\n", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&lt;", "<", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;lt;", "<", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;", "&", $wpClassified_ads_text);
	if ($wpcSettings["wpc_edit_style"]=="plain"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
	if ($wpcSettings["wpc_edit_style"]=="bbcode"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
		if ($wpcSettings["wpc_edit_style"]=="html"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
		if ($wpcSettings["wpc_edit_style"]=="quicktags"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
	$wpClassified_ads_text = trim($wpClassified_ads_text);
	$wpClassified_ads_text = preg_replace("/ *\n */", "\n", $wpClassified_ads_text);
	$wpClassified_ads_text = preg_replace("/\s{3,}/", "\n\n", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("\n", "\\n", $wpClassified_ads_text);
	return $wpClassified_ads_text;
}

?>