<?php

/*
* showAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* @version 1.2.1
* display advertisement information
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');

wpc_header();

if ($wpcSettings["view_must_register"]=="y" && !_is_usr_loggedin()){
	wpc_read_not_allowed();
	wpc_footer();
	return;
}
	
if (count($posts)>$wpcSettings['count_ads_per_page']){
	echo "Pages: ";
	for ($i=0; $i<count($posts)/$wpcSettings['count_ads_per_page']; $i++){
		if ($i*$wpcSettings['count_ads_per_page']==$_GET['pstart']){
			echo " <b>".($i+1)."</b> ";
		} else {
			echo " ".create_public_link("ads_subject", array("name"=>($i+1), "lid"=>$_GET['lid'], "asid"=>$_GET['asid'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
		}
	}
}

if (($i+1)==$hm){ 
	echo "<a name='$post->ads_id'></a><a name='lastpost'></a>"; 
} else {
	echo "<a name='$post->ads_id'></a>";
}
?>

<div class="wpc_container">
<div class="show_ad">
<div class="show_ad_header">
<?php
if ($post->image_file!=''){
	echo "<div class=\"show_ad_img1\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" .$post->image_file."\"></div>";
}
?>

<div class="show_ad_title">
<?php echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a>
</div>
<div class="show_ad_info">
Posted By<img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/user.gif"><?php echo get_post_author($post);?>on<img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/cal.gif"><?php echo @date($wpcSettings['date_format'], $post->date); echo " (User Ad:" .($post->user_info_post_count*1). ")" ?>
</div>
<?php if ($editlink){
	echo '<p class="smallTxt"><span class="edit">'.$editlink.'</span><span class="delete">'. $deletelink . '</span></p>';
	if ($wpcSettings['wpClassified_display_titles']=='y'){
		echo "<small id=\"wpClassified-usertitle\">&nbsp;&nbsp;".$post->user_info_title."</small>";
	}
} ?>
</div><!-- header -->

<script language="javascript" type="text/javascript">
	function addtext_<?php echo $post->ads_id;?>() {
	<?php 
	?>
	var newtext_<?php echo $post->ads_id;?> = "<?php echo get_post_author($post);?> said:\n\"<?php echo wpClassified_commment_quote($post);?>\"\n\n";
	document.ead_form["wpClassified_data[post]"].value += newtext_<?php echo $post->ads_id;?>;

	<?php
	if ($wpcSettings["wpc_edit_style"]=="tinymce"){
		echo "tinyMCE.triggerSave(true, true);";
		echo "document.getElementById('wpClassified_data[post]').value = newtext_".$post->ads_id.";";
		echo "tinyMCE.updateContent('wpClassified_data[post]');";
	}
	?>
	}
</script>
<div class="show_ad_body"><?php echo create_post_html($post);?></div>
<?php 
list ($adExpire, $contactBy) = split('###', $adsInfo[txt]);
echo "<div class=\"show_ad_left\">";
if ($adsInfo[email] && $contactBy==$lang['_YES_CONTACT']) {
	echo '<a href="mailto:' . $adsInfo[email] . '"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/email.jpg" class="imgMiddle">'.$lang['_REPLY'].'</a>&nbsp;&nbsp;&nbsp;';
}
if ($adsInfo[web]) {
	echo "<a href=\"" . $adsInfo[web] . "\" target=_blank><img src=\"" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/images/topic/web.jpg\" class=\"imgMiddle\"></a>";
}
if ($adsInfo[phone]) {
	echo '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/phone.jpg" title="'.$adsInfo[phone].'" class="imgMiddle">';
}
$pageinfo = get_wpClassified_pageinfo();
$printAd = '<a href="'.get_bloginfo('wpurl').'/?page_id='.$pageinfo["ID"].'&_action=prtad&aid='.$post->ads_id.'"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/print.jpg" class="imgMiddle"></a>'; 
echo $printAd;
?>
</div>
<div class="show_ad_right">
<?php
$sendAd = '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/send.jpg" class="imgMiddle"><a href="'.get_bloginfo('wpurl').'/?page_id=' . $pageinfo["ID"].'&_action=sndad&aid=' . $post->ads_id.'">' . $lang['_SENDTOF'].'</a>'; 
echo $sendAd . "</div>";
//if ($i==0){

if (count($posts)>$wpcSettings['count_ads_per_page']){
	echo "Pages: ";
	for ($i=0; $i<count($posts)/$wpcSettings['count_ads_per_page']; $i++){
		if ($i*$wpcSettings['count_ads_per_page']==$_GET['pstart']){
			echo " <b>".($i+1)."</b> ";
		} else {
			echo " ".create_public_link("ads_subject", array("name"=>($i+1), "lid"=>$_GET['lid'], 'asid'=>$_GET['asid'], "subject"=>$adsInfo->subject, "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
		}
	}
}
?>
</div><!-- show -->
<?php
if ($wpcSettings['banner_code']) {
	echo "<div class=\"show_ad_banner\">" . stripslashes($wpcSettings['banner_code']) . "</div>";
}
echo '</div>';
wpc_footer();
?>

