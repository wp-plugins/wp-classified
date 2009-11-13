<?php

//ini_set('display_errors', 'On');
/*
Plugin Name: wpClassified
Plugin URI: http://forgani.com/index.php/tools/wpclassified-plugins/
Description: The wpClassified plugin allows you to add a simple classifieds page in to your wordpress blog
Author:Mohammad Forgani
Version: 1.3.2-a
Requires at least:2.8.x
Author URI: http://www.forgani.com

I create and tested on Wordpress version 2.8.5 
on default and unchanged Permalink structure.


Release Notes:


release 1.2.0-e - Augst 20/08/2008

User Side
- added language file (The work is Not Finished!) 
- Fixed search problem
- Fixed to image showing by editing and setting.
- added the default value for expire

Admin Side
- implement the maximum character limit
- added two directory within the “/images” directory cpcc and topic. 
You will need to make the folders writable (chmod 777).
- option to deactivate the confirmation code
- added google AdSense

release 1.3.0 - Sep 10/09/2008

- Update to  display-style classified ads in one column
- Added the ad images viewer
- Allowed more images per ad
- All the pages using templates
- Added style sheet for page layout 

Changes 1.3.0-b - Sep 13/10/2008
- Modify to expand and collapses the Categories
- Modify to show the last post in footer
- fix the URL faking bug

Changes 1.3.0-c - Sep 27/10/2008
- extending the Administration Interface

Changes 1.3.0-e - Nov 03/11/2008
- include the links of photo to the last ads's list
- NEW: You can now place the last ads history on the sidebar as a widget

Changes 1.3.0-f,g - Nov 05/11/2008
- fixed the login problem wmpu and buddypress and wp v2.6.3

Changes 1.3.0-h - Nov 26/11/2008
Bugfix release

Changes 1.3.1-a - Jan 20/01/2009
- It covers changes between WordPress Version 2.6 and Version 2.7
- fixed the widget

Changes 1.3.1-b - Feb 09/02/2009
- Modify to approve posts before they are published
- fixed thumbnail image width

Changes 1.3.2-a - Oct 25/10/2009
- Fixed bug with auto-install on wordpress 2.8.5



Permalink structure:
You will find an example for .htaccess file that uses to redirect 
to wpClassified in the README file
*/

//ERROR_REPORTING(0); 
require_once(dirname(__FILE__).'/settings.php');

add_filter("the_content", "wpClassified_page_handle_content");
add_filter("the_title", "wpClassified_page_handle_title");
add_filter("wp_list_pages", "wpClassified_page_handle_titlechange");
add_filter("single_post_title", "wpClassified_page_handle_pagetitle");

add_action('admin_menu', 'wpcAdmpage');
add_action("admin_head", "admin_header");
add_action('template_redirect', 'rss_feed');
add_action('init', 'widget_wpClassified_init');

// wpClassified settings 
function wpcOptions_process(){
	global $_GET, $_POST, $PHP_SELF, $wpdb, $table_prefix, $wpClassified_version, $wp_version, $lang;

	javaShowCategoryImg();
	?>
	<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
	<?php
	switch ($_GET['adm_action']){
		case "savesettings":

		$pageinfo = get_wpClassified_pageinfo();
		if ($pageinfo == false){
			$dt = date("Y-m-d");
			$p = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
			if ($p["post_title"]!="[[WP_CLASSIFIED]]"){
				$wpdb->query("insert into {$table_prefix}posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, post_type, menu_order) values ('1', '$dt', '$dt', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', 'publish', '', '', '', 'classified', '', '', '$dt', '$dt', '[[WP_CLASSIFIED]]', '0', '', 'page', '0')");
			}
		}

			foreach ($_POST["wpClassified_data"] as $k=>$v){
				$_POST["wpClassified_data"][$k] = stripslashes($v);
			}
			
			$_POST['wpClassified_data']['userfield'] = get_wpc_user_field();
			$_POST['wpClassified_data']['wpClassified_installed'] = 'y';
			$_POST['wpClassified_data']['wpClassified_version'] = $wpClassified_version;

			update_option('wpClassified_data', $_POST['wpClassified_data']);
			$msg = "Settings Updated!";
		break;
		case "install":
			include("wpClassified_db.php");
			wpClassified_db();
		break;	
	}

	if ($msg!=''){
		?>
		<p>
		<b><?php echo $msg; ?></b>
		</p>
		<?php
	}

	$wpcSettings = get_option('wpClassified_data');

	$t = $table_prefix.'wpClassified';
	if(!$wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
		wpClassified_setOption();
		$pageinfo = get_wpClassified_pageinfo();
		if ($pageinfo == false){
			$dt = date("Y-m-d");
			$p = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
			if ($p["post_title"]!="[[WP_CLASSIFIED]]"){
				$wpdb->query("insert into {$table_prefix}posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, post_type, menu_order) values ('1', '$dt', '$dt', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]', 'publish', '', '', '', 'classified', '', '', '$dt', '$dt', '[[WP_CLASSIFIED]]', '0', '', 'page', '0')");
			}
		}
	}

	$url = "<a href=\"".get_bloginfo('wpurl')."/index.php?pagename=classified\">".get_bloginfo('wpurl')."/index.php?pagename=classified</a>";
	?>
	<div class="wrap">
	<p>
	<form method="post" id="wpcOptions" name="wpcOptions" action="<?php echo $PHP_SELF;?>?page=wpcOptions&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=savesettings">
	<h2>General Settings</h2>
	<table><tr valign="top"><td>
	<fieldset class="fieldset">
	<legend class="legend"><strong>Classifeds Page Details</strong></legend>
	<table width="99%">
	<input type=hidden name="wpClassified_data[wpClassified_version]" value="<?php echo $wpClassified_version;?>">
	<tr>
		<th align="right" valign="top">wpClassified Version: </th>
		<td><?php echo $wpClassified_version;?></td>
	</tr>
	<tr>
		<th align="right" valign="top">wpClassified URL: </th>
		<td><?php echo $url;?></td>
	</tr>
	<tr>
		<th align="right" valign="top">Classified Top Image: </th>
		<td>
	<input type=hidden name="wpClassified_data[classified_top_image]" value="<?php echo $wpcSettings['classified_top_image'];?>">
	<?php
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
				echo "\n<option value=\"$file\" selected>$file</option>\n";
			} else {
				echo "\n<option value=\"$file\">$file</option>\n";
			}
		}
	}
	echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/images/" . $wpcSettings['classified_top_image'] ."\" class=\"imgMiddle\"><br />";
	?>		
	<span class="smallTxt">images from plugins/wp-classified/images directory</span></td>
	</tr>		
	<tr>
		<th align="right" valign="top">Classifieds Description: </th>
		<td><textarea cols=80 rows=3 name="wpClassified_data[description]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['description']));?></textarea></td>
	</tr>
	<tr>
	<th></th>
	<td><input type=checkbox name="wpClassified_data[show_credits]" value="y"<?php echo ($wpcSettings['show_credits']=='y')?" checked":"";?>> Display wpClassified credit line at the bottom of page.</td>
	</tr>
	<tr>
	<th align="right" valign="top">Classifieds Page Link Name: </th>
	<td><input type="text" name="wpClassified_data[wpClassified_slug]" value="<?php echo $wpcSettings['wpClassified_slug'];?>"></td>
	</tr>	

<?php
if (!$wpcSettings['thumbnail_image_width']) $wpcSettings['thumbnail_image_width'] = "120";
if (!$wpcSettings['number_of_image']) $wpcSettings['number_of_image'] = "3";
if (!$wpcSettings['image_position']) $wpcSettings['image_position'] = "1";
if (!$wpcSettings['count_last_ads']) $wpcSettings['count_last_ads'] = 5;
$imgPosition=array ('1' => 'Images on right');	
?>

		<tr>
			<th align="right" valign="top">Number of image columns: </th>
			<td><input type="text" size="3" name="wpClassified_data[number_of_image]" value="<?php echo $wpcSettings['number_of_image'];?>"><br /><span class="smallTxt">example: 3</span></td>
		</tr>
		<th></th>
		<td><input type=checkbox name="wpClassified_data[approve]" value="y"<?php echo ($wpcSettings['approve']=='y')?" checked":"";?>>posts must be pre-approved before being published.</td>
		</tr>

		<tr>
		<th align="right" valign="top">Image Display</th>
		<td>
			<select name="wpClassified_data[image_position]">
			<?php
			foreach($imgPosition as $key=>$value)	{
				if ($key == $wpcSettings[image_position]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
			</select>
			</td>
		</tr>
		<tr>
			<th align="right" valign="top">Max. Ad image size: </th>
			<td>Width: <input type="text" size="5" name="wpClassified_data[image_width]" value="<?php echo $wpcSettings['image_width'];?>"> X Height:<input type="text" size="5" name="wpClassified_data[image_height]" value="<?php echo $wpcSettings['image_height'];?>"><br /><span class="smallTxt">example: 640x480</span></td>
		</tr>
		<tr>
			<th align="right" valign="top">Thumbnail Width: </th>
			<td><input type="text" size="5" name="wpClassified_data[thumbnail_image_width]" value="<?php echo $wpcSettings['thumbnail_image_width'];?>"><br /><span class="smallTxt">example: 120</span></td>
		</tr>
		<tr>
			<th align="right" valign="top">Ad first Image Alignment:</th>
			<td><input type=text size=11 name="wpClassified_data[image_alignment]" value="<?php echo ($wpcSettings['image_alignment']);?>"><br /><span class="smallTxt">choose: left or right</span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[must_registered_user]" value="y"<?php echo ($wpcSettings['must_registered_user']=='y')?" checked":"";?>> Unregistered visitors cannot post.</td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[view_must_register]" value="y"<?php echo ($wpcSettings['view_must_register']=='y')?" checked":"";?>> Unregistered visitors cannot view.</td>
		</tr>
		<tr>
		<th></th>
		<td><input type=checkbox name="wpClassified_data[display_unregistered_ip]" value="y"<?php echo ($wpcSettings['display_unregistered_ip']=='y')?" checked":"";?>> Display first 3 octets of unregistered visitors ip.</td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_display_titles]" value="y"<?php echo ($wpcSettings['wpClassified_display_titles']=='y')?" checked":"";?>> Display user titles on classified.</td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[wpClassified_filter_posts]" value="y"<?php echo ($wpcSettings['wpClassified_filter_posts']=='y')?" checked":"";?>> Apply WP Ad/comment filters to classified posts.</td>
		</tr>
		<tr>
			<th align="right" valign="top">Number of last post to show: </th>
			<td><input type="text" size="3" name="wpClassified_data[count_last_ads]" value="<?php echo $wpcSettings['count_last_ads'];?>"><br /><span class="smallTxt">example: 5</span></td>
		</tr>

		<tr>
			<th align="right" valign="top">Banner Code: </th>
			<td><textarea cols=80 rows=3 name="wpClassified_data[banner_code]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['banner_code']));?></textarea></td>
		</tr>			
	</table>
</fieldset>
</td></tr><tr><td>
<fieldset class="fieldset">
<legend class="legend"><strong>Tools</strong></legend>
<table width="99%"><tr><td>
<?php
//for upgrade versions
if (!$wpcSettings['googleID']) $wpcSettings['googleID'] = 'pub-2844370112691023';
if (!$wpcSettings['inform_user_subject']) 
	$wpcSettings['inform_user_subject'] = "!sitename reminder:classified ads expiring soon!";
if (!$wpcSettings['inform_user_body']) 
	$wpcSettings['inform_user_body'] = "One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.";
if (!$wpcSettings['ad_expiration']) $wpcSettings['ad_expiration'] = "180";
$textarea=array ('tinymce' => 'HTML with TinyMCE (inline wysiwyg)','plain' => 'No HTML, No BBCode');	
?>
		<tr>
			<th align="right">Posting Style: </th>
			<td><select name="wpClassified_data[wpc_edit_style]">
			<?php
			foreach($textarea as $key=>$value)	{
				if ($key == $wpcSettings[wpc_edit_style]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
			</select></td>
		</tr>
		
		<tr>
			<th align="right" valign="top"><?php echo $lang['_ADMAXLIMIT'];?></th>
			<td><input type=text size=4 name="wpClassified_data[count_ads_max_limit]" value="<?php echo ($wpcSettings['count_ads_max_limit']);?>"><br/><span class="smallTxt"><?php echo $lang['_ADMAXLIMITTXT']; ?></span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[editor_toolbar_basic]" value="y"<?php echo ($wpcSettings['editor_toolbar_basic']=='y')?" checked":"";?>> Use basic toolbars in editor.</td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[notify]" value="y"<?php echo ($wpcSettings['notify']=='y')?" checked":"";?>> Notify Admin (email) on new Topic/Post</td>
		</tr>
		<tr>
			<th align="right" valign="top">Ads displayed per page: </th>
			<td><input type=text size=4 name="wpClassified_data[count_ads_per_page]" value="<?php echo ($wpcSettings['count_ads_per_page']);?>"><br /><span class="smallTxt">default:10</span></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo $lang['_DATEFORMAT'];?></th>
			<td><input type=text size=11 name="wpClassified_data[date_format]" value="<?php echo ($wpcSettings['date_format']);?>"><br><span class="smallTxt">example: m-d-Y g:i a</span></td>
		</tr>
		<tr>
			<th></th>
			<td><input type=checkbox name="wpClassified_data[rss_feed]" value="y"<?php echo ($wpcSettings['rss_feed']=='y')?" checked":"";?>> <?php echo $lang['_ALLOWRSS'];?></td>
		</tr>
		<tr>
			<th align="right" valign="top"><?php echo $lang['_NOPOSTS'];?></th>
			<td><input type=text size=4 name="wpClassified_data[rss_feed_num]" value="<?php echo ($wpcSettings['rss_feed_num']);?>"><br>
			<span class="smallTxt"> example: 15</span></td>
		</tr>
		<tr>
			<th></th>	
			<td><input type=checkbox name="wpClassified_data[confirmation_code]" value="y"<?php echo ($wpcSettings['confirmation_code']=='y')?" checked":"";?>> <?php echo $lang['_COMFCODE'];?></td>
		</tr>
</table>
</fieldset>
</td></tr>
<tr><td>
<fieldset class="fieldset">
<legend class="legend"><strong>Google AdSense for Classifieds</strong></legend>
<?php
//for upgrade versions
if (!$wpcSettings[GADcolor_border]) $wpcSettings[GADcolor_border]= 'FFFFFF';
if (!$wpcSettings[GADcolor_link]) $wpcSettings[GADcolor_link]= '0000FF';
if (!$wpcSettings[GADcolor_bg]) $wpcSettings[GADcolor_bg]= 'FFFFFF';
if (!$wpcSettings[GADcolor_text]) $wpcSettings[GADcolor_text]= '000000';
if (!$wpcSettings[GADcolor_url]) $wpcSettings[GADcolor_url]= 'FF0000';
if (!$wpcSettings[GADposition]) $wpcSettings[GADposition]= 'btn';
if (!$wpcSettings[GADproduct]) $wpcSettings[GADproduct]= 'link';
if (!$wpcSettings[googleID]) $wpcSettings[googleID] = 'pub-2844370112691023';
$GADpos = array ('top' => 'top','btn' => 'bottom', 'bth' => 'both','no' => 'none');
?>
<table width="99%"><tr>
  		<th align="right" valign="top"><a href='https://www.google.com/adsense/' target='google'>Google AdSense Account ID: </a></th>
  		<td><input type='text' name='wpClassified_data[googleID]' id='wpClassified_data[googleID]' value="<?php echo ($wpcSettings['googleID']);?>" size='22' /><br><span class="smallTxt"> example: no, pub-2844370112691023 or ...
		</span></td></tr>
		
		<tr>
			<th align="right" valign="top">Google Ad Position: </th>
			<td>
				<select name="wpClassified_data[GADposition]" tabindex="1">
				<?php
				foreach($GADpos as $key=>$value)	{
					if ($key == $wpcSettings[GADposition]) {
						echo "\n<option value='$key' selected='selected'>$value</option>\n";
					} else {
						echo "\n<option value='$key'>$value</option>\n";
					}
				}
				?>
				</select>&nbsp;&nbsp;<span class="smallTxt">(If this value is assigned to 'none' then the Google Ads will not show up)</small>
			</td>
		</tr>

		<?php
		$share = '10'; // my smallest cut on ad revenue is 10% -  
		while($share<101){
		if($share==$wpcSettings['share']){
			$share_list .= "<option value='$share' selected='selected'>$share%\n";
		}else{
			$share_list .= "<option value='$share'>$share%\n";
		}
		++$share;
		}
		?>

<?php
$products=array ('ad' => 'Ad Unit','link' => 'Link Unit');	
$formats=array ('728x90'  => '728 x 90  ' . 'Leaderboard', '468x60'  => '468 x 60  ' . 'Banner','234x60'  => '234 x 60  ' . 'Half Banner');
$lformats=array ('728x15'  => '728 x 15', '468x15' => '468 x 15');
$adtypes=array ('text_image' => 'Text &amp; Image', 'image' => 'Image Only', 'text' => 'Text Only');

?>

	<tr><th align="left" colspan=2>Layout</th></tr>
		<tr><td colspan=2 align="center">
		<table><tr>
		<td>Ad Product:</td>
		<td><select name="wpClassified_data[GADproduct]">
			<?php
			foreach($products as $key=>$value)	{
				if ($key == $wpcSettings[GADproduct]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
		</select></td>
		<td>Ad Format:</td>
		<td><select name="wpClassified_data[GADformat]">
			<optgroup label='Horizontal'>
			<?php
			foreach($formats as $key=>$value)	{
				if ($key == $wpcSettings[GADformat]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
			</optgroup>
		</select></td>	
		<td>Ad Type: </td>
		<td><select name="wpClassified_data[GADtype]">
			<?php
			foreach($adtypes as $key=>$value)	{
				if ($key == $wpcSettings[GADtype]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
		</select></td>	
		<td>Link Format:</td>
		<td><select name="wpClassified_data[GADLformat]">
			<?php
			foreach($lformats as $key=>$value)	{
				if ($key == $wpcSettings[GADLformat]) {
					echo "\n<option value='$key' selected='selected'>$value</option>\n";
				} else {
					echo "\n<option value='$key'>$value</option>\n";
				}
			}
			?>
		</select></td>	
		</tr>
		</th></tr></table>
	</td></tr>
	<tr><th align="left" colspan=2>Ad Colours</th></tr>
		<tr><td colspan=2 align="center">
		<table><tr>
		<td>Border:</td>
		<td><input name='wpClassified_data[GADcolor_border]' id='wpClassified_data[GADcolor_border]' size='6' value='<?php echo $wpcSettings[GADcolor_border]; ?>'/>
		</td>
		<td>Title/Link: </td>
		<td><input name='wpClassified_data[GADcolor_link]' id='wpClassified_data[GADcolor_link]' size='6' value='<?php echo $wpcSettings[GADcolor_link]; ?>'/>
		</td>
		<td>Background: </td>
		<td><input name='wpClassified_data[GADcolor_bg]' id='wpClassified_data[GADcolor_bg]' size='6' value='<?php echo $wpcSettings[GADcolor_bg]; ?>'/>
		</td>
		<td>Text:</td>
		<td><input name='wpClassified_data[GADcolor_text]' id='wpClassified_data[GADcolor_text]' size='6' value='<?php echo $wpcSettings[GADcolor_text]; ?>'/>
		</td>
		<td>URL: </td>
		<td><input name='wpClassified_data[GADcolor_url]' id='wpClassified_data[GADcolor_url]' size='6' value='<?php echo $wpcSettings[GADcolor_url]; ?>'/>
		</td>
		</tr>
		</th></tr></table>
	</td></tr>
</table>
</fieldset>
</td></tr>

<tr><td>
<?php
if (!$wpcSettings[inform_user_expiration]) $wpcSettings[inform_user_expiration]= 14;
?>
<fieldset class="fieldset">
<legend class="legend"><strong><?php echo $lang['_NEWADDURATION'];?></strong></legend>
<table width="99%"><tr><td>
	<tr>
		<th align="right" valign="top"><?php echo $lang['_NEWADDEFAULT'];?></th>
		<td><input type=text size=4 name="wpClassified_data[ad_expiration]" value="<?php echo ($wpcSettings['ad_expiration']);?>"><br><span class="smallTxt">Ads will be auto-removed after these
			number of days since creation. default:365 days<br />
			The expiration will be disabled if you set this value to 0.
</span></td>
	</tr>
	<tr>
		<th align="right" valign="top"><?php echo $lang['_SENDREMIDE'];?></th>
		<td><input type=text size=4 name="wpClassified_data[inform_user_expiration]" value="<?php echo ($wpcSettings['inform_user_expiration']);?>"><br><span class="smallTxt">example:7 days</span></td>
	</tr>
	<tr>
		<th align="right" valign="top"><?php echo $lang['_NOTMESSAGE'];?></th>
		<td><?php echo $lang['_NOTMESSAGESUBJECT'];?>&nbsp;&nbsp;<span class="smallTxt">(is currently not implemented!)</span><br />
		<span class="smallTxt">Substitution variables: !sitename = your website name, !siteurl = your site's base URL, !user_ads_url = link to user's classified ads list.</span><br />
		<textarea cols=60 rows=5 name="wpClassified_data[inform_user_subject]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['inform_user_subject']));?></textarea><br/>
		<span class="smallTxt">example: !sitename reminder:classified ads expiring soon! </span></td></tr>
		<tr><th align="right" valign="top"><?php echo $lang['_NOTMESSAGEBODY'];?></th><td><textarea cols=60 rows=5 name="wpClassified_data[inform_user_body]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['inform_user_body']));?></textarea><br><span class="smallTxt">example: One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.</span></td>
	</tr>
</table>
</fieldset>
</td></tr></table></div>
<p><input type=submit value="Update wpClassifieds Settings"></p>
	</form>
	</p>
	<?php
}


function wpClassified_process(){
	global $_GET, $_POST, $table_prefix, $wpdb, $user_ID, $user_identity;
	if (!isset($msg)) $msg='';
	$wpcSettings = get_option('wpClassified_data');
	if (!isset($_GET['_action'])) $_GET['_action']='';
	if (is_user_logged_in()) { 
		get_currentuserinfo();	
		//_e('Hello, ');
		//echo $user_identity;
		//_e('!');
	}
	?>
	<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
	<?php
	switch ($_GET['_action']){
		default:
		case "classified": wpc_index();	break;
		case "search": display_search($_POST['search_terms']); break;
		case "vl": get_wpc_list($msg); break;
		case "pa": _add_ad(); break;
		case "ea": _edit_ad(); break;
		case "da": _delete_ad(); break;
		case "va": _display_ad(); break;
		case "prtad": _print_ad(); break;
		case "sndad": _send_ad(); break;
		case "mi": _modify_img(); break;
		case "di": _delete_img($_POST['file']); break;
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

$adm_links = array(
	array('name'=>'Settings & Options','arg'=>'wpcOptions','prg'=>'wpcOptions_process'),
	array('name'=>'Add/Edit Categories','arg'=>'wpcStructure','prg'=>'adm_structure_process'),
	array('name'=>'Edit/Remove Ads','arg'=>'wpcModify','prg'=>'adm_modify_process'),
	array('name'=>'Users Admin','arg'=>'wpcUsers','prg'=>'adm_users_process'),
	array('name'=>'Utilities','arg'=>'wpcUtilities','prg'=>'adm_utilities_process'),
	);


function wpcAdmpage(){
	global $wpc_admin_menu, $wpc_admin_menu, $wpc_user_level, $adm_links;
	$wpcSettings = get_option('wpClassified_data');
	
	add_menu_page('wpClassified','wpClassified',8,__FILE__,'wpcOptions_process','../wp-content/plugins/wp-classified/images/wpc.gif');
	$wpcSettings = get_option('wpClassified_data');
	for ($i=0; $i<count($adm_links); $i++){
		$tlink = $adm_links[$i];
		if (!isset($tlink['prg'])) $tlink['prg']='';
		add_submenu_page(__FILE__,$tlink['name'],$tlink['name'],8,$tlink['arg'],$tlink['prg']);
	}
}

// ... and some styling and meta
function admin_header(){
	echo "<link rel='stylesheet' href='".get_bloginfo('wpurl')."/wp-content/plugins/".WPCLASSIFIED."/wpf_admin.css' type='text/css' media='screen'  />"; 
	?><script language="JavaScript" type="text/javascript" src="<?php echo WPCDIR . 'js/script.js'?>"></script><?php
}

	
function wpClassified_adm_page(){
	global $_GET, $_POST, $PHP_SELF, $wpdb, $table_prefix;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	?>
	
	<div class="wrap">
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
function wpClassified_setOption(){
	global $wpClassified_version;

	$wpcSettings = array();
	$wpcSettings = $_POST['wpClassified_data'];
	update_option('wpClassified_data', $wpcSettings);
	$wpcSettings = get_option('wpClassified_data');
	wpClassified_check_db();
	$wpcSettings['wpClassified_installed'] = 'y';
	$wpcSettings['wpClassified_version'] = $wpClassified_version;
	$wpcSettings['userfield'] = get_wpc_user_field();
	$wpcSettings['show_credits'] = 'y';
	$wpClassified_data['approve]'] = 'y';
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
	$wpcSettings['confirmation_code'] = 'y';
	$wpcSettings['count_ads_per_page'] = 10;
	$wpcSettings['count_ads_max_limit'] = 400;
	$wpcSettings['number_of_image'] = 3;
	$wpcSettings['image_position'] = 1;
	$wpcSettings['thumbnail_image_width'] = 120;
	$wpcSettings['inform_user_expiration'] = 7;
	$wpcSettings['image_width'] = 640;
	$wpcSettings['image_height'] = 480;
	$wpcSettings['date_format'] = 'm-d-Y g:i a';
	$wpcSettings['googleID'] = 'pub-2844370112691023';
	$wpcSettings['GADproduct'] = 'link';
	$wpcSettings['GADformat'] = '468x60';
	$wpcSettings['GADLformat'] = '468x15';
	$wpcSettings['GADtype'] = 'text';
	$wpcSettings['GADcolor_border']= 'FFFFFF';
	$wpcSettings['GADcolor_link']= '0000FF';
	$wpcSettings['GADcolor_bg']= 'E4F2FD';
	$wpcSettings['GADcolor_text']= '000000';
	$wpcSettings['GADcolor_url']= 'FF0000';
	$wpcSettings['GADposition'] = 'btn';
	$wpcSettings['count_last_ads'] = 5;
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
	$wpcSettings['inform_user_subject'] = "!sitename reminder:classified ads expiring soon!";
	$wpcSettings['inform_user_body'] = "One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.";
	$wpcSettings['ad_expiration'] = "180";
	//}
	update_option('wpClassified_data', $wpcSettings);
}

function wpClassified_check_db(){
	$activate_url = $PHP_SELF . '?page=wpcOptions&adm_arg=' . $_GET['adm_arg'] . '&adm_action=install';
	echo '<div class="wrap"><h2>Installation the wpClassified</h2>';
	echo '<h3><strong>&nbsp;&nbsp;<a href='.$activate_url.'>Click Here</a> to install the wpClassified.</strong></h3></div>';
}

function adm_structure_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb;

	javaShowCategoryImg();
	$t = $table_prefix.'wpClassified';
	$tab = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'");
	if(!$tab) {
		echo "<h3>No wpClassified tables found in database, May be you simply forget to save settings?</h3>";
	}	
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
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id NOT IN (SELECT categories_id FROM {$table_prefix}wpClassified_categories)");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_ads_subjects_id NOT IN (SELECT lists_id FROM {$table_prefix}wpClassified_lists)");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
		break;
		case "deleteList":
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lid']*1)."'");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE wpClassified_lists_id NOT IN (SELECT lists_id FROM {$table_prefix}wpClassified_lists)");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
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
		<b><?php echo $msg; ?></b>
		</p>
		<?php
	}
	$wpcSettings = get_option('wpClassified_data');
	if ($_GET['adm_action']=='editCategory'){
		$categoryinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_categories WHERE categories_id = '".($_GET['categories_id']*1)."'", ARRAY_A);
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
				echo "\n<option value=\"images/$file\" selected>images/$file</option>\n";
			} else {
				echo "\n<option value=\"images/$file\">images/$file</option>\n";
			}
		}
	}
	echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/" . $categoryinfo['photo'] ."\" class=\"imgMiddle\"><br />";
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
		$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
		$classifiedinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
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
				<th align="right">Parent Category"</th>
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
		$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
		$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
		$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists ORDER BY position ASC");
		?>
		<script language=javascript>
		<!--
		function deleteCategory(x, y){
			if (confirm("Are you sure you wish to delete the category:\n"+x)){
				document.location.href = y;
			}
		}
		function deleteList(x, y){
			if (confirm("Are you sure you wish to delete the list:\n"+x)){
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
		<div class="wrap">
		<H2>Add/Edit Categories</H2><HR>
		<input type=button value="Add Category" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=0';">
		 <input<?php echo (count($categories)<1)?" disabled":"";?> type=button value="Add List" onclick="document.location.href='<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=0';">
		<?php
		for ($i=0; $i<count($tlists); $i++){
			$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
		}
?>
<hr>
<img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/delete.png"> - delete category, including and all lists within.<p>  
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
		$category = $categories[$x];
	?>
		<tr>
		<td style="border:1px #C0C0C0 solid; padding-left:2px"><a style="text-decoration: none;" href="javascript:deleteCategory('<?php echo rawurlencode($category->name);?>', '<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=deleteCategory&categories_id=<?php echo $category->categories_id;?>');"><img border=0 src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/delete.png"></a></td>
		<td style="border:1px #C0C0C0 solid; padding-left:2px"><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupCategory&categories_id=<?php echo $category->categories_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownCategory&categories_id=<?php echo $category->categories_id;?>">&darr;</a> </sup></td>
		<td colspan=4 style="border:1px #C0C0C0 solid; padding-left:2px"><a href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editCategory&categories_id=<?php echo $category->categories_id;?>"><?php echo $category->name;?></a></td>
		</tr>
		<?php
		$tfs = $lists[$category->categories_id];
		for ($i=0; $i<count($tfs); $i++){
			?>
			<tr>
				<td></td>
				<td style="padding-left:2px"><a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=moveupList&lid=<?php echo $tfs[$i]->lists_id;?>">&uarr;</a> - <a style="text-decoration: none;" href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=movedownList&lid=<?php echo $tfs[$i]->lists_id;?>">&darr;</a></td>
				<td style="padding-left:2px"><a style="text-decoration: none;" href="javascript:deleteList('<?php echo $tfs[$i]->name; ?>', '<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=deleteList&lid=<?php echo $tfs[$i]->lists_id;?>')"><img border=0 src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/delete.png"></a>&nbsp;(<?php echo $liststatuses[$tfs[$i]->status];?>) <a href="<?php echo $PHP_SELF;?>?page=wpcStructure&adm_arg=<?php echo $_GET['adm_arg'];?>&adm_action=editList&lid=<?php echo $tfs[$i]->lists_id;?>"><?php echo $tfs[$i]->name;?></a></td>
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


//mohamm
function adm_users_process(){
	global $_GET, $_POST, $wpdb, $table_prefix, $wpmuBaseTablePrefix;
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
					$where .= "{$wpmuBaseTablePrefix}users.".$field." like '%".$wpdb->escape($_GET["term"])."%'";
				}
			} else {
				$where = "";
			}
			$all_users = $wpdb->get_results("select * from {$wpmuBaseTablePrefix}users
								LEFT JOIN {$table_prefix}wpClassified_user_info
								ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$wpmuBaseTablePrefix}users.ID
								$where
								ORDER BY {$wpmuBaseTablePrefix}users.".$searchfields[0]." ASC
								LIMIT $start, $perpage", ARRAY_A);
			$numusers = $wpdb->get_results("select count(*) as numusers from {$wpmuBaseTablePrefix}users $where ", ARRAY_A);
			$numusers = $numusers[0]["numusers"];
			?>
			<div class="wrap">
			<h2>Users Admin</h2>
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
				$bgcolor = ($bgcolor=="#CCCCCC")?"#DDDDDD":"#CCCCCC";
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
			$user = $wpdb->get_results("select * from {$wpmuBaseTablePrefix}users
							LEFT JOIN {$table_prefix}wpClassified_user_info
							ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$wpmuBaseTablePrefix}users.ID
							WHERE {$wpmuBaseTablePrefix}users.ID = '".(int)$_GET['id']."'", ARRAY_A);

			$user = $user[0];
			$namefield = get_wpc_user_field();

			$permissions = array("none"=>"User", "moderator"=>"Moderator", "administrator"=>"Administrator");

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
	global $_GET, $_POST, $wpdb, $table_prefix;
	$t = $table_prefix.'wpClassified';
	$wpcSettings = get_option('wpClassified_data');
	$exit = FALSE;
	switch ($_GET["adm_action"]){
		default:
		case "list":
		break;
		case "uninstall":
			$msg .= '<div class="wrap">';
			$msg .= '<h2>Uninstall wpClassified</h2>';
			if($_tables = $wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'")) {
				foreach ($_tables as $table){
					$wpdb->query("DROP TABLE $table");
					$msg .= '<font style="color:green;">';
					$msg .= 'Table ' . $table . ' has been deleted.';
					$msg .= '</font><br />';
				}
			}
			$msg .= '</p><p>';
			$wpdb->query("DELETE FROM {$table_prefix}posts WHERE post_title = '[[WP_CLASSIFIED]]'");
			$wpdb->query("DELETE FROM {$table_prefix}options WHERE option_name = 'wpClassified_data'");
			$_table = "";

			$deactivate_url = 'plugins.php?action=deactivate&plugin=wp-classified/wpClassified.php';
			if(function_exists('wp_nonce_url')) {
				$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_wp-classified/wpClassified.php');
			}
			$msg .= '<h3><strong><a href='.$deactivate_url.'>Click Here</a> To Finish The Uninstallation And wpClassified Will Be Deactivated Automatically.</strong></h3>';
			$msg .= '</div>';
			$exit = TRUE;
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
		<strong>WARNING:</strong><br />Once uninstalled, this cannot be undone. You should use a database backup of WordPress to back up all the classifieds data first.	</p>
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
			$newtext = str_replace($search_word,"<span style=\"background-color:".$bgcolors[($word_no % $no_colors)].";\">$search_word</span>", $match);
			$post = str_replace($match, $newtext, $post);
		}
		$word_no++;
	}
	return $post;
}

function create_post_html($post){
	global $_GET, $_POST, $user_login, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $table_prefix, $wpdb;
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
	if (isset($_GET['search_words'])){
		$keyword = explode(" ", $_GET['search_words']);
	} else $keyword = '';
	
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
	global $wpdb, $table_prefix, $wpmuBaseTablePrefix;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();

	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$wpmuBaseTablePrefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
			 LEFT JOIN {$wpmuBaseTablePrefix}users
			 ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
			 LEFT JOIN {$wpmuBaseTablePrefix}users AS lu
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
			echo "<textarea name='wpClassified_data[post]' id='wpClassified_data[post]' cols='80' rows='20'>".str_replace("<", "&lt;", $content)."</textarea>";
		break;
		case "tinymce":
	
			echo '<script language="javascript" type="text/javascript" src="' .get_bloginfo('wpurl').  '/wp-content/plugins/wp-classified/includes/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>';

// directionality: "rtl",
		?>
<script language="javascript" type="text/javascript">
        tinyMCE.init({
mode : "textareas",
elements : "wpClassified_data[post]",
			<?php 
				if ($wpcSettings['editor_toolbar_basic']=='y') 
				echo "theme : \"simple\",";
				else
				echo "theme : \"advanced\",";
			?>
plugins: "contextmenu,directionality,paste,emotions",
theme_advanced_buttons1 : "bold,italic,underline,separator,justifyleft,justifycenter,justifyright,separator,bullist ,numlist,separator,link,unlink,separator,forecolor,backcolor,separator,emotions,formatselect,separator,hr,removeformat,separator,ltr,rtl",
theme_advanced_buttons2 : "",
theme_advanced_buttons3 : "",
theme_advanced_toolbar_location : "top",
theme_advanced_resizing : true,
theme_advanced_resize_horizontal : false,
theme_advanced_resizing_use_cookie : false,
accessibility_warnings : false,
entity_encoding : "raw",
verify_html : false,
button_tile_map : true
});

var maxchars ="<?php echo $wpcSettings['count_ads_max_limit'] ?>";

//declare neccessary global variables
var textcounter = new Object, textcounterwarning = new Object,textcountercurrentinst,textcounting_maxcharacters=maxchars;

function myCustomOnChangeHandler(inst) {
    checknumberofcharacters(inst.getBody().innerHTML.length,inst);
}

function checknumberofcharacters(texttocheck,inst){
        if(textcounter[inst.editorId]){
            textcounter[inst.editorId] = texttocheck;
            if ( textcounter[inst.editorId] >= textcounting_maxcharacters && textcounterwarning[inst.editorId] == false){
                //set background color to red-ish
                inst.getWin().document.body.style.backgroundColor='#F6CECC';
                //set flag that user has been warned
                textcounterwarning[inst.editorId] = true;
                //set temp variable holding editor name for alert
                textcountercurrentinst = inst.editorId;
                setTimeout("alert('Your element has exceeded the '+textcounting_maxcharacters+' character limit.  You are currently using '+textcounter[textcountercurrentinst]+' characters. If you add anymore text it may be truncated when saved.')",2);
            }else if(textcounter[inst.editorId] < textcounting_maxcharacters && textcounterwarning[inst.editorId] == true){
                //set background color to white
                inst.getWin().document.body.style.backgroundColor='#FFFFFF';
                //set flag that warning has been disabled
                textcounterwarning[inst.editorId] = false;
                //set temp variable holding editor name for alert
                textcountercurrentinst = inst.editorId;
                setTimeout("alert('The number of characters in your element has been reduced below the '+textcounting_maxcharacters+' character limit.  You are currently using '+textcounter[textcountercurrentinst]+' characters.')",2);
            }
        }else{
            //setup variables
            textcounter[inst.editorId] = texttocheck;
            textcounterwarning[inst.editorId]=false;
            checknumberofcharacters(texttocheck,inst);
            }
}

</script>
<textarea name="wpClassified_data[post]" id="wpClassified_data[post]" cols="80" style="width: 100%" rows='20' tinyMCE_this="true"><?php echo htmlentities($content);?></textarea><br />
<SPAN class="smallTxt" id="msgCounter">Maximum of <SCRIPT language="javascript">document.write(maxchars);</SCRIPT> characters allowed</SPAN><BR/>
	<?php
	break;
	}
}

function javaShowCategoryImg() {
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

// Widget stuff
function widget_wpClassified_init() {
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	function widget_wpClassified($args) {
		extract($args);
		$wpcSettings = get_option('wpClassified_data');	
		echo $before_widget;
		echo $before_title . $wpcSettings['widget_title'] . $after_title;

		$fieldsPre="wpc_";
		$before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
		$after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));

		//echo $before_tag . _widget_display($wpcSettings['widget_format']) . $after_tag;
		echo '<p><ul>' . _widget_display($wpcSettings['widget_format']) . '</ul></p>'; 
	}


	function widget_wpClassified_control() {
		$wpcSettings = $newoptions = get_option('wpClassified_data');
		if ( $_POST["wpClassified-submit"] ) {
			$newoptions['widget_title'] = strip_tags(stripslashes($_POST['widget_title']));
			$newoptions['widget_format'] = $_POST['widget_format'];
			if ( empty($newoptions['widget_title']) ) $newoptions['widget_title'] = 'Last Classifieds Ads';
		}
		if ( $wpcSettings != $newoptions ) {
			$wpcSettings = $newoptions;
			update_option('wpClassified_data', $wpcSettings);
		}
		$title = htmlspecialchars($wpcSettings['widget_title'], ENT_QUOTES);
		if ( empty($newoptions['widget_title']) ) $newoptions['widget_title'] = 'Last Classifieds Ads';
		if ( empty($newoptions['widget_format']) ) $newoptions['widget_format'] = 'y';
		?>
		<label for="wpClassified-widget_title"><?php _e('Title:'); ?><input style="width: 200px;" id="widget_title" name="widget_title" type="text" value="<?php echo htmlspecialchars($wpcSettings['widget_title']); ?>" /></label></p>
		<br />
		<label for="wpClassified-widget_format">
		<input class="checkbox" id="widget_format" name="widget_format" type="checkbox" value="y" <?php echo ($wpcSettings['widget_format']=='y')?" checked":"";?>>Small Format Output</label><br />
		<input type="hidden" id="wpClassified-submit" name="wpClassified-submit" value="1" />
		<?php
	}
	
	register_sidebar_widget('wpClassified', 'widget_wpClassified', null, 'wpClassified');
	register_widget_control('wpClassified', 'widget_wpClassified_control');
}

function _widget_display() {
	$wpcSettings = get_option('wpClassified_data');
	$out = get_last_ads($wpcSettings['widget_format']);
	return $out;
}



?>
