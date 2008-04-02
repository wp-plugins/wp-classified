<?php


wpClassified_get_user_info();
function wpClassified_process(){
	global $_GET, $_POST, $user_level, $wpClassified_user_info, $table_prefix, $wpdb;
	$wpClassified_settings = get_option('wpClassified_data');
	if (file_exists(TEMPLATEPATH."/wpClassified.css")){
		?>
		<link rel="stylesheet" href="<?php bloginfo('stylesheet_directory');?>/wpClassified.css" type="text/css" media="screen" />
		<?php
	} else {
		?>
		<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl');?>/wp-content/plugins/wpClassified/wpClassified.css" type="text/css" media="screen" />
		<?php
	}

	switch ($_GET['wpClassified_action']){
		default:
		case "classified": wpClassified_display_classified();
		break;
		case "search": wpClassified_display_search();
		break;
		case "viewList": wpClassified_display_list();
		break;
		case "postAds":	wpClassified_ads_subject();
		break;
		case "editAds":	wpClassified_edit_ads();
		break;
		case "viewAds":	wpClassified_display_ads_subject();
		break;
	}
}

function wpClassified_header(){
	$wpClassified_settings = get_option('wpClassified_data');
	if ($wpClassified_settings['wpClassified_top_image']!=''){
		echo "<img src=\"".$wpClassified_settings['wpClassified_top_image']."\">";
	}
	if ($wpClassified_settings['wpClassified_announcement']!=''){
		echo "<p class=\"wp_announcement\">".$wpClassified_settings['wpClassified_announcement']."</p>";
	}
}

function wpClassified_display_classified(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb;
	get_currentuserinfo();
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	wpClassified_header();
	if ($wpClassified_settings['wpClassified_view_must_register']=="y" && !wpClassified_is_loggedin()){
		wpClassified_read_not_allowed();
		wpClassified_footer();
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
<table class="cat" width=100%><!-- top  search form  fixme -->
<?php
	$cnt=2;
	for ($x=0; $x<count($categories); $x++){
		if ( ($cnt%2) == 0) {
			echo "<tr><td width=50%>";
		} else echo "</td><td width=50%>";
		$category = $categories[$x];
        $cnt++;
		?>
	    <table  width=100%>
		<tr>
		<div >
		<td class="subcat" width="50px" height="80px">
		<?php $img = get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/".$category->photo;
		echo "<img src='$img'>";
		?>
		</td>
		<td class="subcat" valign="top"><strong><?php echo $category->name;?></strong></td>
		</div>
		</tr>
		<?php
		$tfs = $lists[$category->categories_id];
		for ($i=0; $i<count($tfs); $i++){
			?>
			<tr><td></td><td>
			<div class="list_ads">
			<?php
				if ($rlists[$tfs[$i]->lists_id]=='y' && $user_ID*1>0){
					echo "<img align=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/unread.gif\" alt=\"There are unread posts in this classified.\" height=15 width=15>";
				} else {
					echo "<img align=absmiddle src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/read.gif\" alt=\"You have read all the posts in this classified.\" height=15 width=15>";
				}
				echo wpClassified_create_link("classified", array("name"=>$tfs[$i]->name, "name"=>$tfs[$i]->name, "lists_id"=>$tfs[$i]->lists_id));
				?>
				&nbsp;<small>(<?php echo $tfs[$i]->ads_status+$tfs[$i]->ads;?>)</small>
				<?
				echo ($tfs[$i]->description!="")?"<br /><small>".$tfs[$i]->description."</small>":"";
				?> 
			</div>	
			</td>
			</tr>
			<?php		
		} // for
	    echo "</table>";
	} // for
	?>
	</td></tr>
	</table>
	<?php wpClassified_footer();
}


// display classified
function wpClassified_display_list(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb, $wpClassified_wp_version_info, $quicktags;
	get_currentuserinfo();
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	wpClassified_update_views($_GET['lists_id']);
	wpClassified_header();
		$liststatuses = array(active=>'Open',inactive=>'Closed',readonly=>'Read-Only');
		$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
							 LEFT JOIN {$table_prefix}wpClassified_categories
							 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
							 WHERE {$table_prefix}wpClassified_lists.lists_id = '".($_GET['lists_id']*1)."'", ARRAY_A);
	if (!$_GET['start']){$_GET['start'] = 0;}
	$read = (wpClassified_is_loggedin())?$wpdb->get_col("SELECT ads_subjects_id FROM {$table_prefix}wpClassified_read WHERE read_user_id = ".$wpClassified_user_info["ID"]."'"):array();

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
	LIMIT ".($_GET['start']*1).", ".($wpClassified_settings['wpClassified_ads_subjects_per_page']*1)." ");
	$numAds = $wpdb->get_var("SELECT count(*) FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_list_id = '".($_GET['lists_id']*1)."'	&& status != 'deleted'");
	if ($numAds>$wpClassified_settings['wpClassified_ads_subjects_per_page']){
		echo "<div align=\"left;\">";
		echo __("Pages: ");
		for ($i=0; $i<$numAds/$wpClassified_settings['wpClassified_ads_subjects_per_page']; $i++){
			if ($i*$wpClassified_settings['wpClassified_ads_subjects_per_page']==$_GET['start']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".wpClassified_create_link("classified", array("name"=>($i+1), "lists_id"=>$lists["lists_id"], 	"name"=>$lists["name"], "start"=>($i*$wpClassified_settings['wpClassified_ads_subjects_per_page'])))." ";
			}
		}
		echo "</div>";
	}
	?>
	<table width="100%" class="cat">
	<tr>
		<?php echo $wpClassified_settings["wpClassified_ads_must_register"];
		if ($wpClassified_settings["wpClassified_ads_must_register"]=="y" && !wpClassified_is_loggedin() ) { 
			?><td colspan="3" align=right><b><?php echo wpClassified_create_link("postAds", array("name"=>"Post New Ads", "lists_id"=>$_GET["lists_id"], "name"=>"Add New Ads"));?></b></td><?php
		} else {
			?><td colspan="3" align=right><b><?php echo wpClassified_create_link("postAds", array("name"=>"Post New Ads", "lists_id"=>$_GET["lists_id"], "name"=>"Add New Ads"));?></b></td><?php
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
		if (!@in_array($ad->ads_subjects_id, $read) && wpClassified_is_loggedin()){
			$rour = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/unread.gif\" height=15 width=15 lign=absmiddle alt=\"".__("Unread Posts In This List")."\"> ";
		} else {$rour = "";}
		$pstart = 0;
		$pstart = $ad->ads-($ad->ads%$wpClassified_settings["wpClassified_ads_per_page"]);
		?>
		<tr>
		<td colspan=2 class="ads_subject"><strong>
		<?php
			echo $rour;
			if ($ad->sticky=='y'){
				echo "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/sticky.gif\" height=15 width=15 align=absmiddle alt=\"".__("Sticky")."\"> ";
			}
			echo wpClassified_create_link("ads_subject", array("name"=>$ad->subject, "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$ad->ads_subjects_id));
			?></strong>
		</td>
		<td align="right" valign="middle" class="ads_subject">
		<?php
			if ($wpClassified_settings["wpClassified_display_last_post_link"]=='y'){
				echo wpClassified_create_link("lastAds", array("name"=>"<img align=\"middle\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/lastpost.gif"."\" border=\"0\">", "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$ad->ads_subjects_id, "start"=>$pstart));
			}
		?>
		</td>
		</tr>
		<tr>
			<td align=left class="ads_subject_btn"><?php echo __("By:");?> <?php echo wpClassified_create_ads_author($ad);?></td>
			<td align=right class="ads_subject_btn"><?php echo $ad->views;?></td>
			<td align=right class="ads_subject_btn"><nobr><?php echo @date($wpClassified_settings['wpClassified_date_string'], $ad->date);?></nobr></td>
		</tr>
		<?php
	}
	?>
	</table>
	<?php
	wpClassified_footer();
}

function wpClassified_display_search(){
	global $_GET, $_POST, $user_level, $user_ID, $wpClassified_user_info, $table_prefix, $wpdb;
	get_currentuserinfo();
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	wpClassified_header();
	if ($wpClassified_settings['wpClassified_view_must_register']=="y" && !wpClassified_is_loggedin()){
		wpClassified_read_not_allowed();
		wpClassified_footer();
		return;
	}
	$results = $wpdb->get_results("SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id
			 FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users 
			 WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id 
			 AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id 
			 AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author 
			 AND (subject like '%".$wpdb->escape($_REQUEST['search_terms'])."%' OR post like '%".$wpdb->escape($_REQUEST['search_terms'])."%') 
			 ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC");

	if(! $results)
		echo "<P>No posts matched your search terms.</P>";
	else {
?>

<div>
<table width=100% class="cat">
	<tr>
		<td colspan="7">
			<form action="<?php echo wpClassified_create_link("searchform", array());?>" method="post">
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

		$pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads	WHERE ads_ads_subjects_id = '".$result->ads_subjects_id."'
						AND ads_id < '".$result->ads_id."'", ARRAY_A);
		$post_pstart = ($pstart['count'])/$wpClassified_settings['wpClassified_ads_per_page'];
		if ($post_pstart=='0'){
			$post_pstart = "0";	
		} else {
			$post_pstart = (ceil($post_pstart)*$wpClassified_settings['wpClassified_ads_per_page'])-$wpClassified_settings['wpClassified_ads_per_page'];
		}
		echo wpClassified_create_link("lastAds", array("name"=>$result->name, "lists_id"=>$result->lists_id, "ads_subjects_id"=>$result->ads_subjects_id, "name"=>$new_subject_name, "start"=>$post_pstart, "post_jump"=>$result->ads_id, "search_words"=>$_REQUEST['search_terms']));
		?>
		</td>
		<td>&nbsp;</td>
		<td><?php echo $result->display_name; ?></td>
		<td>&nbsp;</td>
		<td><?php echo @date($wpClassified_settings['wpClassified_date_string'], $result->date); ?></td>
	</tr>
	<?php } ?>
	</table>
	</div>
	<?
	} 
	wpClassified_footer();
}


function wpClassified_ads_subject(){
	global $_GET, $_POST, $user_login, $userdata, $wpClassified_user_info, $fckhtml, $user_level, 
		$user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $wpClassified_wp_version_info, $quicktags;
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	get_currentuserinfo();

	$lists = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_lists
				 LEFT JOIN {$table_prefix}wpClassified_categories
				 ON {$table_prefix}wpClassified_categories.categories_id = {$table_prefix}wpClassified_lists.wpClassified_lists_id
				 WHERE {$table_prefix}wpClassified_lists.lists_id = '".((int)$_GET['lists_id'])."'", ARRAY_A);

	$displayform = true;

	if ($_POST['wpClassified_ads_subject']=='yes'){
		if ($wpClassified_settings['wpClassified_ads_must_register']=='y' && !wpClassified_is_loggedin()){
			die("You can't post without logging in.");
		} else {
			$makepost = true;

			if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !wpClassified_is_loggedin()){
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
					if ($imginfo[0]>(int)$wpClassified_settings["wpClassified_image_width"]  ||
						$imginfo[1]>(int)$wpClassified_settings["wpClassified_image_height"] || $imginfo[0] == 0){
						 echo "<h2>Invalid image size. Image must be ".(int)$wpClassified_settings["wpClassified_image_width"]."x".(int)$wpClassified_settings["wpClassified_image_height"]." pixels or less. Your image was: ".$imginfo[0]."x".$imginfo[1] . "</h2>";
						$makepost=false;	
					} else {
						$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
						$content = @fread($fp, $_FILES['image_file']['size']);
						@fclose($fp);
						$fp = fopen(ABSPATH."wp-content/plugins/wpClassified/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
						@fwrite($fp, $content);
						@fclose($fp);
						@chmod(dirname(__FILE__)."/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], 0777);
						$setImage = (int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'];
					}
				}
			}
			if ($makepost==true){
				$displayform = false;
				$isSpam = wpClassified_spam_filter(stripslashes($_POST['wpClassified_data']['author_name']), '', stripslashes($_POST['wpClassified_data']['subject']), stripslashes($_POST['wpClassified_data']['post']), $user_ID);

				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads_subjects
					(ads_subjects_list_id , date , author , author_name , author_ip , subject , ads , views , sticky , status, last_author, last_author_name, last_author_ip) VALUES
					('".($_GET['lists_id']*1)."', '".time()."' , '".$user_ID."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."' , '".getenv('REMOTE_ADDR')."' , '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['subject']))."' , 0, 0 , 'n' , '".(($isSpam)?"deleted":"open")."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', '".getenv('REMOTE_ADDR')."')");

				$tid = $wpdb->get_var("SELECT last_insert_id()");
				$wpdb->query("INSERT INTO {$table_prefix}wpClassified_ads
					(ads_ads_subjects_id, date, author, author_name, author_ip, status, subject, image_file, post) VALUES
					('".$tid."', '".time()."', '".$user_ID."', '".$wpdb->escape(stripslashes($_POST['wpClassified_data']['author_name']))."', 
					'".getenv('REMOTE_ADDR')."', 'active', 
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data']['subject']))."',
					'".$wpdb->escape(stripslashes($setImage))."',
					'".$wpdb->escape(stripslashes($_POST['wpClassified_data']['post']))."')");
				do_action('wpClassified_new_ads', $tid);
				$pid = $wpdb->get_var("select last_insert_id()");
				if (!$isSpam){
					wpClassified_update_user_post_count($user_ID);
					wpClassified_update_ads($_GET['lists_id']);
				}
				$_GET['ads_subjects_id'] = $tid;
				wpClassified_display_ads_subject();
			} else {
				$displayform = true;
			}
		}
	}

	if ($displayform==true){
		wpClassified_header();
		if ($wpClassified_settings['wpClassified_ads_must_register']=='y' && !wpClassified_is_loggedin()){
			?>
			<br><br><?php echo __("Sorry, you must be registered and logged in to post in these classifieds.");?><br><br>
			<a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo __("Register Here");?></a><br><br>- <?php echo __("OR");?> -<br><br>
			<a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("Login Here");?></a>
			<?php
		} else {
			echo $quicktags;
			?>
			<?php
			if ($msg){echo "<h3>".__($msg)."</h3>";}
			?>
			<table width=100% class="editform">
				<form method="post" id="cat_form_post" name="cat_form_post" enctype="multipart/form-data"
			onsubmit="this.sub.disabled=true;this.sub.value='Posting Ads...';" action="<?php echo wpClassified_create_link("postAdsForm", array("lists_id"=>$_GET["lists_id"], "name"=>$lists["name"]));?>">
				<input type="hidden" name="wpClassified_ads_subject" value="yes">
				<tr>
					<td align=right><?php echo __("Posting Name:");?> </td>
					<td><?php
						if (!wpClassified_is_loggedin()){
						?>
							<input type=text size=15 name="wpClassified_data[author_name]" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['author_name']));?>"><br>
							<span style="font-size: 10px;">(<?php echo __("You are not logged in and posting as a guest, click");?> <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("here");?></a> <?php echo __("to log in");?>.)</span>
						<?php
						} else {
							echo "<b>".$userdata->$userfield."</b>";
						}

						?></td>
				</tr>
				<tr>
					<td align=right><?php echo __("Subject:");?> </td>
					<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data']['subject']));?>"></td>
				</tr>
		<tr>
			<td align=right><?php echo __("Image File: ");?></td>
			<td><input type=file name="image_file"><br /><small><?php echo __("(maximum:" . (int)$wpClassified_settings["wpClassified_image_width"]."x".(int)$wpClassified_settings["wpClassified_image_height"] . " pixel ");?>)</small></td>
		</tr>
				<tr>
					<td valign=top align=right><?php echo __("Comment:");?> </td>
					<td><?php wpClassified_create_ads_input($_POST['wpClassified_data']['post']); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type=submit value="<?php echo __("Post Ads");?>" id="sub"></td>
				</tr>
				</form>
			</table>

			<?php

		}
		wpClassified_footer();
	}
}

function wpClassified_display_ads_subject(){
	global $_GET, $_POST, $user_login, $userdata, $wpClassified_user_info, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $wpClassified_wp_version_info, $quicktags;
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	if ($wpClassified_settings["wpClassified_view_must_register"]=="y" && !wpClassified_is_loggedin()){
		wpClassified_read_not_allowed();
		wpClassified_footer();
		return;
	}
	if (wpClassified_is_loggedin()){
		$readposts = $wpdb->get_col("SELECT read_ads_id FROM {$table_prefix}wpClassified_read_ads WHERE read_ads_ads_subjects_id = '".(int)$_GET['ads_subjects_id']."' && read_ads_user_id = '".(int)$wpClassified_user_info["ID"]."'");
	} else {
		$readposts = array();
	}

	wpClassified_update_ads_views($_GET['ads_subjects_id']);

		if (wpClassified_is_loggedin()){
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
	wpClassified_header();
?>

<table width=100% class="cat">
	<tr>
		<td width=100% align="right">
	<div style="text-align:right">
		<form action="<?php echo wpClassified_create_link("searchform", array());?>" method="post">
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
	if (count($posts)>$wpClassified_settings['wpClassified_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpClassified_settings['wpClassified_ads_per_page']; $i++){
			if ($i*$wpClassified_settings['wpClassified_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".wpClassified_create_link("ads_subject", array("name"=>($i+1), "lists_id"=>$_GET["lists_id"], "ads_subjects_id"=>$_GET['ads_subjects_id'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpClassified_settings['wpClassified_ads_per_page'])))." ";
			}
		}
	}

	if (count($posts)>$wpClassified_settings['wpClassified_ads_per_page']+$_GET['pstart']){
		$hm = $wpClassified_settings['wpClassified_ads_per_page']+$_GET['pstart'];
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
		if (wpClassified_is_admin() || wpClassified_is_mod() || 
			($post->author==$wpClassified_user_info["ID"] && wpClassified_is_loggedin())){
			$editlink = " ".wpClassified_create_link("editAds", array("name"=>"EDIT POST", "lists_id"=>$_GET["lists_id"], "name"=>$lists["name"], 'ads_subjects_id'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "ads_id"=>$post->ads_id))." ";
		} else {
			$editlink = "";
		}
		if (!@in_array($post->ads_id, $readposts) && wpClassified_is_loggedin()){
			$xbefred = "<font color=\"".$wpClassified_settings['wpClassified_unread_color']."\">";
			$xafred = "</font>";
			$setasread[] = "('".(int)$wpClassified_user_info["ID"]."', '".$_GET['ads_subjects_id']."', '".$post->ads_id."')";
		} else {
			$xbefred = "";
			$xafred = "";
		}
				
		if (file_exists(dirname(__FILE__)."/images/".$post->image_file) && $post->image_file!=""){
				$post->image_file = get_bloginfo('wpurl')."/wp-content/plugins/wpClassified/images/".$post->image_file;
				$heightwidth = "";
		}

		if (!file_exists(TEMPLATEPATH."/wpClassified_ads_template.php")){ 
			include(dirname(__FILE__)."/wpClassified_ads_template.php");
		} else {
			include(TEMPLATEPATH."/wpClassified_ads_template.php");
		}
		if ($i==0){
			echo stripslashes($wpClassified_settings['wpClassified_banner_code']);
		}
	}

	if (count($posts)>$wpClassified_settings['wpClassified_ads_per_page']){
		echo __("Pages: ");
		for ($i=0; $i<count($posts)/$wpClassified_settings['wpClassified_ads_per_page']; $i++){
			if ($i*$wpClassified_settings['wpClassified_ads_per_page']==$_GET['pstart']){
				echo " <b>".($i+1)."</b> ";
			} else {
				echo " ".wpClassified_create_link("ads_subject", array("name"=>($i+1), "lists_id"=>$_GET["lists_id"], 'ads_subjects_id'=>$_GET['ads_subjects_id'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpClassified_settings['wpClassified_ads_per_page'])))." ";
			}
		}
	}

	if (count($setasread)>0){
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_read_ads (read_ads_user_id, read_ads_ads_subjects_id, read_ads_id) VALUES ".@implode(", ", $setasread));
	}
	if ($wpClassified_settings['wpClassified_ads_must_register']!="y" || wpClassified_is_loggedin()){
?>
<?php
	}
	wpClassified_footer();
}

// edit post function
function wpClassified_edit_ads(){
	global $_GET, $_POST, $user_login, $wpClassified_user_info, $userdata, $user_level, $user_ID, $user_nicename, $user_email, $user_url, $user_pass_md5, $user_identity, $table_prefix, $wpdb, $quicktags;
	$wpClassified_settings = get_option('wpClassified_data');
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
		if ($wpClassified_user_info["ID"]!=$postinfo->author && !wpClassified_is_admin() && !wpClassified_is_mod()){
			wpClassified_permission_denied();
			return;
		} elseif (!wpClassified_is_loggedin()){
			wpClassified_permission_denied();
			return;
		}
	$displayform = true;
	if ($_POST['wpClassified_edit_ads']=='yes'){
		$makepost = true;
		if (str_replace(" ", "", $_POST['wpClassified_data']['author_name'])=='' && !wpClassified_is_loggedin()){
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
				if ($imginfo[0]>(int)$wpClassified_settings["wpClassified_image_width"]  ||
					$imginfo[1]>(int)$wpClassified_settings["wpClassified_image_height"] || $imginfo[0] == 0){
					 echo "<h2>Invalid image size. Image must be ".(int)$wpClassified_settings["wpClassified_image_width"]."x".(int)$wpClassified_settings["wpClassified_image_height"]." pixels or less. Your image was: ".$imginfo[0]."x".$imginfo[1] . "</h2>";
					$makepost=false;	
				} else {
					$fp = @fopen($_FILES['image_file']['tmp_name'], "r");
					$content = @fread($fp, $_FILES['image_file']['size']);
					@fclose($fp);
					$fp = fopen(ABSPATH."wp-content/plugins/wpClassified/images/".(int)$wpClassified_user_info["ID"]."-".$_FILES['image_file']['name'], "w");
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
		wpClassified_header();
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
		onsubmit="this.sub.disabled=true;this.sub.value='Saving Post...';" action="<?php echo wpClassified_create_link("editAdsform", array("lists_id"=>$lists["lists_id"], "name"=>$lists["name"], 'ads_subjects_id'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "ads_id"=>$_GET["ads_id"]));?>">
		<input type="hidden" name="wpClassified_edit_ads" value="yes">
		<tr><td align=right><?php echo __("Posting Name:");?> </td>
		<td><?php
		echo wpClassified_create_post_author($postinfo);
		?></td>
		</tr>
		<tr>
		<td align=right><?php echo __("Subject:");?> </td>
		<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->subject));?>"></td>
		</tr>
		<tr>
		<td align=right><?php echo __("Image File: ");?></td>
		<td><input type=file name="image_file" id="image_file"><br />(<small><?php echo __("(maximum" . (int)$wpClassified_settings["wpClassified_image_width"]."x".(int)$wpClassified_settings["wpClassified_image_height"]. " pixel ");?>)</small></td>
		</tr>
		<td valign=top align=right><?php echo __("Comment:");?> </td>
		<td><?php wpClassified_create_ads_input($postinfo->post);?></td>
		</tr><tr><td></td><td><input type=submit value="<?php echo __("Save Post");?>" id="sub"></td>
		</tr></form></table>
		<?php
		wpClassified_footer();
	}
}



function wpClassified_permission_denied(){
	echo __("Sorry, it seems that you do not have permission to perform the requested action.");
	return;

}


function wpClassified_create_ads_author($ad){
	global $wpClassified_wp_version_info;
	$wpClassified_settings = get_option('wpClassified_data');

	$userfield = wpClassified_get_field();

	$out = "";

	if ($ad->author==0){
		$out .= $ad->author_name;
	} else {
		$out .= $ad->$userfield;
	}

	return $out;

}


function wpClassified_create_post_author($post){
	global $wpClassified_wp_version_info;
	$wpClassified_settings = get_option('wpClassified_data');
	$userfield = wpClassified_get_field();
	$out = "";
	if ($post->author==0){
		$out .= $post->author_name." (guest)";
		if ($wpClassified_settings['wpClassified_unregistered_display_ip']=='y'){
			$out .= " - ".wpClassified_last_octet($post->author_ip);
		}
		$out .= "";
	} else {
		$out .= $post->$userfield;
	}
	return $out;
}

function wpClassified_update_user_post_count($id){
	global $table_prefix, $wpdb;
	if ($id*1==0)return;
	$test = $wpdb->get_var("SELECT user_info_user_ID FROM {$table_prefix}wpClassified_user_info WHERE user_info_user_ID = '".$id."'");
	if ($test>0){
		$wpdb->query("UPDATE {$table_prefix}wpClassified_user_info SET user_info_post_count = user_info_post_count+1 WHERE user_info_user_ID = '".$id."'");
	} else {
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_user_info (user_info_user_ID, user_info_post_count) values ('".$id."', '1')");
	}
}

function wpClassified_create_post_admin($post, $userdata){
	return "";
}


function wpClassified_read_not_allowed(){
	global $user_level;
	$wpClassified_settings = get_option('wpClassified_data');
	get_currentuserinfo();
	?>
	<h3><?php echo __("Read Access Denied");?></h3>
	<p>
	<?php
		echo __("These classifieds require you to be a registered user in order to view them.<br>If you are already registered you must log in before trying to view the classifieds.");
	?>
	</p>
	<?php
}


function wpClassified_write_not_allowed(){
	$wpClassified_settings = get_option('wpClassified_data');
	?>
	<h3><?php echo __("Posting Access Denied");?></h3>
	<p>
	<?php
		echo __("These classifieds require you to be a registered user in order to post.<br>If you are already registered you must log in before trying to post.");
	?>
	</p>
	<?php
}


function wpClassified_footer(){
$wpClassified_settings = get_option('wpClassified_data');
		if ($wpClassified_settings['wpClassified_show_credits']=='y'){
			echo "<p></p><p><hr><a href=\"http://www.forgani.com\" target=\"_blank\"> WP-Classified Wordpress plugins version 1.0</a><br>";
			echo "<small>Create by<a href=\"mailto:wp_classified@forgani.com\"> wp_classified@forgani.com</a></small></p>\n";
		}
	}


function wpClassified_commment_quote($post){
	$wpClassified_ads_text = $post->post;
	$wpClassified_ads_text = nl2br($wpClassified_ads_text);
	$wpClassified_ads_charset = get_option('blog_charset');
	$wpClassified_ads_text = addslashes(htmlspecialchars($wpClassified_ads_text, ENT_COMPAT, $wpClassified_ads_charset));		
	$wpClassified_ads_text = str_replace(chr(13), "", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace(chr(10), "", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&lt;br /&gt;", "\n", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&lt;", "<", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;lt;", "<", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&gt;", ">", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("&amp;", "&", $wpClassified_ads_text);
	if ($wpClassified_settings["wpClassified_ads_style"]=="plain"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
	if ($wpClassified_settings["wpClassified_ads_style"]=="bbcode"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
		if ($wpClassified_settings["wpClassified_ads_style"]=="html"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
		if ($wpClassified_settings["wpClassified_ads_style"]=="quicktags"){
		$wpClassified_ads_text = str_replace("<p>", "", $wpClassified_ads_text);
		$wpClassified_ads_text = str_replace("</p>", "\r", $wpClassified_ads_text);
	}
	$wpClassified_ads_text = trim($wpClassified_ads_text);
	$wpClassified_ads_text = preg_replace("/ *\n */", "\n", $wpClassified_ads_text);
	$wpClassified_ads_text = preg_replace("/\s{3,}/", "\n\n", $wpClassified_ads_text);
	$wpClassified_ads_text = str_replace("\n", "\\n", $wpClassified_ads_text);
	return $wpClassified_ads_text;
}