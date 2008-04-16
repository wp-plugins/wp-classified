<?php
/*
Plugin Name: wpClassified
Plugin URI: http://forgani.com/index.php/tools/wpclassiefied-plugins/
Description: The wpClassified plugin allows you to add a simple classifieds page in to your wordpress blog
Author: Mohammad Forgani
Version: 1.0.1  
Requires at least: 2.3.x
Author URI: Mohammad Forgani http://www.forgani.com

I create and tested on Wordpress version 2.3.2 
on default and unchanged Permalink structure.


Uninstalling the plugin:

For uninstalling the plugin simply delete the wpClassified directory from the /wp-content/plugins/ directory.
You even don?t need to deactivate the plugin in the WordPress admin menu.
And you remove the page and tables, which are installed by the plugins with drop table in phpMyAdmin.


demo: http://www.bazarcheh.de/?page_id=92

*/

require_once('settings.php');

$_GET["start"] = ereg_replace("[^0-9]", "", $_GET["start"]);
$_REQUEST["start"] = ereg_replace("[^0-9]", "", $_REQUEST["start"]);
$_GET["pstart"] = ereg_replace("[^0-9]", "", $_GET["pstart"]);
$_REQUEST["pstart"] = ereg_replace("[^0-9]", "", $_REQUEST["pstart"]);

if (!$_GET)$_GET = $HTTP_GET_VARS;
if (!$_POST)$_POST = $HTTP_POST_VARS;
if (!$_SERVER)$_SERVER = $HTTP_SERVER_VARS;
if (!$_COOKIE)$_COOKIE = $HTTP_COOKIE_VARS;

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
	global $_GET, $_POST, $user_login, $userdata, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb;
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

			$pstart = $pstart['count']/$wpcSettings['wpClassified_ads_per_page'];
			$pstart = (ceil($pstart)*$wpcSettings['wpClassified_ads_per_page'])-$wpcSettings['wpClassified_ads_per_page'];

			$name = $wpdb->get_row("SELECT name FROM {$table_prefix}wpClassified_lists WHERE lists_id = '".$ad->ads_subjects_list_id."'", ARRAY_A);

			$htmlout .= "<li>".create_wpClassified_link("lastAds", array(
					"name" => $ad->subject,
					"lists_id" => $ad->ads_subjects_list_id,
					"name" => $name['name'],
					"ads_subjects_id" => $ad->ads_subjects_id,
					"start" => $pstart,
			));
			if ($wpcSettings['wpClassified_last_ads_subjects_author']=='y'){
				$wpcSettings['wpClassified_description'] = '';
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

function get_last_adssubjects_content(){
	global $wpdb, $table_prefix;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
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
				 LIMIT 0, ".($wpcSettings['wpClassified_last_ads_subject_num']*1)." ");

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
			$pstart = $pstart['count']/$wpcSettings['wpClassified_ads_per_page'];
			$pstart = (ceil($pstart)*$wpcSettings['wpClassified_ads_per_page'])-$wpcSettings['wpClassified_ads_per_page'];
			$htmlout .= "<li>".create_wpClassified_link("lastAds", array(
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

			$htmlout .=wpClassified_excerpt_text($wpcSettings['wpClassified_excerpt_length'],$postinfo[$ad->ads_subjects_id]);
			$htmlout .= "</li>";
		}
	}
	$htmlout .= "</ul>";
	return $htmlout;
}

function wpClassified_excerpt_text($length, $text){
	$ret = substr(strip_tags(create_post_html($text)), 0, $length);
	$ret = substr($ret, 0, strrpos($ret, " "));
	return $ret."...";
}



// function that echo's the textarea/whatever for post input
function create_ads_input($content=""){
	global $wpdb, $table_prefix;
	$wpcSettings = get_option('wpClassified_data');
	switch ($wpcSettings["wpc_edit_style"]){
		case "plain":
		default:
			echo "<textarea name='wpClassified_data[post]' id='wpClassified_data[post]' cols='40' rows='7'>".str_replace("<", "&lt;", $content)."</textarea>";
		break;
		case "tinymce":
			 $mode="advanced";
			 if ($wpcSettings['editor_toolbar_basic']=='y') $mode="simple";
			?>
			<script language="javascript" type="text/javascript" src="<?php echo dirname($_SERVER["PHP_SELF"]);?>/wp-content/plugins/wp-classified/includes/tinymce/tiny_mce.js"></script>
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



?>
