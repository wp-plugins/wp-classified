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
$wpClassified_user_level = 8;
$wpClassified_version = '1.0.1';
$wordpress_version = false;  // wordpress version 2.x
$wpClassified_user_field = false;
$wpClassified_link_name = 'wpClassified';
$wpClassified_adm_page_name = 'wpClassified Admin';
$wpClassified_pageinfo = false;

define('TOP', 'wp-content/plugins/wpClassified');
define('INC', 'wp-content/plugins/wpClassified/includes');

require_once(ABSPATH . INC . '/_functions.php');
require_once(ABSPATH . TOP . '/admin.php');

add_action('generate_rewrite_rules','wpClassified_mod_rewrite_rules');
add_action('mod_rewrite_rules', 'wpClassified_rewrite_rules');


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


?>