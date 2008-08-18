<?php
/*
Plugin Name: wpClassified
Plugin URI: http://forgani.com/index.php/tools/wpclassiefied-plugins/
Description: The wpClassified plugin allows you to add a simple classifieds page in to your wordpress blog
Author: Mohammad Forgani
Version: 1.2.0-b
Requires at least: 2.3.x
Author URI: Mohammad Forgani http://www.forgani.com

I create and tested on Wordpress version 2.3.2 
on default and unchanged Permalink structure.

demo: http://www.bazarcheh.de/?page_id=92


Release Notes:

Version 1.0.0 - 1/04/2008
- Added Uninstall 

Version 1.0.1 - 1/04/2008
- fix bugs
- implement a new structure

Version 1.0.2 - March 16/2008
- update to display the links to ads at the top of page 

Version 1.1.0 - May 12/2008
- update delete/modify ads function .
- added Move ads function to admin interface.
- fixed some issue which are posted to me.
- using Permalinks. Example to update .htaccess Rewrite Rules.

Version 1.1.1 - June 03/2008
- fix the search function
- implement RSS Feeds
- add admin email notification

Version 1.2.0 - Augst 10/08/2008
Changes August 10/2008
- update {table_prefix}wpClassified_ads_subjects 
and added some new fields email, web, phone, ...
- implement the conformaion code (captcha)
- implement language files (The Work is Not Finished!)

Permalink structure:
You will find an example for .htaccess file that uses to redirect 
to wpClassified in the README file

*/

//require_once('settings.php');

///////////////////////
require_once(dirname(__FILE__).'/settings.php');
////////////////////////////////////////

add_filter("the_content", "wpClassified_page_handle_content");
add_filter("the_title", "wpClassified_page_handle_title");
add_filter("wp_list_pages", "wpClassified_page_handle_titlechange");
add_filter("single_post_title", "wpClassified_page_handle_pagetitle");

if (function_exists('add_action')) {
	add_action('admin_menu', 'wpcAdmpage');
}

add_action('template_redirect', 'rss_feed');

// wpClassified settings 
function wpcOptions_process(){
	global $_GET, $_POST, $wp_rewrite, $PHP_SELF, $wpdb, $table_prefix, $wpClassified_version, $wp_version, $lang;
	ShowImg();
    ?>
	<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
	<?php
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
				$wpdb->query("insert into {$table_prefix}posts (post_author, post_date, post_date_gmt, post_content, post_title, post_category, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, post_type, menu_order) values ('1', '2008-04-27 22:30:57', '2008-04-02 22:30:57', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', '0', '[[WP_CLASSIFIED]]', 'publish', '', '', '', 'classified', '', '', '2008-04-27 22:30:57', '2008-04-27 22:30:57', '[[WP_CLASSIFIED]]', '0', '', 'page', '0')");
			}
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
	$pageinfo = get_wpClassified_pageinfo();
	if ($pageinfo == false){
		echo "<h2>The wpClassified Page not found.</h2>";
	?>
	<hr />	
	<h2><?php _e('Create wpClassified Page', 'wpClassified'); ?></h2>
	<p style="text-align: left;">
	<?php _e('The wpClassified plugin Page will be created automatically', 'wpClassified'); ?>
	</p>
	<form method="post" id="create_wpcOptions" name="create_wpcOptions" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=createpage">
	<p style="text-align: center;">
	<input type="submit" name="do" value="<?php _e('wpClassified Create Page', 'wpClassified'); ?>" class="button" />
	</p>
	</form>
	<pre>
	<h3>Or you can create the page manually in 3 steps:</h3>

 1- Go to 'WP-Admin -> Write -> Write Page' 
 2- Type in the post's title area [[WP_CLASSIFIED]]
 3- Type '[[WP_CLASSIFIED]]' in the post's content area (without the quotes) 
</pre>

	<?
		 return null;
	};

	$url = ($wp_rewrite->get_page_permastruct()=="")?"<a href=\"".get_bloginfo('wpurl')."/index.php?pagename=classified\">".get_bloginfo('wpurl')."/index.php?pagename=classified</a>":"<a href=\"".get_bloginfo('wpurl')."/wpClassified/\">".get_bloginfo('wpurl')."/wpClassified/</a>";
    
	?>
	<p>
	<form method="post" id="wpcOptions" name="wpcOptions" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=savesettings">

<table><tr valign="top"><td>
<fieldset class="fieldset">
	<legend class="legend"><strong>Classifed Page Details</strong></legend>
	<table width="99%">
	<input type=hidden name="wpClassified_data[wpClassified_version]" value="<?php echo $wpClassified_version;?>">
		<tr>
			<th align="right" valign="top"><?php echo __("wpClassified Version:");?> </th>
			<td><?php echo $wpClassified_version;?></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("wpClassified URL: ");?></th>
			<td><?php echo $url;?></td>
		</tr>
<tr>
			<th align="right" valign="top"><?php echo __("Classified Top Image:");?> </th>
			<td>
<input type=hidden name="wpClassified_data[classified_top_image]" value="<?php echo $wpcSettings['classified_top_image'];?>">
			<?

	echo "\n<select name=\"topImage\" onChange=\"showimage()\">";	  
	$rep = ABSPATH."wp-content/plugins/wp-classified/images/";
	$handle=opendir($rep);
	while ($file = readdir($handle)) {
		$filelist[] = $file;
	}
	asort($filelist);
	while (list ($key, $file) = each ($filelist)) {
		
		if (!ereg(".gif|.jpg|.png",$file)) {
			if ($file == "." || $file == "..") $a=1;
		} else {
			if ($file == $wpcSettings['classified_top_image']) {
				echo "\n<option value=$file selected>$file</option>\n";
			} else {
				echo "\n<option value=$file>$file</option>\n";
			}
		}
	}
	echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/images/" . $wpcSettings['classified_top_image'] ."\" class=\"imgMiddle\"><br />";
	?>		
			<span class="smallTxt">images from plugins/wp-classified/images directory</span></td>
		</tr>		
	<tr>
			<th align="right" valign="top"><?php echo __("Classified Description:");?></th>
			<td><input type="text" size=40 name="wpClassified_data[description]" value="<?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['description']));?>"></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[show_credits]" value="y"<?php echo ($wpcSettings['show_credits']=='y')?" checked":"";?>> <?php echo __("Display wpClassified credit line at the bottom of page.");?></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("wpClassified Page Link Name: ");?></th>
			<td><input type="text" name="wpClassified_data[wpClassified_slug]" value="<?php echo $wpcSettings['wpClassified_slug'];?>"></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("Max. Ad image size: ");?></th>
			<td>Width: <input type="text" size="5" name="wpClassified_data[image_width]" value="<?php echo $wpcSettings['image_width'];?>"> X Height: <input type="text" size="5" name="wpClassified_data[image_height]" value="<?php echo $wpcSettings['image_height'];?>"><br /><span class="smallTxt">example: 100x150</span></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("Ad Image Alignment: ");?> </th>
			<td><input type=text size=11 name="wpClassified_data[image_alignment]" value="<?php echo ($wpcSettings['image_alignment']);?>"><br /><span class="smallTxt">choose: left or right</span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[must_registered_user]" value="y"<?php echo ($wpcSettings['must_registered_user']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot post.");?></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[view_must_register]" value="y"<?php echo ($wpcSettings['view_must_register']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot view.");?></td>
		</tr>
		<tr>
		<th></th>
		<td><input type=checkbox name="wpClassified_data[display_unregistered_ip]" value="y"<?php echo ($wpcSettings['display_unregistered_ip']=='y')?" checked":"";?>> <?php echo __("Display first 3 octets of unregistered visitors ip.");?></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_display_titles]" value="y"<?php echo ($wpcSettings['wpClassified_display_titles']=='y')?" checked":"";?>> <?php echo __("Display user titles on classified.");?></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_filter_posts]" value="y"<?php echo ($wpcSettings['wpClassified_filter_posts']=='y')?" checked":"";?>> <?php echo __("Apply WP Ad/comment filters to classified posts.");?></td>
		</tr>

		<tr>
			<th align="right" valign="top"><?php echo __("Banner Code:");?> </th>
			<td><textarea cols=40 rows=3 name="wpClassified_data[banner_code]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['banner_code']));?></textarea></td>
		</tr>			
	</table>
</fieldset>
</td></tr><tr><td>
<fieldset class="fieldset">
<legend class="legend"><strong>Tools</strong></legend>
<table width="99%"><tr><td>
		<tr>
			<th align="right"><?php echo __("Posting Style: ");?></th>
			<td><select name="wpClassified_data[wpc_edit_style]">
			<option value="tinymce"<?php echo ($wpcSettings["wpc_edit_style"]=="tinymce")?" selected":"";?>>HTML with TinyMCE (inline wysiwyg)</option>
			<option value="plain">No HTML, No BBCode</option>
			</select></td>
		</tr>
		
		<tr>
			<th align="right" valign="top"><?php echo $lang['_ADMAXLIMIT'];?></th>
			<td><input type=text size=4 name="wpClassified_data[count_ads_max_limit]" value="<?php echo ($wpcSettings['count_ads_max_limit']);?>"></td><tr><td colspan=2><span class="smallTxt"><?php echo $lang['_ADMAXLIMITTXT']; ?></span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[editor_toolbar_basic]" value="y"<?php echo ($wpcSettings['editor_toolbar_basic']=='y')?" checked":"";?>> <?php echo __("Use basic toolbars in editor.");?></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[notify]" value="y"<?php echo ($wpcSettings['notify']=='y')?" checked":"";?>> <?php echo __("Notify Admin (email) on new Topic/Post");?></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("Ads displayed per page");?></th>
			<td><input type=text size=4 name="wpClassified_data[count_ads_per_page]" value="<?php echo ($wpcSettings['count_ads_per_page']);?>"><br /><span class="smallTxt">default: 10</span></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("Date Format String");?></th>
			<td><input type=text size=11 name="wpClassified_data[date_format]" value="<?php echo ($wpcSettings['date_format']);?>"><br><span class="smallTxt">example: m-d-Y g:i a</span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[rss_feed]" value="y"<?php echo ($wpcSettings['rss_feed']=='y')?" checked":"";?>> <?php echo __("Allow RSS Feeds");?></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo __("Number of Recent Posts to feed");?></th>
			<td><input type=text size=11 name="wpClassified_data[rss_feed_num]" value="<?php echo ($wpcSettings['rss_feed_num']);?>"><br>
			<span class="smallTxt"> example: 15</span></td>
		</tr>
</table>
</fieldset>
</td></tr><tr><td>
<fieldset class="fieldset">
<legend class="legend"><strong><?php echo $lang['_NEWADDURATION'];?></strong></legend>
<table width="99%"><tr><td>
	<tr>
			<th align="right" valign="top"><?php echo $lang['_NEWADDEFAULT'];?></th>
			<td><input type=text size=11 name="wpClassified_data[ad_expiration]" value="<?php echo ($wpcSettings['ad_expiration']);?>"><br><span class="smallTxt">Ads will auto-removed this many days after the ad is created. default:90 days</span></td>
	</tr>
	<tr>
			<th align="right" valign="top"><?php echo $lang['_SENDREMIDE'];?></th>
			<td><input type=text size=11 name="wpClassified_data[inform_user_expiration]" value="<?php echo ($wpcSettings['inform_user_expiration']);?>"><br><span class="smallTxt">(is currently not implemented!) example: 7 days</span></td>
	</tr>
	<tr>
			<th align="right" valign="top"><?php echo $lang['_NOTMESSAGE'];?></th>
			<td><?php echo $lang['_NOTMESSAGESUBJECT'];?>
			<textarea cols=70 rows=5 name="wpClassified_data[not_message]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['not_message']));?>!sitename reminder: classified ads expiring soon!</textarea></td></tr><tr><td align="right" colspan=2>
			<span class="smallTxt">Substitution variables: !sitename = your website name, !siteurl = your site's base URL, !user_ads_url = link to user's classified ads list.</span></td></tr>
			<tr><th align="right" valign="top"><?php echo $lang['_NOTMESSAGEBODY'];?></th><td><textarea cols=70 rows=5 name="wpClassified_data[not_message]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['not_message']));?>One or more of your classified ads on !sitename (!siteurl) are expiring soon.  Please sign in and visit !user_ads_url to check your ads.</textarea></td>
	</tr>
</table>
</fieldset>
</td></tr></table>
<p><input type=submit value="<?php echo __("Update wpClassified Settings");?>"></p>
	</form>
	</p>
	<?php
}


function wpClassified_process(){
	global $_GET, $_POST, $wpc_user_info, $table_prefix, $wpdb;
	$wpcSettings = get_option('wpClassified_data');
	get_user_info();
	?>
	<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
	<?php
	
	switch ($_GET['_action']){
		default:
		case "classified": wpc_index();
		break;
		case "search": display_search($_POST['search_terms']);
		break;
		case "vl": get_wpc_list($msg);
		break;
		case "pa": add_ads_subject();
		break;
		case "ea": _edit_ad();
		break;
		case "da": _delete_ad();
		break;
		case "va": _display_ad();
		break;
		case "prtad": _print_ad();
		break;
		case "sndad": _send_ad();
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


function adm_sync_count($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads.status = 'active'");
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads_subjects.status = 'open'");
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = '".$posts."', ads_status = '".$ads."' WHERE lists_id = '".$id."'");
}


function wpcAdmpage(){
	global $wpc_admin_menu, $wpc_admin_menu, $wpc_user_level;
	add_management_page($wpc_admin_menu, $wpc_admin_menu, $wpc_user_level, 'wpClassified', 'wpClassified_adm_page');
}


function wpClassified_adm_page(){
	global $_GET, $_POST, $PHP_SELF, $wpdb, $adm_links, $table_prefix;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$t = $table_prefix.'wpClassified';
	if(! $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
	    wpClassified_install();
	}
	
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
				case "wpcOptions":
				default:
					wpcOptions_process();
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
	//if ($wpcSettings['wpClassified_installed']!='y' || !$wpcSettings['wpClassified_slug']){
		$wpcSettings['wpClassified_installed'] = 'y';
		$wpcSettings['userfield'] = get_wpc_user_field();
		$wpcSettings['show_credits'] = 'y';
		$wpcSettings['wpClassified_slug'] = 'Classifieds';
		$wpcSettings['description'] = '';
		$wpcSettings['must_registered_user'] = 'n';
		$wpcSettings['view_must_register'] = 'n';
		$wpcSettings['display_unregistered_ip'] = 'y';
		$wpcSettings['notify'] = 'y';
		$wpcSettings['wpClassified_display_titles'] = 'y';
		$wpcSettings['editor_toolbar_basic'] = 'y';
		$wpcSettings['wpClassified_filter_posts'] = 'y';
		$wpcSettings['rss_feed'] = 'y';
		$wpcSettings['rss_feed_num'] = 15;
		$wpcSettings['count_ads_per_page'] = 10;
		$wpcSettings['count_ads_max_limit'] = 500;
		$wpcSettings['image_width'] = 150;
		$wpcSettings['image_height'] = 200;
		$wpcSettings['date_format'] = 'm-d-Y g:i a';
		$wpcSettings['wpClassified_unread_color'] = '#FF0000';
		$wpcSettings['image_alignment'] = 'left';
		$wpcSettings['classified_top_image'] = 'default.gif';
		$wpcSettings['wpClassified_read_user_level'] = -1;
		$wpcSettings['wpClassified_write_user_level'] = -1;
		$wpcSettings['banner_code'] = '';
		$wpcSettings['wpClassified_display_last_ads_subject'] = 'y';
		$wpcSettings['wpClassified_display_last_post_link'] = 'y';
		$wpcSettings['wpClassified_last_ads_subject_num'] = 5;
		$wpcSettings['wpClassified_last_ads_subjects_author'] = "y";
		$wpcSettings['ad_expiration'] = "90";
	//}
	update_option('wpClassified_data', $wpcSettings);
}

function wpClassified_check_db(){
	global $_GET, $_POST, $wpdb, $table_prefix;
	$t = $table_prefix.'wpClassified';
	include("wpClassified_db.php");
	if($_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
		return;
	} else {
		wpClassified_db();
	}
}

function adm_structure_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb;

	ShowImg();
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
			if ($_GET['lid']==0){
				$position = $wpdb->get_var("SELECT MAX(position) FROM {$table_prefix}wpClassified_lists")+1;
				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_lists (wpClassified_lists_id, name, description, position, status) values ('".($_POST['wpClassified_data']['wpClassified_lists_id']*1)."', '".$wpdb->escape($_POST['wpClassified_data']['name'])."', '".$wpdb->escape($_POST['wpClassified_data']['description'])."', '".$position."', '".$wpdb->escape($_POST['wpClassified_data']['status'])."')");
			} else {
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET status = '".$wpdb->escape($_POST['wpClassified_data']['status'])."', wpClassified_lists_id = '".($_POST['wpClassified_data']['wpClassified_lists_id']*1)."', name = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['name']))."', description = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['description']))."' WHERE lists_id = '".($_GET['lid']*1)."'");
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
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = '".$ginfo['wpClassified_lists_id']."' && position < '".$ginfo['position']."' ORDER BY position DESC", ARRAY_A);
			if ($above['lists_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$above['position']."' WHERE lists_id = '".($_GET['lid']*1)."'");
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
			$ginfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
			$above = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = '".$ginfo['wpClassified_lists_id']."' && position > '".$ginfo['position']."' ORDER BY position ASC", ARRAY_A);
			if ($above['lists_id']>0){
				$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET position = '".$above['position']."' WHERE lists_id = '".($_GET['lid']*1)."'");
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
	<form method="post" id="admCatStructure" name="admCatStructure" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveCategory&categories_id=<?php echo $_GET['categories_id'];?>">
		<table border=0 class="editform">
		<tr><th align="right"><?php echo __("Category Name");?></th>
		<td><input type=text size=50 name="wpClassified_data[name]" value="<?php echo $categoryinfo['name'];?>"></td>
		</tr>

	<th align="right" valign="top"><?php echo __("Category Photo");?> </th>
	<td>
	<input type=hidden name="wpClassified_data[photo]" value="<?php echo $categoryinfo['photo'];?>">
	<?

	echo "\n<select name=\"topImage\" onChange=\"showCatimage()\">";	  
	$rep = ABSPATH."wp-content/plugins/wp-classified/images/";
	$handle=opendir($rep);
	while ($file = readdir($handle)) {
		$filelist[] = $file;
	}
	asort($filelist);
	while (list ($key, $file) = each ($filelist)) {
		
		if (!ereg(".gif|.jpg|.png",$file)) {
			if ($file == "." || $file == "..") $a=1;
		} else {
			if ("images/" . $file == $categoryinfo['photo']) {
				echo "\n<option value=images/$file selected>images/$file</option>\n";
			} else {
				echo "\n<option value=images/$file>images/$file</option>\n";
			}
		}
	}
	echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/" . $categoryinfo['photo'] ."\" class=\"imgMiddle\"><br />";
	?>		
	<span class="smallTxt">images from plugins/wp-classified/images directory</span></td>
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
		$classifiedinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
	?>
	<p>
	<form method="post" id="admLstStructure" name="admLstStructure" action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=saveList&lid=<?php echo $_GET['lid'];?>">
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
		 <input<?php echo (count($categories)<1)?" disabled":"";?> type=button value="Add List" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=0';">
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
		<th align=right width=100><?php echo __("Ad");?></th>
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
					<td><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupList&lid=<?php echo $tfs[$i]->lists_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownList&lid=<?php echo $tfs[$i]->lists_id;?>">&darr;</a></td>
					<td><span style="font-size: 10px;">(<?php echo $liststatuses[$tfs[$i]->status];?>)</span> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
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
			<form method="post" id="admUser" name="admUser" enctype="multipart/form-data"
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
	global $_GET, $_POST, $wpdb, $table_prefix;
      
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
			$deactivate_url = 'plugins.php?action=deactivate&plugin=wp-classified/wpClassified.php';
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
	
	<form method="post" id="admUtilities" name="admUtilities"
			 action="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=uninstall">
	<p style="text-align: center;">
	<br />
	<input type="submit" name="do" value="<?php _e('UNINSTALL wpClassified', 'wpClassified'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall wpClassified From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wpClassified'); ?>')" />
        </p>
	</form>
	</p>
	<?
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


function wpClassified_search_highlight($keywords,$post,$bgcolors='yellow'){
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
		$regex1 = ">[^<]*(";
		$regex2 = ")[^<]*<";
		preg_match_all("/".$regex1.$keyword.$regex2."/i", $post, $matches, PREG_PATTERN_ORDER);
		foreach($matches[0] as $match){
			preg_match("/$keyword/i", $match, $out);
			$search_word = $out[0];
			$newtext = str_replace($search_word,"<span style=\"background-color: ".$bgcolors[($word_no % $no_colors)].";\">$search_word</span>", $match);
			$post = str_replace($match, $newtext, $post);
		}
		$word_no++;
	}
	return $post;
}

function create_post_html($post){
	global $_GET, $_POST, $user_login, $userdata, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();
	switch ($wpcSettings["wpc_edit_style"]){
		case "plain":
		default:
			$post->post = nl2br(str_replace("<", "&lt;", $post->post));
			break;
		case "html":
		case "quicktags":
		case "tinymce":
			$post->post = nl2br($post->post);
			break;
	}

	if ($wpcSettings['wpClassified_filter_posts']=='y'){
		$post->post = apply_filters('comment_text', nl2br($post->post));
	}
	$keyword = explode(" ",$_GET['search_words']);
	$colors[0]=$wpcSettings['wpClassified_highlight_color'];
	$post->post = wpClassified_search_highlight($keyword,$post->post,$colors);
	return $post->post;

}

function update_ads_views($ads_subjects_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET views = views".$sign."1 WHERE ads_subjects_id = '".((int)$ads_subjects_id)."'");
}

function update_posts($lists_id, $sign="+", $num=1){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = ads".$sign.$num." WHERE lists_id = '".((int)$lists_id)."'");
}

function update_ads($lists_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_status = ads_status".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
}

function update_views($lists_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_views = ads_views".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
}


function get_last_ads_subjects(){
	global $wpdb, $table_prefix;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();

	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
			 LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
			 LEFT JOIN {$table_prefix}users AS lu
			 ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
			 WHERE {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
			 ORDER BY {$table_prefix}wpClassified_ads_subjects.date DESC
			 LIMIT 0, ".((int)$wpcSettings['wpClassified_last_ads_subject_num'])." ");

	$htmlout = "<ul>";
	if (is_array($ads)){
		foreach ($ads as $ad){	
			$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$ad->ads_subjects_id."'", ARRAY_A);

			$pstart = $pstart['count']/$wpcSettings['count_ads_per_page'];
			$pstart = (ceil($pstart)*$wpcSettings['count_ads_per_page'])-$wpcSettings['count_ads_per_page'];

			$name = $wpdb->get_row("SELECT name FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".$ad->ads_subjects_list_id."'", ARRAY_A);

			$htmlout .= "<li>".create_public_link("lastAd", array(
					"name" => $ad->subject,
					"lid"=> $ad->ads_subjects_list_id,
					"name" => $name['name'],
					"asid"=> $ad->ads_subjects_id,
					"start" => $pstart,
			));
			if ($wpcSettings['wpClassified_last_ads_subjects_author']=='y'){
				$wpcSettings['description'] = '';
				if 	($ad->last_author>0){
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

// function that echo's the textarea/whatever for post input
function create_ads_input($content=""){
	global $wpdb, $table_prefix, $wp_filesystem;
	$wpcSettings = get_option('wpClassified_data');
	switch ($wpcSettings["wpc_edit_style"]){
		case "plain":
		default:
			echo "<textarea name='wpClassified_data[post]' id='wpClassified_data[post]' cols='40' rows='7'>".str_replace("<", "&lt;", $content)."</textarea>";
		break;
		case "tinymce":
			 $mode="advanced";
			 if ($wpcSettings['editor_toolbar_basic']=='y') $mode="simple";
	
			echo '<script language="javascript" type="text/javascript" src="' .get_bloginfo('wpurl').  '/wp-content/plugins/wp-classified/includes/tinymce/tiny_mce.js"></script>';
		?>
			<script language="javascript" type="text/javascript" src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/tinymce/tiny_mce_gzip.php"></script>

			<script language="javascript" type="text/javascript">
			tinyMCE.init({
			mode : "textareas",
			<?php echo "theme : \"" . $mode ."\"" ?>
		});
		</script>
		<textarea name="wpClassified_data[post]" id="wpClassified_data[post]" cols='60' rows='20' style="width:100%;"><?php echo htmlentities($content);?></textarea>
		<?php
		break;
	}
}

function ShowImg() {
echo "<script type=\"text/javascript\">\n";
	echo "<!--\n\n";
	echo "function showimage() {\n";
	echo "if (!document.images)\n";
	echo "return\n";
	echo "document.images.avatar.src=\n";
	echo "'".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/' + document.wpcOptions.topImage.options[document.wpcOptions.topImage.selectedIndex].value;\n";
	echo 'document.wpcOptions.elements["wpClassified_data[classified_top_image]"].value = document.wpcOptions.topImage.options[document.wpcOptions.topImage.selectedIndex].value;';
		echo "}\n\n";

	echo "function showCatimage() {\n";
	echo "if (!document.images)\n";
	echo "return\n";
	echo "document.images.avatar.src=\n";
	echo "'".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/' + document.admCatStructure.topImage.options[document.admCatStructure.topImage.selectedIndex].value;\n";
	echo 'document.admCatStructure.elements["wpClassified_data[photo]"].value = document.admCatStructure.topImage.options[document.admCatStructure.topImage.selectedIndex].value;';
		echo "}\n\n";


		echo "//-->\n";
		echo "</script>\n"; 
}


// 

?>
