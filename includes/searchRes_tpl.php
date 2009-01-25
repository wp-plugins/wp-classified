<?php

/*
* searchRes_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* 
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');
wpc_header();
?>
<div class="wpc_container">
<div class="list_ads">
<?php
if ($wpcSettings['view_must_register']=="y" && !_is_usr_loggedin()){
	wpc_read_not_allowed();
	echo "</div></div>";
	wpc_footer();
	return;
}

if(! $results) {
	echo "<P>No posts matched your search terms.</P>";
	echo '<input type="button" value="' .$lang['_BACK']. '" onClick="history.back();">';
} else {
?>
	<div class="main_col_left"><?php echo $lang['_SUBJECT'];?></div>
	<div class="main_col_middle"><?php echo $lang['_LIST'];?></div>
	<div class="main_col_right"><?php echo $lang['_POSTON'];?></div>
	<div class="list_ads_top"></div>
	<?php 
	foreach($results as $result) { ?>
		<?php
		$re_find = '/RE: /';
		$re_strip = '';
		$new_subject_name = preg_replace($re_find, $re_strip, $result->subject);

		$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$result->ads_subjects_id."' AND ads_id < '".$result->ads_id."'", ARRAY_A);
		$post_pstart = ($pstart['count'])/$wpcSettings['count_ads_per_page'];
		if ($post_pstart=='0'){
			$post_pstart = '0';
		} else {
			$post_pstart = (ceil($post_pstart)*$wpcSettings['count_ads_per_page'])-$wpcSettings['count_ads_per_page'];
		}
		echo '<div class="list_ads_sub">';
		echo create_public_link("lastAd", array("name"=>$result->name, "lid"=>$result->lists_id, "asid"=>$result->ads_subjects_id, "name"=>$new_subject_name, "start"=>$post_pstart, "post_jump"=>$result->ads_id, "search_words"=>$wpcSettings['search_terms']));
		?>
		</div>
		<div class="main_col_left_btn"><?php echo $lang['_FROM'];?> <?php echo $result->display_name; ?></div>
		<div class="main_col_middle_btn"><?php echo $result->name; ?></div>
		<div class="main_col_right_btn"><nobr><?php echo @date($wpcSettings['date_format'], $ad->date);?></nobr></div>
		<?php
	} 
	echo '<input type="button" value="' . $lang['_BACK'] .'" onClick="history.back();">';
}
echo "</div></div>";
wpc_footer();

?>
