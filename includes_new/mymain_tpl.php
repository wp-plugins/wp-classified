<?php

/*
* main_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.3.1
* show the Main page
*/

global $lang, $wpClassified;
$wpcSettings = get_option('wpClassified_data');

?>
<center>
<script type="text/javascript"><!--
google_ad_client = "pub-2844370112691023";
/* 468x60, created 6/16/08 */
google_ad_slot = "4149874500";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</center>
<?php

wpcHeader();
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
		if ($category->photo)
		echo "<img class=\"catphoto\" src=\"" . $wpClassified->plugin_url . "/" . $category->photo . "\">\n";
		?>
		<dl id="group">
		<dt><img src="<?php echo $wpClassified->plugin_url; ?>/images/colapse.gif" border="0">&nbsp;<?php echo $category->name;?></dt>
		<dd>
		<?php
		$catlist = $lists[$category->categories_id];
		for ($i=0; $i<count($catlist); $i++){
			?>
			<div class="wpc_main">
			<?php
			if (isset($rlists[$catlist[$i]->lists_id]) && $rlists[$catlist[$i]->lists_id]=='y' && $user_ID>0){
				echo "<img src=\"". $wpClassified->plugin_url . "/images/unread.gif\">\n";
			} else {
				echo "<img src=\"". $wpClassified->plugin_url . "/images/read.gif\">\n";
			}
			echo wpcPublicLink("classified", array("name"=>$catlist[$i]->name, "lid"=>$catlist[$i]->lists_id));
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
wpcFooter();
?>
