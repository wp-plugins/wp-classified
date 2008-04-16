<?php

/**
 * _functions.php
 *
 **/


if (!$_SESSION) session_start();

// fix me
function wpc_header(){
	$wpcSettings = get_option('wpClassified_data');

	if ($wpcSettings['wpClassified_ads_per_page'] < 1) { 
		$wpcSettings['wpClassified_ads_per_page'] = 10;
	}
	
	if ($wpcSettings['wpClassified_top_image']!=''){
		echo "<img src=\"".$wpcSettings['wpClassified_top_image']."\">";
	}
	if ($wpcSettings['wpClassified_description']!=''){
		echo "<p class=\"wp_announcement\">".$wpcSettings['wpClassified_description']."</p>";
	}

	if ($lnks==""){$lnks = get_wpc_header_link();}
	echo $lnks;
}




function wpc_index(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	wpc_header();

	if ($wpcSettings['view_must_register']=="y" && !_is_usr_loggedin()){
		wpc_read_not_allowed();
		wpc_footer();
		return;
	}
	
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
	$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists WHERE status != 'inactive' ORDER BY position ASC");
	if ((int)$wpClassified_user_info["ID"]){
		$readtest = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id, {$table_prefix}wpClassified_ads_subjects.status, {$table_prefix}wpClassified_read.read_ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects
		LEFT JOIN {$table_prefix}wpClassified_read ON
		{$table_prefix}wpClassified_read.read_user_id = '".$user_ID."' &&
		{$table_prefix}wpClassified_read.read_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id");
	}

	for ($i=0; $i<count($tlists); $i++){
		$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
	}
	for ($i=0; $i<count($readtest); $i++){
		if ($readtest[$i]->read_ads_subjects_id*1<1 && $readtest[$i]->status=='open'){
			$rlists[$readtest[$i]->ads_subjects_list_id] = 'y';
		} 
	}
	
?>
<table class="cat" width=100%><!-- top  search form  fix me -->
<?php
	$cnt=2;
	$catCnt = count($categories);
	if ($catCnt!="0"){
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
			echo '<img src="/wp-content/plugins/wp-classified/' . $category->photo . '">';
			?>
			</td>
			<td class="subcat" valign="top"><strong><?php echo $category->name;?></strong></td>
			</div>
			</tr>
			</table>
		</td></tr>
		<tr><td>
			<?php
			$tfs = $lists[$category->categories_id];
			for ($i=0; $i<count($tfs); $i++){
				?>
				<tr><td>
				<div class="list_ads">
				<?php
					if ($rlists[$tfs[$i]->lists_id]=='y' && $user_ID*1>0){
						echo "<img align=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/unread.gif\" alt=\"There are unread posts in this classified.\" height=15 width=15>";
					} else {
						echo "<img align=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/read.gif\" alt=\"You have read all the posts in this classified.\" height=15 width=15>";
					}
					echo create_wpClassified_link("classified", array("name"=>$tfs[$i]->name, "name"=>$tfs[$i]->name, "lists_id"=>$tfs[$i]->lists_id));
					?>
					&nbsp;<small>(<?php echo $tfs[$i]->ads_status+$tfs[$i]->ads;?>)</small>
					<?
					echo ($tfs[$i]->description!="")?"<br /><small>".$tfs[$i]->description."</small>":"";
					?> 
				</div>	
				</td>
				</tr>
				<?php	
			} // fix me
		    echo "</table>";	
		} // for
		?>
		</td></tr>
		</table>	
		<?php
	} 
	wpc_footer();
}


// display classified
function get_wpc_list(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb, $quicktags;

	$listId = get_query_var("lists_id");
	$start = get_query_var("start");
	$start = ereg_replace("[^0-9]", "", $g_pstart);

	if (!$start){$start = 0;}

	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');

	if ($wpcSettings['wpClassified_ads_per_page'] < 1) { 
		$wpcSettings['wpClassified_ads_per_page'] = 10;
	}

	$userfield = get_wpc_user_field();

	
	//update_views($_GET['lists_id']);
	wpc_header();
	
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');

	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
						 LEFT JOIN {$table_prefix}wpClassified_categories
						 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
						 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($listId*1)."'", ARRAY_A);
						
	$read = (_is_usr_loggedin())?$wpdb->get_col("SELECT ads_subjects_id FROM {$table_prefix}wpClassified_read WHERE read_user_id = ".$wpClassified_user_info["ID"]."'"):array();

	$ads = $wpdb->get_results("SELECT {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}users.*, lu.$userfield AS lastuser FROM {$table_prefix}wpClassified_ads_subjects
		LEFT JOIN {$table_prefix}users
		ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
		LEFT JOIN {$table_prefix}users AS lu
		ON lu.ID = {$table_prefix}wpClassified_ads_subjects.last_author
		WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lists_id']*1)."'
		&& {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
		GROUP BY ads_subjects_id
		ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,
		{$table_prefix}wpClassified_ads_subjects.date DESC
		LIMIT ".($start*1).", ".($wpcSettings['wpClassified_ads_per_page']*1)." ");

	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lists_id']*1)."'	&& status != 'deleted'");

	if ($numAds>$wpcSettings['wpClassified_ads_per_page']){
		echo "<div align=\"left;\">";
		echo __("Pages: ");
		for ($i=0; $i<$numAds/$wpcSettings['wpClassified_ads_per_page']; $i++){
			if ($i*$wpcSettings['wpClassified_ads_per_page']==$start){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_wpClassified_link("classified", array("name"=>($i+1), "lists_id"=>$lists["lists_id"], 	"name"=>$lists["name"], "start"=>($i*$wpcSettings['wpClassified_ads_per_page'])))." ";
			}
		}
		echo "</div>";
	}
	?>
	<table width="100%" class="cat">
	<tr>
		<?php echo $wpcSettings["must_registered_user"];
		if ($wpcSettings["must_registered_user"]=="y" && !_is_usr_loggedin() ) { 
			?><td colspan="3" align=right><b><?php echo create_wpClassified_link("postAds", array("name"=>"Post New Ads", "lists_id"=>$_GET["lists_id"], "name"=>"Add New Ads"));?></b></td><?php
		} else {
			?><td colspan="3" align=right><b><?php echo create_wpClassified_link("postAds", array("name"=>"Post New Ads", "lists_id"=>$_GET["lists_id"], "name"=>"Add New Ads"));?></b></td><?php
		} 
		?>
	</tr>
	</table>
	<br><br>
	<table class="ads_title" width=100%>
	<tr>
		<td class="ads"><?php echo __("Ads");?></td>
		<td class="ads" align=right><?php echo __("Views");?></td>
		<td class="ads" align=right><?php echo __("Last Post");?></td>
	</tr>
	<?php
	for ($x=0; $x<count($ads); $x++){
		$ad = $ads[$x];
		if (!@in_array($ad->ads_subjects_id, $read) && _is_usr_loggedin()){
			$rour = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/unread.gif\" height=15 width=15 lign=absmiddle alt=\"".__("Unread Posts In This List")."\"> ";
		} else {$rour = "";}
		$pstart = 0;
		$pstart = $ad->ads-($ad->ads%$wpcSettings["wpClassified_ads_per_page"]);
		?>
		<tr>
		<td colspan=2 class="ads_subject"><strong>
		<?php
			echo $rour;
			if ($ad->sticky=='y'){
				echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/sticky.gif\" height=15 width=15 align=absmiddle alt=\"".__("Sticky")."\"> ";
			}
			echo create_wpClassified_link("ads_subject", array("name"=>$ad->subject, "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$ad->ads_subjects_id));
			?></strong>
		</td>
		<td align="right" valign="middle" class="ads_subject">
		<?php
			if ($wpcSettings["wpClassified_display_last_post_link"]=='y'){
				echo create_wpClassified_link("lastAds", array("name"=>"<img align=\"middle\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/lastpost.gif"."\" border=\"0\">", "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$ad->ads_subjects_id, "start"=>$pstart));
			}
		?>
		</td>
		</tr>
		<tr>
			<td align=left class="ads_subject_btn"><?php echo __("By:");?> <?php echo create_ads_author($ad);?></td>
			<td align=right class="ads_subject_btn"><?php echo $ad->views;?></td>
			<td align=right class="ads_subject_btn"><nobr><?php echo @date($wpcSettings['wpClassified_date_string'], $ad->date);?></nobr></td>
		</tr>
		<?php
	}
	?>
	</table>
	<?php
	wpc_footer();
}



function wpc_read_not_allowed(){
	global $user_level;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();

	$tpl->assign('user_level', "<!--".($user_level*1)."-->");
	$tpl->assign('access_denied', __("Read Access Denied", 'wpClassified'));
	$tpl->assign('access_denied_reason', __("These classifieds require you to be a registered user in order to view them. If you are already registered you must log in before trying to view the classifieds.", 'wpClassified'));
	$tpl->display('permission_denied.tpl');
}

//fix me

function wpc_footer(){
	$wpcSettings = get_option('wpClassified_data');
	$wpcSettings['credit_line'] = 'wpClassified plugins powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';

	if ($wpcSettings['show_credits']=='y'){
		echo "<p></p><p><hr>" . stripslashes($wpcSettings['credit_line']);
	}
}



// edit post function
function wpClassified_edit_ads(){
	global $_GET, $_POST, $user_login, $wpClassified_user_info, $userdata, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $quicktags;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
			 LEFT JOIN {$table_prefix}wpClassified_categories
			 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
			 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lists_id']."'", ARRAY_A);
		$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
			LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
			 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['ads_subjects_id']."'", ARRAY_A);
		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 WHERE ads_id = '".(int)$_GET['ads_id']."'");
		$postinfo = $postinfo[0];
		if ($wpClassified_user_info["ID"]!=$postinfo->author && !_is_usr_admin() && !_is_usr_mod()){
			wpClassified_permission_denied();
			return;
		} elseif (!_is_usr_loggedin()){
			wpClassified_permission_denied();
			return;
		}
	$displayform = true;
	if ($_POST['wpClassified_edit_ads']=='yes'){
		$makepost = true;
		if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !_is_usr_loggedin()){
			$msg = "You must provide a posting name!";
			$makepost = false;
		}
		if (str_replace(" ", "", $_POST['wpClassified_data']['subject'])==''){
			$msg = "You must provide a subject!";
			$makepost = false;
		}

		if (str_replace(" ", "", $_POST['wpClassified_data']['post'])==''){
			$msg = "You must provide a comment!";
			$makepost = false;
		}

		if ($_FILES['image_file']!=''){
			$ok = (substr($_FILES['image_file']['type'], 0, 5)=="image")?true:false;
			if ($ok==true){
				$imginfo = @getimagesize($_FILES['image_file']['tmp_name']);
				if ($imginfo[0]>(int)$wpcSettings["wpClassified_image_width"]  ||
					$imginfo[1]>(int)$wpcSettings["wpClassified_image_height"] || $imginfo[0] == 0){
					 echo "<h2>Invalid image size. Image must be ".(int)$wpcSettings["wpClassified_image_width"]."x".(int)$wpcSettings["wpClassified_image_height"]." pixels or less. Your image was: ".$imginfo[0]."x".$imginfo[1] . "</h2>";
					$makepost=false;	
				} else {
					$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
					$content = @fread($fp, $_FILES['image_file']['size']);
					@fclose($fp);
					$fp = fopen(ABSPATH."wp-content/plugins/wp-classified/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
					@fwrite($fp, $content);
					@fclose($fp);
					@chmod(dirname(__FILE__)."/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
					$setImage = (int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'];
				}
			}
		}
		if ($makepost==true){
			$displayform = false;
			$_FILES['image_file'] = $id."-".$_FILES['image_file']['name'];
			$wpdb->query("update {$table_prefix}wpClassified_ads
			set subject = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['subject']))."',
			image_file = '".$wpdb->escape(stripslashes($setImage))."',
			post = '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['post']))."'
			WHERE
			ads_id = '".(int)$_GET['ads_id']."' ");
			do_action('wpClassified_edit_ads', $tid);
			wpClassified_display_ads_subject();
		} else {
			$displayform = true;
		}
	} 
	if ($displayform==true){
		wpc_header();
		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			 LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 WHERE ads_id = '".(int)$_GET['ads_id']."'");
		$postinfo = $postinfo[0];
		?>
		<?php
		if ($msg){echo "<h3>".__($msg)."</h3>";}
		echo $quicktags;
		?>
		<table width=100% class="editform" border=0>
		<form method="post" id="cat_form_post" name="cat_form_post" enctype="multipart/form-data"
		onsubmit="this.sub.disabled=true;this.sub.value='Saving Post...';" action="<?php echo create_wpClassified_link("editAdsform", array("lists_id"=>$lists["lists_id"], "name"=>$lists["name"], 'ads_subjects_id'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "ads_id"=>$_GET["ads_id"]));?>">
		<input type="hidden" name="wpClassified_edit_ads" value="yes">
		<tr><td align=right><?php echo __("Posting Name:");?> </td>
		<td><?php
		echo get_post_author($postinfo);
		?></td>
		</tr>
		<tr>
		<td align=right><?php echo __("Subject:");?> </td>
		<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->subject));?>"></td>
		</tr>
		<tr>
		<td align=right><?php echo __("Image File: ");?></td>
		<td><input type=file name="image_file" id="image_file"><br />(<small><?php echo __("(maximum" . (int)$wpcSettings["wpClassified_image_width"]."x".(int)$wpcSettings["wpClassified_image_height"]. " pixel ");?>)</small></td>
		</tr>
		<td valign=top align=right><?php echo __("Comment:");?> </td>
		<td><?php create_ads_input($postinfo->post);?></td>
		</tr><tr><td></td><td><input type=submit value="<?php echo __("Save Post");?>" id="sub"></td>
		</tr></form></table>
		<?php
		wpc_footer();
	}
}




function wpClassified_display_ads_subject(){
	global $_GET, $_POST, $user_login, $userdata, $wpClassified_user_info, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $quicktags;

	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	
	if ($wpcSettings["view_must_register"]=="y" && !_is_usr_loggedin()){
		 wpc_read_not_allowed();
		wpc_footer();
		return;
	}
	
	if (_is_usr_loggedin()){
		$readposts = $wpdb->get_col("SELECT read_ads_id FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id = '".(int)$_GET['ads_subjects_id']."' && read_ads_user_id = '".(int)$wpClassified_user_info["ID"]."'");
	} else {
		$readposts = array();
	}

	update_ads_views($_GET['ads_subjects_id']);

	if (_is_usr_loggedin()){
		$wpdb->query("REPLACE INTO {$table_prefix}wpClassified_read (read_user_id, read_ads_subjects_id) VALUES ('".(int)$wpClassified_user_info["ID"]."', '".(int)$_GET['ads_subjects_id']."')");
	}
	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".(int)$_GET['lists_id']."'", ARRAY_A);
	$adsInfo = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
				 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = '".(int)$_GET['ads_subjects_id']."'", ARRAY_A);
	$posts = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
				 LEFT JOIN {$table_prefix}users
				 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
				 LEFT JOIN {$table_prefix}wpClassified_user_info
				 ON {$table_prefix}wpClassified_user_info.user_info_user_ID = {$table_prefix}users.ID
				 WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".(int)$_GET['ads_subjects_id']."'
					 && {$table_prefix}wpClassified_ads.status = 'active'
				 ORDER BY {$table_prefix}wpClassified_ads.date ASC");
	wpc_header();

?>

<table width=100% class="cat">
	<tr>
		<td width=100% align="right">
	<div style="text-align:right">
		<form action="<?php echo create_wpClassified_link("searchform", array());?>" method="post">
			<input type="text" name="search_terms" VALUE="<?php echo str_replace('"', "&quot;", $_REQUEST['search_terms']);?>">
			<input type="submit" value="Search">
		</form>
	</div>
		</td>
	</tr>
	<tr>
		<td></td>
	</tr>
</table>
<?php
	if (count($posts)>$wpcSettings['wpClassified_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpcSettings['wpClassified_ads_per_page']; $i++){
			if ($i*$wpcSettings['wpClassified_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_wpClassified_link("ads_subject", array("name"=>($i+1), "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$_GET['ads_subjects_id'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['wpClassified_ads_per_page'])))." ";
			}
		}
	}

	if (count($posts)>$wpcSettings['wpClassified_ads_per_page']+$_GET['pstart']){
		$hm = $wpcSettings['wpClassified_ads_per_page']+$_GET['pstart'];
	} else {
		$hm = count($posts);
	}
	if ($hm>count($posts)){
		$hm = count($posts);
	}
	if ($_GET['pstart']*1<0){
		$_GET['pstart'] = 0;
	}

	for ($i=$_GET['pstart']*1; $i<$hm; $i++){
		$post = $posts[$i];
		
		if (_is_usr_admin() || _is_usr_mod() || 
			($post->author==$wpClassified_user_info["ID"] && _is_usr_loggedin())){
			$editlink = " ".create_wpClassified_link("editAds", array("name"=>"EDIT POST", "lists_id"=>$_GET["lists_id"], "name"=>$lists["name"], 'ads_subjects_id'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "ads_id"=>$post->ads_id))." ";
		} else {
			$editlink = "";
		}
		if (!@in_array($post->ads_id, $readposts) && _is_usr_loggedin()){
			$xbefred = "<font color=\"".$wpcSettings['wpClassified_unread_color']."\">";
			$xafred = "</font>";
			$setasread[] = "('".(int)$wpClassified_user_info["ID"]."', '".$_GET['ads_subjects_id']."', '".$post->ads_id."')";
		} else {
			$xbefred = "";
			$xafred = "";
		}
				
		if (file_exists(dirname(__FILE__)."/images/".$post->image_file) && $post->image_file!=""){
				$post->image_file = get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/".$post->image_file;
				$heightwidth = "";
		}

		if (!file_exists(ABSPATH . INC . "/body_tpl.php")){ 
			include(dirname(__FILE__)."/body_tpl.php");
		} else {
			include(ABSPATH . INC . "/body_tpl.php");
		}
		if ($i==0){
			echo stripslashes($wpcSettings['wpClassified_banner_code']);
		}

	}

	if (count($posts)>$wpcSettings['wpClassified_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpcSettings['wpClassified_ads_per_page']; $i++){
			if ($i*$wpcSettings['wpClassified_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".create_wpClassified_link("ads_subject", array("name"=>($i+1), "lists_id"=>$_GET["lists_id"], 'ads_subjects_id'=>$_GET['ads_subjects_id'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['wpClassified_ads_per_page'])))." ";
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




function wpClassified_display_search(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb;
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
# fixed by Matt Gibson
# 07-Apr-2008
#
	$results = $wpdb->get_results("SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id
		 FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users 
			WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id 
			 AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id 
			 AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author 
			 AND (subject like '%".$wpdb->escape($_REQUEST['search_terms'])."%' OR ${table_prefix}wpClassified_ads.post like '%".$wpdb->escape($_REQUEST['search_terms'])."%') ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC");

	if(! $results)
		echo "<P>No posts matched your search terms.</P>";
	else {
?>

<div>
<table width=100% class="cat">
	<tr>
		<td colspan="7">
			<form action="<?php echo create_wpClassified_link("searchform", array());?>" method="post">
				<input type="text" name="search_terms" VALUE="<?php echo str_replace('"', "&quot;", $_REQUEST['search_terms']);?>">&nbsp;<input type="submit" value="Search">
			</form>
		</td>
	</tr>
	<tr>
		<th><p><?php echo __("List");?></p></th>
		<th>&nbsp;</th>
		<th><p><?php echo __("Subject");?></p></th>
		<th>&nbsp;</th>
		<th><p><?php echo __("Author");?></p></th>
		<th>&nbsp;</th>
		<th><p><?php echo __("Date");?></p></th>
	</tr>

	<?php foreach($results as $result) { ?>
	<tr>
		<td><?php echo $result->name; ?></td>
		<td>&nbsp;</td>
		<td>
		<?php
		$re_find = '/RE: /';
		$re_strip = '';
		$new_subject_name = preg_replace($re_find, $re_strip, $result->subject);

		$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads	WHERE ads_ads_subjects_id = '".$result->ads_subjects_id."' AND ads_id < '".$result->ads_id."'", ARRAY_A);
		$post_pstart = ($pstart['count'])/$wpcSettings['wpClassified_ads_per_page'];
		if ($post_pstart=='0'){
			$post_pstart = "0";	
		} else {
			$post_pstart = (ceil($post_pstart)*$wpcSettings['wpClassified_ads_per_page'])-$wpcSettings['wpClassified_ads_per_page'];
		}
		echo create_wpClassified_link("lastAds", array("name"=>$result->name, "lists_id"=>$result->lists_id, "ads_subjects_id"=>$result->ads_subjects_id, "name"=>$new_subject_name, "start"=>$post_pstart, "post_jump"=>$result->ads_id, "search_words"=>$_REQUEST['search_terms']));
		?>
		</td>
		<td>&nbsp;</td>
		<td><?php echo $result->display_name; ?></td>
		<td>&nbsp;</td>
		<td><?php echo @date($wpcSettings['wpClassified_date_string'], $result->date); ?></td>
	</tr>
	<?php } ?>
	</table>
	</div>
	<?
	} 
	wpc_footer();
}


?>
