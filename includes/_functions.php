<?php

/*
* _function.php
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : forgani.com
* Licence Type   : GPL
* @version 1.3
*/

if (!$_SESSION) session_start();

function wpc_header(){
	global $_GET, $_POST, $table_prefix, $wpdb, $lang;
	$wpcSettings = get_option('wpClassified_data');
	if ($wpcSettings['count_ads_per_page'] < 1) { 
		$wpcSettings['count_ads_per_page'] = 10;
	}
	echo '<div class="wpchead">';
	if ($wpcSettings['classified_top_image']!=''){
		$img=preg_replace('/\s+/','',$wpcSettings['classified_top_image']);
		echo '<div class="wpcheadimg"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-classified/images/topic/' .$img. '"></div>';
	}
	if ($wpcSettings['description']!=''){
		echo '<div class="wpcheaddesc">'.$wpcSettings['description'] . "&nbsp;</div>";
	}
	if ($lnks==""){$lnks = get_wpc_header_link();}
	echo '<p class="wpcheadlnk">' . $lnks. '</p>';
?>
	<div class="wpcsearch">
		<form action="<?php echo create_public_link("searchform", array());?>" method="post">
		<input type="text" name="search_terms" VALUE="">
		<input type="submit" value="<?php echo $lang['_SEARCH']; ?>">
		</form>
	</div>
	</div><!--wpchead-->
<?php
	if ($wpcSettings['googleID'] && $wpcSettings['googleID'] != 'no') {
		$gAd = get_GADlink();
		echo '<div class="wpc_googleAd">' . $gAd . '</div>';
	}

	$today = time();
	$sql = "SELECT ads_subjects_id, txt, date FROM {$table_prefix}wpClassified_ads_subjects";
	$rmRecords = $wpdb->get_results($sql);
	foreach ($rmRecords as $rmRecord) { 
		list ($adExpire, $contactBy) = split('###', $rmRecord->txt);
		if (!$adExpire) { $adExpire=$wpcSettings['ad_expiration']; };
		if (!$adExpire || $adExpire < 1 ) {
			$adExpire=365;
		}
		$second = $adExpire*24*60*60; // second
		$l = $today-$second;
		if ($rmRecord->date < $l) {
			$asid = $rmRecord->ads_subjects_id;
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id =" . $asid);
			$wpdb->query("DELETE FROM {$table_prefix}wpClassified_ads_subjects WHERE ads_subjects_id = ". $asid);
		}
	}	
}


// function to show the Main page
function wpc_index(){
	global $_GET, $user_ID, $wpc_user_info, $table_prefix, $wpdb;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();

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
	
	if (!file_exists(ABSPATH . INC . "/main_tpl.php")){ 
		include(dirname(__FILE__)."/main_tpl.php");
	} else {
		include(ABSPATH . INC . "/main_tpl.php");
	}
	
}


// function to list all ads already exist under a defined category
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

	if (!file_exists(ABSPATH . INC . "/listAds_tpl.php")){ 
		include(dirname(__FILE__)."/listAds_tpl.php");
	} else {
		include(ABSPATH . INC . "/listAds_tpl.php");
	}
}

function wpc_read_not_allowed(){
	global $user_level;
	$wpcSettings = get_option('wpClassified_data');
	get_currentuserinfo();

	$tpl->assign('user_level', "<!--".($user_level)."-->");
	$tpl->assign('access_denied', "Read Access Denied");
	$tpl->assign('access_denied_reason', "These classifieds require you to be a registered user in order to view them. If you are already registered you must log in before trying to view the classifieds.");
	$tpl->display('permission_denied.tpl');
}

function wpc_footer(){
	global $wpClassified_version;
	$wpcSettings = get_option('wpClassified_data');
	$wpcSettings['credit_line'] = 'wpClassified plugins (Version '.$wpClassified_version.') powered by <a href=\"http://www.forgani.com\" target=\"_blank\"> M. Forgani</a>';
	
	echo "<div class=\"wpc_footer\">";
	if($wpcSettings['rss_feed']=='y'){
		$rssurl= _rss_url();
		$out = '<a class="rssIcon" href="'.$rssurl.'"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/wp-classified/images/topic/rss.png" /> ' . $wpcSettings['wpClassified_slug'] . ' RSS&nbsp;</a>';
		echo $out;
	}

	if ($wpcSettings['show_credits']=='y'){
		echo "<div class=\"smallTxt\">&nbsp;&nbsp;" .stripslashes($wpcSettings['credit_line']) . "</div>";
	}

	echo "</div>";
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

	if ($wpc_user_info["ID"]!=$postinfo->author &&	
		!_is_usr_admin() && !_is_usr_mod() &&
		(!_is_usr_loggedin() && getenv('REMOTE_ADDR')!=$postinfo->author_ip)){
		wpClassified_permission_denied();
		return;
	}
	list ($adExpire, $contactBy) = split('###',  $adsInfo["txt"]);
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
		if($wpcSettings['confirmation_code']=='y'){ 
			if (! _captcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   				$msg = $lang['_INVALIDCONFIRM'];
				$addPost = false;
  			}
		}
		if (str_replace(" ", "", $_POST['wpClassified_data'][post])==''){
			$msg = $lang['_INVALIDCOMMENT'];
			$addPost = false;
		}

		if ($_POST['wpClassified_data'][count_ads_max] > $wpcSettings['count_ads_max_limit']){
			$msg = "Classified Text must be less than or equal to ". $wpcSettings['count_ads_max_limit'] . " characters in length";
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
				web='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][web]))."',
				phone='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][phone]))."',
				txt='".$wpdb->escape(stripslashes($_POST['wpClassified_data'][adExpire])).'
				###'.$_POST['wpClassified_data'][contactBy]."'
				WHERE ads_subjects_id='".(int)$_GET['asid']."' ";

				$wpdb->query($sql);
				get_wpc_list($lang['_UPDATE']);
		} else {
			$displayform = true;
		}
	} 
	if ($displayform==true){
		$postinfos = $wpdb->get_results("SELECT * FROM {$table_prefix}wpClassified_ads
			 LEFT JOIN {$table_prefix}users ON 
			{$table_prefix}users.ID = {$table_prefix}wpClassified_ads.author
			 LEFT JOIN {$table_prefix}wpClassified_ads_subjects ON 
			ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id
			 WHERE ads_id = '".(int)$_GET['aid']."'");
		$postinfo = $postinfos[0];

		if (!file_exists(ABSPATH . INC . "/editAd_tpl.php")){ 
			include(dirname(__FILE__)."/editAd_tpl.php");
		} else {
			include(ABSPATH . INC . "/editAd_tpl.php");
		}
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

		if (!eregi("^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}$", $_POST['wpClassified_data'][mailto])){
			$sendMsg = $lang['_INVALIDEMAIL2'];
			$sendAd = false;
		}
		if($wpcSettings['confirmation_code']=='y'){ 
			if (! _captcha::Validate($_POST['wpClassified_data'][confirmCode])) {
   				$sendMsg = $lang['_INVALIDCONFIRM'];
				$sendAd = false;
  			}
		}
		if ($sendAd == true) {
			$displayform = false;

		$message = "Dear " . $_POST['wpClassified_data'][fname]. "<br>";
		$message .= "your friend " . $yourname . " sent you information about " . $subject . "<br><br>";
		$message .= $lang['_ADDETAIL']. "<BR>" . $msg . "<BR><BR>";
		$message .= $lang['_FRIENDBTN1'];
		$message .= get_bloginfo('wpurl')."/?page_id=".$pageinfo["ID"]."&_action=va&asid=".$post->ads_subjects_id."&pstart=".((int)$vars["start"]) . "<BR><BR><BR>";
		$message .= $yourname . $lang['_FRIENDBTN2'];
		
  			$txt = html2text($message); 
			$from = "From: ". $yourname . "<" .$mailfrom. ">";
			//$from .= "Content-Type: text/html";
			$sub = "your friend " . $yourname . " sent you information";

			if (mail($mailto, $sub, $txt, $from ."\n")) {
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
		if (!file_exists(ABSPATH . INC . "/sendAd_tpl.php")){ 
			include(dirname(__FILE__)."/sendAd_tpl.php");
		} else {
			include(ABSPATH . INC . "/sendAd_tpl.php");
		}
	}	
}



// function to display advertisement information
function _display_ad(){
	global $_GET, $wpc_user_info, $table_prefix, $wpdb;
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();
	
	
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
			(!_is_usr_loggedin() && getenv('REMOTE_ADDR')==$post->author_ip) ||
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

		if (!file_exists(ABSPATH . INC . "/showAd_tpl.php")){ 
			include(dirname(__FILE__)."/showAd_tpl.php");
		} else {
			include(ABSPATH . INC . "/showAd_tpl.php");
		}
	}

	if (count($setasread)>0){
		$wpdb->query("INSERT INTO {$table_prefix}wpClassified_read_ads (read_ads_user_id, read_ads_ads_subjects_id, read_ads_id) VALUES ".@implode(", ", $setasread));
	}
	//if ($wpcSettings['must_registered_user']!="y" || _is_usr_loggedin()){	}
}


function display_search($term){
	global $_GET, $_POST, $table_prefix, $wpdb, $lang;
	get_currentuserinfo();
	$wpcSettings = get_option('wpClassified_data');
	$userfield = get_wpc_user_field();

	#
	# fixed 07-Apr-2008
	#
	$sql = "SELECT {$table_prefix}wpClassified_lists.lists_id,{$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.subject, {$table_prefix}wpClassified_ads.post,{$table_prefix}wpClassified_ads_subjects.ads_subjects_id, {$table_prefix}users.display_name, {$table_prefix}wpClassified_ads.date, {$table_prefix}wpClassified_ads.ads_id, {$table_prefix}wpClassified_ads.ads_ads_subjects_id FROM {$table_prefix}wpClassified_lists, {$table_prefix}wpClassified_ads_subjects, {$table_prefix}wpClassified_ads,{$table_prefix}users WHERE {$table_prefix}wpClassified_lists.lists_id = {$table_prefix}wpClassified_ads_subjects.ads_subjects_list_id AND {$table_prefix}wpClassified_ads_subjects.ads_subjects_id = {$table_prefix}wpClassified_ads.ads_ads_subjects_id  AND {$table_prefix}users.id = {$table_prefix}wpClassified_ads.author AND ({$table_prefix}wpClassified_ads_subjects.subject like '%".$wpdb->escape($term)."%' OR ${table_prefix}wpClassified_ads.post like '%".$wpdb->escape($term)."%') ORDER BY {$table_prefix}wpClassified_lists.name, {$table_prefix}wpClassified_ads.date DESC";

	$results = $wpdb->get_results($sql);

	if (!file_exists(ABSPATH . INC . "/searchRes_tpl.php")){ 
		include(dirname(__FILE__)."/searchRes_tpl.php");
	} else {
		include(ABSPATH . INC . "/searchRes_tpl.php");
	}
}


function get_GADlink() {
	$wpcSettings = get_option('wpClassified_data');
	$wpcSettings = get_option('wpClassified_data');
	$rand = rand(0,100);
	$key_code = ($rand <= $wpcSettings['share']) ? 'pub-2844370112691023' : $wpcSettings['googleID'];
	if ( $wpcSettings['GADproduct']=='link' )	{
		$format = $wpcSettings[GADLformat] . '_0ads_al'; // _0ads_al_s  5 Ads Per Unit
		list($width,$height,$null) = split('[x]',$wpcSettings[GADLformat]);
	} else {
		$format = $wpcSettings[GADformat] . '_as';
		list($width,$height,$null) = split('[x]',$wpcSettings[GADformat]);
	}

	$code = "\n" . '<script type="text/javascript"><!--' . "\n";
	$code.= 'google_ad_client="' . $key_code . '"; ' . "\n";
	$code.= 'google_ad_width="' . $width . '"; ' . "\n";
	$code.= 'google_ad_height="' . $height . '"; ' . "\n";
	$code.= 'google_ad_format="' . $format . '"; ' . "\n";
	if($settings['alternate_url']!=''){ 
		$code.= 'google_alternate_ad_url="' . $settings['alternate_url'] . '"; ' . "\n";
	} else {
		if($settings['alternate_color']!='') { 
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
	$code.= 'google_color_border="' . $wpcSettings[GADcolor_border] . '"' . ";\n";
	$code.= 'google_color_bg="' . $wpcSettings[GADcolor_bg] . '"' . ";\n";
	$code.= 'google_color_link="' . $wpcSettings[GADcolor_link] . '"' . ";\n";
	$code.= 'google_color_text="' . $wpcSettings[GADcolor_text] . '"' . ";\n";
	$code.= 'google_color_url="' . $wpcSettings[GADcolor_url] . '"' . ";\n";
	$code.= '//--></script>' . "\n";
	$code.= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>' . "\n";
	return $code;
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
