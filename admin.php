<?php
/*
* admin.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.3.1-a
*/



	//array('name'=>'Classified Options','arg'=>'process_settings'),
function wpcAdminMenu(){
  global $wpcAdminMenu, $PHP_SELF;
  $head = '<div class="wrap"><h2>Wordpress Classified</h2><p>';
  $head .= '<div style="text-align: right;"><a href="http://www.forgani.com/">Support this software</a><br>Read my opinion</div>';
  $menu = '<a href=' . $PHP_SELF . '?page=' . 'wpcSettings' . '>Settings & Options</a> | ';
  for ($i=0; $i<count($wpcAdminMenu); $i++){
    $tlink = $wpcAdminMenu[$i];
    if (!isset($_GET['adm_arg'])) $_GET['adm_arg']='';
	 
	 $sel = "";
	 // TOTO
	 /*
    if ($tlink['arg']==$_GET['adm_arg'] || ($_GET['adm_arg'] == '' && $i==0)){
      $sel = " class=\"current\"";
      $pagelabel = $tlink['name'];
    } else {
      $sel = "";
    }
	 */
    $menu .= '<a href=' . $PHP_SELF . '?page=' .$tlink['arg']. ' ' . $sel .'>'.$tlink['name']. '</a> | ';
  }
  return $head . $menu . '<p><hr style="display: block; border:1px solid #e18a00;"></p>';
}


function adm_modify_process(){
	global $_GET, $_POST, $table_prefix, $PHP_SELF, $wpdb, $wpClassified;
	print wpcAdminMenu();
	$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
	$wpcSettings = get_option('wpClassified_data');
	$loadpage = true;
	switch ($_GET['adm_action']){
		case "deleteAd":
			$loadpage = wpcAdmDeleteAd();
		break;
		case "deleteImg":
			$loadpage = wpcAdmDeleteImg();
		break;
		case "deleteAdSubject":
			$loadpage = wpcAdmDeleteAdSubject();
		break;
		case "activateAd":
			wpcAdmActivateAd($_GET['aid']);
			unset($_GET['aid']);
		break;
		case "activateAdSubject":
			wpcAdmActivateAdSubject($_GET['asid']);
			unset($_GET['asid']);
		break;
		case "stickyAdSubject":
			wpcAdmStickyAdSubject($_GET['asid']);
			unset($_GET['asid']);
		break;
		case "move":
			wpcAdmMove();
			$loadpage = false;
		break;
		case "moveAd":
			wpcAdmMoveAd();
			unset($_GET['asid']);
			$loadpage = true;
		break;
		case "saveAd":
			wpcAdmSaveAd();
		break;
		case "editAdSubject":
			wpcAdmEditAdSubject();
			$loadpage = false;
		break;
		case "editAd":
		 	wpcAdmEditAd();	
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

<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>
<h3>Viewing Ads: <strong><?php echo $adsInfo['subject'];?></strong><br />
In List: <a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>&lid=<?php echo $adsInfo['lists_id'];?>">(<?php echo $adsInfo['c_name'] . " - " .$adsInfo['l_name'];?>)</a></h3>
	<?php
	for ($i=0; $i<count($ads); $i++){
		$ad = $ads[$i];
		$url = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&lid=".$adsInfo['lists_id']."&asid=".$_GET['asid']."&";
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
						<small><?php echo __("Posted By:");?> <strong><?php echo wpcPostAuthor($ad);?></strong> on <?php echo __(@date($wpcSettings['date_format'], $ad->date));?></small>
					</div>
					<p id="post-<?php echo $i;?>-content"><?php echo nl2br(str_replace("<", "&lt;", $ad->post));?></p>
				</div>
			</div>
		</div>
	<?php
	$postinfo = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_id =". $ad->ads_id);
	$post = $postinfo[0];
	$array = split('###', $post->image_file);
		if ($array[0]) {
		?>
		<h3>Images:</h3>
		<center>
		<table><tr>
		<?php
		foreach($array as $f) {
		?>
			<td>
			<!-- Image Upload -->
			<img valign=absmiddle src="<?php echo $wpClassified->public_url ?>/<?php echo $f; ?>" class="imgMiddle" width="120" height="100"><br>
		<?php
			echo "<a href=\"".$url."adm_action=deleteImg&aid=".$ad->ads_id."&file=".$f."\">Delete Image</a></td>";
		}
		?>
			</tr></table></center>
			<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>
		<?php
		} else { echo "<p>No Image Available!</p>"; }
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

	$sql = "SELECT {$table_prefix}wpClassified_ads_subjects.*, {$table_prefix}wpClassified_ads.ads_id,
		{$table_prefix}wpClassified_ads.status as adstatus
		 FROM {$table_prefix}wpClassified_ads_subjects
		 LEFT JOIN {$table_prefix}users
		 ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads_subjects.author
		 LEFT JOIN {$table_prefix}wpClassified_ads
		 ON {$table_prefix}wpClassified_ads.ads_ads_subjects_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_id
		 WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".($_GET['lid'])."'
		 AND {$table_prefix}wpClassified_ads_subjects.status != 'deleted'
		 ORDER BY {$table_prefix}wpClassified_ads_subjects.sticky ASC, {$table_prefix}wpClassified_ads_subjects.date DESC";


	$ads = $wpdb->get_results($sql);
	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lid'])."' AND status != 'deleted'");
	?>
	<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>

	<h3><?php echo __("Viewing List:");?> <strong><?php echo $lists['name'];?></strong></h3>
	<?php
	if ($numAds>$wpcSettings['count_ads_per_page']){
		echo "Pages: ";
		for ($i=0; $i<$numAds/$wpcSettings['count_ads_per_page']; $i++){
			if ($i*$wpcSettings['count_ads_per_page']==$_GET['start']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " <a href=\"".$PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&lid=".$_GET['lid']."&start=".($i*$wpcSettings['count_ads_per_page'])."\">".($i+1)."</a> ";
			}
		}
	}
	?>
	<style type="text/css">
		table { background-color:#fafafa; border:1px #C0C0C0 double; border-collapse:collapse; }
		th { border:1px #C0C0C0 solid; background-color:#fafafa; padding-left:2px; }
		td { border:1px #C0C0C0 solid; padding-left:2px; }
	</style>
	<table width=100% cellpadding=3 cellspacing=0 border=0>
	<tr>
		<th><?php echo __("Actions");?></th>
		<th><?php echo __("Ads");?></th>
		<th><?php echo __("Author");?></th>
		<th><?php echo __("Views");?></th>
		<th><?php echo __("Date");?></th>
	</tr>
	<?php
	for ($x=0; $x<count($ads); $x++){
		$ad = $ads[$x];
		$url = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&lid=".$_GET['lid']."&";
		$slab = ($ad->sticky!='y')?"Sticky":"Unsticky";
		$act = ($ad->adstatus=='inactive')?"De-activate":"Activate";
		$links = array(
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=editAd&aid=".$ad->ads_id."\">".__("Edit")."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=stickyAd&aid=".$ad->ads_id."\">".__($slab)."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=activateAd&aid=".$ad->ads_id."\">".__($act)."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=deleteAd&aid=".$ad->ads_id."\">".__("Delete")."</a>",
		"<a href=\"".$url."asid=".$ad->ads_subjects_id."&adm_action=move&aid=".$ad->ads_id."\">".__("Move")."</a>");
		if ($ad->adstatus=='inactive') $color='#F5D0A9';
		else $color ='#fff';
		?>
		<tr style="background-color:<?php echo $color; ?>";>
			<td><small><?php echo @implode(" | ", $links);?></small></td>
			<td align=left><strong><a href="<?php echo $url;?>asid=<?php echo $ad->ads_subjects_id;?>"><?php echo $ad->subject;?></a></strong></td>
			<td align=left><?php echo $wpClassified->create_ads_subject_author($ad);?></td>
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
			$tlists = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_lists WHERE wpClassified_lists_id IN (SELECT categories_id FROM {$table_prefix}wpClassified_categories) ORDER BY position ASC");
			for ($i=0; $i<count($tlists); $i++){
				$lists[$tlists[$i]->wpClassified_lists_id][] = $tlists[$i];
			}


		if (!$wpcSettings['count_last_ads']) $wpcSettings['count_last_ads'] = 5;
		echo "<div class=\"wrap\"><h2>Edit/Remove Ads</h2><div class=\"wpc_footer\">";
		echo "<h3>Last " . $wpcSettings['count_last_ads'] . " Ads posted...</h3>";
		$start = 0;
		// top lst 8 ads
		$sql ="SELECT ADS.*, A.status as adstatus, L.name as l_name, C.name as c_name FROM {$table_prefix}wpClassified_ads_subjects ADS, {$table_prefix}wpClassified_lists L, {$table_prefix}wpClassified_ads A, {$table_prefix}wpClassified_categories C WHERE ADS.ads_subjects_list_id = L.lists_id AND C.categories_id = L.wpClassified_lists_id AND ADS.ads_subjects_id=A.ads_ads_subjects_id ORDER BY ADS.ads_subjects_id DESC, ADS.date DESC LIMIT ".($start).", ".($wpcSettings['count_last_ads']);
		$lastAds = $wpdb->get_results($sql);
		
		foreach ($lastAds as $lastAd) {
			if ($lastAd->adstatus=='inactive') $color='#DF0101';
			else $color ='#000';
			echo "<a href=\"".$PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&lid=&asid=" .$lastAd->ads_subjects_id."\">".$lastAd->subject."</a>";
			echo " - <span class=\"smallTxt\" style=\"color:". $color . "\"><i>". @date($wpcSettings['date_format'],$lastAd->date)."</i>, (".$lastAd->c_name. " - ".$lastAd->l_name. ")";
			if ($lastAd->adstatus=='inactive') echo " INACTIVE";
			echo "</span><BR />";
		}	
		echo "</div>";
		?>

		<P>
		<style type="text/css">
		table { background-color:#fafafa; border:1px #C0C0C0 double; border-collapse:collapse; }
		th { border:1px #C0C0C0 solid; background-color:#fafafa; padding-left:2px; }
		td { border:1px #C0C0C0 solid; padding-left:2px; }
		</style>
		<hr>
		<table width=100%>
			<tr>
			<th>Category/List</th>
			<th width=100>Ads</th>
			<th width=100>Status</th>
		</tr>
		<?php
			for ($x=0; $x<count($categories); $x++){
				$category = $categories[$x];
				?>
				<tr>
					<td><h3><?php echo $category->name;?></h3></td>
					<td colspan=3></td>
				</tr>
				<?php
				$catIds = $lists[$category->categories_id];
				for ($i=0; $i<count($catIds); $i++){
					?>
					<tr>
					<td><small>(<?php echo $liststatuses[$catIds[$i]->status]; ?>)</small> <a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>&lid=<?php echo $catIds[$i]->lists_id;?>"><?php echo $catIds[$i]->name;?></a></td>
					<td>
					<?php
					$sql= "SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects ADS WHERE ADS.ads_subjects_list_id=". $catIds[$i]->lists_id;
					$adsCount = $wpdb->get_var($sql);
					echo $adsCount;
					?>
					</td>
					<td><?php echo $catIds[$i]->status;?></td>
				</tr>
				<?php
			}
		}
		?>
		</table></div>
		<?php
	}
}


function wpcAdmCountAds($id){
	global $wpdb, $table_prefix;
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".((int)$id)."' AND status = 'active'")-1;
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET ads = '".$ads."' WHERE ads_subjects_id = '".((int)$id)."'");
}


function wpcAdmStickyAdSubject($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT sticky FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='y')?"n":"y";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET sticky = '".$new."' WHERE ads_subjects_id = '".$id."'");
}

function wpcAdmDeleteAd(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$linkb = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=deleteAd&lid=".$_GET['lid']."&aid=".$_GET['aid'];
	
	if ($_POST['deleteid']>0){
		$sql = "DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id =
		 (SELECT ads_ads_subjects_id FROM {$table_prefix}wpClassified_ads WHERE ads_id =".((int)$_POST['deleteid']).")";
		$wpdb->query($sql);
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".((int)$_POST['deleteid'])."'");
		wpcSyncCount($_GET['lid']);
		$wpdb->query("DELETE FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id NOT IN (SELECT ads_subjects_id FROM {$table_prefix}wpClassified_ads_subjects)");
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


function wpcAdmDeleteImg(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$linkb = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=deleteImg&aid=".$_GET['aid']."&file=".$_GET[file];

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
		$file = $wpClassified->public_dir ."/" . $_GET[file];
		if ($_GET[file]) unlink($file);
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


function wpcAdmActivateAd($id){
	global $table_prefix, $wpdb;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads WHERE ads_id = '".$id."'");
	$new = ($cur=='active')?"inactive":"active";
	$sql = "UPDATE {$table_prefix}wpClassified_ads SET status = '".$new."' WHERE ads_id = '".$id."'";
	$wpdb->query($sql);
	wpcAdmCountAds($id);
}

function wpcAdmActivateAdSubject($id){
	global $table_prefix, $wpdb, $_GET;
	$cur = $wpdb->get_var("SELECT status FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$id."'");
	$new = ($cur=='open')?"closed":"open";
	$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = '".$new."' WHERE ads_subjects_id = '".$id."'");
	wpcSyncCount($_GET['lid']);
}

function wpcAdmDeleteAdSubject(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$url = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=deleteAd&lid=".$_GET['lid'];
	//adm_arg=wpcModify&lid=7&asid=47&adm_action=deleteAd&aid=47 ok
	//adm_arg=wpcModify&lid=&asid=60&adm_action=deleteAd&aid=63 nok
	if ($_POST['deleteid']>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads SET status = 'inactive' WHERE ads_ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		$wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET status = 'deleted' WHERE ads_subjects_id = '".((int)$_POST['deleteid'])."'");
		wpcSyncCount($_GET['lid']);
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
function wpcAdmEditAdSubject(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;

	$rec = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects
						WHERE ads_subjects_id = '".($_GET['asid'])."'");
	?>
	<h3>Rename Ad subject</h3>

	<?php echo "Current Ad subject: "."<strong>".$rec->subject."</strong>"; ?>
	<br />
	<form method="post" id="ead_form" name="ead_form" action="<?php echo $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=saveSubject&asid=".$_GET['asid']."&lid=".$_GET['lid'];?>">
		<input type="text" size="30" name="ad_subject" id="ad_subject" value="<?php echo $rec->subject;?>" />
		<input type="hidden" name="ad_old_subject" id="ad_old_subject" value="<?php echo $rec->subject;?>" />
		<input type="submit" value="<?php echo __("Save");?>">&nbsp;&nbsp;<input type=button value="Cancel" onclick="history.go(-1);">
	</form>
	<?php
}


function wpcAdmEditAd(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
	$rec = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			LEFT JOIN {$table_prefix}users
			ON {$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			WHERE ads_id = '".(int)$_GET['aid']."'");

	$rec = $rec[0];
	?>

	<P>
	<form method="post" id="ead_form" name="ead_form" onsubmit="this.sub.disabled=true;this.sub.value='Saving Ad...';" action="<?php echo $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=saveAd&aid=".$_GET['aid'];?>">
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



function wpcAdmSaveAd(){
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


function wpcAdmMoveAd(){
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



function wpcAdmMove(){
	global $_GET, $_POST, $wpdb, $table_prefix, $PHP_SELF;
    ?>
	<p><a href="javascript:javascript:history.go(-1)">back to previous page</a>&nbsp;&nbsp;<a href="<?php echo $PHP_SELF;?>?page=wpcModify&adm_arg=<?php echo $_GET['adm_arg'];?>">back to main page</a></p>

	<h3>Move Ad</h3>
	
    <?php
	$lst_cat_org = $wpdb->get_row("SELECT l.name lst, c.name cat FROM {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c WHERE l.lists_id = '".$_GET['lid']."' AND l.wpClassified_lists_id = c.categories_id");
	$lst_cat = $wpdb->get_results("SELECT l.lists_id, l.name lst, c.name cat FROM {$table_prefix}wpClassified_lists l, {$table_prefix}wpClassified_categories c WHERE l.wpClassified_lists_id = c.categories_id ORDER BY lst ASC");
	$asid = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = '".$_GET['asid']."'");
	echo "<br /><br /><br /><strong>Ad to move:</strong>" .$asid->subject . "<br />";
	echo "<strong>Actual List:</strong>" . $lst_cat_org->lst ."<br ><strong>Category:</strong>".$lst_cat_org->cat . "<br />";
	echo "<br />";

	$url = $PHP_SELF."?page=wpcModify&adm_arg=".$_GET['adm_arg']."&adm_action=moveAd&aid=".$_GET['aid']."&lst=".$_GET['lid']."&asid=".$_GET['asid'];

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


function wpcSyncCount($id){
	global $wpdb, $table_prefix;
	$posts = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads.status = 'active'");
	$ads = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id = '".((int)$id)."' && {$table_prefix}wpClassified_ads_subjects.status = 'open'");
	$wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = '".$posts."', ads_status = '".$ads."' WHERE lists_id = '".$id."'");
}


?>
