<?php

/**
 * admin.php
 *
 **/

add_filter("the_content", "wpClassified_page_handle_content");
add_filter("the_title", "wpClassified_page_handle_title");
add_filter("wp_list_pages", "wpClassified_page_handle_titlechange");
add_filter("single_post_title", "wpClassified_page_handle_pagetitle");

//add_filter('rewrite_rules_array','wpc_general_rewrite_rules');

if (function_exists('add_action')) {
	add_action('admin_menu', 'wpcAdmpage');
}

if ($_REQUEST["wpClassified_action"]){
	$_SERVER["REQUEST_URI"] = dirname(dirname($_SERVER["PHP_SELF"]))."/wp-classified/";
	$_SERVER["REQUEST_URI"] = stripslashes($_SERVER["REQUEST_URI"]);
}

$adm_links = array(
		array(name=>'List Settings',arg=>'wpcSettings'),
		array(name=>'Add/Edit Categories',arg=>'wpcStructure'),
		array(name=>'Edit/Remove Ads',arg=>'wpcAdssubjects_posts'),
		array(name=>'Users Admin',arg=>'wpcUsers'),
		array(name=>'Utilities',arg=>'wpcUtilities'),
		);


function wpc_general_rewrite_rules(&$rules){
	global $wp_version;
	return $rules;
}


function wpc_mod_rewrite_rules($wp_rewrite){
	global $wp_rewrite;
	$wpcSettings = get_option('wpClassified_data');
	$wpClassified_slug = $wpClassified_settings['wpClassified_slug'];
	$wpClassified_rules = array(
	$wpClassified_slug.'/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?(\([^/\(\)]*\))?/?' => '/'.$wpClassified_slug.'/index.php?pagename='.$wpClassified_slug.'&wpClassified_action=$matches[1]&lists_id=$matches[3]&ads_subjects_id=$matches[5]&ads_id=$matches[6]&start=$matches[8]&amp;pstart=$matches[8]'
	);
	$wp_rewrite->rules = $wpClassified_rules + $wp_rewrite->rules;
}

// wpClassified settings 
function wpcSettings_process(){
	global $_GET, $_POST, $wp_rewrite, $PHP_SELF, $wpdb, $table_prefix, $user_level, $wpClassified_version, $wp_version;

	switch ($_GET['adm_action']){
		case "savesettings":
			foreach ($_POST["wpClassified_data"] as $k=>$v){
				$_POST["wpClassified_data"][$k] = stripslashes($v);
			}
			
			$_POST['wpClassified_data']['userfield'] = get_wpc_user_field();
			$_POST['wpClassified_data']['wpClassified_installed'] = 'y';
			$_POST['wpClassified_data']['wpClassified_version'] = $wpClassified_version;

			update_option('wpClassified_data', $_POST['wpClassified_data']);
			$msg = "Settings Updated!";
		break;
		case "createpage":	
			$p = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
			if ($p["post_title"]!="[[WP_CLASSIFIED]]"){
				$wpdb->query("insert into {$table_prefix}posts (post_author, post_date, post_date_gmt, post_content, post_title, post_category, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, post_type, menu_order) values ('1', '2008-03-27 22:30:57', '2008-03-02 22:30:57', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', '0', '[[WP_CLASSIFIED]]', 'publish', '', '', '', 'wpClassified', '', '', '2008-03-27 22:30:57', '2008-03-27 22:30:57', '[[WP_CLASSIFIED]]', '0', '', 'page', '0')");
			}
		break;
	}

	$selflink = 
	($wp_rewrite->get_page_permastruct()=="")?"<a href=\"".get_bloginfo('wpurl')."/index.php?pagename=wpClassified\">".get_bloginfo('wpurl')."/index.php?pagename=wpClassified</a>":"<a href=\"".get_bloginfo('wpurl')."/wp-classified/\">".get_bloginfo('wpurl')."/wp-classified/</a>";

	if ($msg!=''){
		?>
		<p>
		<b><?php echo __($msg);?></b>
		</p>
		<?php
	}
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	if ($pageinfo == false){
		echo "<h2>The wpClassified Page not found.</h2>";
	?>
	<hr />	
	<h2><?php _e('Create wpClassified Page', 'wpClassified'); ?></h2>
	<p style="text-align: left;">
	<?php _e('The wpClassified plugin Page will be created automatically', 'wpClassified'); ?>
	</p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=createpage">
	<p style="text-align: center;">
	<input type="submit" name="do" value="<?php _e('wpClassified Create Page', 'wpClassified'); ?>" class="button" />
	</p>
	</form>
	<pre>
	<h3>Or you can create the page manually in 4 steps:</h3>

 1- Go to 'WP-Admin -> Write -> Write Page' 
 2- Type in the post's title area [[WP_CLASSIFIED]]
 3- Type '[[WP_CLASSIFIED]]' in the post's content area (without the quotes) 
 4- Type 'useronline' in the post's slug area (without the quotes) 
 Click 'Publish' 

If you ARE NOT using nice permalinks, you need to go to 'WP-Admin -> Options -> wpClassiefied Admin' and under 'wpClassified URL', you need to fill in the URL to the Classified Page you created above.
</pre>

	<?
		 return null;
	};
	if ($wpcSettings['wpClassified_page_url'] == "") $wpcSettings['wpClassified_page_url']=$selfpage;
	?>
	<p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=savesettings">
		<input type=hidden name="wpClassified_data[wpClassified_version]" value="<?php echo $wpClassified_version;?>">
		<table border=0 class="editform"><tr>
			<th align="right"><?php echo __("wpClassified Version:");?> </th>
			<td><?php echo $wpClassified_version;?></td>
			</tr>
		    <th align="right"><?php echo __("Wordpress Version:");?> </th>
			<td><?php echo $wp_version;?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Classified Top Image:");?> </th>
				<td><input type=text size=25 name="wpClassified_data[wpClassified_top_image]" value="<?php echo ($wpcSettings['wpClassified_top_image']);?>"></td>
			</tr>
		    <tr>
			<th align="right"><?php echo __("Classified Description (optional):");?></th>
				<td><textarea cols=65 rows=1 name="wpClassified_data[wpClassified_description]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['wpClassified_description']));?></textarea>
			<tr>
			<th align="right"><?php echo __("wpClassified URL: ");?> </th>
				<td><input type="text" size=60 name="wpClassified_data[wpClassified_page_url]" value="<?php echo $wpcSettings['wpClassified_page_url'];?>"></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[show_credits]" value="y"<?php echo ($wpcSettings['show_credits']=='y')?" checked":"";?>> <?php echo __("Display wpClassified credit line at the bottom of classified pages");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("wpClassified Page Link Name: ");?></th>
				<td><input type="text" name="wpClassified_data[wpClassified_slug]" value="<?php echo $wpcSettings['wpClassified_slug'];?>"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Max Ads Image Size: ");?></th>
				<td>Width: <input type="text" size="5" name="wpClassified_data[wpClassified_image_width]" value="<?php echo $wpcSettings['wpClassified_image_width'];?>" onchange="this.value=this.value*1"> X Height: <input type="text" size="5" name="wpClassified_data[wpClassified_image_height]" value="<?php echo $wpcSettings['wpClassified_image_height'];?>" onchange="this.value=this.value*1"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Ads Image Alignment:");?> </th>
				<td><input type=text size=11 name="wpClassified_data[wpClassified_image_alignment]" value="<?php echo ($wpcSettings['wpClassified_image_alignment']);?>"></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[must_registered_user]" value="y"<?php echo ($wpcSettings['must_registered_user']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot post.");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[view_must_register]" value="y"<?php echo ($wpcSettings['view_must_register']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot view.");?></td>
			</tr>
			<tr>
			<th align="right"></th>
			<td><input type=checkbox name="wpClassified_data[display_unregistered_ip]" value="y"<?php echo ($wpcSettings['display_unregistered_ip']=='y')?" checked":"";?>> <?php echo __("Display first 3 octets of unregistered visitors ip (ie - 192.168.0.***).");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_display_titles]" value="y"<?php echo ($wpcSettings['wpClassified_display_titles']=='y')?" checked":"";?>> <?php echo __("Display user titles on classified.");?></td>
			</tr>
			<tr>
			<th align="right"></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_filter_posts]" value="y"<?php echo ($wpcSettings['wpClassified_filter_posts']=='y')?" checked":"";?>> <?php echo __("Apply WP Ads/comment filters to classified posts.");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Posting Style: ");?></th>
				<td><select name="wpClassified_data[wpc_edit_style]">
					<option value="tinymce"<?php echo ($wpcSettings["wpc_edit_style"]=="tinymce")?" selected":"";?>>HTML with TinyMCE (inline wysiwyg)</option>
					<option value="plain">No HTML, No BBCode</option>
					</select></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[editor_toolbar_basic]" value="y"<?php echo ($wpcSettings['editor_toolbar_basic']=='y')?" checked":"";?>> <?php echo __("Use basic toolbars in editor.");?></td>
			</tr>
			<!--tr>
				<th align="right"><?php echo __("Number Of 'Last Ads'");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_last_ads_subject_num]" value="<?php echo ($wpcSettings['wpClassified_last_ads_subject_num']);?>" onchange="this.value=this.value*1;"></td>
			</tr-->
			<tr>
				<th align="right"><?php echo __("Excerpt Length");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_excerpt_length]" value="<?php echo ($wpcSettings['wpClassified_excerpt_length']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Ads Per Page");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_ads_per_page]" value="<?php echo ($wpcSettings['wpClassified_ads_per_page']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Date Format String");?></th>
				<td><input type=text size=11 name="wpClassified_data[wpClassified_date_string]" value="<?php echo ($wpcSettings['wpClassified_date_string']);?>"></td>
			</tr>
			<tr>
				<th align="right" valign="top"><?php echo __("Banner Code:");?> </th>
				<td><textarea cols=45 rows=5 name="wpClassified_data[wpClassified_banner_code]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['wpClassified_banner_code']));?></textarea></td>
			</tr>			
			<th></th>
				<td><input type=submit value="<?php echo __("Update wpClassified Settings");?>"></td>
			</tr>
		</table>
	</form>
	</p>
	<?php
}


function wpClassified_process(){
	global $_GET, $_POST, $user_level, $wpClassified_user_info, $table_prefix, $wpdb;
	$wpcSettings = get_option('wpClassified_data');

	if (!file_exists(ABSPATH . INC . "/wpClassified.css")){
		?>
		<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory');?>/wpClassified.css" type="text/css" media="screen" />
		<?php
	} else {
		?>
		<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
		<?php
	}

	
	switch ($_GET['wpClassified_action']){
		default:
		case "classified": wpc_index();
		break;
		case "search": wpClassified_display_search();
		break;
		case "viewList": get_wpc_list();
		break;
		case "postAds":	wpClassified_ads_subject();
		break;
		case "editAds":	wpClassified_edit_ads();
		break;
		case "viewAds":	wpClassified_display_ads_subject();
		break;
	}
}

function create_ads_subject_author($ad){
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

function create_admin_post_author($post){
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	$out = "";
	if ($post->author==0){
		$out .= $post->author_name." (guest)";
		$out .= " - ".wpClassified_last_octet($post->author_ip);
		$out .= "";
	} elseif ($post->display_name){
		$out .= $post->$userfield;
	}
	return $out;
}

function set_sticky_ads_subject($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT sticky FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='y')?"n":"y";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET sticky = '".$new."' WHERE ads_subjects_id = '".$id."'");
}

function delete_ads($id){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=deletepost&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&ads_subjects_id=".$_GET['ads_subjects_id'];

	if ($_POST['deleteid']*1>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'deleted' WHERE ads_id = '".((int)$_POST['deleteid'])."'");
		adm_sync_count($_GET['lists_id']);
		return true;
	} else {
		?>
		<h3><?php echo __("Ads Deletion Confirmation");?></h3>
		<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $linkb;?>">
		<strong>
			<input type="hidden" name="deleteid" value="<?php echo $_GET['ads_id'];?>">
			<?php echo __("Are you sure you want to delete this post?");?><br />
			<input type=submit value="<?php echo __("Yes");?>"> <input type=button value="<?php echo __("No");?>" onclick="history.go(-1);">
		</strong>
		</form>
		<?php
		return false;
	}
}

function adm_sync_count($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads.status = 'active'");
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads_subjects.status = 'open'");
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = '".$posts."', ads_status = '".$ads."' WHERE lists_id = '".$id."'");
}

function adm_count_ads($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$id)."' && status = 'active'")-1;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET ads = '".$posts."' WHERE ads_subjects_id = '".((int)$id)."'");
}

function activate_post($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".$id."'");
	$new = ($cur=='active')?"inactive":"active";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = '".$new."' WHERE ads_id = '".$id."'");
	adm_count_ads($id);
}

function activate_ads_subject($id){
	global $table_prefix, $wpdb, $_GET;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='open')?"closed":"open";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = '".$new."' WHERE ads_subjects_id = '".$id."'");
	adm_sync_count($_GET['lists_id']);
}

function wpcAdmpage(){
	global $wpClassified_user_level, $wpc_admin_pagename;
	add_management_page($wpc_admin_pagename, $wpc_admin_pagename, $wpClassified_user_level, 'wpClassified', 'wpClassified_adm_page');
}


function delete_ads_subject($id){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=deleteAds&lists_id=".$_GET['lists_id']."&start=".$_GET['start'];

	if ($_POST['deleteid']*1>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'inactive' WHERE ads_ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = 'deleted' WHERE ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		adm_sync_count($_GET['lists_id']);
		return true;
	} else {
		?>
		<h3><?php echo __("Ads Deletion Confirmation");?></h3>
		<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $linkb;?>">
		<strong>
			<input type="hidden" name="deleteid" value="<?php echo $_GET['ads_subjects_id'];?>">
			<?php echo __("Are you sure you want to delete this ads?");?><br />
			<input type=submit value="<?php echo __("Yes");?>"> <input type=button value="<?php echo __("No");?>" onclick="history.go(-1);">
		</strong>
		</form>
		<?php
		return false;
	}
}

function wpClassified_adm_page(){
	global $_GET, $_POST, $PHP_SELF, $user_level, $wpdb, $adm_links, $wpClassified_user_level, $wp_version;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');

	wpClassified_install();
	?>
	<div class="wrap">
		<ul id="<?php echo "submenu"; ?>">
		<?php
			for ($i=0; $i<count($adm_links); $i++){
				$tlink = $adm_links[$i];
				if ($tlink['arg']==$_GET['adm_arg'] || (!$_GET['adm_arg'] && $i==0)){
					$sel = " class=\"current\"";
					$pagelabel = $tlink['name'];
				} else {
					$sel = "";
				}
			?>
			<li><a href='<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $tlink['arg'];?>'<?php echo $sel;?>><?php echo __($tlink['name']);?></a></li>
			<?php
			}
			?>
		</ul>
		<h2><?php echo __($pagelabel);?></h2>
			<?php
			switch ($_REQUEST['adm_arg']){
				case "wpcSettings":
				default:
					wpcSettings_process();
				break;
				case "wpcStructure":
					adm_structure_process();
				break;
				case "wpcAdssubjects_posts":
					adm_adssubjects_process();
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


// install function 
// create the db tables.
function wpClassified_install(){
	global $wpClassified_version, $wp_rewrite;

	$wpcSettings = array();
	$wpcSettings = $_POST['wpClassified_data'];
	update_option('wpClassified_data', $wpcSettings);
	$wp_rewrite->flush_rules();
	$wpcSettings = get_option('wpClassified_data');
	wpClassified_check_db();

	$wpcSettings['wpClassified_version'] = $wpClassified_version;
	if ($wpcSettings['wpClassified_installed']!='y'){
		$wpcSettings['wpClassified_installed'] = 'y';
		$wpcSettings['userfield'] = get_wpc_user_field();
		$wpcSettings['show_credits'] = 'y';
		$wpcSettings['wpClassified_slug'] = 'Classifieds';
		$wpcSettings['must_registered_user'] = 'n';
		$wpcSettings['view_must_register'] = 'n';
		$wpcSettings['display_unregistered_ip'] = 'y';
		$wpcSettings['wpClassified_display_titles'] = 'y';
		$wpcSettings['editor_toolbar_basic'] = 'y';
		$wpcSettings['wpClassified_filter_posts'] = 'y';
		$wpcSettings['wpClassified_ads_per_page'] = 10;
		$wpcSettings['wpClassified_image_width'] = 150;
		$wpcSettings['wpClassified_image_height'] = 200;
		$wpcSettings['wpClassified_date_string'] = 'm-d-Y g:i a';
		$wpcSettings['wpClassified_unread_color'] = '#FF0000';
		$wpcSettings['wpClassified_image_alignment'] = 'left';
		$wpcSettings['wpClassified_top_image'] = '';
		$wpcSettings['wpClassified_read_user_level'] = -1;
		$wpcSettings['wpClassified_write_user_level'] = -1;
		$wpcSettings['wpClassified_banner_code'] = '';
		$wpcSettings['wpClassified_display_last_ads_subject'] = 'y';
		$wpcSettings['wpClassified_display_last_post_link'] = 'y';
		$wpcSettings['wpClassified_last_ads_subject_num'] = 5;
		$wpcSettings['wpClassified_excerpt_length'] = 100;
		$wpcSettings['wpClassified_last_ads_subjects_author'] = "y";
		$wpcSettings['credit_line'] = 'wpClassified plugins powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';
	}
	update_option('wpClassified_data', $wpcSettings);
}

function wpClassified_check_db(){
	global $_GET, $_POST, $wpdb, $table_prefix, $wpClassified_pageinfo;
	$t = $table_prefix.'wpClassified';
	include("wpClassified_db.php");
	if($_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
		return;
	} else {
		wpClassified_db();
	}
}




function adm_structure_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $user_level;
	switch ($_GET['adm_action']){
		case "saveCategory":
			if ($_GET['categories_id']==0){
				$position = $wpdb->get_var("SELECT MAX(position) FROM {$table_prefix}wpClassified_categories")+1;
				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_categories (name, photo, position, status) values (
				'".	$wpdb->escape($_POST['wpClassified_data']['name']).	"',
				'". $wpdb->escape($_POST['wpClassified_data']['photo'])."', 
				'".$position."', 'active')");
			} else {
				$wpdb->query("
					UPDATE {$table_prefix}wpClassified_categories 
					SET name = '".$wpdb->escape($_POST['wpClassified_data']['name'])."',
				    photo = '".$wpdb->escape($_POST['wpClassified_data']['photo'])."' WHERE categories_id = '".($_GET['categories_id']*1)."'");
			}
			$msg = "Classifieds Category Saved!";
		break;
		case "saveList":
			if ($_GET['lists_id']==0){
				$position = $wpdb->get_var("SELECT MAX(position) FROM {$table_prefix}wpClassified_lists")+1;
				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_lists (wpClassified_lists_id, name, description, position, status) values ('".($_POST['wpClassified_data']['wpClassified_lists_id']*1)."', '".$wpdb->escape($_POST['wpClassified_data']['name'])."', '".$wpdb->escape($_POST['wpClassified_data']['description'])."', '".$position."', '".$wpdb->escape($_POST['wpClassified_data']['status'])."')");
			} else {
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET status = '".$wpdb->escape($_POST['wpClassified_data']['status'])."', wpClassified_lists_id = '".($_POST['wpClassified_data']['wpClassified_lists_id']*1)."', name = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['name']))."', description = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['description']))."' WHERE lists_id = '".($_GET['lists_id']*1)."'");
			}
			$msg = "List Saved!";
		break;
		case "deleteCategory":
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'");
		break;
		case "moveupCategory":
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE position < '".$ginfo['position']."' ORDER BY position DESC", ARRAY_A);
			if ($above['categories_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position = '".$above['position']."' WHERE categories_id = '".($_GET['categories_id']*1)."'");
				$wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position = '".$ginfo['position']."' WHERE categories_id = '".$above['categories_id']."'");
			}
			$msg = "Category Moved Up";
		break;
		case "moveupList":
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = '".$ginfo['wpClassified_lists_id']."' && position < '".$ginfo['position']."' ORDER BY position DESC", ARRAY_A);
			if ($above['lists_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$above['position']."' WHERE lists_id = '".($_GET['lists_id']*1)."'");
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$ginfo['position']."' WHERE lists_id = '".$above['lists_id']."'");
			}
			$msg = "List Moved Up";
		break;
		case "movedownCategory":
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE position > '".$ginfo['position']."' ORDER BY position ASC", ARRAY_A);
			if ($above['categories_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position = '".$above['position']."' WHERE categories_id = '".($_GET['categories_id']*1)."'");
				$wpdb->query("UPDATE {$table_prefix}wpClassified_categories SET position = '".$ginfo['position']."' WHERE categories_id = '".$above['categories_id']."'");
			}
			$msg = "Category Moved Down";
		break;
		case "movedownList":
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = '".$ginfo['wpClassified_lists_id']."' && position > '".$ginfo['position']."' ORDER BY position ASC", ARRAY_A);
			if ($above['lists_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$above['position']."' WHERE lists_id = '".($_GET['lists_id']*1)."'");
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$ginfo['position']."' WHERE lists_id = '".$above['lists_id']."'");
			}
			$msg = "List Moved Down";
		break;
	}
	if ($msg!=''){
		?>
		<p>
		<b><?php echo __($msg);?></b>
		</p>
		<?php
	}
	$wpcSettings = get_option('wpClassified_data');
	if ($_GET['adm_action']=='editCategory'){
		$categoryinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'", ARRAY_A);
	?>
	<p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveCategory&categories_id=<?php echo $_GET['categories_id'];?>">
		<table border=0 class="editform">
		<tr><th align="right"><?php echo __("Category Name");?></th>
		<td><input type=text size=50 name="wpClassified_data[name]" value="<?php echo $categoryinfo['name'];?>"></td>
		</tr>
		<th align="right"><?php echo __("Category Photo");?></th>
		<td><input type=text size=50 name="wpClassified_data[photo]" value="<?php echo $categoryinfo['photo'];?>"><br><small>images from plugins/wp-classified/images directory: e.g: 'images/default.gif' </small></td>
		</tr>
			<tr>
				<th></th>
				<td><input type=submit value="<?php echo __("Save");?>"></td>
			</tr>
		</table>
	</form>
	</p>
	<?php
	} elseif ($_GET['adm_action']=='editList'){
		$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
		$classifiedinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
	?>
	<p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveList&lists_id=<?php echo $_GET['lists_id'];?>">
		<table border=0 class="editform">
			<tr>
				<th align="right"><?php echo __("List Name");?></th>
				<td><input type=text size=50 name="wpClassified_data[name]" value="<?php echo $classifiedinfo['name'];?>"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("List Description");?></th>
				<td><textarea name="wpClassified_data[description]" rows="3" cols="35"><?php echo $classifiedinfo['description'];?></textarea></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Parent Category");?></th>
				<td><select name="wpClassified_data[wpClassified_lists_id]">
					<?php
			for ($x=0; $x<count($categories); $x++){
				$category = $categories[$x];
				$sel = ($category->categories_id==$classifiedinfo['wpClassified_lists_id'])?" selected":"";
				echo "<option value=\"".$category->categories_id."\"$sel>".$category->name."</option>\n";
			}
			?>
			</select></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("List Status");?></th>
				<td><select name="wpClassified_data[status]">
					<option value="active">Open</option>
					<option value="inactive"<?php echo ($classifiedinfo['status']=='inactive')?" selected":"";?>>Closed</option>
					<option value="readonly"<?php echo ($classifiedinfo['status']=='readonly')?" selected":"";?>>Read-Only</option>
				</select></td>
			</tr>
			<tr>
				<th></th>
				<td><input type=submit value="<?php echo __("Save");?>"></td>
			</tr>
		</table>
	</form>
	</p>
	<?php
	} else {
		$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
		$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
		$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists ORDER BY position ASC");
		?>
		<script language=javascript>
		<!--
		function deleteCategory(x, y){
			if (confirm("Are you sure you wish to delete the group:\n"+x)){
				document.location.href = y;
			}
		}
		function deleteclassified(x, y){
			if (confirm("Are you sure you wish to delete the classified:\n"+x)){
				document.location.href = y;
			}
		}
		-->
		</script>
		<input type=button value="Add Category" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=0';">
		 <input<?php echo (count($categories)<1)?" disabled":"";?> type=button value="Add List" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lists_id=0';">
		<?php
		for ($i=0; $i<count($tlists); $i++){
			$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
		}
?>
<hr>
<table border=0 width=100%>
	<tr>
		<th></th>
		<th align=left colspan=2><?php echo __("Category/List");?></th>
		<th align=right width=100><?php echo __("Ads");?></th>
		<th align=right width=100><?php echo __("List");?></th>
		<th align=right width=100><?php echo __("Views");?></th>
	</tr>
<?php
	for ($x=0; $x<count($categories); $x++){
		$category = $categories[$x];
	?>
		<tr>
		<td><sup><h3><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupCategory&categories_id=<?php echo $category->categories_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownCategory&categories_id=<?php echo $category->categories_id;?>">&darr;</a> <?php
		if (count($lists[$category->categories_id])<1){
			?> <a style="text-decoration: none;" href="javascript:deleteCategory('<?php echo rawurlencode($category->name);?>', '<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=deleteCategory&categories_id=<?php echo $category->categories_id;?>');" style="color: red; font-size: 10px;">[Delete]</a><?php
		}
		?></h3></sup></td>
		<td colspan=2><h3><a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=<?php echo $category->categories_id;?>"><?php echo $category->name;?></a></h3></td>
		<td colspan=3></td>
			</tr>
			<?php
			$tfs = $lists[$category->categories_id];
			for ($i=0; $i<count($tfs); $i++){
				?>
				<tr>
					<td></td>
					<td><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupList&lists_id=<?php echo $tfs[$i]->lists_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownList&lists_id=<?php echo $tfs[$i]->lists_id;?>">&darr;</a></td>
					<td><span style="font-size: 10px;">(<?php echo $liststatuses[$tfs[$i]->status];?>)</span> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lists_id=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
					<td align=right><?php echo $tfs[$i]->ads_status;?></td>
					<td align=right><?php echo $tfs[$i]->ads;?></td>
					<td align=right><?php echo $tfs[$i]->ads_views;?></td>
				</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}
}


//mohamm
function adm_users_process(){
	global $_GET, $_POST, $wpdb, $table_prefix;
	$wpcSettings = get_option('wpClassified_data');
	if ($_GET["adm_action"]=="saveuser"){
		$id = (int)$_GET["id"];
		$update = array();
		foreach ($_POST["wpClassified_user_info"] as $k=>$v){
			$update[] = "$k = '".$wpdb->escape($v)."'";
		}
		$wpdb->query("update {$table_prefix}wpClassified_user_info set ".implode(", ", $update)." where user_info_user_ID = '".$id."'", ARRAY_A);
	}

	switch ($_GET["adm_action"]){
		default:
		case "saveuser":
		case "list":
			$start = (int)$_GET["start"];
			$perpage = ((int)$_GET["perpage"])?(int)$_GET["perpage"]:20;
			$searchfields = array(
				($namefield=get_wpc_user_field()),
				"user_login",
				"user_nicename",
				"user_email",
				"user_url",
			);
			if ($_GET["term"]){
				$where = " WHERE ";
				foreach ($searchfields as $field){
					if ($where!=" WHERE "){
						$where .= " || ";
					}
					$where .= "{$table_prefix}users.".$field." like '%".$wpdb->escape($_GET["term"])."%'";
				}
			} else {
				$where = "";
			}
			$all_users = $wpdb->get_results("select * from {$table_prefix}users
								LEFT JOIN {$table_prefix}wpClassified_user_info
								ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
								$where
								ORDER BY {$table_prefix}users.".$searchfields[0]." ASC
								LIMIT $start, $perpage", ARRAY_A);
			$numusers = $wpdb->get_results("select count(*) as numusers from {$table_prefix}users $where ", ARRAY_A);
			$numusers = $numusers[0]["numusers"];
			?>
			<form method="get" id="adm_form_get" action="<?php echo $_SERVER["PHP_SELF"];?>">
				<input type="hidden" name="adm_arg" value="<?php echo $_GET["adm_arg"];?>" />
				<input type="hidden" name="page" value="wpClassified" />
				<table width="100%">
					<tr>
						<td>Pages: <?php
						$query_string = "perpage=$perpage&adm_arg=".$_GET["adm_arg"]."&page=wpClassified&term=".urlencode($_GET["term"]);

						for ($i=0; $i<($numusers/$perpage); $i++){
							if ($i*$perpage==$start){
								echo " <b>".($i+1)."</b> ";
							} else {
								echo " <a href=\"".$_SERVER["PHP_SELF"]."?".$query_string."&start=".($i*$perpage)."\">".($i+1)."</a> ";
							}
						}
						?></td>
						<td align="right"><input type="text" size="25" name="term" value="<?php echo $_GET["term"];?>" /><input type="submit" value="Search" /></td>
					</tr>
				</table>
			</form>
			<table width="100%" cellpadding="3" cellspacing="3" border="0">
				<tr>
					<th></th>
					<th>ID</th>
					<th>Username</th>
					<th>Display Name</th>
					<th>E-mail Address</th>
					<th>URL</th>
				</tr>
				<?php		
				foreach ($all_users as $user){
				$bgcolor = ($bgcolor=="#CCCCCC")?"#DDDDDD":"#CCCCCC";
				?>
				<tr bgcolor="<?php echo $bgcolor;?>">
				<td align="center"><a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=edit&id=<?php echo $user["ID"];?>&start=<?php echo $start;?>&perpage=<?php echo $perpage;?>&term=<?php echo urlencode($_GET["term"]);?>">Edit</a></td>
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
			</table>
			<?php
		break;
		case "edit":
			$user = $wpdb->get_results("select * from {$table_prefix}users
							LEFT JOIN {$table_prefix}wpClassified_user_info
							ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
							WHERE {$table_prefix}users.ID = '".(int)$_GET['id']."'", ARRAY_A);

			$user = $user[0];
			$namefield = get_wpc_user_field();

			$permissions = array("none"=>"User", "moderator"=>"Moderator", "administrator"=>"Administrator");

			?>
			<form method="post" id="cat_form_post" name="cat_form_post" enctype="multipart/form-data"
			 action="<?php echo $_SERVER["PHP_SELF"];?>?page=wpClassified&adm_arg=<?php echo $_GET["adm_arg"];?>&adm_action=saveuser&id=<?php echo $_GET["id"];?>&start=<?php echo $_GET["start"];?>&perpage=<?php echo $_GET["perpage"];?>&term=<?php echo urlencode($_GET["term"]);?>">
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
					$sel = ($perm==$user["permission"])?" selected=\"selected\"":"";
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
				<td><input type="text" name="wpClassified_user_info[user_info_post_count]" size="10" value="<?php echo str_replace('"', "&quot;", $user["post_count"]);?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" value="Save" /></td>
			</tr>
			</table>
			<?php
		break;
	}
}

function adm_utilities_process(){
	global $_GET, $_POST, $wpdb, $table_prefix, $wpClassified_pageinfo;
      
	$t = $table_prefix.'wpClassified';
	$wpcSettings = get_option('wpClassified_data');
	switch ($_GET["adm_action"]){
		default:
		case "list":
		break;
		case "uninstall":
			$msg = '<p>';
			$msg .= '<h2>'.__('Uninstall wpClassified', 'wpClassified').'</h2>';
			if($_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
				foreach ($_tables as $table){
					$wpdb->query("DROP TABLE $table");
					$msg .= '<font style="color: green;">';
					$msg .= 'Table ' . $table . ' has been deleted.';
					$msg .= '</font><br />';
				}
			}
			$msg .= '</p><p>';
			$wpdb->query("DELETE FROM " . $table_prefix . "OPTIONS WHERE OPTION_NAME='wpClassified_data'");
			$wpdb->query("DELETE FROM " . $table_prefix . "posts WHERE post_title = '[[WP_CLASSIFIED]]'");
			$_table = "";
			$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=wp-classified/wpClassified.php';
			if(function_exists('wp_nonce_url')) { 
				$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_wpclassified/wpClassiefied.php');
			}
			$msg .= '<p><strong>';
			$msg .= "Uninstallation and deactivated automatically!</strong></p>";
		break;
	}

	if ($msg!=''){
		?>
		<p>
		<b><?php echo __($msg);?></b>
		</p>
		<?php
	}
	?>
	<p>
	<h2><?php _e('Uninstall wpClassified', 'wpClassified'); ?></h2>
			
	<p style="text-align: left;">
	<?php _e('Deactivating wpClassified plugin does not remove any data, which are created by installation. To completely remove the plugin, you can uninstall it here.', 'wpClassified'); ?>
	</p>
	<p style="text-align: left; color: red">
	<strong><?php _e('WARNING:', 'wpClassified'); ?></strong><br />
	<?php _e('Once uninstalled, this cannot be undone. You should use a database backup of WordPress to back up all the classified data first.', 'wpClassified'); ?>
	</p>
	<p style="text-align: left; color: red">
	<strong><?php _e('The following WordPress Options/Tables will be DELETED:', 'wpClassified'); ?></strong><br />
	</p>
	<table width="70%"  border="0" cellspacing="3" cellpadding="3">
	<tr class="thead">
		<td align="center"><strong><?php _e('WordPress Tables', 'wpClassified'); ?></strong></td>
	</tr>
	<tr>
	<td valign="top" style="background-color: #eee;">
		<ol>
		<?php
		if($tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
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
	
	<form method="post" id="cat_form_post" name="cat_form_post"
			 action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=uninstall">
	<p style="text-align: center;">
	<br />
	<input type="submit" name="do" value="<?php _e('UNINSTALL wpClassified', 'wpClassified'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall wpClassified From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wpClassified'); ?>')" />
        </p>
	</form>
	</p>
	<?
}


function adm_adssubjects_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $user_level;
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$wpcSettings = get_option('wpClassified_data');
	$loadpage = true;
	switch ($_GET['adm_action']){
		case "deletepost":
			$loadpage = delete_ads($_GET['ads_id']*1);
		break;
		case "deleteAds":
			$loadpage = delete_ads_subject($_GET['ads_subjects_id']*1);
		break;
		case "closeAds":
			adm_close_ads_subject($_GET['ads_subjects_id']*1);
		break;
		case "activatepost":
			activate_post($_GET['ads_id']*1);
			unset($_GET['ads_id']);
		break;
		case "activateAds":
			activate_ads_subject($_GET['xtid']*1);
			unset($_GET['ads_subjects_id']);
		break;
		case "stickyAds":
			set_sticky_ads_subject($_GET['ads_subjects_id']*1);
			unset($_GET['ads_subjects_id']);
		break;
		case "moveAds":
			move_ads_subject($_GET['ads_subjects_id']*1, $_GET['new_lists_id']);
		break;
		case "savepost":
			save_post($_GET['ads_id']*1);
		break;
		case "saveAds":
			save_ads_subject($_GET['ads_subjects_id']*1);
		break;
		case "editAds":
			edit_ads_subject($_GET['ads_subjects_id']*1);
			$loadpage = false;
		break;
		case "editAds":
			edit_ads($_GET['ads_id']*1);
			$loadpage = false;
		break;
	}

	if ($msg!=''){
		?>
		<p>
		<b><?php echo __($msg);?></b>
		</p>
		<?php
	}
	if ($_GET['ads_subjects_id']*1>0 && $loadpage==true){
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
		$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'", ARRAY_A);
		$posts = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
					 WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'
						 && {$table_prefix}wpClassified_ads.status != 'deleted'
					 ORDER BY {$table_prefix}wpClassified_ads.date ASC");

?>
<h3><?php echo __("Viewing Ads:");?> <strong><?php echo $adsInfo['subject'];?></strong><br />
<?php echo __("In List:");?> <i><a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&lists_id=<?php echo $_GET['lists_id'];?>&start=<?php echo $_GET['start'];?>"><?php echo $lists['name'];?></a></i></h3>
<?php
	for ($i=0; $i<count($posts); $i++){
		$post = $posts[$i];
		$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&ads_subjects_id=".$_GET['ads_subjects_id']."&";
		$act = ($post->status=='inactive')?"Activate":"De-activate";
		$tlinks = array(
					"<a xhref=\"".$linkb."adm_action=editAds&ads_id=".$post->ads_id."\">".__("Edit")."</a>",
					"<a href=\"".$linkb."adm_action=deletepost&ads_id=".$post->ads_id."\">".__("Delete")."</a>",
					"<a href=\"".$linkb."adm_action=activatepost&ads_id=".$post->ads_id."\">".__($act)."</a>"
					);
		?>
<div class="wrap">
	<div class="wrap">
	<strong><?php
	echo @implode(" | ", $tlinks);
	?>
	</strong></div>
	<div class="post-bottom">
		<div class="entry" id="post-<?php echo $i;?>-entry">
			<div class="title" id="post-<?php echo $i;?>-title">
				<h2><?php echo str_replace("<", "&lt;", $post->subject);?></h2>
				<small><?php echo __("Posted By:");?> <strong><?php echo create_admin_post_author($post);?></strong> on <?php echo __(@date($wpcSettings['wpClassified_date_string'], $post->date));?></small>
			</div>
			<p id="post-<?php echo $i;?>-content"><?php echo nl2br(str_replace("<", "&lt;", $post->post));?></p>
		</div>
	</div>
</div>
		<?php
	}
	?>
	<?php
	} elseif ($_GET['lists_id']*1>0 && $loadpage==true){
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
	// list ads
	if (!$_GET['start']){
		$_GET['start'] = 0;
	}
	$ads = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
				 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lists_id']*1)."'
					&& {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
				 ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,
					    {$table_prefix}wpClassified_ads_subjects.date DESC
				 LIMIT ".($_GET['start']*1).", ".($wpcSettings['wpClassified_ads_per_page']*1)." ");

	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lists_id']*1)."'
							&& status != 'deleted'");
?>
<h3><?php echo __("Viewing List:");?> <strong><?php echo $lists['name'];?></strong></h3>
<a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>"><?php echo __("Back To Lists List");?></a>
<?php

if ($numAds>$wpcSettings['wpClassified_ads_per_page']){
	echo "Pages: ";
	for ($i=0; $i<$numAds/$wpcSettings['wpClassified_ads_per_page']; $i++){
		if ($i*$wpcSettings['wpClassified_ads_per_page']==$_GET['start']){
			echo " <b>".($i+1)."</b> ";
		} else {
			echo " <a href=\"".$PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lists_id=".$_GET['lists_id']."&start=".($i*$wpcSettings['wpClassified_ads_per_page'])."\">".($i+1)."</a> ";
		}
	}
}
?>
	<table width=100% cellpadding=3 cellspacing=0 border=0>
	<tr>
		<th align=left><?php echo __("Actions");?></th>
		<th align=left><?php echo __("Ads");?></th>
		<th align=left><?php echo __("Author");?></th>
		<th align=right><?php echo __("Views");?></th>
		<th align=right><?php echo __("Date");?></th>
	</tr>
	<?php
	for ($x=0; $x<count($ads); $x++){
		$ad = $ads[$x];
		$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&";
		$slab = ($ad->sticky!='y')?"Sticky":"Unsticky";
		$act = ($ad->status=='open')?"De-activate":"Activate";
		$tlinks = array(
			"<a xhref=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&adm_action=editAds\">".__("Edit")."</a>",
			"<a href=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&adm_action=stickyAds\">".__($slab)."</a>",
			"<a href=\"".$linkb."xtid=".$ad->ads_subjects_id."&adm_action=activateAds\">".__($act)."</a>",
				"<a href=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&adm_action=deleteAds\">".__("Delete")."</a>"
		);
		?>
		<tr>
			<td><small>
			<?php echo @implode(" | ", $tlinks);?>
			</small></td>
			<td align=left><strong><a href="<?php echo $linkb;?>ads_subjects_id=<?php echo $ad->ads_subjects_id;?>"><?php echo $ad->subject;?></a></strong></td>
			<td align=left><?php echo create_ads_subject_author($ad);?></td>

			<td align=right><?php echo $ad->views;?></td>
			<td align=right><?php echo @date($wpcSettings['wpClassified_date_string'], $ad->date);?></td>
			</tr>
			<?php
		}
		?>
		</table></td></tr></table>
		<?php
		} elseif ($loadpage==true){
			$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
			$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists ORDER BY position ASC");
			for ($i=0; $i<count($tlists); $i++){
				$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
			}
		?>
		<hr>
		<table border=0 width=100%>
			<tr>
			<th></th>
			<th align=left><?php echo __("Category/List");?></th>
			<th align=right width=100><?php echo __("Subjects");?></th>
			<th align=right width=100><?php echo __("Ads");?></th>
			<th align=right width=100><?php echo __("Views");?></th>
		</tr>
		<?php
			for ($x=0; $x<count($categories); $x++){
				$category = $categories[$x];
				?>
				<tr>
					<td colspan=2><h3><?php echo $category->name;?></h3></td>
					<td colspan=3></td>
				</tr>
				<?php
				$tfs = $lists[$category->categories_id];
				for ($i=0; $i<count($tfs); $i++){
					?>
					<tr>
					<td></td>
					<td><small>(<?php echo __($liststatuses[$tfs[$i]->status]);?>)</small> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&lists_id=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
					<td align=right><?php echo $tfs[$i]->ads_status;?></td>
					<td align=right><?php echo $tfs[$i]->ads;?></td>
					<td align=right><?php echo $tfs[$i]->ads_views;?></td>
				</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}
}



function wpClassified_page_handle_title($title){
	$wpcSettings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpcSettings['wpClassified_slug'], $title);
}

function wpClassified_page_handle_pagetitle($title){
	$wpcSettings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpcSettings['wpClassified_slug'], $title);
}

function wpClassified_page_handle_content($content){
   	$wpcSettings = get_option('wpClassified_data');
	require_once(dirname(__FILE__)."/functions.php");
	$content = preg_replace( "/\[\[WP_CLASSIFIED\]\]/ise", "wpClassified_process()", $content); 
	return $content;
}

function wpClassified_page_handle_titlechange($title){
	$wpcSettings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpcSettings["wpClassified_slug"], $title);
}

?>