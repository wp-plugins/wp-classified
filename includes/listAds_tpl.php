<?php

/*
* listAds_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* list all ads already exist under a defined category
*/

global $_GET, $table_prefix, $wpdb, $lang;
$wpcSettings = get_option('wpClassified_data');


wpc_header();
echo "<div class=\"wpc_container\">";
if ($msg!='') echo "<p class=\"message\">" . $msg . "</p>";

if ($numAds>$wpcSettings['count_ads_per_page']){
	echo "<div align=\"left;\">";
	echo "Pages: ";
	for ($i=0; $i<$numAds/$wpcSettings['count_ads_per_page']; $i++){
		if ($i*$wpcSettings['count_ads_per_page']==$start){
			echo " <b>".($i+1)."</b> ";
		} else {
			echo " ".create_public_link("classified", array("name"=>($i+1), "lid"=>$lists["lists_id"], "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
		}
	}
	echo "</div>";
}
?>
	
<div class="list_ads">
  <div class="list_ads_top">
  <?php 
  $addtopicImg = '<img src="' .get_bloginfo('wpurl'). '/wp-content/plugins/wp-classified/images/topic/addtopic.jpg">';
  if ($wpcSettings["must_registered_user"]=="y" && !_is_usr_loggedin() ) { 
	echo $addtopicImg;
	echo create_public_link("pa", array("name"=>"Post New Ad", "lid"=>$_GET['lid'], "name"=>$lang['_ADDANNONCE']));?>
	<?php
  } else {
	echo $addtopicImg;
	echo create_public_link("pa", array("name"=>"Post New Ad", "lid"=>$_GET['lid'], "name"=>$lang['_ADDANNONCE']));?><?php
  } 
  ?>
  </div><!--list_ads_top-->
  <div id="main_col_title">
  <div class="main_col_left"><?php echo $lang['_SUBJECT'];?></div>
  <div class="main_col_middle"><?php echo $lang['_VIEWS']?></div>
  <div class="main_col_right"><?php echo $lang['_POSTON'];?></div>
  </div>
  <?php
  for ($x=0; $x<count($ads); $x++){
	$ad = $ads[$x];
	if (!@in_array($ad->ads_subjects_id, $read) && _is_usr_loggedin()){
		$rour = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/unread.gif\" class=\"imgMiddle\"> ";
	} else {$rour = "";} // fix me
	$pstart = 0;
	$pstart = $ad->ads-($ad->ads%$wpcSettings["count_ads_per_page"]);
	?>
	<div class="list_ads_sub">
	<?php
	echo $rour;
	if ($ad->sticky=='y'){
		echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/sticky.gif\" class=\"imgMiddle\" alt=\"".__("Sticky")."\"> ";
	}
	echo create_public_link("ads_subject", array("name"=>$ad->subject, "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id));
	if ($wpcSettings["wpClassified_display_last_post_link"]=='y'){
			echo create_public_link("lastAd", array("name"=>"<img class=\"imgMiddle\"  src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/lastpost.gif"."\" border=\"0\">", "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id, "start"=>$pstart));
		}
	?>
	</div><!--list_ads_sub-->
	<div class="main_col_left_btn"><?php echo $lang['_FROM'];?> <?php echo create_ads_author($ad);?></div>
	<div class="main_col_middle_btn">
	<?php
	
	$rec = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads 
		WHERE ads_ads_subjects_id = $ad->ads_subjects_id ");
	$array = split('###', $rec->image_file);
	$img = $array[0];

	if ($img !='') {
		include (dirname(__FILE__).'/js/viewer.js.php');
		echo "<a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" rel=\"thumbnail\"><img  src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/camera.gif"."\"></a>";
    }
	?>
	&nbsp;<?php echo $ad->views;?></div>
	<div class="main_col_right_btn"><nobr><?php echo @date($wpcSettings['date_format'], $ad->date);?></nobr></div><P>
	<?php
  }
  ?>
</div>
</div>
<?php
wpc_footer();
?>
