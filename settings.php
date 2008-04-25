<?php

/**
 * settings.php
 *
*/


global $table_prefix, $wpdb;
if (!$table_prefix){
	$table_prefix = $wpdb->prefix;
}

// user level
$wpc_user_level = 8;
$wpClassified_version = '1.0.1';
$wp_version = false;  // wordpress version 2.x
$wpClassified_user_field = false;
$wpc_public_pagename = 'wpClassified';
$wpc_admin_pagename = 'wpClassified Admin';
$wpClassified_pageinfo = false;

define('TOP', 'wp-content/plugins/wp-classified');
define('INC', 'wp-content/plugins/wp-classified/includes');

//require_once(ABSPATH . TOP . '/functions.php');
require_once(ABSPATH . INC . '/_functions.php');
require_once(ABSPATH . TOP . '/admin.php');

add_action('generate_rewrite_rules','wpc_mod_rewrite_rules');
add_action('mod_rewrite_rules', 'wpc_general_rewrite_rules');

// fix me
$incfile = ABSPATH . 'wp-includes/pluggable.php';
//echo "---> $incfile";
//require_once($incfile);

$wpClassified_user_info = array();


function get_wpClassified_pageinfo(){
	global $wpdb, $wpClassified_pageinfo, $table_prefix;
	if ($wpClassified_pageinfo == false){
		$wpClassified_pageinfo = $wpdb->get_row("SELECT * FROM {$table_prefix}posts 
			WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
		if ($wpClassified_pageinfo["post_title"]!="[[WP_CLASSIFIED]]"){
			return false;
		}
	}
	return $wpClassified_pageinfo;
}

function get_wpc_user_field(){
	global $wpdb, $table_prefix, $wpClassified_user_field, $wp_version;
	if ($wpClassified_user_field == false){
		$tcols = $wpdb->get_results("SHOW COLUMNS FROM {$table_prefix}users", ARRAY_A);
		$cols = array();
		for ($i=0; $i<count($tcols); $i++){
			$cols[] = $tcols[$i]['Field'];
		}
		if (in_array("display_name", $cols)){
			$wpClassified_user_field = "display_name";
			$wp_version = "2";
		} else {
			$wpClassified_user_field = "user_nickname";
			$wp_version = "1";
		}
	}
	return $wpClassified_user_field;
}

function _is_usr_admin(){
	global $wpClassified_user_info;
	return ($wpClassified_user_info["permission"]=="administrator")?true:false;
}

function _is_usr_mod($classified=0){
	global $wpdb, $wpClassified_user_info, $table_prefix;
	return ($wpClassified_user_info["permission"]=="moderator")?true:false;
}

function _is_usr_loggedin(){
	global $wpClassified_user_info;
	return ((int)$wpClassified_user_info["ID"])?true:false;
}


function wpc_get_top_lnks(){
	global $_GET, $_POST, $user_level, $table_prefix, $wpdb, $_SERVER;
	if (basename($_SERVER['PHP_SELF'])!='index.php'){
		return "[[WP_CLASSIFIED]]";
	} else {
		$wpClassified_settings = get_option('wpClassified_data');
		if (!$_POST['search_terms']) {
			$_GET['wpClassified_action'] = $_GET['wpClassified_action'];
		} else {
			$_GET['wpClassified_action'] = "search";
		}
		switch ($_GET['wpClassified_action']){
			default:
			case "classified":
				return "Classified";
			break;
			case "search":
				$search_title = "Searching For: ".$_POST['search_terms'];
				return $search_title;
			break;
			case "viewList":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".$lists['name'];
			break;
			case "postAds":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
					return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lists_id"=>$lists['lists_id']))." - Ads New Ads";
			break;
			case "editAds":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'", ARRAY_A);

				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lists_id"=>$adsInfo['lists_id']))." <br> ".create_wpClassified_link("ads_subject", array("name"=>$adsInfo["subject"], "ads_subjects_id"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lists_id"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "viewAds":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'", ARRAY_A);
				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified", array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], 
				"lists_id"=>$adsInfo['lists_id']))." <br> ".$adsInfo['subject'];
			break;
		}
	}
}



function get_wpc_header_link(){
	global $_GET, $_POST, $user_level, $table_prefix, $wpdb, $_SERVER;
	if (basename($_SERVER['PHP_SELF'])!='index.php'){
		return "[[WP_CLASSIFIED]]";
	} else {
		$wpClassified_settings = get_option('wpClassified_data');
		if (!$_POST['search_terms']) {
			$_GET['wpClassified_action'] = $_GET['wpClassified_action'];
		} else {
			$_GET['wpClassified_action'] = "search";
		}
		switch ($_GET['wpClassified_action']){
			default:
			case "classified":
				return "Classified";
			break;
			case "search":
				$search_title = "Searching For: ".$_POST['search_terms'];
				return $search_title;
			break;
			case "viewList":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				LEFT JOIN {$table_prefix}wpClassified_categories
				ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);

				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".$lists['name'];
			break;
			case "postAds":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);

				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lists_id"=>$lists['lists_id']))." - Ads New Ads";
			break;
			case "editAds":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'", ARRAY_A);
				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lists_id"=>$adsInfo['lists_id']))." <br> ".create_wpClassified_link("ads_subject", array("name"=>$adsInfo["subject"], "ads_subjects_id"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lists_id"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "viewAds":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
						 LEFT JOIN {$table_prefix}wpClassified_lists
						 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
						 LEFT JOIN {$table_prefix}users
						 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
						 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['ads_subjects_id']*1)."'", ARRAY_A);

				return create_wpClassified_link("index", array("name"=>"Classified"))." - ".create_wpClassified_link("classified",
						array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], 
				"lists_id"=>$adsInfo['lists_id']))." <br> ".$adsInfo['subject'];
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
