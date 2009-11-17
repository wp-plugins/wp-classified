<?php

/*
* _function.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* Licence Type   : GPL
* @version 1.3.1-a
*/

if (!isset($_SESSION)) session_start();


function wpcHeader(){
	global $_GET, $_POST, $table_prefix, $wpdb, $lang;
	$wpcSettings = get_option('wpClassified_data');
	if ($wpcSettings['count_ads_per_page'] < 1) {
		$wpcSettings['count_ads_per_page'] = 10;
	}
	echo '<div class="wpc_head">';
	if (!isset($lnks)) $lnks = '';
	if ($lnks == ''){$lnks = wpcHeaderLink();}
	echo '<h3>' . $lnks. '</h3>';
	echo "<table width=90% border=0 cellspacing=0 cellpadding=8><tr>";
	if ($wpcSettings['top_image']!=''){
		
		$img=preg_replace('/\s+/','',$wpcSettings['top_image']);
		echo '<td><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-classified/images/topic/' .$img. '"></td>';
	}
	if ($wpcSettings['description']!=''){
		echo '<td valign=middle>'.$wpcSettings['description'] . "</td>";
	}
	echo "</tr></table>";
	?>
	<div class="wpc_search">
		<form action="<?php echo wpcPublicLink("searchform", array());?>" method="post">
		<input type="text" name="search_terms" VALUE="">
		<input type="submit" value="<?php echo $lang['_SEARCH']; ?>">
		</form>
	</div>
	<?php
	if ($wpcSettings['GADposition'] == 'top' || $wpcSettings['GADposition'] == 'bth') {
		$gAd = wpcGADlink();
		echo '<div class="wpc_googleAd">' . $gAd . '</div>';
	}
	?>
	</div><!--wpc_head-->
	<?php

	
	//
	wpcCleanUpIpTempImages();


	$today = time();
	$sql = "SELECT ads_subjects_id, txt, date FROM {$table_prefix}wpClassified_ads_subjects";
	$rmRecords = $wpdb->get_results($sql);
	foreach ($rmRecords as $rmRecord) { 
		list ($adExpire, $contactBy) = preg_split('/###/', $rmRecord->txt);
		if (!isset($adExpire)) { $adExpire=$wpcSettings[ad_expiration]; };
		if ($adExpire && $adExpire > 0 ) {
			$second = $adExpire*24*60*60; // second
			$l = $today-$second;
			if ($rmRecord->date < $l) {
				$asid = $rmRecord->ads_subjects_id;
				$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id =" . $asid);
				$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = ". $asid);
			}
		}
	}	
}


// function to show the Main page
function wpcIndex(){
	global $_GET, $user_ID, $table_prefix, $wpdb;
	get_currentuserinfo();
	$liststatuses = array('active'=>'Open','inactive'=>'Closed','readonly'=>'Read-Only');
	$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
	$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists WHERE status != 'inactive' ORDER BY position ASC");
	if ((int)$user_ID){
		$readtest = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id, {$table_prefix}wpClassified_ads_subjects.status, {$table_prefix}wpClassified_read.read_ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects
		LEFT JOIN {$table_prefix}wpClassified_read ON
		{$table_prefix}wpClassified_read.read_user_id = '".$user_ID."' &&
		{$table_prefix}wpClassified_read.read_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id");
	}

	for ($i=0; $i<count($tlists); $i++){
		$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
	}

	for ($i=0; $i<count($readtest); $i++){
		if ($readtest[$i]->read_ads_subjects_id<1 && $readtest[$i]->status=='open'){
			$rlists[$readtest[$i]->ads_subjects_list_id] = 'y';
		} 
	}
	include(dirname(__FILE__)."/main_tpl.php");
}


// function to list all ads already exist under a defined category
function wpcList($msg){
	global $_GET, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $user_ID, $wpClassified;
	//$listId = get_query_var("lists_id");
	$listId = get_query_var("lid");
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	if ($wpcSettings['count_ads_per_page'] < 1) { 
		$wpcSettings['count_ads_per_page'] = 10;
	}
	$userfield = $wpClassified->get_user_field();
	//update_views($_GET['lid']);
	$liststatuses = array('active'=>'Open','inactive'=>'Closed','readonly'=>'Read-Only');
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
		LEFT JOIN {$table_prefix}wpClassified_categories ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id	 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($listId)."'", ARRAY_A);
	
	$read = ($wpClassified->is_usr_loggedin())?$wpdb->get_col("SELECT read_ads_subjects_id FROM {$table_prefix}wpClassified_read WHERE read_user_id = ".$user_ID):array();

$sql = "SELECT {$table_prefix}wpClassified_ads_subjects.*, {$wpmuBaseTablePrefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author	LEFT JOIN {$wpmuBaseTablePrefix}users AS lu ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author LEFT JOIN {$table_prefix}wpClassified_ads ON {$table_prefix}wpClassified_ads.ads_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id  WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lid'])."' AND {$table_prefix}wpClassified_ads_subjects.status != 'deleted' AND {$table_prefix}wpClassified_ads.status='active' GROUP BY ads_subjects_id ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,{$table_prefix}wpClassified_ads_subjects.date DESC";

	$ads = $wpdb->get_results($sql);	
	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lid'])."' && status != 'deleted'");

	include(dirname(__FILE__)."/listAds_tpl.php");
}

function wpcReadNotAllowed(){
	global $user_level;
	get_currentuserinfo();
}

function wpcFooter(){
	global $wpClassified, $table_prefix, $wpdb, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$wpcSettings['credit_line'] = 'wpClassified plugins (Version '.$wpClassified->version.') powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';
	if ($wpcSettings['GADposition'] == 'btn' || $wpcSettings['GADposition'] == 'bth') {
		$gAd = wpcGADlink();
		echo '<div class="wpc_googleAd">' . $gAd . '</div>';
	}
	echo "<div class=\"wpc_footer\">";
	echo "<h3>Last " . $wpcSettings['count_last_ads'] . " Ads posted...</h3>";
	echo wpcLastAds(false);
	echo '<HR class="wpc_footer_hr">';
	if($wpcSettings['rss_feed']=='y'){
		$rssurl= wpcRssUrl();
		$out = '<div class="rssIcon"><a href="'.$rssurl.'">' . $wpcSettings['slug'] . ' RSS</a></div>';
		echo $out;
	}

	if ($wpcSettings['show_credits']=='y'){
		echo "<div class=\"smallTxt\">&nbsp;&nbsp;" .stripslashes($wpcSettings['credit_line']) . "</div>";
	}

	echo "</div>";
}

function wpcRssFilter($text)
{echo convert_chars(ent2ncr($text));} 

function wpcRssUrl() {
	global $wpdb, $table_prefix;
	$siteurl = trailingslashit(get_option('siteurl')); 
	$url = $siteurl."?page=wpClassified&wpcfeed=all";
	return $url;
} 

function wpcRssFeed() {
	if(isset($_GET['wpcfeed'])) {
		include (dirname(__FILE__).'/_rss.php');	
		exit;
	}
} 

function wpcRssLink($action, $vars) {
	global $wpdb, $table_prefix, $wp_rewrite, $wpClassified;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = $wpClassified->get_pageinfo();
	return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/vl/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=vl&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
}

function wpcDeleteAd(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF, $lang, $user_ID, $wpClassified;

	if (!$_GET['aid']) $_GET['aid']=$_POST['YesOrNo'];
	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id =" .(int)$_GET['aid'];

	 $postinfos = $wpdb->get_results($sql, ARRAY_A);

	$postinfo = $postinfos[0];
	$permission=false;
	if (($wpClassified->is_usr_loggedin() && $user_ID==$postinfo['author']) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
		$permission=true;
        }
	
	if (!$permission) {
		if (getenv('REMOTE_ADDR')==$postinfo['author_ip']) $permission=true;
	}	
	if (!$permission) {
		wpcPermissionDenied();
		return;
	}

	$pageinfo = $wpClassified->get_pageinfo();
	$link_del = get_bloginfo('wpurl')."?page_id=".$pageinfo["ID"]."&_action=da&lid=".$_GET['lid']."&asid=".$_GET['asid'];

	if ($_POST['YesOrNo']>0){
		$sql = "DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$_GET['asid'])."'";
		$wpdb->query($sql);
		$sql = "DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".((int)$_GET['asid'])."'";
		$wpdb->query($sql);
		wpcList($lang['_ANNDEL']);
		return true;
	} else {
	?>
	<h3><?php echo $lang['_CONFDEL'];?></h3>
	<form method="post" id="delete_ad_conform" name="delete_ad_conform" action="<?php echo $link_del;?>">
	<strong>
		<input type="hidden" name="YesOrNo" value="<?php echo $_GET['aid'];?>">
		<?php echo $lang['_SURDELANN'];?><br />
		<input type=submit value="<?php echo $lang['_YES'];?>"> <input type=button value="<?php echo $lang['_NO'];?>" onclick="history.go(-1);">
	</strong>
	</form>
	<?php
	return false;
	}
}

// edit post function
function wpcEditAd(){

	global $_GET, $_POST, $user_ID, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $quicktags, $lang, $wpClassified;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();

	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
	 LEFT JOIN {$table_prefix}wpClassified_categories
	 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
	 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'";

	$adsInfo = $wpdb->get_row($sql, ARRAY_A);

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id = '".(int)$_GET['aid']."'";


	$postinfos = $wpdb->get_results($sql, ARRAY_A);
	$postinfo = $postinfos[0];

	$permission=false;
	if (($wpClassified->is_usr_loggedin() && $user_ID==$postinfo['author']) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
		$permission=true;
        }
	if (!$permission) {
		if (getenv('REMOTE_ADDR')==$postinfo['author_ip']) $permission=true;
	}	
	if (!$permission) {
		wpcPermissionDenied();
		return;
	}
	if (isset( $adsInfo["txt"])) list($adExpire, $contactBy)=preg_split('/###/', $adsInfo["txt"]);
	$displayform = true;
	if ($_POST['edit_ad']=='yes'){
		$addPost = true;
		if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !$wpClassified->is_usr_loggedin()){
			$msg = $lang['_INVALIDNAME'];
			$addPost = false;
		}
		if (str_replace(" ", "", $_POST['wpClassified_data'][subject])==''){
			$msg = $lang['_INVALIDSUBJECT'];
			$addPost = false;
		}
		if (str_replace(" ", "", $_POST['wpClassified_data'][email])==''){
			$msg = $lang['_INVALIDEMAIL'];
			$addPost = false;
		} 
		if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$", $_POST['wpClassified_data'][email])){
			$msg = $lang['_INVALIDEMAIL2'];
			$addPost = false;
		}

		if ($_POST['wpClassified_data'][web]) {
			if (!wpcCheckUrl($_POST['wpClassified_data'][web])){
				$msg = $lang['_INVALIDURL'];
				$addPost = false;
			}
		}
		if (isset($_POST['wpClassified_data']['phone']) && !preg_match('/^\s*$/',$_POST['wpClassified_data']['phone']) ) {
			str_replace('/^\s+/',"",$_POST['wpClassified_data']['phone']);
			str_replace('/\s+$/',"",$_POST['wpClassified_data']['phone']);
			if ( strlen($_POST['wpClassified_data']['phone']) > 1 && !wpcValidatePhone($_POST['wpClassified_data']['phone'])) {
				$msg = $lang['_INVALIDPHONE'];
				$addPost = false;
			}
		}
		
		$_POST['wpClassified_data'][subject] = preg_replace("/(\<)(.*?)(\>)/mi", "", $_POST['wpClassified_data'][subject]);
		if (str_replace(" ", "", $_POST['wpClassified_data'][subject])=='' || !wpcCheckInput($_POST['wpClassified_data'][subject])){
			$msg = $lang['_INVALIDTITLE'];
			$addPost = false;
		}
		if($wpcSettings['confirmation_code']=='y'){ 
			if (! wpcCaptcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   				$msg = $lang['_INVALIDCONFIRM'];
				$addPost = false;
  			}
		}
		if (str_replace(" ", "", $_POST['wpClassified_data'][post])==''){
			$msg = $lang['_INVALIDCOMMENT'];
			$addPost = false;
		}

		if ($_POST['wpClassified_data'][maxchars_limit] > $wpcSettings['maxchars_limit']){
			$msg = "Classified Text must be less than or equal to ". $wpcSettings['maxchars_limit'] . " characters in length";
			$addPost = false;
		}

		if ($_FILES['image_file']!=''){
			$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
			if ($ok==true){
				$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
				if ($imginfo[0] && 
						($imginfo[0]>(int)$wpcSettings["image_width"] ||
						$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0))	{
					 $msg = $lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1];
					$addPost=false;	
				} else {
					$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
					$content = @fread($fp, $_FILES['image_file']['size']);
					@fclose($fp);
					$fp = fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$user_ID."-".$_FILES['image_file']['name'], "w");
					@fwrite($fp, $content);
					@fclose($fp);
					@chmod(dirname(__FILE__)."/images/".(int)$user_ID."-".$_FILES['image_file']['name'], 0777);
					$setImage = (int)$user_ID."-".$_FILES['image_file']['name'];
				}
			}
		}
		if ($addPost==true) {
			$displayform = false;

			$web = $_POST['wpClassified_data'][web];

			$_FILES['image_file'] = $id."-".$_FILES['image_file']['name'];
			$sql = "update {$table_prefix}wpClassified_ads
				set subject='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',";
			if ($_FILES['image_file'] =='') {
				$sql .= "image_file='".$wpdb->escape(stripslashes($setImage))."',";
			}
			$sql .= "post='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][post]))."'
				WHERE ads_id='".(int)$_GET['aid']."' ";
			$wpdb->query($sql);

			$sql = "update {$table_prefix}wpClassified_ads_subjects
			set subject='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',
			email='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][email]))."',
			web='".$web."',
			phone='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."',
			txt='".(int)$wpdb->escape(stripslashes($_POST['wpClassified_data'][ad_expiration])).'###'.$_POST['wpClassified_data'][contactBy]."'WHERE ads_subjects_id='".(int)$_GET['asid']."'";

			$wpdb->query($sql);
			wpcList($lang['_UPDATE']);
		} else {
			$displayform = true;
		}
	} 
	if ($displayform==true){

		$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE ads_id = '".(int)$_GET['aid']."'";

		$postinfos = $wpdb->get_results($sql);
		$postinfo = $postinfos[0];
 
		include(dirname(__FILE__)."/editAd_tpl.php");
	}
}

function wpcPrintAad(){
	global $_GET, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $wpClassified;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = $wpClassified->get_user_field();
	$pageinfo = $wpClassified->get_pageinfo();
	$aid = (int)$_GET['aid'];

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" . $aid;

	$post = $wpdb->get_row($sql);

	$subject = $post->subject;
	$desctext = $post->post;
	$phone = $post->phone;
	$photo = $post->image_file;

	$array = preg_split('/\#\#\#/', $post->image_file);
	$submitter = get_post_author($post);
	//wpc_header();
	echo "<html><head><title>".$wpcSettings['slug']."</title></head>";

	
    	echo "<body bgcolor=\"#FFFFFF\" text=\"#000000\">";
	echo "<table border=0><tr><td><table border=0 width=100% cellpadding=0 cellspacing=1 bgcolor=\"#000000\"><tr><td>";
    	echo "<table border=0 width=100% cellpadding=15 cellspacing=1 bgcolor=\"#FFFFFF\"><tr><td>";
	echo "<br /><br /><table width=99% border=0><tr><td>".$lang['_CLASSIFIED_AD']."(No. $aid)<br />" .$lang['_FROM']. "<br /><br />";
	echo " <b>" . $lang['_TITLE']. "</b> <i>" .$subject. "</i><br />";
	
	foreach($array as $f) {
		if ($f !=''){
			include (dirname(__FILE__).'/js/viewer.js.php');
			echo "<div class=\"show_ad_img12\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $f . "\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $f . "\" style=\"width: 120px; height: 100px\"></a><br>" .$f . "</div>";
		} 
	} 
	

	echo "<p class=\"justify\"><b>".$lang['_DESC']."</b><br /><br />".$desctext."</p>";
	if ($phone) {
		echo "<br /><b>".$lang['_TEL']."</b>" . $phone . "<br />";
	}
	if ($web) {
		echo"<b>".$lang['_WEB']."</b> ". $web ;
	}
	?>
	<hr />
	<?php echo $lang['_TOCONTACTBY'];?>
	<?php
	echo " <a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."\">".$subject."</a>";
	echo "<br /><br />".$lang['_ADSADDED']. " " . "<nobr>" . @date($wpcSettings['date_format'], $post->date) ."</nobr>";
	?>
	<br /><?php echo $lang['_ADLINKINFO'];?> <a href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?>.</a>
	</td></tr></table>
	</td></tr></table></td></tr></table>
    	</td></tr></table>
	<?php
	//wpc_footer();
}



function wpcHtml2Text( $badStr ) {
    //remove PHP if it exists
    while( substr_count( $badStr, '<'.'?' ) && substr_count( $badStr, '?'.'>' ) && strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) > strpos( $badStr, '<'.'?' ) ) {
        $badStr = substr( $badStr, 0, strpos( $badStr, '<'.'?' ) ) . substr( $badStr, strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) + 2 ); }
    //remove comments
    while( substr_count( $badStr, '<!--' ) && substr_count( $badStr, '-->' ) && strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) > strpos( $badStr, '<!--' ) ) {
        $badStr = substr( $badStr, 0, strpos( $badStr, '<!--' ) ) . substr( $badStr, strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) + 3 ); }
    //now make sure all HTML tags are correctly written (> not in between quotes)
    for( $x = 0, $goodStr = '', $is_open_tb = false, $is_open_sq = false, $is_open_dq = false; strlen( $chr = $badStr{$x} ); $x++ ) {
        //take each letter in turn and check if that character is permitted there
        switch( $chr ) {
            case '<':
                if( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 5 ) ) == 'style' ) {
                    $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</style>', $x ) + 7 ); $chr = '';
                } elseif( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 6 ) ) == 'script' ) {
                    $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</script>', $x ) + 8 ); $chr = '';
                } elseif( !$is_open_tb ) { $is_open_tb = true; } else { $chr = '&lt;'; }
                break;
            case '>':
                if( !$is_open_tb || $is_open_dq || $is_open_sq ) { $chr = '&gt;'; } else { $is_open_tb = false; }
                break;
            case '"':
                if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_dq = true; }
                elseif( $is_open_tb && $is_open_dq && !$is_open_sq ) { $is_open_dq = false; }
                else { $chr = '&quot;'; }
                break;
            case "'":
                if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_sq = true; }
                elseif( $is_open_tb && !$is_open_dq && $is_open_sq ) { $is_open_sq = false; }
        } $goodStr .= $chr;
    }
    //now that the page is valid (I hope) for strip_tags, strip all unwanted tags
    $goodStr = strip_tags( $goodStr, '<title><hr><h1><h2><h3><h4><h5><h6><div><p><pre><sup><ul><ol><br><dl><dt><table><caption><tr><li><dd><th><td><a><area><img><form><input><textarea><button><select><option>' );
    //strip extra whitespace except between <pre> and <textarea> tags
    $badStr = preg_split( "/<\/?pre[^>]*>/i", $goodStr );
    for( $x = 0; is_string( $badStr[$x] ); $x++ ) {
        if( $x % 2 ) { $badStr[$x] = '<pre>'.$badStr[$x].'</pre>'; } else {
            $goodStr = preg_split( "/<\/?textarea[^>]*>/i", $badStr[$x] );
            for( $z = 0; is_string( $goodStr[$z] ); $z++ ) {
                if( $z % 2 ) { $goodStr[$z] = '<textarea>'.$goodStr[$z].'</textarea>'; } else {
                    $goodStr[$z] = preg_replace( "/\s+/", ' ', $goodStr[$z] );
            } }
            $badStr[$x] = implode('',$goodStr);
    } }
    $goodStr = implode('',$badStr);
    //remove all options from select inputs
    $goodStr = preg_replace( "/<option[^>]*>[^<]*/i", '', $goodStr );
    //replace all tags with their text equivalents
    $goodStr = preg_replace( "/<(\/title|hr)[^>]*>/i", "\n          --------------------\n", $goodStr );
    $goodStr = preg_replace( "/<(h|div|p)[^>]*>/i", "\n\n", $goodStr );
    $goodStr = preg_replace( "/<sup[^>]*>/i", '^', $goodStr );
    $goodStr = preg_replace( "/<(ul|ol|br|dl|dt|table|caption|\/textarea|tr[^>]*>\s*<(td|th))[^>]*>/i", "\n", $goodStr );
    $goodStr = preg_replace( "/<li[^>]*>/i", "\n· ", $goodStr );
    $goodStr = preg_replace( "/<dd[^>]*>/i", "\n\t", $goodStr );
    $goodStr = preg_replace( "/<(th|td)[^>]*>/i", "\t", $goodStr );
    $goodStr = preg_replace( "/<a[^>]* href=(\"((?!\"|#|javascript:)[^\"#]*)(\"|#)|'((?!'|#|javascript:)[^'#]*)('|#)|((?!'|\"|>|#|javascript:)[^#\"'> ]*))[^>]*>/i", "[LINK: $2$4$6] ", $goodStr );
    $goodStr = preg_replace( "/<img[^>]* alt=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "[IMAGE: $2$3$4] ", $goodStr );
    $goodStr = preg_replace( "/<form[^>]* action=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "\n[FORM: $2$3$4] ", $goodStr );
    $goodStr = preg_replace( "/<(input|textarea|button|select)[^>]*>/i", "[INPUT] ", $goodStr );
    //strip all remaining tags (mostly closing tags)
    $goodStr = strip_tags( $goodStr );
    //convert HTML entities
    $goodStr = strtr( $goodStr, array_flip( get_html_translation_table( HTML_ENTITIES ) ) );
    preg_replace( "/&#(\d+);/me", "chr('$1')", $goodStr );
    //wordwrap
    $goodStr = wordwrap( $goodStr );
    //make sure there are no more than 3 linebreaks in a row and trim whitespace
    return preg_replace( "/^\n*|\n*$/", '', preg_replace( "/[ \t]+(\n|$)/", "$1", preg_replace( "/\n(\s*\n){2}/", "\n\n\n", preg_replace( "/\r\n?|\f/", "\n", str_replace( chr(160), ' ', $goodStr ) ) ) ) );
}


function wpcSendAd(){
    global $_GET, $_POST, $wpdb, $table_prefix, $wpmuBaseTablePrefix, $PHP_SELF, $lang, $wpClassified;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = $wpClassified->get_user_field();
	$pageinfo = $wpClassified->get_pageinfo();
	$aid = (int)$_GET['aid'];

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author WHERE ads_id =" . $aid;

	$post = $wpdb->get_row($sql);

	$link_snd = get_bloginfo('wpurl')."?page_id=".$pageinfo["ID"]."&_action=sndad&aid=".$_GET['aid'];

	$msg=$post->post;
	$subject=$post->subject;
	$displayform = true;
	if (isset($_POST['send_ad']) && $_POST['send_ad']=='yes'){
		$sendAd = true;
		$yourname=$_POST['wpClassified_data'][yourname];
		$mailfrom=$_POST['wpClassified_data'][mailfrom];
		$mailto=$_POST['wpClassified_data'][mailto];

		if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$", $_POST['wpClassified_data'][mailto])){
			$sendMsg = $lang['_INVALIDEMAIL2'];
			$sendAd = false;
		}
		if($wpcSettings['confirmation_code']=='y'){ 
			if (! wpcCaptcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   				$sendMsg = $lang['_INVALIDCONFIRM'];
				$sendAd = false;
  			}
		}
		if ($sendAd == true) {
			$displayform = false;

			$message = "Dear " . $_POST['wpClassified_data'][fname]. "<br>";
			$message .= "your friend " . $yourname . " send you this interesting advertisement about " . $subject . "<br><br>";
			$message .= $lang['_ADDETAIL']. "<BR>" . $msg . "<BR><BR>";
			$message .= $lang['_FRIENDBTN1'];
			$message .= get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."<BR><BR><BR>";
			$message .= $yourname . $lang['_FRIENDBTN2'];
			
  			$txt = html2text($message); 
			$from = "From: ". $yourname . "<" .$mailfrom. ">";
			//$from .= "Content-Type: text/html";
			$sub = "your friend " . $yourname . " sent you an interesting advertisement";

			$status = array();
			$email = wp_mail($mailto, $sub, $txt, $from);
			if ($email == false) {
				$status[0] = false;
				$sendMsg = $lang['_SENDERR'];
				$sendAd = false;
			} else {
				$status[0] = true;
				wpcList($lang['_SEND']);
			} 
			return $status;	
		}
	} else {
		$displayform = true;
	}

	if ($displayform==true){
		include(dirname(__FILE__)."/sendAd_tpl.php");
	}	
}



// function to display advertisement information
function wpcDisplayAd(){
	global $_GET, $user_ID, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $wpClassified;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = $wpClassified->get_user_field();
	
	
	if ($wpClassified->is_usr_loggedin()){
		$readposts = $wpdb->get_col("SELECT read_ads_id FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id = '".(int)$_GET['asid']."' && read_ads_user_id = '".(int)$user_ID."'");
	} else {
		$readposts = array();
	}
	$wpClassified->update_ads_views($_GET['asid']);
	if ($wpClassified->is_usr_loggedin()){
		$wpdb->query("REPLACE INTO {$table_prefix}wpClassified_read (read_user_id, read_ads_subjects_id) VALUES ('".(int)$user_ID."', '".(int)$_GET['asid']."')");
	}
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
		 LEFT JOIN {$table_prefix}wpClassified_categories
		 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
		 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);


	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads_subjects LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'";

	$adsInfo = $wpdb->get_row($sql, ARRAY_A);

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads LEFT JOIN {$wpmuBaseTablePrefix}users ON {$wpmuBaseTablePrefix}users.ID = {$table_prefix}wpClassified_ads.author LEFT JOIN {$table_prefix}wpClassified_user_info ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$wpmuBaseTablePrefix}users.ID WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".(int)$_GET['asid']."' && {$table_prefix}wpClassified_ads.status = 'active' ORDER BY {$table_prefix}wpClassified_ads.date ASC";
	
	$posts = $wpdb->get_results($sql);
	
	if (count($posts)>$wpcSettings['count_ads_per_page']){
		$hm = $wpcSettings['count_ads_per_page'];
	} else {
		$hm = count($posts);
	}
	if ($hm>count($posts)){
		$hm = count($posts);
	}
	
	for ($i=0; $i<$hm; $i++){
		$post = $posts[$i];

		$permission=false;
		if (($wpClassified->is_usr_loggedin() && $user_ID==$post->author) || $wpClassified->is_usr_admin() || $wpClassified->is_usr_mod()){
			$permission=true;
        	}
		if (!$permission) {
			if (getenv('REMOTE_ADDR')==$post->author_ip) $permission=true;
		}	
		
		if ($permission){
			$editlink = " ".wpcPublicLink("ea", array("name"=>"EDIT AD", "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>"Edit Your Ad", "aid"=>$post->ads_id))." ";

			$deletelink = " ".wpcPublicLink("da", array("name"=>"DELETE AD", "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>"Delete", "aid"=>$post->ads_id))." ";
		} else {
			$editlink = "";
		}
		if (!@in_array($post->ads_id, $readposts) && $wpClassified->is_usr_loggedin()){
			$xbefred = "<font color=\"".$wpcSettings['unread_color']."\">";
			$xafred = "</font>";
			$setasread[] = "('".(int)$user_ID."', '".$_GET['asid']."', '".$post->ads_id."')";
		} else {
			$xbefred = "";
			$xafred = "";
		}
				
		if (file_exists(dirname(__FILE__)."/images/".$post->image_file) && $post->image_file!=""){
			$post->image_file = get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/".$post->image_file;
		}

		include(dirname(__FILE__)."/showAd_tpl.php");
	}

	if (isset($setasread) && count($setasread)>0){
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_read_ads (read_ads_user_id, read_ads_ads_subjects_id, read_ads_id) VALUES ".@implode(", ", $setasread));
	}
	//if ($wpcSettings['must_registered_user']!="y" || _is_usr_loggedin()){	}
}


function wpcSearch($term){
	global $_GET, $_POST, $table_prefix, $wpmuBaseTablePrefix, $wpdb, $lang, $wpClassified;
	get_currentuserinfo();
	$userfield = $wpClassified->get_user_field();

	#
	# fixed 07-Apr-2008
	#
	$sql = "SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$wpmuBaseTablePrefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$wpmuBaseTablePrefix}users WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id  AND {$wpmuBaseTablePrefix}users.id = {$table_prefix}wpClassified_ads.author AND ({$table_prefix}wpClassified_ads_subjects.subject like '%".$wpdb->escape($term)."%' OR ${table_prefix}wpClassified_ads.post like '%".$wpdb->escape($term)."%') ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC";

	$results = $wpdb->get_results($sql);

	include(dirname(__FILE__)."/searchRes_tpl.php");
}


function wpcGADlink() {
	$wpcSettings = get_option('wpClassified_data');
	$key_code = $wpcSettings['googleID']; 
	if ( $wpcSettings['GADproduct']=='link' )	{
		$format = $wpcSettings['GADLformat'] . '_0ads_al'; // _0ads_al_s  5 Ads Per Unit
		list($width,$height) = preg_split('/[x]/',$wpcSettings['GADLformat']);
	} else {
		$format = $wpcSettings['GADformat'] . '_as';
		list($width,$height,$null) = preg_split('/[x]/',$wpcSettings['GADformat']);
	}

	$code = "\n" . '<script type="text/javascript"><!--' . "\n";
	$code.= 'google_ad_client="' . $key_code . '"; ' . "\n";
	$code.= 'google_ad_width="' . $width . '"; ' . "\n";
	$code.= 'google_ad_height="' . $height . '"; ' . "\n";
	$code.= 'google_ad_format="' . $format . '"; ' . "\n";
	if(isset($settings['alternate_url']) && $settings['alternate_url']!=''){ 
		$code.= 'google_alternate_ad_url="' . $settings['alternate_url'] . '"; ' . "\n";
	} else {
		if(isset($settings['alternate_color']) && $settings['alternate_color']!='') { 
			$code.= 'google_alternate_color="' . $settings['alternate_color'] . '"; ' . "\n";
		}
	}				
	//Default to Ads
	if($wpcSettings['GADproduct']!=='link') { 
		$code.= 'google_ad_type="' . $wpcSettings['GADtype'] . '"; ' . "\n"; 
		$code.= 'google_ui_features="rc:6"' . ";\n";
		// '0' => 'Square corners' 
		// '6' => 'Slightly rounded corners'
	    	// '10' => 'Very rounded corners'
	}
	$code.= 'google_color_border="' . $wpcSettings['GADcolor_border'] . '"' . ";\n";
	$code.= 'google_color_bg="' . $wpcSettings['GADcolor_bg'] . '"' . ";\n";
	$code.= 'google_color_link="' . $wpcSettings['GADcolor_link'] . '"' . ";\n";
	$code.= 'google_color_text="' . $wpcSettings['GADcolor_text'] . '"' . ";\n";
	$code.= 'google_color_url="' . $wpcSettings['GADcolor_url'] . '"' . ";\n";
	$code.= '//--></script>' . "\n";
	$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";
	return $code;
}


function wpcFilterHtml($content){
	return addslashes (wp_kses(stripslashes($content), array()));
}

function wpcFilterContent($content, $searchvalue) {
	$content = apply_filters('sf_show_post_content', $content);
	$content = convert_smilies($content);
	if(empty($searchvalue)) {
		return $content."\n";
	}
	$searchvalue=urldecode($searchvalue);
	return $content."\n";
}

function wpcLastAds($format) {
	global $table_prefix, $wpdb, $lang;
	$wpcSettings = get_option('wpClassified_data');
	if (!$wpcSettings['count_last_ads']) $wpcSettings['count_last_ads'] = 5;

	$start = 0;
	$out ='';

   	$sql ="SELECT ADS.*, L.name as l_name, C.name as c_name FROM {$table_prefix}wpClassified_ads_subjects ADS, {$table_prefix}wpClassified_ads AD, {$table_prefix}wpClassified_lists L, {$table_prefix}wpClassified_categories C WHERE ADS.ads_subjects_list_id = L.lists_id  AND C.categories_id=L.wpClassified_lists_id AND AD.ads_ads_subjects_id=ADS.ads_subjects_id AND AD.status='active' ORDER BY ADS.ads_subjects_id DESC, ADS.date DESC LIMIT ".($start).", ".($wpcSettings['count_last_ads']);


 	$lastAds = $wpdb->get_results($sql);

	foreach ($lastAds as $lastAd) {
		$link= wpcPublicLink("ads_subject", array("name"=>$lastAd->subject, "lid"=>'', "asid"=>$lastAd->ads_subjects_id));
		$out .= '<li>'.$link;
		$sql = "SELECT * FROM {$table_prefix}wpClassified_ads WHERE status='active' and ads_ads_subjects_id=" .$lastAd->ads_subjects_id;
		$rec = $wpdb->get_row($sql);
		$array = preg_split('/\#\#\#/', $rec->image_file);
		$img = $array[0];
		if (!$format) {
			if ($img !='') {
				include (dirname(__FILE__).'/js/viewer.js.php');
				$out .= "&nbsp;<a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" rel=\"thumbnail\"><img  src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/topic/camera.gif"."\"></a>";
			}
			$out .= "&nbsp;-<span class=\"smallTxt\"> " . $lastAd->author_name ." <i>". @date($wpcSettings['date_format'],$lastAd->date)."</i>, (".$lastAd->c_name. " - ".$lastAd->l_name. ")</span>";
		}
		$out .= "</li>\n";
	}	
	return $out;
}


function wpcCleanUpIpTempImages()  {
	$dir = ABSPATH."wp-content/plugins/wp-classified/images/cpcc/";
	$deleteTimeDiff=50;
	if (!($dh = opendir($dir)))
	echo 'Unable to open cache directory "'.$dir.'"';
	$result = true;
	while ($file = readdir($dh)) {
	if (($file != '.') && ($file != '..')) {
		$file2 = $dir.DIRECTORY_SEPARATOR.$file;
		if (is_file($file2)) {
			if ((mktime() - @filemtime($file2)) < $deleteTimeDiff)
				@unlink( $strDir.$strFile );
			}
		}
	}
}


function wpcValidatePhone($phone){
	$phoneregexp ='/^(\+[1-9][0-9]*(\([0-9]*\)|-[0-9]*-))?[0]?[1-9][0-9\- ]*$/';
	$phonevalid = false;
    if (preg_match($phoneregexp, $phone)) {
		$phonevalid = true;
	}
	return $phonevalid;
}



?>
