<?php
/*
Plugin Name: WP-Classified
Plugin URI: http://forgani.com/index.php/tools/wpclassiefied-plugins/
Description: The WP-Classified plugin allows you to add a simple classifieds page in to your wordpress blog
Author: Mohammad Forgani
Version: 1.0  (initial version)
Requires at least: 2.3.x
Author URI: Mohammad Forgani http://www.forgani.com

I create and tested on Wordpress version 2.3.2 
on default and unchanged Permalink structure.


Uninstalling the plugin:

For uninstalling the plugin simply delete the wp-classified directory from the /wp-content/plugins/ directory.
You even don?t need to deactivate the plugin in the WordPress admin menu.
And you remove the page and tables, which are installed by the plugins with drop table in phpMyAdmin.


demo: http://www.bazarcheh.de/?page_id=92

*/

global $table_prefix, $wpdb;
if (!$table_prefix){
	$table_prefix = $wpdb->prefix;
}

$wpClassified_user_info = array();
function wpClassified_get_user_info(){
	global $table_prefix, $wpdb, $user_ID, $wpClassified_user_info;
	get_currentuserinfo();
	$wpClassified_user_info = $wpdb->get_row("SELECT * from {$table_prefix}users
					LEFT JOIN {$table_prefix}wpClassified_user_info
					ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
					WHERE {$table_prefix}users.ID = '".(int)$user_ID."'", ARRAY_A);
}

function wpClassified_is_admin(){
	global $wpClassified_user_info;
	return ($wpClassified_user_info["permission"]=="administrator")?true:false;
}

function wpClassified_is_mod($classified=0){
	global $wpdb, $wpClassified_user_info, $table_prefix;
	return ($wpClassified_user_info["permission"]=="moderator")?true:false;
}

function wpClassified_is_loggedin(){
	global $wpClassified_user_info;
	return ((int)$wpClassified_user_info["ID"])?true:false;
}

$_GET["start"] = ereg_replace("[^0-9]", "", $_GET["start"]);
$_REQUEST["start"] = ereg_replace("[^0-9]", "", $_REQUEST["start"]);
$_GET["pstart"] = ereg_replace("[^0-9]", "", $_GET["pstart"]);
$_REQUEST["pstart"] = ereg_replace("[^0-9]", "", $_REQUEST["pstart"]);

if (!$_GET)$_GET = $HTTP_GET_VARS;
if (!$_POST)$_POST = $HTTP_POST_VARS;
if (!$_SERVER)$_SERVER = $HTTP_SERVER_VARS;
if (!$_COOKIE)$_COOKIE = $HTTP_COOKIE_VARS;

// user level
$wpClassified_user_level = 8;
$wpClassified_version = 1.0;
$wp_mainversion = "2";  // wordpress version 2.x
$user_field = "display_name";
$wpClassified_admin_page_name = 'WP-Classified Admin';
$wpClassified_wp_pageinfo = false;


function wpClassified_is_logged_in(){
	global $user_ID;
	get_currentuserinfo();
	if ((int)$user_ID)return true;
	return false;
}

function wpClassified_get_page(){
	global $wpdb, $wpClassified_wp_pageinfo, $table_prefix;
	if ($wpClassified_wp_pageinfo == false){
		$wpClassified_wp_pageinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
		if ($wpClassified_wp_pageinfo["post_title"]!="[[WP_CLASSIFIED]]"){
			return false;
		}
	}
	return $wpClassified_wp_pageinfo;
}

$wpClassified_admin_links = array(
		array(name=>'List Settings',arg=>'wpClassifiedsettings'),
		array(name=>'List Structure',arg=>'wpClassifiedstructure'),
		array(name=>'List Ads/Ads Admin',arg=>'wpClassified_adssubjects_posts'),
		array(name=>'Users Admin',arg=>'wpClassifiedusers'),
		array(name=>'Utilities',arg=>'wpClassifiedutilities'),
		);

function wpClassified_add_admin_page(){
	global $wpClassified_user_level, $wpClassified_admin_page_name;
	add_management_page($wpClassified_admin_page_name, $wpClassified_admin_page_name, $wpClassified_user_level, 'wpClassified', 'wpClassified_admin_page');
}




function wpClassified_rewrite_rules(&$rules){
	global $wp_mainversion;
	if ($wp_mainversion=="1"){
		$url = dirname(dirname($_SERVER["PHP_SELF"]));
		$rules['wpClassified/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?(\([^/\(\)]*\))?/?([^/\(\)]*)/?'] = 'index.php?pagename=wpClassified&wpClassified_action=$1&lists_id=$3&ads_subjects_id=$5&ads_id=$6&start=$8&pstart=$8&search_words=$9';
	
	}
	// http://localhost/wordpress/wpClassified/viewList/12/1/
	// http://localhost/wordpress/?page_id=9&wpClassified_action=viewList&lists_id=1&start=0
	return $rules;
}



function wpClassified_mod_rewrite_rules($wp_rewrite){
	global $wp_rewrite;
	$wpClassified_settings = get_option('wpClassified_data');
	$wpClassified_slug = $wpClassified_settings['wpClassified_slug'];
	$wpClassified_rules = array(
$wpClassified_slug.'/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?(\([^/\(\)]*\))?/?' => '/'.$wpClassified_slug.'/index.php?pagename='.$wpClassified_slug.'&wpClassified_action=$matches[1]&lists_id=$matches[3]&ads_subjects_id=$matches[5]&ads_id=$matches[6]&start=$matches[8]&amp;pstart=$matches[8]'
	);
	$wp_rewrite->rules = $wpClassified_rules + $wp_rewrite->rules;
}

add_action('generate_rewrite_rules','wpClassified_rewrite_rules');
add_action('mod_rewrite_rules', 'wpClassified_mod_rewrite_rules');

if ($_REQUEST["wpClassified_action"]){
	$_SERVER["REQUEST_URI"] = dirname(dirname($_SERVER["PHP_SELF"]))."/wpClassified/";
	$_SERVER["REQUEST_URI"] = stripslashes($_SERVER["REQUEST_URI"]);
}

function wpClassified_admin_activate_post($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".$id."'");
	$new = ($cur=='active')?"inactive":"active";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = '".$new."' WHERE ads_id = '".$id."'");
	wpClassified_admin_count_ads($id);
}

function wpClassified_admin_activate_ads_subject($id){
	global $table_prefix, $wpdb, $_GET;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='open')?"closed":"open";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = '".$new."' WHERE ads_subjects_id = '".$id."'");
	wpClassified_admin_sync_count($_GET['lists_id']);
}

function wpClassified_last_octet($ip){
	$ip = explode(".", $ip);
	$ip[count($ip)-1] = "***";
	return @implode(".", $ip);
}

function wpClassified_admin_delete_ads_subject($id){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$linkb = $PHP_SELF."?page=wpClassified&wpClassified_admin_page_arg=".$_GET['wpClassified_admin_page_arg']."&wpClassified_admin_action=deleteAds&lists_id=".$_GET['lists_id']."&start=".$_GET['start'];

	if ($_POST['deleteid']*1>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'inactive' WHERE ads_ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = 'deleted' WHERE ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		wpClassified_admin_sync_count($_GET['lists_id']);
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

function wpClassified_admin_page(){
	global $_GET, $_POST, $PHP_SELF, $user_level, $wpdb, $wpClassified_admin_links, $wpClassified_user_level, $wp_mainversion;
	get_currentuserinfo();
	$wpClassified_settings = get_option('wpClassified_data');
	wpClassified_install();
	?>
	<div class="wrap">
		<ul id="<?php
		if ($wp_mainversion=="2"){
			echo "submenu";
		} else {
			echo "adminmenu2";
		}
		?>">
			<?php
			for ($i=0; $i<count($wpClassified_admin_links); $i++){
				$tlink = $wpClassified_admin_links[$i];
				if ($tlink['arg']==$_GET['wpClassified_admin_page_arg'] || (!$_GET['wpClassified_admin_page_arg'] && $i==0)){
					$sel = " class=\"current\"";
					$pagelabel = $tlink['name'];
				} else {
					$sel = "";
				}
			?>
			<li><a href='<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $tlink['arg'];?>'<?php echo $sel;?>><?php echo __($tlink['name']);?></a></li>
			<?php
			}
			?>
		</ul>
		<h2><?php echo __($pagelabel);?></h2>
			<?php
			switch ($_REQUEST['wpClassified_admin_page_arg']){
				case "wpClassifiedsettings":
				default:
					process_wpClassifiedsettings();
				break;
				case "wpClassifiedstructure":
					process_wpClassifiedstructure();
				break;
				case "wpClassified_adssubjects_posts":
					process_wpClassified_adssubjects_posts();
				break;
				case "wpClassifiedusers":
					process_wpClassifiedusers();
				break;
				case "wpClassifiedutilities":
					process_wpClassifiedutilities();
				break;
			}
		?>
	</div>
	<?php
}




// wpClassified settings 
function process_wpClassifiedsettings(){
	global $_GET, $_POST, $wp_rewrite, $PHP_SELF, $wpdb, $table_prefix, $user_level, $wpClassified_version, $wp_version;
	$selfpage = ($wp_rewrite->get_page_permastruct()=="")?get_bloginfo('wpurl')."/index.php?pagename=wpClassified":get_bloginfo('wpurl')."/wpClassified/";

	switch ($_GET['wpClassified_admin_action']){
		case "savesettings":
			foreach ($_POST["wpClassified_data"] as $k=>$v){
				$_POST["wpClassified_data"][$k] = stripslashes($v);
			}
			$_POST['wpClassified_data']['userfield'] = wpClassified_get_field();
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
	if ($msg!=''){
		?>
		<p>
		<b><?php echo __($msg);?></b>
		</p>
		<?php
	}
	$wpClassified_settings = get_option('wpClassified_data');
	$pageinfo = wpClassified_get_page();
	if ($pageinfo == false){
		echo "<h2>The wpClassified Page not found.</h2>";
	?>
	<hr />	
	<h2><?php _e('Create WP-Classified Page', 'wpClassified'); ?></h2>
	<p style="text-align: left;">
	<?php _e('The WP-Classified plugin Page will be created automatically', 'wpClassified'); ?>
	</p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=createpage">
	<p style="text-align: center;">
	<input type="submit" name="do" value="<?php _e('WP-Classified Create Page', 'WP-Classified'); ?>" class="button" />
	</p>
	</form>
	<pre>
	<h3>Or you can create the page manually in 4 steps:</h3>

 1- Go to 'WP-Admin -> Write -> Write Page' 
 2- Type in the post's title area [[WP_CLASSIFIED]]
 3- Type '[[WP_CLASSIFIED]]' in the post's content area (without the quotes) 
 4- Type 'useronline' in the post's slug area (without the quotes) 
 Click 'Publish' 

If you ARE NOT using nice permalinks, you need to go to 'WP-Admin -> Options -> WP-Classiefied Admin' and under 'WP-Classified URL', you need to fill in the URL to the Classified Page you created above.
</pre>

	<?
		 return null;
	};
	if ($wpClassified_settings['wpClassified_page_url'] == "") $wpClassified_settings['wpClassified_page_url']=$selfpage;
	?>
	<p>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=savesettings">
		<input type=hidden name="wpClassified_data[wpClassified_version]" value="<?php echo $wpClassified_version;?>">
		<table border=0 class="editform"><tr>
			<th align="right"><?php echo __("WP-Classified Version:");?> </th>
				<td><?php echo $wpClassified_version;?></td>
			</tr><tr>
			<th align="right"><?php echo __("WP-Classified Announcement (optional):");?></th>
				<td><textarea cols=65 rows=5 name="wpClassified_data[wpClassified_announcement]"><?php echo str_replace("<", "&lt;", stripslashes($wpClassified_settings['wpClassified_announcement']));?></textarea>
			<tr>
			<th align="right"><?php echo __("WP-Classified URL: ");?> </th>
				<td><input type="text" size=60 name="wpClassified_data[wpClassified_page_url]" value="<?php echo $wpClassified_settings['wpClassified_page_url'];?>"></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_show_credits]" value="y"<?php echo ($wpClassified_settings['wpClassified_show_credits']=='y')?" checked":"";?>> <?php echo __("Display WP-Classified credit line at the bottom of classified pages");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("WP-Classified Page Link Name: ");?></th>
				<td><input type="text" name="wpClassified_data[wpClassified_slug]" value="<?php echo $wpClassified_settings['wpClassified_slug'];?>"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Max Ads Image Size: ");?></th>
				<td>Width: <input type="text" size="5" name="wpClassified_data[wpClassified_image_width]" value="<?php echo $wpClassified_settings['wpClassified_image_width'];?>" onchange="this.value=this.value*1"> X Height: <input type="text" size="5" name="wpClassified_data[wpClassified_image_height]" value="<?php echo $wpClassified_settings['wpClassified_image_height'];?>" onchange="this.value=this.value*1"><br />
				(0 or blank = unlimited)</td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Ads Image Alignment:");?> </th>
				<td><input type=text size=11 name="wpClassified_data[wpClassified_image_alignment]" value="<?php echo ($wpClassified_settings['wpClassified_image_alignment']);?>"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Classifieds Top Image:");?> </th>
				<td><input type=text size=25 name="wpClassified_data[wpClassified_top_image]" value="<?php echo ($wpClassified_settings['wpClassified_top_image']);?>"></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_ads_must_register]" value="y"<?php echo ($wpClassified_settings['wpClassified_ads_must_register']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot post.");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_view_must_register]" value="y"<?php echo ($wpClassified_settings['wpClassified_view_must_register']=='y')?" checked":"";?>> <?php echo __("Unregistered visitors cannot view.");?></td>
			</tr>
			<tr>
			<th align="right"></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_unregistered_display_ip]" value="y"<?php echo ($wpClassified_settings['wpClassified_unregistered_display_ip']=='y')?" checked":"";?>> <?php echo __("Display first 3 octets of unregistered visitors ip (ie - 192.168.0.***).");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_display_titles]" value="y"<?php echo ($wpClassified_settings['wpClassified_display_titles']=='y')?" checked":"";?>> <?php echo __("Display user titles on classified.");?></td>
			</tr>
			<tr>
			<th align="right"></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_filter_posts]" value="y"<?php echo ($wpClassified_settings['wpClassified_filter_posts']=='y')?" checked":"";?>> <?php echo __("Apply WP Ads/comment filters to classified posts.");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_display_last_ads_subject]" value="y"<?php echo ($wpClassified_settings['wpClassified_display_last_ads_subject']=='y')?" checked":"";?>> <?php echo __("Display Last Ads Info In List.");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Posting Style: ");?></th>
				<td><select name="wpClassified_data[wpClassified_ads_style]">
					<option value="tinymce"<?php echo ($wpClassified_settings["wpClassified_ads_style"]=="tinymce")?" selected":"";?>>HTML with TinyMCE (inline wysiwyg)</option>
					<option value="plain">No HTML, No BBCode</option>
					</select></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[editor_toolbar_basic]" value="y"<?php echo ($wpClassified_settings['editor_toolbar_basic']=='y')?" checked":"";?>> <?php echo __("Use basic toolbars in editor.");?></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_display_last_post_link]" value="y"<?php echo ($wpClassified_settings['wpClassified_display_last_post_link']=='y')?" checked":"";?>> <?php echo __("Display Link To Last Ads In List.");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Number Of 'Last Ads'");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_last_ads_subject_num]" value="<?php echo ($wpClassified_settings['wpClassified_last_ads_subject_num']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"></th>
				<td><input type=checkbox name="wpClassified_data[wpClassified_last_ads_subjects_author]" value="y"<?php echo ($wpClassified_settings['wpClassified_last_ads_subjects_author']=='y')?" checked":"";?>> <?php echo __("Display 'Last Ads' Author.");?></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Excerpt Length");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_excerpt_length]" value="<?php echo ($wpClassified_settings['wpClassified_excerpt_length']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Ads Per Page");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_ads_subjects_per_page]" value="<?php echo ($wpClassified_settings['wpClassified_ads_subjects_per_page']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Ads Per Page");?></th>
				<td><input type=text size=4 name="wpClassified_data[wpClassified_ads_per_page]" value="<?php echo ($wpClassified_settings['wpClassified_ads_per_page']);?>" onchange="this.value=this.value*1;"></td>
			</tr>
			<tr>
				<th align="right"><?php echo __("Date Format String");?></th>
				<td><input type=text size=11 name="wpClassified_data[wpClassified_date_string]" value="<?php echo ($wpClassified_settings['wpClassified_date_string']);?>"></td>
			</tr>
			<tr>
				<th align="right" valign="top"><?php echo __("Banner Code:");?> </th>
				<td><textarea cols=45 rows=5 name="wpClassified_data[wpClassified_banner_code]"><?php echo str_replace("<", "&lt;", stripslashes($wpClassified_settings['wpClassified_banner_code']));?></textarea></td>
			</tr>			
			<th></th>
				<td><input type=submit value="<?php echo __("Update WP-Classified Settings");?>"></td>
			</tr>
		</table>
	</form>
	</p>
	<?php
}

	function process_wpClassifiedstructure(){
		global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $user_level;

		switch ($_GET['wpClassified_admin_action']){
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
					    photo = '".$wpdb->escape($_POST['wpClassified_data']['photo'])."' WHERE categories_id = '".($_GET['categories_id']*1)."'"
						);
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
		$wpClassified_settings = get_option('wpClassified_data');
		if ($_GET['wpClassified_admin_action']=='editCategory'){
			$categoryinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'", ARRAY_A);
		?>
		<p>
		<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=saveCategory&categories_id=<?php echo $_GET['categories_id'];?>">
			<table border=0 class="editform">
			<tr><th align="right"><?php echo __("Category Name");?></th>
			<td><input type=text size=50 name="wpClassified_data[name]" value="<?php echo $categoryinfo['name'];?>"></td>
			</tr>
			<th align="right"><?php echo __("Category Photo");?></th>
			<td><input type=text size=50 name="wpClassified_data[photo]" value="<?php echo $categoryinfo['photo'];?>"><br><small>images from plugins/wpClassified/images directory: e.g: 'images/default.jpg' </small></td>
			</tr>
				<tr>
					<th></th>
					<td><input type=submit value="<?php echo __("Save");?>"></td>
				</tr>
			</table>
		</form>
		</p>
		<?php
		} elseif ($_GET['wpClassified_admin_action']=='editList'){
			$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
			$classifiedinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
		?>
		<p>
		<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=saveList&lists_id=<?php echo $_GET['lists_id'];?>">
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
			<input type=button value="Add Category" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=editCategory&categories_id=0';">
			 <input<?php echo (count($categories)<1)?" disabled":"";?> type=button value="Add List" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=editList&lists_id=0';">
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
		<td><sup><h3><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=moveupCategory&categories_id=<?php echo $category->categories_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=movedownCategory&categories_id=<?php echo $category->categories_id;?>">&darr;</a> <?php
		if (count($lists[$category->categories_id])<1){
			?> <a style="text-decoration: none;" href="javascript:deleteCategory('<?php echo rawurlencode($category->name);?>', '<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=deleteCategory&categories_id=<?php echo $category->categories_id;?>');" style="color: red; font-size: 10px;">[Delete]</a><?php
		}
		?></h3></sup></td>
		<td colspan=2><h3><a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=editCategory&categories_id=<?php echo $category->categories_id;?>"><?php echo $category->name;?></a></h3></td>
			<td colspan=3></td>
				</tr>
				<?php
				$tfs = $lists[$category->categories_id];
				for ($i=0; $i<count($tfs); $i++){
					?>
					<tr>
						<td></td>
						<td><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=moveupList&lists_id=<?php echo $tfs[$i]->lists_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=movedownList&lists_id=<?php echo $tfs[$i]->lists_id;?>">&darr;</a></td>
						<td><span style="font-size: 10px;">(<?php echo $liststatuses[$tfs[$i]->status];?>)</span> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=editList&lists_id=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
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
	function process_wpClassifiedusers(){
		global $_GET, $_POST, $wpdb, $table_prefix;
		$wpClassified_settings = get_option('wpClassified_data');
		if ($_GET["wpClassified_admin_action"]=="saveuser"){
			$id = (int)$_GET["id"];
			$update = array();
			foreach ($_POST["wpClassified_user_info"] as $k=>$v){
				$update[] = "$k = '".$wpdb->escape($v)."'";
			}
			$wpdb->query("update {$table_prefix}wpClassified_user_info set ".implode(", ", $update)." where user_info_user_ID = '".$id."'", ARRAY_A);
		}

		switch ($_GET["wpClassified_admin_action"]){
			default:
			case "saveuser":
			case "list":
				$start = (int)$_GET["start"];
				$perpage = ((int)$_GET["perpage"])?(int)$_GET["perpage"]:20;
				$searchfields = array(
					($namefield=wpClassified_get_field()),
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
				<form method="get" id="admin_form_get" action="<?php echo $_SERVER["PHP_SELF"];?>">
					<input type="hidden" name="wpClassified_admin_page_arg" value="<?php echo $_GET["wpClassified_admin_page_arg"];?>" />
					<input type="hidden" name="page" value="wpClassified" />
					<table width="100%">
						<tr>
							<td>Pages: <?php
							$query_string = "perpage=$perpage&wpClassified_admin_page_arg=".$_GET["wpClassified_admin_page_arg"]."&page=wpClassified&term=".urlencode($_GET["term"]);

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
					<td align="center"><a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=edit&id=<?php echo $user["ID"];?>&start=<?php echo $start;?>&perpage=<?php echo $perpage;?>&term=<?php echo urlencode($_GET["term"]);?>">Edit</a></td>
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
									WHERE {$table_prefix}users.ID = '".(int)$_GET['id']."'
									", ARRAY_A);

				$user = $user[0];
				$namefield = wpClassified_get_field();

				$permissions = array("none"=>"User", "moderator"=>"Moderator", "administrator"=>"Administrator");

				?>
				<form method="post" id="cat_form_post" name="cat_form_post" enctype="multipart/form-data"
				 action="<?php echo $_SERVER["PHP_SELF"];?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET["wpClassified_admin_page_arg"];?>&wpClassified_admin_action=saveuser&id=<?php echo $_GET["id"];?>&start=<?php echo $_GET["start"];?>&perpage=<?php echo $_GET["perpage"];?>&term=<?php echo urlencode($_GET["term"]);?>">
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

	function process_wpClassifiedutilities(){
		global $_GET, $_POST, $wpdb, $table_prefix, $wpClassified_wp_pageinfo;
      
		$t = $table_prefix.'wpClassified';
		$wpClassified_settings = get_option('wpClassified_data');
		switch ($_GET["wpClassified_admin_action"]){
			default:
			case "list":
			break;
			case "uninstall":
				$msg = '<p>';
				$msg .= '<h2>'.__('Uninstall WP-Classified', 'wpClassified').'</h2>';
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
				$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=wpClassified/wpClassified.php';
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
		<h2><?php _e('Uninstall WP-Classified', 'wpClassified'); ?></h2>
			
		<p style="text-align: left;">
		<?php _e('Deactivating WP-Classified plugin does not remove any data that may have been created, such as the classifieds options. To completely remove this plugin, you can uninstall it here.', 'wpClassified'); ?>
		</p>
		<p style="text-align: left; color: red">
		<strong><?php _e('WARNING:', 'wpClassified'); ?></strong><br />
		<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.', 'wpClassified'); ?>
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
			 action="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&wpClassified_admin_action=uninstall">
		<p style="text-align: center;">
		<br />
		<input type="submit" name="do" value="<?php _e('UNINSTALL WP-Classified', 'WP-Classified'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall WP-Classified From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wpClassified'); ?>')" />
	        </p>
		</form>
		</p>
		<?
	}

	function process_wpClassified_adssubjects_posts(){
		global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $user_level;
		$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
		$wpClassified_settings = get_option('wpClassified_data');
		$loadpage = true;
		switch ($_GET['wpClassified_admin_action']){
			case "deletepost":
				$loadpage = wpClassified_admin_delete_ads($_GET['ads_id']*1);
			break;
			case "deleteAds":
				$loadpage = wpClassified_admin_delete_ads_subject($_GET['ads_subjects_id']*1);
			break;
			case "closeAds":
				wpClassified_admin_close_ads_subject($_GET['ads_subjects_id']*1);
			break;
			case "activatepost":
				wpClassified_admin_activate_post($_GET['ads_id']*1);
				unset($_GET['ads_id']);
			break;
			case "activateAds":
				wpClassified_admin_activate_ads_subject($_GET['xtid']*1);
				unset($_GET['ads_subjects_id']);
			break;
			case "stickyAds":
				wpClassified_admin_sticky_ads_subject($_GET['ads_subjects_id']*1);
				unset($_GET['ads_subjects_id']);
			break;
			case "moveAds":
				wpClassified_admin_move_ads_subject($_GET['ads_subjects_id']*1, $_GET['new_lists_id']);
			break;
			case "savepost":
				wpClassified_admin_save_post($_GET['ads_id']*1);
			break;
			case "saveAds":
				wpClassified_admin_save_ads_subject($_GET['ads_subjects_id']*1);
			break;
			case "editAds":
				wpClassified_admin_edit_ads_subject($_GET['ads_subjects_id']*1);
				$loadpage = false;
			break;
			case "editAds":
				wpClassified_admin_edit_ads($_GET['ads_id']*1);
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
<?php echo __("In List:");?> <i><a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&lists_id=<?php echo $_GET['lists_id'];?>&start=<?php echo $_GET['start'];?>"><?php echo $lists['name'];?></a></i></h3>
<?php
	for ($i=0; $i<count($posts); $i++){
		$post = $posts[$i];
		$linkb = $PHP_SELF."?page=wpClassified&wpClassified_admin_page_arg=".$_GET['wpClassified_admin_page_arg']."&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&ads_subjects_id=".$_GET['ads_subjects_id']."&";
		$act = ($post->status=='inactive')?"Activate":"De-activate";
		$tlinks = array(
					"<a xhref=\"".$linkb."wpClassified_admin_action=editAds&ads_id=".$post->ads_id."\">".__("Edit")."</a>",
					"<a href=\"".$linkb."wpClassified_admin_action=deletepost&ads_id=".$post->ads_id."\">".__("Delete")."</a>",
					"<a href=\"".$linkb."wpClassified_admin_action=activatepost&ads_id=".$post->ads_id."\">".__($act)."</a>"
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
				<small><?php echo __("Posted By:");?> <strong><?php echo wpClassified_admin_create_post_author($post);?></strong> on <?php echo __(@date($wpClassified_settings['wpClassified_date_string'], $post->date));?></small>
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
						 LIMIT ".($_GET['start']*1).", ".($wpClassified_settings['wpClassified_ads_subjects_per_page']*1)." ");

	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lists_id']*1)."'
							&& status != 'deleted'");
?>
<h3><?php echo __("Viewing List:");?> <strong><?php echo $lists['name'];?></strong></h3>
<a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>"><?php echo __("Back To Lists List");?></a>
<?php

if ($numAds>$wpClassified_settings['wpClassified_ads_subjects_per_page']){
	echo "Pages: ";
	for ($i=0; $i<$numAds/$wpClassified_settings['wpClassified_ads_subjects_per_page']; $i++){
		if ($i*$wpClassified_settings['wpClassified_ads_subjects_per_page']==$_GET['start']){
			echo " <b>".($i+1)."</b> ";
		} else {
			echo " <a href=\"".$PHP_SELF."?page=wpClassified&wpClassified_admin_page_arg=".$_GET['wpClassified_admin_page_arg']."&lists_id=".$_GET['lists_id']."&start=".($i*$wpClassified_settings['wpClassified_ads_subjects_per_page'])."\">".($i+1)."</a> ";
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
		$linkb = $PHP_SELF."?page=wpClassified&wpClassified_admin_page_arg=".$_GET['wpClassified_admin_page_arg']."&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&";
		$slab = ($ad->sticky!='y')?"Sticky":"Unsticky";
		$act = ($ad->status=='open')?"De-activate":"Activate";
		$tlinks = array(
			"<a xhref=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&wpClassified_admin_action=editAds\">".__("Edit")."</a>",
			"<a href=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&wpClassified_admin_action=stickyAds\">".__($slab)."</a>",
			"<a href=\"".$linkb."xtid=".$ad->ads_subjects_id."&wpClassified_admin_action=activateAds\">".__($act)."</a>",
				"<a href=\"".$linkb."ads_subjects_id=".$ad->ads_subjects_id."&wpClassified_admin_action=deleteAds\">".__("Delete")."</a>"
		);
		?>
		<tr>
			<td><small>
			<?php echo @implode(" | ", $tlinks);?>
			</small></td>
			<td align=left><strong><a href="<?php echo $linkb;?>ads_subjects_id=<?php echo $ad->ads_subjects_id;?>"><?php echo $ad->subject;?></a></strong></td>
			<td align=left><?php echo wpClassified_admin_create_ads_subject_author($ad);?></td>

			<td align=right><?php echo $ad->views;?></td>
			<td align=right><?php echo @date($wpClassified_settings['wpClassified_date_string'], $ad->date);?></td>
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
					<td><small>(<?php echo __($liststatuses[$tfs[$i]->status]);?>)</small> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&wpClassified_admin_page_arg=<?php echo $_GET['wpClassified_admin_page_arg'];?>&lists_id=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
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

function wpClassified_admin_create_ads_subject_author($ad){
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	$out = "";
	if ($ad->author==0){
		$out .= $ad->author_name;
	} else {
		$out .= $ad->$userfield;
	}
	return $out;
}


function wpClassified_admin_create_post_author($post){
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
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

function wpClassified_create_post_html($post){
	global $_GET, $_POST, $user_login, $userdata, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb;
	$wpClassified_settings = get_option('wpClassified_data');
	get_currentuserinfo();
	switch ($wpClassified_settings["wpClassified_ads_style"]){
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

	if ($wpClassified_settings['wpClassified_filter_posts']=='y'){
		$post->post = apply_filters('comment_text', nl2br($post->post));
	}
	$keyword = explode(" ",$_GET['search_words']);
	$colors[0]=$wpClassified_settings['wpClassified_highlight_color'];
	$post->post = wpClassified_search_highlight($keyword,$post->post,$colors);
	return $post->post;

}


function wpClassified_admin_sticky_ads_subject($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT sticky FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='y')?"n":"y";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET sticky = '".$new."' WHERE ads_subjects_id = '".$id."'");
}

function wpClassified_admin_delete_ads($id){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$linkb = $PHP_SELF."?page=wpClassified&wpClassified_admin_page_arg=".$_GET['wpClassified_admin_page_arg']."&wpClassified_admin_action=deletepost&lists_id=".$_GET['lists_id']."&start=".$_GET['start']."&ads_subjects_id=".$_GET['ads_subjects_id'];

	if ($_POST['deleteid']*1>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'deleted' WHERE ads_id = '".((int)$_POST['deleteid'])."'");
		wpClassified_admin_sync_count($_GET['lists_id']);
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

function wpClassified_admin_sync_count($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads.status = 'active'");
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads_subjects.status = 'open'");
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = '".$posts."', ads_status = '".$ads."' WHERE lists_id = '".$id."'");
}

function wpClassified_admin_count_ads($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$id)."' && status = 'active'")-1;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET ads = '".$posts."' WHERE ads_subjects_id = '".((int)$id)."'");
}

function wpClassified_update_ads_views($ads_subjects_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET views = views".$sign."1 WHERE ads_subjects_id = '".((int)$ads_subjects_id)."'");
}

function wpClassified_update_posts($lists_id, $sign="+", $num=1){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = ads".$sign.$num." WHERE lists_id = '".((int)$lists_id)."'");
}

function wpClassified_update_ads($lists_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_status = ads_status".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
}

function wpClassified_update_views($lists_id, $sign="+"){
	global $wpdb, $table_prefix;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_views = ads_views".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
}

// install function to create the db tables.
function wpClassified_install(){
	global $wpClassified_version;
	wpClassified_check_db();
	$wpClassified_settings = get_option('wpClassified_data');
	$wpClassified_settings['wpClassified_version'] = $wpClassified_version;
	if ($wpClassified_settings['wpClassified_installed']!='y'){
		$wpClassified_settings['wpClassified_installed'] = 'y';
		$wpClassified_settings['userfield'] = wpClassified_get_field();
		$wpClassified_settings['wpClassified_show_credits'] = 'y';
		$wpClassified_settings['wpClassified_slug'] = 'Classifieds';
		$wpClassified_settings['wpClassified_ads_must_register'] = 'n';
		$wpClassified_settings['wpClassified_view_must_register'] = 'n';
		$wpClassified_settings['wpClassified_unregistered_display_ip'] = 'y';
		$wpClassified_settings['wpClassified_display_titles'] = 'y';
		$wpClassified_settings['editor_toolbar_basic'] = 'y';
		$wpClassified_settings['wpClassified_filter_posts'] = 'y';
		$wpClassified_settings['wpClassified_ads_subjects_per_page'] = 10;
		$wpClassified_settings['wpClassified_ads_per_page'] = 10;
		$wpClassified_settings['wpClassified_image_width'] = 150;
		$wpClassified_settings['wpClassified_image_height'] = 200;
		$wpClassified_settings['wpClassified_date_string'] = 'm-d-Y g:i a';
		$wpClassified_settings['wpClassified_unread_color'] = '#FF0000';
		$wpClassified_settings['wpClassified_image_alignment'] = 'left';
		$wpClassified_settings['wpClassified_top_image'] = '';
		$wpClassified_settings['wpClassified_read_user_level'] = -1;
		$wpClassified_settings['wpClassified_write_user_level'] = -1;
		$wpClassified_settings['wpClassified_banner_code'] = '';
		$wpClassified_settings['wpClassified_display_last_ads_subject'] = 'y';
		$wpClassified_settings['wpClassified_display_last_post_link'] = 'y';
		$wpClassified_settings['wpClassified_last_ads_subject_num'] = 5;
		$wpClassified_settings['wpClassified_excerpt_length'] = 100;
		$wpClassified_settings['wpClassified_last_ads_subjects_author'] = "y";
	}
	update_option('wpClassified_data', $wpClassified_settings);
}

function wpClassified_check_db(){
	global $_GET, $_POST, $wpdb, $table_prefix, $wpClassified_wp_pageinfo;
	$t = $table_prefix.'wpClassified';
	include("wpClassified_db.php");
	if($_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
		return;
	} else {
		wpClassified_db();
	}
}

function wpClassified_get_last_ads_subjects(){
	global $wpdb, $table_prefix, $wpClassified_wp_version_info;
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();

	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
			 LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
			 LEFT JOIN {$table_prefix}users AS lu
			 ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
			 WHERE {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
			 ORDER BY {$table_prefix}wpClassified_ads_subjects.date DESC
			 LIMIT 0, ".((int)$wpClassified_settings['wpClassified_last_ads_subject_num'])." ");

	$htmlout = "<ul>";
	if (is_array($ads)){
		foreach ($ads as $ad){	
			$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$ad->ads_subjects_id."'", ARRAY_A);

			$pstart = $pstart['count']/$wpClassified_settings['wpClassified_ads_per_page'];
			$pstart = (ceil($pstart)*$wpClassified_settings['wpClassified_ads_per_page'])-$wpClassified_settings['wpClassified_ads_per_page'];

			$name = $wpdb->get_row("SELECT name FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".$ad->ads_subjects_list_id."'", ARRAY_A);

			$htmlout .= "<li>".wpClassified_create_link("lastAds", array(
					"name" => $ad->subject,
					"lists_id" => $ad->ads_subjects_list_id,
					"name" => $name['name'],
					"ads_subjects_id" => $ad->ads_subjects_id,
					"start" => $pstart,
			));
			if ($wpClassified_settings['wpClassified_last_ads_subjects_author']=='y'){
				$wpClassified_settings['wpClassified_announcement'] = '';
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

function wpClassified_get_last_ads_subjects_with_content(){
	global $wpdb, $table_prefix, $wpClassified_wp_version_info;
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_lists.*, {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
				 LEFT JOIN {$table_prefix}users AS lu
				 ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
				 LEFT JOIN {$table_prefix}wpClassified_lists
				 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
				 WHERE {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
				  && {$table_prefix}wpClassified_ads_subjects.status != 'inactive'
				 ORDER BY {$table_prefix}wpClassified_ads_subjects.date DESC
				 LIMIT 0, ".($wpClassified_settings['wpClassified_last_ads_subject_num']*1)." ");

	$htmlout = "<ul>";
	if (is_array($ads)){
		$adids = array();
		foreach ($ads as $ad){
			$adids[] = $ad->ads_subjects_id;
		}
		$posts = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id IN (".@implode(", ", $adids).")	&& status = 'active' ORDER BY date DESC");
		$postinfo = array();
		foreach ($posts as $post){
			if (!$postinfo[$post->ads_ads_subjects_id]){
				$postinfo[$post->ads_ads_subjects_id] = $post;
			}
		}

		foreach ($ads as $ad){
			$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$ad->ads_subjects_id."'", ARRAY_A);
			$pstart = $pstart['count']/$wpClassified_settings['wpClassified_ads_per_page'];
			$pstart = (ceil($pstart)*$wpClassified_settings['wpClassified_ads_per_page'])-$wpClassified_settings['wpClassified_ads_per_page'];
			$htmlout .= "<li>".wpClassified_create_link("lastAds", array(
					"name" => $ad->subject,
					"lists_id" => $ad->ads_subjects_list_id,
					"name" => $ad->name,
					"ads_subjects_id" => $ad->ads_subjects_id,
					"start" => $pstart,
			));
			if ($ad->last_author>0){
				$htmlout .= "<br />".$ad->lastuser.": ";
			} else {
				$htmlout .= "<br />".rawurldecode($ad->last_author_name)." (Guest): ";
			}

			$htmlout .=wpClassified_excerpt_text($wpClassified_settings['wpClassified_excerpt_length'],$postinfo[$ad->ads_subjects_id]);
			$htmlout .= "</li>";
		}
	}
	$htmlout .= "</ul>";
	return $htmlout;
}

function wpClassified_excerpt_text($length, $text){
	$ret = substr(strip_tags(wpClassified_create_post_html($text)), 0, $length);
	$ret = substr($ret, 0, strrpos($ret, " "));
	return $ret."...";
}

function wpClassified_page_handle_title($title){
	$wpClassified_settings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpClassified_settings['wpClassified_slug'], $title);
}

function wpClassified_page_handle_pagetitle($title){
	$wpClassified_settings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpClassified_settings['wpClassified_slug'], $title);
}

function wpClassified_page_handle_content($content){
   	$wpClassified_settings = get_option('wpClassified_data');
	require_once(dirname(__FILE__)."/functions.php");
	$content = preg_replace( "/\[\[WP_CLASSIFIED\]\]/ise", "wpClassified_process()", $content); 
	return $content;
}

function wpClassified_page_handle_titlechange($title){
	$wpClassified_settings = get_option('wpClassified_data');
	return str_replace("[[WP_CLASSIFIED]]", $wpClassified_settings["wpClassified_slug"], $title);
}


function wpClassified_get_field(){
	global $wpdb, $table_prefix, $wpClassified_user_field;
	if ($wpClassified_user_field == false){
		$tcols = $wpdb->get_results("SHOW COLUMNS FROM {$table_prefix}users", ARRAY_A);
		$cols = array();
		for ($i=0; $i<count($tcols); $i++){
			$cols[] = $tcols[$i]['Field'];
		}
		if (in_array("display_name", $cols)){
			$wpClassified_user_field = "display_name";
		} else {
			$wpClassified_user_field = "user_nickname";
		}
	}
	return $wpClassified_user_field;
}


function wpClassified_create_link($action, $vars){
	global $wpdb, $table_prefix, $wp_rewrite;
		
	$pageinfo = wpClassified_get_page();
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
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=classified\">".$vars["name"]."</a> ";
		break;
		case "classified":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/viewList/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".$starts."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=viewList&lists_id=".$vars["lists_id"]."&start=".(int)$vars['start']."\">".$vars["name"]."</a> ";
		break;
		case "postAds":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/postAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=postAds&lists_id=".$vars["lists_id"]."\">".$vars["name"]."</a> ";
		break;
		case "postAdsForm":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/postAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]:get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=postAds&lists_id=".$vars["lists_id"];
		break;
		case "ads_subject":			
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/viewAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".$starts."?search_words=".ereg_replace("[^[:alnum:]]", "+", $vars["search_words"]).$lastAds."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=viewAds&lists_id=".$vars['lists_id']."&ads_subjects_id=".$vars['ads_subjects_id']."&pstart=".((int)$vars["start"])."&search_words=".$vars['search_words'].$lastAds."\">".$vars['name']."</a>";
		break;
		case "editAds":
			return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/editAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".((int)$vars["ads_id"])."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=editAds&lists_id=".$vars['lists_id']."&ads_subjects_id=".$vars['ads_subjects_id']."&ads_id=".((int)$vars["ads_id"])."\">".$vars['name']."</a> ";
		break;
		case "editAdsform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/editAds/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["lists_id"]."/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars["ads_subjects_id"]."/".((int)$vars["ads_id"]):get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=editAds&lists_id=".$vars['lists_id']."&ads_subjects_id=".$vars['ads_subjects_id']."&ads_id=".((int)$vars["ads_id"]);
		break;
		case "searchform":
			return ($rewrite)?get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/search/":get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&wpClassified_action=search";
		break;
	}
}

// function that echo's the textarea/whatever for post input
function wpClassified_create_ads_input($content=""){
	global $wpdb, $table_prefix;
	$wpClassified_settings = get_option('wpClassified_data');
	switch ($wpClassified_settings["wpClassified_ads_style"]){
		case "plain":
		default:
			echo "<textarea name='wpClassified_data[post]' id='wpClassified_data[post]' cols='40' rows='7'>".str_replace("<", "&lt;", $content)."</textarea>";
		break;
		case "tinymce":
			 $mode="advanced";
			 if ($wpClassified_settings['editor_toolbar_basic']=='y') $mode="simple";
			?>
			<script language="javascript" type="text/javascript" src="<?php echo dirname($_SERVER["PHP_SELF"]);?>/wp-content/plugins/wpClassified/tinymce/tiny_mce.js"></script>
			<script language="javascript" type="text/javascript">
			tinyMCE.init({
			mode : "textareas",
			<?php echo "theme : \"" . $mode ."\"" ?> ,
			plugins : "style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,flash,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable",
			theme_advanced_buttons1 : "bold,italic,underline,strikethrough,undo,redo,link,unlink",
			theme_advanced_buttons1_add : "separator,insertdate,inserttime,preview,separator,forecolor,backcolor,separator,search,replace",
			theme_advanced_buttons2 : "fontselect,fontsizeselect",
			theme_advanced_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator",
			theme_advanced_buttons3 : "tablecontrols,separator",
			theme_advanced_buttons4 : "emotions,iespell,flash,advhr,separator,print,separator,ltr,rtl,separator,fullscreen,insertlayer,moveforward,movebackward,absolute,|,styleprops",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_path_location : "bottom",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
			theme_advanced_resize_horizontal : false,
			theme_advanced_resizing : true
		});
		</script>
		<script type="text/javascript">
		edCanvas = document.getElementById('wpClassified_data[post]');
		// This code is meant to allow tabbing from Title to Post (TinyMCE).
		if ( tinyMCE.isMSIE )
			document.getElementById('wpClassified_data_subject').onkeydown = function (e) {
			e = e ? e : window.event;
			if (e.keyCode == 9 && !e.shiftKey && !e.controlKey && !e.altKey) {
				var i = tinyMCE.selectedInstance;
				if(typeof i ==  'undefined')
				return true;
				tinyMCE.execCommand("mceStartTyping");
				this.blur();
				i.contentWindow.focus();
				e.returnValue = false;
				return false;
			}
		} else	document.getElementById('wpClassified_data_subject').onkeypress = function (e)	{
			e = e ? e : window.event;
			if (e.keyCode == 9 && !e.shiftKey && !e.controlKey && !e.altKey) {
				var i = tinyMCE.selectedInstance;
				if(typeof i ==  'undefined')
				return true;
				tinyMCE.execCommand("mceStartTyping");
				this.blur();
				i.contentWindow.focus();
				e.returnValue = false;
				return false;
			}
		}
		</script><textarea name="wpClassified_data[post]" id="wpClassified_data[post]" cols='60' rows='20' style="width:100%;"><?php echo htmlentities($content);?></textarea>
		<?php
		break;
	}
}


function wpClassified_spam_filter($name, $email, $subject, $post, $userID){
	global $ksd_api_host, $ksd_api_port;

	$spamcheck = array(
		"user_ip"		=> $_SERVER['REMOTE_ADDR'],
		"user_agent"		=> $_SERVER['HTTP_USER_AGENT'],
		"referrer"		=> $_SERVER['HTTP_REFERER'],
		"blog"			=> get_option('home'),
		"comment_author"	=> rawurlencode($name),
		"comment_author_email"	=> rawurlencode($email),
		"comment_author_url"	=> "http://",
		"comment_content"	=> str_replace("%20", "+", rawurlencode($subject))."+".str_replace("%20", "+", rawurlencode($post)),
		"comment_type"		=> "",
		"user_ID"		=> $userID
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

add_filter("the_content", "wpClassified_page_handle_content");
add_filter("the_title", "wpClassified_page_handle_title");
add_filter("wp_list_pages", "wpClassified_page_handle_titlechange");
add_filter("single_post_title", "wpClassified_page_handle_pagetitle");

if (function_exists('add_action')) {
	add_action('admin_menu', 'wpClassified_add_admin_page');
}


?>
