<?php

/*
* main_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.2.1
* show the Main page
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');


wpc_header();
$catCnt = count($categories);

echo "\n<div class=\"wpc_container\">\n";
echo "<div class=\"main-content\">\n"; 
//echo "<div class=\"column-left\">\n";
if ($catCnt!="0"){
	for ($x=0; $x<$catCnt; $x++){
        echo "<div class=\"list-content\">\n";
		$category = $categories[$x];
		$img = get_bloginfo('wpurl');
		echo "<img class=\"catphoto\" src=\"" . $img . "/wp-content/plugins/wp-classified/" . $category->photo . "\">\n";
		?>
		<h2><?php echo $category->name;?></h2></td>
		<?php
		$catlist = $lists[$category->categories_id];
		for ($i=0; $i<count($catlist); $i++){
			?>
			<div class="wpc_main">
			<?php
			if ($rlists[$catlist[$i]->lists_id]=='y' && $user_ID>0){
				echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/unread.gif\">\n";
			} else {
				echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/read.gif\">\n";
			}
			echo create_public_link("classified", array("name"=>$catlist[$i]->name, "lid"=>$catlist[$i]->lists_id));
			$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE STATUS = 'open' AND sticky = 'n' AND ads_subjects_list_id = " .  $catlist[$i]->lists_id );
			echo "<small>(" . $numAds . ")</small>";
			echo ($catlist[$i]->description!="")?"<span class=\"main_desc\">".$catlist[$i]->description."</span>":"";
			?> 
			</div>
			<?php
		} 
		echo "\n</div><!--list-content-->";
	} // for
} 
echo "</div><!--main-content--></div><!--wpc_container-->";
wpc_footer();
?>