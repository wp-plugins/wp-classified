<?php

/*
* searchRes_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.2.1
* 
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');

wpc_header();
if ($wpcSettings['view_must_register']=="y" && !_is_usr_loggedin()){
	wpc_read_not_allowed();
	wpc_footer();
	return;
}
if(! $results) {
	echo "<P>No posts matched your search terms.</P>";
	echo '<input type="button" value="' .$lang['_BACK']. '" onClick="history.back();">';
} else {
?>

	<p>&nbsp;</p>
	<table width=100% class="cat">
	<tr>
	<th><p><?php echo $lang['_LIST']; ?></p></th>
	<th><p><?php echo $lang['_SUBJET']; ?></p></th>
	<th><p><?php echo $lang['_AUTHORSEARCH']; ?></p></th>
	<th><p><?php echo $lang['_DATE']; ?></p></th>
	</tr>

	<?php foreach($results as $result) { ?>
	<tr class="wpc_main" >
	<td><?php echo $result->name; ?></td>
	<td>
<?php
	$re_find = '/RE: /';
	$re_strip = '';
	$new_subject_name = preg_replace($re_find, $re_strip, $result->subject);

	$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_adsWHERE ads_ads_subjects_id = '".$result->ads_subjects_id."' AND ads_id < '".$result->ads_id."'", ARRAY_A);
	$post_pstart = ($pstart['count'])/$wpcSettings['count_ads_per_page'];
	if ($post_pstart=='0'){
	$post_pstart = "0";
	} else {
		$post_pstart = (ceil($post_pstart)*$wpcSettings['count_ads_per_page'])-$wpcSettings['count_ads_per_page'];
	}
	echo create_public_link("lastAd", array("name"=>$result->name, "lid"=>$result->lists_id, "asid"=>$result->ads_subjects_id, "name"=>$new_subject_name, "start"=>$post_pstart, "post_jump"=>$result->ads_id, "search_words"=>$wpcSettings['search_terms']));
?>
	</td>
	<td><?php echo $result->display_name; ?></td>
	<td><?php echo @date($wpcSettings['date_format'], $result->date); ?></td>
	</tr>
	<?php } ?>
	</table>
	<input type="button" value="<?php echo $lang['_BACK']; ?>" onClick="history.back();">
	<?php
} 
wpc_footer();

?>