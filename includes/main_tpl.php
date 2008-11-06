<?php

/*
* main_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* show the Main page
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');


wpc_header();
$catCnt = count($categories);

?>

<script language="JavaScript" type="text/JavaScript">

window.onload=function() {
var aDT=document.getElementsByTagName('dt');
for(var i=0; i<aDT.length; i++) {
    if (aDT[i].addEventListener) { // W3C
    	aDT[i].addEventListener('click', function() {hideAll(this.nextSibling.nextSibling);}, false);
        }
    else {
    	aDT[i].onclick=function() {hideAll(this.nextSibling);};
        }
	aDT[i].style.cursor='pointer';
	}
//hideAll();
showAll();
}

function hideAll(dt) {
var aDD=(dt)? dt.parentNode.getElementsByTagName('dd') : document.getElementsByTagName('dd');
for(var i=0; i<aDD.length; i++) {
	if(aDD[i]!=dt) {aDD[i].style.display='none';}
    }
if(dt) {dt.style.display=(dt.style.display=='none')? '' : 'none';}
}

function showAll() {
var aDD=document.getElementsByTagName('dd');
for(var i=0; i<aDD.length; i++) {
	aDD[i].style.display='';
    }
}

</script>
<p><div class="wpc_colaps"><a href="javascript:showAll()">Expand All</a></div><div class="wpc_colaps"><a href="javascript:hideAll()">Collapse All</a></div></p>
<div class="wpc_container">
<div class="main-content">

<?php
if ($catCnt!="0"){
	for ($x=0; $x<$catCnt; $x++){
        echo "<div class=\"list-content\">\n";
		$category = $categories[$x];
		$img = get_bloginfo('wpurl');
		if ($category->photo)
		echo "<img class=\"catphoto\" src=\"" . $img . "/wp-content/plugins/wp-classified/" . $category->photo . "\">\n";
		?>
		<dl id="group">
		<dt><img src="<?php echo $img; ?>/wp-content/plugins/wp-classified/images/topic/colapse.gif" border="0">&nbsp;<?php echo $category->name;?></dt>
		<dd>
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
		echo "</dd></dl>";
		echo "\n</div><!--list-content-->";
	} // for
} 
echo "</div><!--main-content--></div><!--wpc_container-->";
wpc_footer();
?>
