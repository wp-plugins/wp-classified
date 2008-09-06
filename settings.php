<?php

/*
* settings.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.2.0-c
*/

// user level
$wpc_user_level = 8;
$wpClassified_version = '1.3';
$wpc_user_field = false;
$wpc_admin_menu = 'wpClassified';
$wpc_page_info = false;

// include 

$locale = get_locale();
list ($lng, $locale) = split('_', $locale);
$languageFile = dirname(__FILE__).'/language/lang_'. $lng . '.php';
if (file_exists($languageFile)) {	
	require_once($languageFile);
} else {
	require_once(dirname(__FILE__).'/language/lang_en.php');
}

require_once (dirname(__FILE__).'/includes/_functions.php');
require_once (dirname(__FILE__).'/admin.php');


if (!$_GET)$_GET = $HTTP_GET_VARS;
if (!$_POST)$_POST = $HTTP_POST_VARS;
if (!$_SERVER)$_SERVER = $HTTP_SERVER_VARS;
if (!$_COOKIE)$_COOKIE = $HTTP_COOKIE_VARS;


global $table_prefix, $wpdb;
if (!$table_prefix){
	$table_prefix = $wpdb->prefix;
}

$wpc_user_info = array();
$adm_links = array(
	array(name=>'Classified Options',arg=>'wpcOptions'),
	array(name=>'Add/Edit Categories',arg=>'wpcStructure'),
	array(name=>'Edit/Remove Ads',arg=>'wpcModify'),
	array(name=>'Users Admin',arg=>'wpcUsers'),
	array(name=>'Utilities',arg=>'wpcUtilities'),
	);

function get_wpClassified_pageinfo(){
	global $wpdb, $wpc_page_info, $table_prefix;
	if ($wpc_page_info == false){
		$wpc_page_info = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
		if ($wpc_page_info["post_title"]!="[[WP_CLASSIFIED]]"){
			return false;
		}
	}
	return $wpc_page_info;
}

function get_user_info(){
	global $table_prefix, $wpdb, $user_ID, $wpc_user_info;
	get_currentuserinfo();
	$wpc_user_info = $wpdb->get_row("SELECT * from {$table_prefix}users
							LEFT JOIN {$table_prefix}wpClassified_user_info
							ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
							WHERE {$table_prefix}users.ID = '".(int)$user_ID."'", ARRAY_A);
}

function get_wpc_user_field(){
	global $wpdb, $table_prefix, $wpc_user_field, $wp_version;
	if ($wpc_user_field == false){
		$tcols = $wpdb->get_results("SHOW COLUMNS FROM {$table_prefix}users", ARRAY_A);
		$cols = array();
		for ($i=0; $i<count($tcols); $i++){
			$cols[] = $tcols[$i]['Field'];
		}
		if (in_array("display_name", $cols)){
			$wpc_user_field = "display_name";
			$wp_version = "2";
		} else {
			$wpc_user_field = "user_nickname";
			$wp_version = "1";
		}
	}
	return $wpc_user_field;
}

function _is_usr_admin(){
	global $wpc_user_info;
	return ($wpc_user_info["permission"]=="administrator")?true:false;
}

function _is_usr_mod($classified=0){
	global $wpdb, $wpc_user_info, $table_prefix;
	return ($wpc_user_info["permission"]=="moderator")?true:false;
}

function _is_usr_loggedin(){
	global $wpc_user_info;
	return ((int)$wpc_user_info["ID"])?true:false;
}


function wpc_get_top_lnks(){
	global $_GET, $_POST, $user_level, $table_prefix, $wpdb, $_SERVER;
	if (basename($_SERVER['PHP_SELF'])!='index.php'){
		return "[[WP_CLASSIFIED]]";
	} else {
		$wpClassified_settings = get_option('wpClassified_data');
		if ($_POST['search_terms']) {
			$_GET['_action'] = "search";
		}
		switch ($_GET['_action']){
			default:
			case "classified":
				return "Classified";
			break;
			case "search":
				$search_title = "Searching For: ".$_POST['search_terms'];
				return $search_title;
			break;
			case "vl":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
				return create_public_link("index", array("name"=>"Classified"))." ".$lists['name'];
			break;
			case "pa":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
					return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lid"=>$lists['lists_id']))." - Ads New Ads";
			break;
			case "ea":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lid"=>$adsInfo['lists_id']))." <br> ".create_public_link("ads_subject", array("name"=>$adsInfo["subject"], "asid"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "va":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);
				return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified", array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." <br> ".$adsInfo['subject'];
			break;
		}
	}
}



function get_wpc_header_link(){
	global $_GET, $_POST, $user_level, $table_prefix, $wpdb, $_SERVER;
	$pageinfo = get_wpClassified_pageinfo();
	if (basename($_SERVER['PHP_SELF'])!='index.php'){
		return "[[WP_CLASSIFIED]]";
	} else {
		$wpClassified_settings = get_option('wpClassified_data');
		if ($_POST['search_terms']) {
			$_GET['_action'] = "search";
		} else {
			$_POST['search_terms'] = '';
		}
		switch ($_GET['_action']){
			default:
			case "classified":
				return "<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=classified\">Main</a>";
			break;
			case "search":
				$search_title = "Searching for: ".$_POST['search_terms'];
				return $search_title;
			break;
			case "vl":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				LEFT JOIN {$table_prefix}wpClassified_categories
				ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." ".$lists['name'];
			break;
			case "pa":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lid"=>$lists['lists_id']))." - Add a new Ad in this category";
			break;
			case "ea":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);
				return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lid"=>$adsInfo['lists_id']))." <br> ".create_public_link("ads_subject", array("name"=>$adsInfo["subject"], "asid"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "va":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
						 LEFT JOIN {$table_prefix}wpClassified_lists
						 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
						 LEFT JOIN {$table_prefix}users
						 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
						 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." ".create_public_link("classified",
						array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." <br> ".$adsInfo['subject'];
			break;
		}
	}
}


function wpClassified_last_octet($ip){
	$ip = explode(".", $ip);
	$ip[count($ip)-1] = "***";
	return @implode(".", $ip);
}


?>
