<?php

/*
* showAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* display advertisement information
*/

//global $lang, $wpClassified;
//$wpcSettings = get_option('wpClassified_data');
wpcHeader();

if ($wpcSettings["view_must_register"]=="y" && !$wpClassified->is_usr_loggedin()){
	wpcReadAllowed();
	wpcFooter();
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
$array = preg_split('/\#\#\#/', $post->image_file);
$img = $array[0];
if ($img !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img1\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $img . "\" style=\"width: ". $wpcSettings["thumbnail_image_width"]."px;\"></a></div>";
}
?>

<div class="show_ad_title">
<?php echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a>
</div>
<div class="show_ad_info">
Posted By<img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/user.gif"><?php echo wpcPostAuthor($post);?> on <img src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/images/topic/cal.gif"><?php echo @date($wpcSettings['date_format'], $post->date); echo " (User Ad:" .($post->user_info_post_count*1). ")" ?>
</div>
<?php if ($editlink){
	echo '<p class="smallTxt"><span class="edit">'.$editlink.'</span><span class="delete">'. $deletelink . '</span></p>';
	if ($wpcSettings['display_titles']=='y'){
		echo "<small id=\"wpClassified-usertitle\">&nbsp;&nbsp;".$post->user_info_title."</small>";
	}
} ?>
</div><!-- header -->

<?php
if (isset($array[1]) && $array[1] !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img12\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[1] . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[1] . "\" style=\"width:". $wpcSettings["thumbnail_image_width"] ."px;\"></a></div>"; //<br>" .$array[1] . "
} 
if (isset($array[2]) && $array[2] !=''){
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo "<div class=\"show_ad_img12\"><a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[2] . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $array[2] . "\" style=\"width:". $wpcSettings["thumbnail_image_width"] ."px;\"></a></div>"; //<br>" .$array[2] . "
} 
?>

<script language="javascript" type="text/javascript">
	function addtext_<?php echo $post->ads_id;?>() {
	<?php 
	?>
	var newtext_<?php echo $post->ads_id;?> = "<?php echo wpcPostAuthor($post);?> said:\n\"<?php echo wpcCommmentQuote($post);?>\"\n\n";
	document.ead_form["wpClassified_data[post]"].value += newtext_<?php echo $post->ads_id;?>;

	<?php
	if ($wpcSettings["edit_style"]=="tinymce"){
		echo "tinyMCE.triggerSave(true, true);";
		echo "document.getElementById('wpClassified_data[post]').value = newtext_".$post->ads_id.";";
		echo "tinyMCE.updateContent('wpClassified_data[post]');";
	}
	?>
	}
</script>


<p class="justify"><?php echo wpcPostHtml($post);?></p>

<?php

list ($adExpire, $contactBy) = preg_split('/###/', $adsInfo['txt']);

echo "<hr><div class=\"info\"><div class=\"left\">";
if (isset($adsInfo['email']) && $contactBy==$lang['_YES_CONTACT']) {
	echo '<a href="mailto:' . $adsInfo['email'] . '"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/email.jpg" class="imgMiddle">'.$lang['_REPLY'].'</a>&nbsp;&nbsp;&nbsp;';
}
if (isset($adsInfo['web'])) {
	echo "<a href=\"" . $adsInfo['web'] . "\" target=_blank><img src=\"" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-classified/images/topic/web.jpg\" class=\"imgMiddle\"></a>";
}
if (isset($adsInfo['phone'])) {
	echo '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/phone.jpg" title="'.$adsInfo['phone'].'" class="imgMiddle">';
}
$pageinfo = $wpClassified->get_pageinfo();
$printAd = '<a href="'.get_bloginfo('wpurl').'/?page_id='.$pageinfo["ID"].'&_action=prtad&aid='.$post->ads_id.'"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/topic/print.jpg" class="imgMiddle"></a>'; 
echo $printAd;

?>

<script language=”JavaScript”>
var thePopup = window.open('<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/print.php', 'myPopupFile','width = 250, height = 250');
</script>

<form>
<input type="button" value="Print the Popup" onClick="thePopup.print()">
</form>



<?php

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
wpcFooter();
?>

