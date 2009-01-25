<?php
/*
* admin.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.3.0-c
*/

function wpClassified_spam_filter($name, $email, $subject, $post, $userID){
	global $ksd_api_host, $ksd_api_port;

	$spamcheck = array(
		"user_ip"=> $_SERVER['REMOTE_ADDR'],
		"user_agent"=> $_SERVER['HTTP_USER_AGENT'],
		"referrer"=> $_SERVER['HTTP_REFERER'],
		"blog"=> get_option('home'),
		"comment_author"=> rawurlencode($name),
		"comment_author_email"=> rawurlencode($email),
		"comment_author_url"=> "http://",
		"comment_content"=> str_replace("%20", "+", rawurlencode($subject))."+".str_replace("%20", "+", rawurlencode($post)),
		"comment_type"=> "",
		"user_ID"=> $userID
		);

	$query_string = '';
	foreach ($spamcheck as $k=>$v){
		$query_string .= $k.'='.urlencode(stripslashes($v)).'&';
	}

	// into akismet's spam protection
	if (function_exists('ksd_http_post')){
		$response = ksd_http_post($query_string, $ksd_api_host, '/1.1/comment-check', $ksd_api_port);
		if ('true' == $response[1]){
			return true;
		}
	} 
	return false;
}


function adm_modify_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb;

	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$wpcSettings = get_option('wpClassified_data');
	$loadpage = true;
	switch ($_GET['adm_action']){
		case "deleteAd":
			$loadpage = delete_ad();
		break;
		case "deleteImg":
			$loadpage = delete_img();
		break;
		case "deleteAdSubject":
			$loadpage = delete_ad_subject();
		break;
		case "activateAd":
			activate_ad($_GET['aid']);
			unset($_GET['aid']);
		break;
		case "activateAdSubject":
			activate_ad_subject($_GET['asid']);
			unset($_GET['asid']);
		break;
		case "stickyAdSubject":
			set_sticky_ad_subject($_GET['asid']);
			unset($_GET['asid']);
		break;
		case "move":
			_move();
			$loadpage = false;
		break;
		case "moveAd":
			move_ad();
			unset($_GET['asid']);
			$loadpage = true;
		break;
		case "saveAd":
			save_ad();
		break;
		case "editAdSubject":
			edit_ad_subject();
			$loadpage = false;
		break;
		case "editAd":
		 	edit_ad();	
			$loadpage = false;
		break;
	}

	if ($msg!=''){
		?>
		<p>
		<b><?php echo $msg; ?></b>
		</p>
		<?php
	}
	if ($_GET['asid']>0 && $loadpage==true){
		$sql = "SELECT ADS.*, L.name as l_name, C.name as c_name, L.lists_id
			FROM {$table_prefix}wpClassified_ads_subjects ADS, {$table_prefix}wpClassified_lists L, {$table_prefix}wpClassified_categories C
			 WHERE ADS.ads_subjects_list_id = L.lists_id 
			AND C.categories_id = L.wpClassified_lists_id 
			AND ADS.ads_subjects_id = ".$_GET['asid'];
		$adsInfo = $wpdb->get_row($sql, ARRAY_A);

		$ads = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			 LEFT JOIN {$table_prefix}users
			 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 WHERE {$table_prefix}wpClassified_ads.ads_ads_subjects_id = '".($_GET['asid'])."'
			 ORDER BY {$table_prefix}wpClassified_ads.date ASC");

?>

<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>
<h3>Viewing Ads: <strong><?php echo $adsInfo['subject'];?></strong><br />
In List: <a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&lid=<?php echo $adsInfo['lists_id'];?>">(<?php echo $adsInfo['c_name'] . " - " .$adsInfo['l_name'];?>)</a></h3>
	<?php
	for ($i=0; $i<count($ads); $i++){
		$ad = $ads[$i];
		$url = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lid=".$adsInfo['lists_id']."&asid=".$_GET['asid']."&";
		$act = ($ad->status=='inactive')?"Activate":"De-activate";
		$links = array(
		"<a href=\"".$url."adm_action=editAd&aid=".$ad->ads_id."\">".__("Edit")."</a>",
		"<a href=\"".$url."adm_action=deleteAd&aid=".$ad->ads_id."\">".__("Delete")."</a>",
		"<a href=\"".$url."adm_action=activateAd&aid=".$ad->ads_id."\">".__($act)."</a>",
		"<a href=\"".$url."adm_action=move&aid=".$ad->ads_id."\">".__("Move")."</a>"
		);
		?>
		<div style="border: 1px solid #bbb; padding:8px; background-color: #fafafa;">
			<strong><?php echo @implode(" | ", $links);	?></strong>
			<div class="post-bottom">
				<div class="entry" id="post-<?php echo $i;?>-entry">
					<div class="title" id="post-<?php echo $i;?>-title">
						<h2><?php echo str_replace("<", "&lt;", $ad->subject);?></h2>
						<small><?php echo __("Posted By:");?> <strong><?php echo create_admin_post_author($ad);?></strong> on <?php echo __(@date($wpcSettings['date_format'], $ad->date));?></small>
					</div>
					<p id="post-<?php echo $i;?>-content"><?php echo nl2br(str_replace("<", "&lt;", $ad->post));?></p>
				</div>
			</div>
		</div>
	<?php
	$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id =". $ad->ads_id);
	$post = $postinfo[0];
	$array = split('###', $post->image_file);
	?>	
	<h3>Images:</h3>
	<center>
	<table><tr>
	<?php
	foreach($array as $f) {
	?>
	<td>
	<!-- Image Upload -->
	<img valign=absmiddle src="<?php echo get_bloginfo('wpurl') ?>/wp-content/plugins/wp-classified/images/<?php echo $f; ?>" class="imgMiddle" width="120" height="100"><br>
	<?php
	echo "<a href=\"".$url."adm_action=deleteImg&aid=".$ad->ads_id."&file=".$f."\">Delete</a></td>";
	}
	?>
	</tr></table></center>

		<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>
		<?php
	}
	?>
	<?php
	} elseif ($_GET['lid']>0 && $loadpage==true){
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lid'])."'", ARRAY_A);
	// list ads
	if (!$_GET['start']){
		$_GET['start'] = 0;
	}

	$sql = "SELECT * FROM {$table_prefix}wpClassified_ads_subjects
		 LEFT JOIN {$table_prefix}users
		 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
		 LEFT JOIN {$table_prefix}wpClassified_ads
		 ON {$table_prefix}wpClassified_ads.ads_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id
		 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lid'])."'
				&& {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
		 ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC,
			    {$table_prefix}wpClassified_ads_subjects.date DESC
		 LIMIT ".($_GET['start']).", ".($wpcSettings['count_ads_per_page']);

	$ads = $wpdb->get_results($sql);


	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lid'])."' && status != 'deleted'");
	?>
	<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>

	<h3><?php echo __("Viewing List:");?> <strong><?php echo $lists['name'];?></strong></h3>
	<?php
	if ($numAds>$wpcSettings['count_ads_per_page']){
		echo "Pages: ";
		for ($i=0; $i<$numAds/$wpcSettings['count_ads_per_page']; $i++){
			if ($i*$wpcSettings['count_ads_per_page']==$_GET['start']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " <a href=\"".$PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lid=".$_GET['lid']."&start=".($i*$wpcSettings['count_ads_per_page'])."\">".($i+1)."</a> ";
			}
		}
	}
	?>
	<table width=100% cellpadding=3 cellspacing=0 border=0>
	<tr>
		<th align=left><?php echo __("Actions");?></th>
		<th align=left><?php echo __("Ads");?></th>
		<th align=left><?php echo __("Author");?></th>
		<th align=right><?php echo __("Views");?></th>
		<th align=right><?php echo __("Date");?></th>
	</tr>
	<?php
	for ($x=0; $x<count($ads); $x++){
		$ad = $ads[$x];
		$url = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lid=".$_GET['lid']."&";
		$slab = ($ad->sticky!='y')?"Sticky":"Unsticky";
		$act = ($ad->status=='open')?"De-activate":"Activate";
		$links = array(
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=editAd&aid=".$ad->ads_id."\">".__("Edit")."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=stickyAd&aid=".$ad->ads_id."\">".__($slab)."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=activateAd&aid=".$ad->ads_id."\">".__($act)."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=deleteAd&aid=".$ad->ads_id."\">".__("Delete")."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=move&aid=".$ad->ads_id."\">".__("Move")."</a>");
		?>
		<tr>
			<td><small><?php echo @implode(" | ", $links);?></small></td>
			<td align=left><strong><a href="<?php echo $url;?>asid=<?php echo $ad->ads_subjects_id;?>"><?php echo $ad->subject;?></a></strong></td>
			<td align=left><?php echo create_ads_subject_author($ad);?></td>
			<td align=right><?php echo $ad->views;?></td>
			<td align=right><?php echo @date($wpcSettings['date_format'], $ad->date);?></td>
			</tr>
			<?php
		}
		?>
		</table></td></tr></table>
		<?php
		} elseif ($loadpage==true){
			$categories = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_categories ORDER BY position ASC");
			$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists ORDER BY position ASC");
			for ($i=0; $i<count($tlists); $i++){
				$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
			}
		if (!$wpcSettings['count_last_ads']) $wpcSettings['count_last_ads'] = 5;
		echo "<div class=\"wpc_footer\">";
		echo "<h3>Last " . $wpcSettings['count_last_ads'] . " Ads posted...</h3>";
		$start = 0;

		$sql ="SELECT ADS.*, L.name as l_name, C.name as c_name FROM {$table_prefix}wpClassified_ads_subjects ADS, {$table_prefix}wpClassified_lists L, {$table_prefix}wpClassified_categories C WHERE ADS.ads_subjects_list_id = L.lists_id  AND C.categories_id = L.wpClassified_lists_id ORDER BY ADS.ads_subjects_id DESC, ADS.date DESC LIMIT ".($start).", ".($wpcSettings['count_last_ads']);
		$lastAds = $wpdb->get_results($sql);
		foreach ($lastAds as $lastAd) {
			echo "<a href=\"".$PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&lid=&asid=" .$lastAd->ads_subjects_id."\">".$lastAd->subject."</a>";
			echo " - <span class=\"smallTxt\"><i>". @date($wpcSettings['date_format'],$lastAd->date)."</i>, (".$lastAd->c_name. " - ".$lastAd->l_name. ")</span><BR />";
		}	
		echo "</div>";
		?>
		<hr>
		<table border=0 width=100%>
			<tr>
			<th></th>
			<th align=left>Category/List</th>
			<th align=right width=100>Subjects</th>
			<th align=right width=100>Ads</th>
			<th align=right width=100>Views</th>
		</tr>
		<?php
			for ($x=0; $x<count($categories); $x++){
				$category = $categories[$x];
				?>
				<tr>
					<td colspan=2><h3><?php echo $category->name;?></h3></td>
					<td colspan=3></td>
				</tr>
				<?php
				$catIds = $lists[$category->categories_id];
				for ($i=0; $i<count($catIds); $i++){
					?>
					<tr>
					<td></td>
					<td><small>(<?php echo $liststatuses[$catIds[$i]->status]; ?>)</small> <a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>&lid=<?php echo $catIds[$i]->lists_id;?>"><?php echo $catIds[$i]->name;?></a></td>
					<td align=right><?php echo $catIds[$i]->ads_status;?></td>
					<td align=right><?php echo $catIds[$i]->ads;?></td>
					<td align=right><?php echo $catIds[$i]->ads_views;?></td>
				</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}
}



function adm_count_ads($id){
	global $wpdb, $table_prefix;
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$id)."' && status = 'active'")-1;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET ads = '".$ads."' WHERE ads_subjects_id = '".((int)$id)."'");
}


function set_sticky_ad_subject($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT sticky FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='y')?"n":"y";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET sticky = '".$new."' WHERE ads_subjects_id = '".$id."'");
}

function delete_ad(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=deleteAd&lid=".$_GET['lid']."&aid=".$_GET['aid'];
	
	if ($_POST['deleteid']>0){
		$sql = "DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id =
		 (SELECT ads_ads_subjects_id FROM {$table_prefix}wpClassified_ads WHERE ads_id =".((int)$_POST['deleteid']).")";
		$wpdb->query($sql);
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".((int)$_POST['deleteid'])."'");
		adm_sync_count($_GET['lid']);
		return true;
	} else {
	?>
	<h3>Confirmation to delete</h3>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $linkb;?>">
	<strong>
		<input type="hidden" name="deleteid" value="<?php echo $_GET['aid'];?>">
		Are you sure you want to delete this ad?<br />
		<input type=submit value="<?php echo __("Yes");?>"> <input type=button value="<?php echo __("No");?>" onclick="history.go(-1);">
	</strong>
	</form>
	<?php
	return false;
	}
}


function delete_img(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$linkb = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=deleteImg&aid=".$_GET['aid']."&file=".$_GET[file];

	if ($_POST['deleteid']>0){
		$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".(int)$_GET['aid']."'");
		$rec = $postinfo[0];
		$array = split('###', $rec->image_file);
		foreach($array as $f) {
			if ($f == $_GET[file]){
			} else {
			  $txt .= $f . '###';
			}
		}
		$newstring = substr($txt, 0, -3);
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET image_file ='" . $wpdb->escape(stripslashes($newstring)) . "' WHERE ads_id=" . $_GET['aid'] );

		$file = ABSPATH."wp-content/plugins/wp-classified/images/" . $_GET[file];
		unlink($file);
		return true;
	} else {
	?>
	<h3>Confirmation to delete</h3>
	<form method="post" id="cat_form_post" name="cat_form_post" action="<?php echo $linkb;?>">
	<strong>
		<input type="hidden" name="deleteid" value="<?php echo $_GET['aid'];?>">
		Are you sure you want to delete this Image?<br />
		<input type=submit value="<?php echo __("Yes");?>"> <input type=button value="<?php echo __("No");?>" onclick="history.go(-1);">
	</strong>
	</form>
	<?php
	return false;
	}
}


function activate_ad($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".$id."'");
	$new = ($cur=='active')?"inactive":"active";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = '".$new."' WHERE ads_id = '".$id."'");
	adm_count_ads($id);
}

function activate_ad_subject($id){
	global $table_prefix, $wpdb, $_GET;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='open')?"closed":"open";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = '".$new."' WHERE ads_subjects_id = '".$id."'");
	adm_sync_count($_GET['lid']);
}

function delete_ad_subject(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$url = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=deleteAd&lid=".$_GET['lid'];
	//adm_arg=wpcModify&lid=7&asid=47&adm_action=deleteAd&aid=47 ok
	//adm_arg=wpcModify&lid=&asid=60&adm_action=deleteAd&aid=63 nok
	if ($_POST['deleteid']>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'inactive' WHERE ads_ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = 'deleted' WHERE ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		adm_sync_count($_GET['lid']);
		return true;
	} else {
		?>
		<h3>Ad Deletion Confirmation</h3>
		<form method="post" id="ead_form" name="ead_form" action="<?php echo $url;?>">
		<strong>
			<input type="hidden" name="deleteid" value="<?php echo $_GET['aid'];?>">
			Are you sure you want to delete this ads? <br />
			<input type=submit value="<?php echo __("Yes");?>"> <input type=button value="<?php echo __("No");?>" onclick="history.go(-1);">
		</strong>
		</form>
		<?php
		return false;
	}
}
function edit_ad_subject(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$rec = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
						WHERE ads_subjects_id = '".($_GET['asid'])."'");
	?>
	<h3>Rename Ad subject</h3>

	<?php echo "Current Ad subject: "."<strong>".$rec->subject."</strong>"; ?>
	<br />
	<form method="post" id="ead_form" name="ead_form" action="<?php echo $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=saveSubject&asid=".$_GET['asid']."&lid=".$_GET['lid'];?>">
		<input type="text" size="30" name="ad_subject" id="ad_subject" value="<?php echo $rec->subject;?>" />
		<input type="hidden" name="ad_old_subject" id="ad_old_subject" value="<?php echo $rec->subject;?>" />
		<input type="submit" value="<?php echo __("Save");?>">&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);">
	</form>
	<?php
}


function edit_ad(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$rec = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			LEFT JOIN {$table_prefix}users
			ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			WHERE ads_id = '".(int)$_GET['aid']."'");

       
	$rec = $rec[0];
	?>

	<P>
	<style type="text/css">
	fieldset {
	  padding: 1em;
	  font:12px;
	  font-weight:bold;
	  border:1px solid #ddd;
	}
	label {
	  float:left;
	  width:15%;
	  margin-right:0.5em;
	  padding-top:0.2em;
	  text-align:right;
	  font-weight:bold;}
	 </style>

	<form method="post" id="ead_form" name="ead_form" onsubmit="this.sub.disabled=true;this.sub.value='Saving Ad...';" action="<?php echo $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=saveAd&aid=".$_GET['aid'];?>">
	<input type="hidden" name="modify_ad" value="true">
	<fieldset>
	<legend>Original Ad (<?php echo $_GET['aid']; ?>)</legend>
	<b><?php echo $rec->subject; ?></b><BR>
	<div style="font-weight:normal;"><?php echo $rec->post;?></div>
	</fieldset>
	<fieldset>
	<legend>Edit</legend>
	<br>
	<table width="100%" class="editform" border="0">
			<tr>
				<td valign="top" align="left">Subject: </td><td><input type="text" size="60" name="ad_subject" id="ad_subject" value="<?php echo $rec->subject;?>" /></td></tr>
				<tr><td valign="top" align="left">Description: </td><td><input type="hidden" name="ads_subjects_id" id="ads_subjects_id" value="<?php echo $rec->ads_ads_subjects_id;?>" /><br>
				<?php echo "<textarea name='ad_content_data' id='ad_content_data' cols='80' rows='10'>".str_replace("<", "&lt;", $rec->post)."</textarea>" ?>	
			</td></tr>
			<tr><td valign="top" align="left"></td><td><BR><input type="submit" value="Save">&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);"></td></tr>
	</table>
	</fieldset>
	</form>
	<?php
}



function save_ad(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$mod = $_POST['modify_ad'];
	if ($mod=="true"){
		$html=stripslashes($_POST['ad_content_data']); 
		$sbj=$_POST['ad_subject'];
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET post = '".$html."', subject='".$sbj."' WHERE ads_id = '".(int)$_GET['aid']."'");
		$sql = "UPDATE {$table_prefix}wpClassified_ads_subjects SET subject='".$sbj."' WHERE ads_subjects_id=".$_POST['ads_subjects_id'];
		$wpdb->query($sql);		
	}
	$msg = "Ad Saved";
	return $msg;
}


function move_ad(){
	global $_GET, $_POST, $wpdb, $table_prefix;
	list($olst, $ocat) = split(' -> ', $_POST['lstCatNames']);
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET ads_subjects_list_id = 
		'".$_POST['adLid']."' WHERE ads_subjects_id = '".$_GET['asid']."'");
	/*
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET ads_ads_subjects_id = 
		'".$_POST['adLid']."' WHERE ads_ads_subjects_id = '".$_GET['asid']."'");
	*/
	$asid = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = 
		'".$_GET['asid']."'");
	$lids = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = 
		'".$_GET['lid']."'");
	$newLids = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id = 
		'".$_POST['adLid']."'");

	if($lids->ads_status!='0'){
		$oldStatus = $lids->ads_status-1;
	}else{
		$oldStatus = $lids->ads_status;
	}

	$oldAd = $lids->ads-$asids->ads;
	$old_views_count = $lids->ads_views-$asids->views;

	$newLidStatus = $newLids->ads_status+1;
	$newAd = $newLids->ads+$asids->ads;
	$newadView = $newLids->ads_views+$asids->views;

	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_status = '".$oldStatus."', ads = '".$oldAd."', ads_views = '".$old_views_count."' WHERE wpClassified_lists_id = '".$_GET['lid']."'");
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_status = 
		'".$newLidStatus."', ads = '".$newAd."',
		ads_views = '".$newadView."' WHERE wpClassified_lists_id = '".$_POST['adLid']."'");

	$msg = "Ad moved to: ".$_POST['lstCatNames'];

	return $msg;
}


function _move(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
    ?>
	<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpClassified&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>

	<h3>Move Ad</h3>
	
    <?php
	$lst_cat_org = $wpdb->get_row("SELECT l.name lst, c.name cat FROM {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c WHERE l.lists_id = '".$_GET['lid']."' AND l.wpClassified_lists_id = c.categories_id");
	$lst_cat = $wpdb->get_results("SELECT l.lists_id, l.name lst, c.name cat FROM {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c WHERE l.wpClassified_lists_id = c.categories_id ORDER BY lst ASC");
	$asid = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$_GET['asid']."'");
	echo "<br /><br /><br /><strong>Ad to move:</strong>" .$asid->subject . "<br />";
	echo "<strong>Actual List:</strong>" . $lst_cat_org->lst ."<br ><strong>Category:</strong>".$lst_cat_org->cat . "<br />";
	echo "<br />";

	$url = $PHP_SELF."?page=wpClassified&adm_arg=".$_GET['adm_arg']."&adm_action=moveAd&aid=".$_GET['aid']."&lst=".$_GET['lid']."&asid=".$_GET['asid'];

	?>
	<form method="post" id="ead_form" name="ead_form" onsubmit="this.sub.disabled=true;this.sub.value='Moving Ad...';" action="<?php echo $url;?>" >
		<table width="100%" class="editform" border="0">
			<tr>
				<td valign="top" align="left">
					<input type="hidden" name="moveAd" value="true">
					<input type="hidden" value="<?php echo $lst_cat_org->lst." -> ".$lst_cat_org->cat;?>" name="lstCatNames">
					Select the list to move the Ad to: 
				<?php
					echo "<select id=\"adLid\" name=\"adLid\">";
					foreach($lst_cat as $adLid) {
						echo "<option value=\"$adLid->lists_id\">" . $adLid->lst . " -> " . $adLid->cat;
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td valign="top" align="left">
					&nbsp;
				</td>
			</tr>
			<tr>
				<td valign="top" align="left">
					<input type="submit" value="Move Ad" id="sub" />&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);">
				</td>
			</tr>
		</table>
	</form>

	<?php
}


?>
