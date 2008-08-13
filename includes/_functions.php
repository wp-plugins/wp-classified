<?php

/*
* _function.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.2
*/

if (!$_SESSION) session_start();

// fix me
function wpc_header(){
	global $wpdb, $table_prefix, $lang;
	$wpcSettings = get_option('wpClassified_data');
	if ($wpcSettings['count_ads_per_page'] < 1) { 
		$wpcSettings['count_ads_per_page'] = 10;
	}
	echo '<table border=0><tr><td>';
	if ($wpcSettings['wpClassified_top_image']!=''){
		$img=preg_replace('/\s+/','',$wpcSettings['wpClassified_top_image']);
		echo '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-classified/' .$img. '">';
	}
	echo '</td><td valign=middle>';
	if ($wpcSettings['description']!=''){
		echo $wpcSettings['description'] . "&nbsp;";
	}
	echo '</td></tr></table>';
	if ($lnks==""){$lnks = get_wpc_header_link();}
	echo $lnks;
	$expire=365;
	$expire=$wpcSettings['ad_expiration'];
	if (!$expire || $expire < 1 ) {
		$expire=365;
	}
	$today = time();
	$second = $expire*24*60*60; // second
	$l = $today-$second;
	$rm_id = $wpdb->get_results("SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects WHERE date < " . $l );

	$cnt = count($rm_id);
	if ($cnt!=0){
		for ($x=0; $x<$cnt; $x++){
		$id = $rm_id[$x];
		$asid = $id->ads_subjects_id;
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id =" . $asid);
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = ". $asid);
		}
	}
?>
	<div style="text-align:right; float:right;">
		<form action="<?php echo create_public_link("searchform", array());?>" method="post">
		<input type="text" name="search_terms" VALUE="<?php echo str_replace('"', "&quot;", $_REQUIREDUEST['search_terms']);?>">
		<input type="submit" value="<?php echo $lang['_SEARCH']; ?>">
		</form>
	</div>
	<p>&nbsp;</p>		
<?
}

// index page 
function wpc_index(){
	global $_GET, $user_ID, $wpc_user_info, $table_prefix, $wpdb;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	wpc_header();
	
	if ($wpcSettings['view_must_register']=="y" && !_is_usr_loggedin()){
		wpc_read_not_allowed();
		wpc_footer();
		return;
	}
	echo '<div class="wpClassified_ads_container">';
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
	$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists WHERE status != 'inactive' ORDER BY position ASC");
	if ((int)$wpc_user_info["ID"]){
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
	
?>

<?php
	$cnt=2;
	$catCnt = count($categories);
	if ($catCnt!="0"){
		echo '<table class="cat" width=100%>'; 
		for ($x=0; $x<$catCnt; $x++){
			if ( ($cnt%2) == 0) {
				echo "<tr><td width=50%>";
			} else echo "</td><td width=50%>";
			$category = $categories[$x];
	        	$cnt++;
			?>
			<table width=100%>
			<tr><td>
			<div >
			<table width=100%>
			<td class="subcat" width="90px" height="60px" valign="top">
			<?php 
			$img = get_bloginfo('wpurl');
			echo "<img src=\"" . $img . "/wp-content/plugins/wp-classified/" . $category->photo . "\"  class=\"imgMiddle\">";
			?>
			</td>
			<td class="subcat" valign="top"><strong><?php echo $category->name;?></strong></td>
			</div>
			</tr>
			</table>
			</td></tr>
			<tr><td>
			<?php
			$catlist = $lists[$category->categories_id];
			for ($i=0; $i<count($catlist); $i++){
				?>
				<tr><td>
<div class="list_ads">
<?php
	if ($rlists[$catlist[$i]->lists_id]=='y' && $user_ID>0){
	echo "<img valign=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/unread.gif\" class=\"imgMiddle\">";
	} else {
	echo "<img valign=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/read.gif\" class=\"imgMiddle\">";	
	}
	echo create_public_link("classified", array("name"=>$catlist[$i]->name, "lid"=>$catlist[$i]->lists_id));
	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE STATUS = 'open' AND sticky = 'n' AND ads_subjects_list_id = " .  $catlist[$i]->lists_id );
	echo "&nbsp;<small>(" . $numAds . ")</small>";
	echo ($catlist[$i]->description!="")?"<br /><small>".$catlist[$i]->description."</small>":"";
?> 
</div>	
				</td></tr>
				<?php
			} // fix me
		    echo "</td></tr></table>";	
		} // for
		?>
		</td></tr>
		</table>	
		<?php
	} 
	echo "</div>";
	wpc_footer();
}


// display classified
function get_wpc_list($msg){
	global $_GET, $wpc_user_info, $table_prefix, $wpdb, $lang;
	//$listId = get_query_var("lists_id");
	$listId = get_query_var("lid");
	$start = get_query_var("start");
	$start = ereg_replace("[^0-9]", "", $g_pstart);
	if (!$start){$start = 0;}
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	if ($wpcSettings['count_ads_per_page'] < 1) { 
		$wpcSettings['count_ads_per_page'] = 10;
	}
	$userfield = get_wpc_user_field();
	//update_views($_GET['lid']);
	wpc_header();
	echo "<div class=\"wpClassified_ads_container\">";
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
		LEFT JOIN {$table_prefix}wpClassified_categories ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id	 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($listId)."'", ARRAY_A);
	$read = (_is_usr_loggedin())?$wpdb->get_col("SELECT read_ads_subjects_id FROM {$table_prefix}wpClassified_read WHERE read_user_id = ".$wpc_user_info["ID"]):array();
	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
		LEFT JOIN {$table_prefix}users
		ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
		LEFT JOIN {$table_prefix}users AS lu
		ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
		WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lid'])."'
		&& {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
		GROUP BY ads_subjects_id
		ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,
		{$table_prefix}wpClassified_ads_subjects.date DESC
		LIMIT ".($start).", ".($wpcSettings['count_ads_per_page'])." ");
	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lid'])."'	&& status != 'deleted'");

    if ($msg!='') echo "<p class=\"message\">" . $msg . "</p>";
	if ($numAds>$wpcSettings['count_ads_per_page']){
		echo "<div align=\"left;\">";
		echo __("Pages: ");
		for ($i=0; $i<$numAds/$wpcSettings['count_ads_per_page']; $i++){
			if ($i*$wpcSettings['count_ads_per_page']==$start){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_public_link("classified", array("name"=>($i+1), "lid"=>$lists["lists_id"], 	"name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
			}
		}
		echo "</div>";
	}
	?>
	<table width="100%" class="cat">
	<tr>
		<?php 
		
		if ($wpcSettings["must_registered_user"]=="y" && !_is_usr_loggedin() ) { 
			
		echo '<td colspan="3" class="rightCell"><img src="' .get_bloginfo('wpurl'). '/wp-content/plugins/wp-classified/images/addtopic.jpg" class="imgMiddle"><b>';
		echo create_public_link("pa", array("name"=>"Post New Ad", "lid"=>$_GET['lid'], "name"=>$lang['_ADDANNONCE']));?></b></td><?php
		} else {
			
		echo '<td colspan="3" class="rightCell"><img src="' .get_bloginfo('wpurl'). '/wp-content/plugins/wp-classified/images/addtopic.jpg" class="imgMiddle"><b>';
		echo create_public_link("pa", array("name"=>"Post New Ad", "lid"=>$_GET['lid'], "name"=>$lang['_ADDANNONCE']));?></b></td><?php
		} 
		

		?>
	</tr>
	</table>
	<br><br>
	<table class="table_ads">
	<tr>
		<td class="ads"><?php echo $lang['_ADS'];?></td>
		<td class="ads" align=right><?php echo $lang['_VIEWS']?></td>
		<td class="ads" align=right><?php echo $lang['_LAST'];?></td>
	</tr>
	<?php
	for ($x=0; $x<count($ads); $x++){
		$ad = $ads[$x];
		if (!@in_array($ad->ads_subjects_id, $read) && _is_usr_loggedin()){
			$rour = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/unread.gif\" class=\"imgMiddle\"> ";
		} else {$rour = "";} // fix me
		$pstart = 0;
		$pstart = $ad->ads-($ad->ads%$wpcSettings["count_ads_per_page"]);
		?>
		<tr>
		<td colspan=2 class="ads_subject"><strong>
		<?php
			echo $rour;
			if ($ad->sticky=='y'){
			echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/sticky.gif\" class=\"imgMiddle\" alt=\"".__("Sticky")."\"> ";
			}
			echo create_public_link("ads_subject", array("name"=>$ad->subject, "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id));
			?></strong>
		</td>
		<td align="right" valign="middle" class="ads_subject">
		<?php
			if ($wpcSettings["wpClassified_display_last_post_link"]=='y'){
				echo create_public_link("lastAd", array("name"=>"<img class=\"imgMiddle\"  src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/lastpost.gif"."\" border=\"0\">", "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id, "start"=>$pstart));
			}
		?>
		</td>
		</tr>
		<tr>
			<td align=left class="ads_subject_btn"><?php echo $lang['_FROM'];?> <?php echo create_ads_author($ad);?></td>
			<td align=right class="ads_subject_btn"><?php echo $ad->views;?></td>
			<td align=right class="ads_subject_btn"><nobr><?php echo @date($wpcSettings['date_format'], $ad->date);?></nobr></td>
		</tr>
		<?php
	}
	?>
	</table>
	</div>
	<?php
	wpc_footer();
}

function wpc_read_not_allowed(){
	global $user_level;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();

	$tpl->assign('user_level', "<!--".($user_level)."-->");
	$tpl->assign('access_denied', __("Read Access Denied", 'wpClassified'));
	$tpl->assign('access_denied_reason', __("These classifieds require you to be a registered user in order to view them. If you are already registered you must log in before trying to view the classifieds.", 'wpClassified'));
	$tpl->display('permission_denied.tpl');
}

function wpc_footer(){
	global $wpClassified_version;
	$wpcSettings = get_option('wpClassified_data');
	$wpcSettings['credit_line'] = 'wpClassified plugins (Version '.$wpClassified_version.') powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';
	
	echo "<p>&nbsp;</p><hr><p class=\"leftCell\">";
	if($wpcSettings['rss_feed']=='y'){
		$rssurl= _rss_url();
		$out = '<a class="rssIcon" href="'.$rssurl.'"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-classified/images/rss.png" /> ' . $wpcSettings['wpClassified_slug'] . ' RSS&nbsp;</a>';
		echo "<p>" .$out. "</p>";
	}

	if ($wpcSettings['show_credits']=='y'){
		echo "&nbsp;&nbsp;" .stripslashes($wpcSettings['credit_line']);
	}

	echo '</p>';
}

function rss_filter($text)
{echo convert_chars(ent2ncr($text));} 

function _rss_url() {
	global $wpdb, $table_prefix;
	$siteurl = trailingslashit(get_option('siteurl')); 
	$url = $siteurl."?page=wpClassified&wpcfeed=all";
	return $url;
} 

function rss_feed() {
	if(isset($_GET['wpcfeed'])) {
		include (dirname(__FILE__).'/_rss.php');	
		exit;
	}
} 

function create_rss_link($action, $vars) {
	global $wpdb, $table_prefix, $wp_rewrite;
	$wpcSettings = get_option('wpClassified_data');
	$pageinfo = get_wpClassified_pageinfo();
	return ($rewrite)?"<a href=\"".get_bloginfo('wpurl')."/".$pageinfo["post_name"]."/vl/".ereg_replace("[^[:alnum:]]", "-", $vars["name"])."/".$vars['lid']."/".$starts."\">".$vars["name"]."</a>":"<a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=vl&lid=".$vars['lid']."\">".$vars["name"]."</a> ";
}

function _delete_ad(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF, $lang;
	$pageinfo = get_wpClassified_pageinfo();
	$link_del = get_bloginfo('wpurl')."?page_id=".$pageinfo["ID"]."&_action=da&lid=".$_GET['lid']."&asid=".$_GET['asid'];

	if ($_POST['subject_id']>0){
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$_POST['subject_id'])."'");
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".((int)$_POST['subject_id'])."'");
		get_wpc_list($lang['_ANNDEL']);
		return true;
	} else {
	?>
	<h3><?php echo $lang['_CONFDEL'];?></h3>
	<form method="post" id="delete_ad_conform" name="delete_ad_conform" action="<?php echo $link_del;?>">
	<strong>
		<input type="hidden" name="subject_id" value="<?php echo $_GET['asid'];?>">
		<?php echo $lang['_SURDELANN'];?><br />
		<input type=submit value="<?php echo $lang['_YES'];?>"> <input type=button value="<?php echo $lang['_NO'];?>" onclick="history.go(-1);">
	</strong>
	</form>
	<?php
	return false;
	}
}

// edit post function
function _edit_ad(){
	global $_GET, $_POST, $wpc_user_info, $table_prefix, $wpdb, $quicktags, $lang;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
			 LEFT JOIN {$table_prefix}wpClassified_categories
			 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
			 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);
		$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
			LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
			 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'", ARRAY_A);
		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 WHERE ads_id = '".(int)$_GET['aid']."'");
		$postinfo = $postinfo[0];
		if ($wpc_user_info["ID"]!=$postinfo->author && !_is_usr_admin() && !_is_usr_mod()){
			wpClassified_permission_denied();
			return;
		} elseif (!_is_usr_loggedin()){
			wpClassified_permission_denied();
			return;
		}
	$displayform = true;
	if ($_POST['wpClassified_edit_ad']=='yes'){
		$addPost = true;
		if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !_is_usr_loggedin()){
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

		if (! _captcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   			$msg = $lang['_INVALIDCONFIRM'];
			$addPost = false;
  		}

		if (str_replace(" ", "", $_POST['wpClassified_data'][post])==''){
			$msg = $lang['_INVALIDCOMMENT'];
			$addPost = false;
		}

		if ($_FILES['image_file']!=''){
			$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
			if ($ok==true){
				$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
				if ($imginfo[0]>(int)$wpcSettings["image_width"]  ||
					$imginfo[1]>(int)$wpcSettings["image_height"] || $imginfo[0] == 0){
					 echo "<h2>" .$lang['_INVALIDIMG'] . $lang['_INVALIDMSG2'] .(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. $lang['_INVALIDMSG3'].$lang['_YIMG']. " " . $imginfo[0]."x".$imginfo[1] . "</h2>";
					$addPost=false;	
				} else {
					$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
					$content = @fread($fp, $_FILES['image_file']['size']);
					@fclose($fp);
					$fp = fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
					@fwrite($fp, $content);
					@fclose($fp);
					@chmod(dirname(__FILE__)."/images/".(int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
					$setImage = (int)$wpc_user_info["ID"]."-".$_FILES['image_file']['name'];
				}
			}
		}
		if ($addPost==true) {
		$displayform = false;
		$_FILES['image_file'] = $id."-".$_FILES['image_file']['name'];
	$sql = "update {$table_prefix}wpClassified_ads
	set subject='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',
	image_file='".$wpdb->escape(stripslashes($setImage))."',
	post='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][post]))."'
	WHERE ads_id='".(int)$_GET['aid']."' ";
	$wpdb->query($sql);

	$sql = "update {$table_prefix}wpClassified_ads_subjects
	set subject='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][subject]))."',
	email='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][email]))."',
	web='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][web]))."',
	phone='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."'
	WHERE ads_subjects_id='".(int)$_GET['asid']."' ";
	$wpdb->query($sql);

	do_action('wpClassified_edit_ad', $id);
	get_wpc_list($lang['_UPDATE']);
	} else {
		$displayform = true;
	}
	} 
	if ($displayform==true){

		wpc_header();

  $aFonts = array(ABSPATH."wp-content/plugins/wp-classified/fonts/arial.ttf");
  $oVisualCaptcha = new _captcha($aFonts);
  $captcha = rand(1, 20) . ".png";
  $oVisualCaptcha->create(ABSPATH."wp-content/plugins/wp-classified/images/" . $captcha);

		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			 LEFT JOIN {$table_prefix}users ON 
			{$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON 
			ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id
			 WHERE ads_id = '".(int)$_GET['aid']."'");
		$postinfo = $postinfo[0];
		?>
		<?php
		if ($msg){echo "<p class=\"error\">".__($msg)."</p>";}
		echo $quicktags;
		?>
		<table width=100% class="editform" border=0>
		<form method="post" id="ead_form" name="ead_form" enctype="multipart/form-data"
		onsubmit="this.sub.disabled=true;this.sub.value='Saving Post...';" action="<?php echo create_public_link("eaform", array("lid"=>$lists["lists_id"], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "aid"=>$_GET['aid']));?>">
		<input type="hidden" name="wpClassified_edit_ad" value="yes">
		<tr><td align=right><?php echo $lang['_AUTHOR']; ?>:</td>
		<td><?php echo get_post_author($postinfo); ?></td>
		</tr>
		<tr>
		<td align=right><?php echo $lang['_TITLE']; ?></td>
		<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->subject));?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td>		</tr>
		<tr>
		<td align=right valign=top><?php echo $lang['_PIC']; ?></td>
		<td><input type=file name="image_file" id="image_file" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->image_file));?>"><small>&nbsp;<?php echo str_replace('"', "&quot;", stripslashes($postinfo->image_file));?><br /><?php echo __("(maximum" . (int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. " pixel");?>)</small></td>
		</tr>
		<tr>
		<td align=right><?php echo $lang['_EMAIL']; ?></td>
		<td><input type=text size=30 name="wpClassified_data[email]" id="wpClassified_data_email" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->email));?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td></tr>

		<tr>
		<td align=right><?php echo $lang['_WEB']; ?></td>
		<td><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->web));?>"><small><?php echo $lang['_OPTIONAL']; ?></small></td></tr>

		<tr>
		<td align=right><?php echo $lang['_TEL']; ?></td>
		<td><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->phone));?>"><small><?php echo $lang['_OPTIONAL']; ?></small></td></tr>
		<tr>
		<td valign=top align=right><?php echo $lang['_DESC']; ?></td>
		<td><?php create_ads_input($postinfo->post);?></td>
		</tr>

		<tr>
		<td valign=top align=right><?php echo $lang['_CONFIRM']; ?></td>
		<td><img src="<?php echo get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/" .$captcha ?>" alt="ConfirmCode" align="middle"/><br>
		<input type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10">
		</tr><tr><td></td><td><input type=submit value="<?php echo $lang['_SAVEAD']; ?>" id="sub"></td></tr>
		</form></table>
		<?php
		wpc_footer();
	}
}

function _print_ad(){
	global $_GET, $table_prefix, $wpdb, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	$pageinfo = get_wpClassified_pageinfo();
	$aid = (int)$_GET['aid'];

	$post = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads 
		LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id 
		LEFT JOIN {$table_prefix}users ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
		WHERE ads_id = $aid");

	$subject = $post->subject;
	$desctext = $post->post;
	$phone = $post->phone;
	$photo = $post->image_file;
	$submitter = get_post_author($post);
	//wpc_header();
	echo "<html><head><title>".$wpcSettings['wpClassified_slug']."</title></head>";
    	echo "<body bgcolor=\"#FFFFFF\" text=\"#000000\">";
	echo "<table border=0><tr><td><table border=0 width=100% cellpadding=0 cellspacing=1 bgcolor=\"#000000\"><tr><td>";
    	echo "<table border=0 width=100% cellpadding=15 cellspacing=1 bgcolor=\"#FFFFFF\"><tr><td>";
	echo "<br /><br /><table width=99% border=0><tr><td>".$lang['_CLASSIFIED_AD']."(No. $aid)<br />" .$lang['_FROM']. "<br /><br />";
	echo " <b>" . $lang['_TITLE']. "</b> <i>" .$subject. "</i><br />";
	
	if ($photo) {     
		echo "<tr><td><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" .$photo."\" border=0>";
	}
	echo "</td></tr><tr><td><b>".$lang['_DESC']."</b><br /><br /><div style=\"text-align:justify;\">".$desctext."</div><p>";
	if ($phone) {
		echo "<br /><b>".$lang['_TEL']."</b>" . $phone . "<br />";
	}
	if ($web) {
		echo"<b>".$lang['_WEB']."</b> ". $web ;
	}
	echo "<hr />";
	echo "To contact by e-mail please use the contact form on our site by clicking on the e-mail link in the ad, you can view the ad at the following web address.";
	echo "<br /><a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."&pstart=".((int)$vars["start"])."\">".$subject."</a><br />";
	echo "<br /><br />".$lang['_ADSADDED'].$subject."<br /><br />";
	echo "</td></tr></table>";
	echo "<br /><br /></td></tr></table></td></tr></table>";
    	echo "<br /><br /><center>This advertisement is from the classified ads section on the website  ";
	echo "<b>".bloginfo('name')."</b><br />";
    	echo "</td></tr></table>";
	//wpc_footer();
}



function html2text( $badStr ) {
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


function _send_ad(){
    global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF, $lang;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	$pageinfo = get_wpClassified_pageinfo();
	$aid = (int)$_GET['aid'];

	$post = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads 
		LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id 
		LEFT JOIN {$table_prefix}users ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
		WHERE ads_id = $aid");

	$link_snd = get_bloginfo('wpurl')."?page_id=".$pageinfo["ID"]."&_action=sndad&aid=".$_GET['aid'];

	$msg=$post->post;
	$subject=$post->subject;
	$displayform = true;
	if ($_POST['wpClassified_send_ad']=='yes'){
		$sendAd = true;
		$yourname=$_POST['wpClassified_data'][yourname];
		$mailfrom=$_POST['wpClassified_data'][mailfrom];
		$mailto=$_POST['wpClassified_data'][mailto];
		//$message .= $name . "just filled in your comments form. They said:\n" . $msg . "\n\n";
		$message .= "<BR>" .$lang['_FRIENDTITLE']. "<br><br>";

		if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$",   $_POST['wpClassified_data'][mailto])){
			$sendMsg = $lang['_INVALIDEMAIL'];
			$sendAd = false;
		}

		if (! _captcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   			$sendMsg = $lang['_INVALIDCONFIRM'];
			$sendAd = false;
  		}
		
		if ($sendAd == true) {
			$displayform = false;

			$message = $lang['_ADDETAIL']."<BR>" . $msg . "<BR><BR>";
			$message .= "View Photo and More Details: <a href=\"".get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."&pstart=".((int)$vars["start"])."\">".$subject."</a><HR>";

  			$txt = html2text($message); 
			$from = "From: ". $yourname . "<" .$mailfrom. ">";
			$from .= "Bcc: email3@domain.de\n";
			//$from .= "Content-Type: text/html";


			if (mail($mailto, $subject, $txt, $from ."\n")) {
				get_wpc_list($lang['_SEND']);
			} else {
				$sendMsg = $lang['_SENDERR'];
				$sendAd = false;
			}
			return true;	
		}
	} else {
		$displayform = true;
	}

	if ($displayform==true){
		wpc_header();
		$yourname = get_post_author($post);
		$aFonts = array(ABSPATH."wp-content/plugins/wp-classified/fonts/arial.ttf");
		$oVisualCaptcha = new _captcha($aFonts);
		$captcha = rand(1, 20) . ".png";
		$oVisualCaptcha->create(ABSPATH."wp-content/plugins/wp-classified/images/" . $captcha);
		if ($sendMsg){echo "<p class=\"error\">".__($sendMsg)."</p>";}
		?>
		<div class="wpClassified_ads_container">
		<div class="wpClassified_ads_header">
		<?php echo $lang['_FRIENDSEND']; ?><?php echo $aid;?>
		<b><?php echo $post->subject;?></b><?php echo $lang['_TOAFRIEND']; ?><br /><br />
		</div>
		<p>&nbsp;</p>
		<form method="post" enctype="multipart/form-data" id="sndad" name="sndad" action="<?php echo $link_snd;?>">
		<table width='99%' cellspacing='1'>
		<tr><td class="sendTd">Your Name: </td><td><input size=35 type="text" name="wpClassified_data[yourname]" /></td></tr>
		<tr><td class="sendTd"><?php echo $lang['_YOUREMAIL']; ?></td><td><input size=35 type="text" name="wpClassified_data[mailfrom]" value="<?php echo $post->email;?>" /></td></tr>
		<tr><td></td><td><hr></td></tr>
		<tr><td class="sendTd">Friend's Name: </td><td><input size=35 type="text" name="wpClassified_data[fname]" /></td></tr>
		<tr><td class="sendTd"><?php echo $lang['_FEMAIL']; ?></td><td><input size=35 type="text" name="wpClassified_data[mailto]" /></td></tr>
		<tr><td></td><td><img src="<?php echo get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/" .$captcha ?>" alt="ConfirmCode" align="middle"/></td></Tr>
		<tr><td class="sendTd"><?php echo $lang['_CONFIRM']; ?></td><td><input size=10 type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10"></tr>
		<input type="hidden" name="wpClassified_send_ad" value="yes">
		<tr><td></td><td><input type=submit value="<?php echo $lang['_SENDEMAIL']; ?>"></td></tr>
		</form></table>
		</div>
		<?
		wpc_footer();
	}	
}

// 
function _display_ad(){
	global $_GET, $wpc_user_info, $table_prefix, $wpdb;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	
	if ($wpcSettings["view_must_register"]=="y" && !_is_usr_loggedin()){
		wpc_read_not_allowed();
		wpc_footer();
		return;
	}
	
	if (_is_usr_loggedin()){
		$readposts = $wpdb->get_col("SELECT read_ads_id FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id = '".(int)$_GET['asid']."' && read_ads_user_id = '".(int)$wpc_user_info["ID"]."'");
	} else {
		$readposts = array();
	}
	update_ads_views($_GET['asid']);
	if (_is_usr_loggedin()){
		$wpdb->query("REPLACE INTO {$table_prefix}wpClassified_read (read_user_id, read_ads_subjects_id) VALUES ('".(int)$wpc_user_info["ID"]."', '".(int)$_GET['asid']."')");
	}
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lid']."'", ARRAY_A);
	$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
				 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['asid']."'", ARRAY_A);
	$posts = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
				 LEFT JOIN {$table_prefix}wpClassified_user_info
				 ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
				 WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".(int)$_GET['asid']."'
					 && {$table_prefix}wpClassified_ads.status = 'active'
				 ORDER BY {$table_prefix}wpClassified_ads.date ASC");
	wpc_header();

?>

<?php

	if (count($posts)>$wpcSettings['count_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpcSettings['count_ads_per_page']; $i++){
			if ($i*$wpcSettings['count_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_public_link("ads_subject", array("name"=>($i+1), "lid"=>$_GET['lid'], "asid"=>$_GET['asid'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
			}
		}
	}

	if (count($posts)>$wpcSettings['count_ads_per_page']+$_GET['pstart']){
		$hm = $wpcSettings['count_ads_per_page']+$_GET['pstart'];
	} else {
		$hm = count($posts);
	}
	if ($hm>count($posts)){
		$hm = count($posts);
	}
	if ($_GET['pstart']<0){
		$_GET['pstart'] = 0;
	}

	for ($i=$_GET['pstart']; $i<$hm; $i++){
		$post = $posts[$i];
		
		if (_is_usr_admin() || _is_usr_mod() || 
			($post->author==$wpc_user_info["ID"] && _is_usr_loggedin())){
			$editlink = " ".create_public_link("ea", array("name"=>"EDIT AD", "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>"Edit Your Ad", "aid"=>$post->ads_id))." ";

			$deletelink = " ".create_public_link("da", array("name"=>"DELETE AD", "lid"=>$_GET['lid'], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>"Delete", "aid"=>$post->ads_id))." ";
		} else {
			$editlink = "";
		}
		if (!@in_array($post->ads_id, $readposts) && _is_usr_loggedin()){
			$xbefred = "<font color=\"".$wpcSettings['wpClassified_unread_color']."\">";
			$xafred = "</font>";
			$setasread[] = "('".(int)$wpc_user_info["ID"]."', '".$_GET['asid']."', '".$post->ads_id."')";
		} else {
			$xbefred = "";
			$xafred = "";
		}
				
		if (file_exists(dirname(__FILE__)."/images/".$post->image_file) && $post->image_file!=""){
			$post->image_file = get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/".$post->image_file;
		}

		if (!file_exists(ABSPATH . INC . "/body_tpl.php")){ 
			include(dirname(__FILE__)."/body_tpl.php");
		} else {
			include(ABSPATH . INC . "/body_tpl.php");
		}
		if ($i==0){
			echo stripslashes($wpcSettings['banner_code']);
		}
	}

	if (count($posts)>$wpcSettings['count_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpcSettings['count_ads_per_page']; $i++){
			if ($i*$wpcSettings['count_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_public_link("ads_subject", array("name"=>($i+1), "lid"=>$_GET['lid'], 'asid'=>$_GET['asid'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
			}
		}
	}

	if (count($setasread)>0){
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_read_ads (read_ads_user_id, read_ads_ads_subjects_id, read_ads_id) VALUES ".@implode(", ", $setasread));
	}
	if ($wpcSettings['must_registered_user']!="y" || _is_usr_loggedin()){

?>
<?php
	}
	wpc_footer();
}

function display_search(){
	global $_GET, $table_prefix, $wpdb, $lang;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	wpc_header();
	if ($wpcSettings['view_must_register']=="y" && !_is_usr_loggedin()){
		wpc_read_not_allowed();
		wpc_footer();
		return;
	}
#
# fixed according the post from -gibson
# 07-Apr-2008
#

	$sql = "SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id  AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author AND ({$table_prefix}wpClassified_ads_subjects.subject like '%".$wpdb->escape($_REQUIREDUEST['search_terms'])."%' OR ${table_prefix}wpClassified_ads.post like '%".$wpdb->escape($_REQUIREDUEST['search_terms'])."%') ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC";
	$results = $wpdb->get_results($sql);

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
		<th><p><?php echo $lang['_AUTHOR']; ?></p></th>
		<th><p><?php echo $lang['_DATE']; ?></p></th>
	</tr>

	<?php foreach($results as $result) { ?>
	<tr class="list_ads" >
		<td><?php echo $result->name; ?></td>
		<td>
		<?php
		$re_find = '/RE: /';
		$re_strip = '';
		$new_subject_name = preg_replace($re_find, $re_strip, $result->subject);

		$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads	WHERE ads_ads_subjects_id = '".$result->ads_subjects_id."' AND ads_id < '".$result->ads_id."'", ARRAY_A);
		$post_pstart = ($pstart['count'])/$wpcSettings['count_ads_per_page'];
		if ($post_pstart=='0'){
			$post_pstart = "0";	
		} else {
			$post_pstart = (ceil($post_pstart)*$wpcSettings['count_ads_per_page'])-$wpcSettings['count_ads_per_page'];
		}
		echo create_public_link("lastAd", array("name"=>$result->name, "lid"=>$result->lists_id, "asid"=>$result->ads_subjects_id, "name"=>$new_subject_name, "start"=>$post_pstart, "post_jump"=>$result->ads_id, "search_words"=>$_REQUIREDUEST['search_terms']));
		?>
		</td>
		<td><?php echo $result->display_name; ?></td>
		<td><?php echo @date($wpcSettings['date_format'], $result->date); ?></td>
	</tr>
	<?php } ?>
	</table>
	<input type="button" value="<?php echo $lang['_BACK']; ?>" onClick="history.back();">
	<?
	} 
	wpc_footer();
}

function _filter_nohtml_kses($content){
	return addslashes (wp_kses(stripslashes($content), array()));
}

function _filter_content($content, $searchvalue) {
	$content = apply_filters('sf_show_post_content', $content);
	$content = convert_smilies($content);
	if(empty($searchvalue)) {
		return $content."\n";
	}
	$searchvalue=urldecode($searchvalue);
	return $content."\n";
}

?>
