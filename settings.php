<?php

/**
 * settings.php
 *
*/

// user level
$wpc_user_level = 8;
$wpClassified_version = '1.1.0-b';
$wpc_user_field = false;
$wpc_public_pagename = 'classified';
$wpc_admin_menu = 'wpClassified';
$wpc_page_info = false;

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


/*

//$wpClassified_slug.'/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?(\([^/\(\)]*\))?/?' => '/'.$wpClassified_slug.'/index.php?pagename='.$wpClassified_slug.'&wpClassified_action=$matches[1]&lists_id=$matches[3]&ads_subjects_id=$matches[5]&ads_id=$matches[6]&start=$matches[8]&amp;pstart=$matches[8]'
function wpc_mod_rewrite_rules($wp_rewrite){
	global $wp_rewrite;
	$wpcSettings = get_option('wpClassified_data');
	$wpClassified_slug = $wpClassified_settings['wpClassified_slug'];
	$wpClassified_rules = array(
	$wpClassified_slug.'/([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?([^/\(\)]*)/?(\([^/\(\)]*\))?/?' => '/'.$wpClassified_slug.'/index.php?pagename='.$wpClassified_slug.'&wpClassified_action=$1&lists_id=$3&ads_subjects_id=$5&ads_id=$6&start=$8&pstart=$8'
	);
	$wp_rewrite->rules = $wpClassified_rules + $wp_rewrite->rules;
}
*/


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
		if (!$_POST['search_terms']) {
			$_GET['_action'] = $_GET['_action'];
		} else {
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
				return create_public_link("index", array("name"=>"Classified"))." - ".$lists['name'];
			break;
			case "pa":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);
					return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lid"=>$lists['lists_id']))." - Ads New Ads";
			break;
			case "ea":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lid"=>$adsInfo['lists_id']))." <br> ".create_public_link("ads_subject", array("name"=>$adsInfo["subject"], "asid"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "va":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);
				return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified", array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." <br> ".$adsInfo['subject'];
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
			$_GET['_action'] = $_GET['_action'];
		} else {
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
				ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." - ".$lists['name'];
			break;
			case "pa":
				$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
					 LEFT JOIN {$table_prefix}wpClassified_categories
					 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
					 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified", array("name"=>$lists["name"], "name"=>$lists["name"], "lid"=>$lists['lists_id']))." - Ads New Ads";
			break;
			case "ea":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
					 LEFT JOIN {$table_prefix}wpClassified_lists
					 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
					 LEFT JOIN {$table_prefix}users
					 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
					 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);
				return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified" , array("name"=>$adsInfo["name"], "name"=>$adsInfo["name"], "lid"=>$adsInfo['lists_id']))." <br> ".create_public_link("ads_subject", array("name"=>$adsInfo["subject"], "asid"=>$adsInfo["ads_subjects_id"], "name"=>$adsInfo["name"], 
				"lid"=>$adsInfo['lists_id']))." - Edit Ads";
			break;
			case "va":
				$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
						 LEFT JOIN {$table_prefix}wpClassified_lists
						 ON {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id
						 LEFT JOIN {$table_prefix}users
						 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
						 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".($_GET['asid']*1)."'", ARRAY_A);

				return create_public_link("index", array("name"=>"Classified"))." - ".create_public_link("classified",
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
