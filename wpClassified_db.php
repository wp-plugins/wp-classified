<?php


function wpClassified_db(){
	global $wpdb, $table_prefix, $wpClassified_version;

	$wpClassified_sql[$table_prefix.'wpClassified_lists'] = "
	CREATE TABLE `{$table_prefix}wpClassified_lists` (
	`lists_id` int(11) NOT NULL auto_increment,
	`wpClassified_lists_id` int(11) NOT NULL default '0',
	`position` int(11) NOT NULL default '0',
	`name` varchar(150) NOT NULL default '',
	`description` varchar(255) NOT NULL default '',
	`status` enum('active','inactive','readonly') NOT NULL default 'active',
	`ads_status` int(11) NOT NULL default '0',
	`ads` int(11) NOT NULL default '0',
	`ads_views` int(11) NOT NULL default '0',
	PRIMARY KEY  (`lists_id`),
	KEY `lists_id` (`lists_id`)
	)";
	
	$wpClassified_sql[$table_prefix.'wpClassified_categories'] = "
	CREATE TABLE `{$table_prefix}wpClassified_categories` (
	`categories_id` int(11) NOT NULL auto_increment,
	`name` varchar(150) NOT NULL default 'Unnamed',
	`photo` varchar(150) NOT NULL default 'Unnamed',
	`position` int(11) NOT NULL default '0',
	`status` enum('active','inactive') NOT NULL default 'inactive',
	PRIMARY KEY  (`categories_id`),
	KEY `categories_id` (`categories_id`)
	)";
	
	
	$wpClassified_sql[$table_prefix.'wpClassified_ads'] = "
	CREATE TABLE `{$table_prefix}wpClassified_ads` (
	`ads_id` int(20) NOT NULL auto_increment,
	`ads_ads_subjects_id` int(11) NOT NULL default '0',
	`date` int(20) NOT NULL default '0',
	`author` int(11) NOT NULL default '0',
	`author_name` varchar(100) NOT NULL default '',
	`author_ip` varchar(20) NOT NULL default '',
	`status` enum('active','deleted','inactive') NOT NULL default 'active',
	`subject` varchar(255) NOT NULL default '',
	`image_file` varchar(200) NOT NULL default '',
	`post` text NOT NULL,
	PRIMARY KEY  (`ads_id`)
	)";	
	
	$wpClassified_sql[$table_prefix.'wpClassified_ads_subjects'] = "CREATE TABLE `{$table_prefix}wpClassified_ads_subjects` (
	`ads_subjects_id` int(11) NOT NULL auto_increment,
	`ads_subjects_list_id` int(11) NOT NULL default '0',
	`date` int(20) NOT NULL default '0',
	`author` int(11) NOT NULL default '0',
	`author_name` varchar(100) NOT NULL default '',
	`author_ip` varchar(20) NOT NULL default '',
	`subject` varchar(255) NOT NULL default '',
	`email` varchar(255) NOT NULL default '',
	`location` varchar(255) NOT NULL default '',
	`web` varchar(255) NOT NULL default '',
	`fax` varchar(255) NOT NULL default '',
	`phone` varchar(255) NOT NULL default '',
	`txt` varchar(255) NOT NULL default '',
	`ads` int(11) NOT NULL default '0',
	`views` int(11) NOT NULL default '0',
	`sticky` enum('y','n') NOT NULL default 'n',
	`status` enum('closed','deleted','open') NOT NULL default 'open',
	`last_author` bigint(20) NOT NULL default '0',
	`last_author_name` varchar(100) NOT NULL default '',
	`last_author_ip` varchar(15) NOT NULL default '',
	PRIMARY KEY  (`ads_subjects_id`)
	)";
	
	$wpClassified_sql[$table_prefix.'wpClassified_user_info'] = "CREATE TABLE `{$table_prefix}wpClassified_user_info` (
	`user_info_user_ID` bigint(20) NOT NULL default '0',
	`user_info_permission` enum('none','moderator','administrator') NOT NULL default 'none',
	`user_info_post_count` int(11) NOT NULL default '0',
	`user_info_title` varchar(200) NOT NULL default '', PRIMARY KEY  (`user_info_user_ID`)
	)";
	
	$wpClassified_sql[$table_prefix.'wpClassified_read'] = "CREATE TABLE `{$table_prefix}wpClassified_read` (
	`read_user_id` bigint(20) NOT NULL default '0',
	`read_ads_subjects_id` bigint(20) NOT NULL default '0'
	)";
	
	$wpClassified_sql[$table_prefix.'wpClassified_read_ads'] = "CREATE TABLE `{$table_prefix}wpClassified_read_ads` (
	`read_ads_user_id` bigint(20) NOT NULL default '0',
	`read_ads_ads_subjects_id` bigint(20) NOT NULL default '0',
	`read_ads_id` bigint(20) NOT NULL default '0'
	)";

	$wp_users = $wpdb->get_results("SELECT ID FROM {$table_prefix}users");
	foreach($wp_users as $wp_user){
		$classified_user_info = $wpdb->get_row("SELECT user_info_user_ID FROM {$table_prefix}wpClassified_user_info WHERE user_info_user_ID = '".$wp_user->ID."'");
		if(!$classified_user_info && $wp_user->ID > 0){
			$wpdb->query("INSERT INTO {$table_prefix}wpClassified_user_info
			(user_info_user_ID, user_info_permission, user_info_post_count, user_info_title)
			VALUES ('".$wp_user->ID."', 'none', '0', '')");
		}
	}

	// start: make any tables not there already
	$tabs = $wpdb->get_results("SHOW TABLES", ARRAY_N);
	$tables = array();
	for ($i=0; $i<count($tabs); $i++){
		$tables[] = $tabs[$i][0];
	}
	@reset($wpClassified_sql);
	while (list($k, $v) = @each($wpClassified_sql)){
		if (!@in_array($k, $tables)){
			echo " - create table: " .  $k . "<BR>"; 
			$wpdb->query($v);
		}
		$wpcSettings['wpClassified_installed'] = 'y';
	}
}

?>