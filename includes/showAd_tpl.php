<?php

/*
* showAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
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
$array = split('###', $post->image_file);
$img = $array[0];
if ($img !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img1\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" style=\"width: 120px; height: 100px\"></a></div>";
}
?>

<div class="show_ad_title">
<?php echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a>
</div>
<div class="show_ad_info">
Posted By<img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/user.gif"><?php echo get_post_author($post);?> on <img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/cal.gif"><?php echo @date($wpcSettings['date_format'], $post->date); echo " (User Ad:" .($post->user_info_post_count*1). ")" ?>
</div>
<?php if ($editlink){
	echo '<p class="smallTxt"><span class="edit">'.$editlink.'</span><span class="delete">'. $deletelink . '</span></p>';
	if ($wpcSettings['wpClassified_display_titles']=='y'){
		echo "<small id=\"wpClassified-usertitle\">&nbsp;&nbsp;".$post->user_info_title."</small>";
	}
} ?>
</div><!-- header -->

<?php
if ($array[1] !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img12\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[1] . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[1] . "\" style=\"width: 120px; height: 100px\"></a></div>"; //<br>" .$array[1] . "
} 
if ($array[2] !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img12\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[2] . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[2] . "\" style=\"width: 120px; height: 100px\"></a></div>"; //<br>" .$array[2] . "
} 
?>

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


<p class="justify"><?php echo create_post_html($post);?></p>

<?php

list ($adExpire, $contactBy) = split('###', $adsInfo[txt]);

echo "<hr><div class=\"info\"><div class=\"left\">";
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
echo "</div><div class=\"right\">";
$sendAd = '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/send.jpg" class="imgMiddle"><a href="'.get_bloginfo('wpurl').'/?page_id=' . $pageinfo["ID"].'&_action=sndad&aid=' . $post->ads_id.'">' . $lang['_SENDTOF'].'</a>'; 
echo $sendAd . "</div>";
echo "</div>";




?>
</div><!-- show -->
<?php
if ($wpcSettings['banner_code']) {
	echo "<div class=\"show_ad_banner\">" . stripslashes($wpcSettings['banner_code']) . "</div>";
}
echo '</div>';
wpc_footer();
?>

