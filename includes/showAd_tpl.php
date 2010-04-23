<?php

/*
* showAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* display advertisement information
*/

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
			preg_replace(array('/\s/'), '', $post->image_file);
			if (!empty($post->image_file) ) {
				$array = preg_split('/###/', $post->image_file);
			}
			$file = $wpClassified->public_dir ."/". $array[0];
			if ( !empty($array[0]) && file_exists($file) ){
				include (dirname(__FILE__).'/js/viewer.js.php');
				echo "<div class=\"show_ad_img1\"><a href=\"" . $wpClassified->public_url ."/" . $array[0] . "\" rel=\"thumbnail\"><img src=\"". $wpClassified->public_url. "/" . $array[0] . "\" style=\"width: ". $wpcSettings["thumbnail_image_width"]."px;\"></a></div>";
			}
			?>

			<div class="show_ad_title">
			<?php echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a>
			</div>
			<div class="show_ad_info">
			Posted By<img src="<?php echo $wpClassified->plugin_url; ?>/images/user.gif"><?php echo wpcPostAuthor($post);?> on <img src="<?php echo $wpClassified->plugin_url; ?>/images/cal.gif"><?php echo @date($wpcSettings['date_format'], $post->date); echo " (User Ad:" .($post->user_info_post_count*1). ")" ?>
			</div>
			<?php if ($editlink){
				echo '<p class="smallTxt"><span class="edit">'.$editlink.'</span><span class="delete">'. $deletelink . '</span></p>';
				if ($wpcSettings['display_titles']=='y'){
					echo "<small id=\"wpClassified-usertitle\">&nbsp;&nbsp;".$post->user_info_title."</small>";
				}
			} ?>
		</div><!-- show_ad_header -->

		<?php

		$file = $wpClassified->public_dir ."/". $array[1];
		if ( !empty($array[1]) && file_exists($file) ){
			include (dirname(__FILE__).'/js/viewer.js.php');
			echo "<div class=\"show_ad_img12\"><a href=\"". $wpClassified->public_url . "/" . $array[1] . "\" rel=\"thumbnail\"><img src=\"". $wpClassified->public_url . "/" . $array[1] . "\" style=\"width:". $wpcSettings["thumbnail_image_width"] ."px;\"></a></div>"; //<br>" .$array[1] . "
		} 
		$file = $wpClassified->public_dir ."/". $array[2];
		if ( !empty($array[2]) && file_exists($file) ){
			include (dirname(__FILE__).'/js/viewer.js.php');
			echo "<div class=\"show_ad_img12\"><a href=\"". $wpClassified->public_url . "/" . $array[2] . "\" rel=\"thumbnail\"><img src=\"". $wpClassified->public_url . "/" . $array[2] . "\" style=\"width:". $wpcSettings["thumbnail_image_width"] ."px;\"></a></div>"; //<br>" .$array[2] . "
		} 
		?>

		<script language="javascript" type="text/javascript">
			function addtext_<?php echo $post->ads_id;?>() {
			<?php 
			?>
			var newtext_<?php echo $post->ads_id;?> = "<?php echo wpcPostAuthor($post);?> said:\n\"<?php echo wpcCommmentQuote($post);?>\"\n\n";
			document.ead_form["description"].value += newtext_<?php echo $post->ads_id;?>;

			<?php
			if ($wpcSettings["edit_style"]=="tinymce"){
				echo "tinyMCE.triggerSave(true, true);";
				echo "document.getElementById('description').value = newtext_".$post->ads_id.";";
				echo "tinyMCE.updateContent('description');";
			}
			?>
			}
		</script>

		<p class="justify"><?php echo wpcPostHtml($post);?></p>
		<?php
		list ($adExpire, $contactBy) = preg_split('/###/', $adsInfo['txt']);
		echo "<hr><div class=\"info\"><div class=\"left\">";
		if (isset($adsInfo['email']) && $contactBy==$lang['_YES_CONTACT']) {
			echo '<img src="' . $wpClassified->plugin_url. '/images/email.jpg" class="imgMiddle"><a href="mailto:' . $adsInfo['email'] . '">'.$lang['_REPLY'].'</a>&nbsp;&nbsp;&nbsp;';
		}
		if (isset($adsInfo['web'])) {
			echo "<a href=\"" . $adsInfo['web'] . "\" target=_blank><img src=\"" . $wpClassified->plugin_url. "/images/web.jpg\" class=\"imgMiddle\"></a>";
		}
		if (isset($adsInfo['phone'])) {
			echo '<img src="' . $wpClassified->plugin_url . '/images/phone.jpg" title="'.$adsInfo['phone'].'" class="imgMiddle">';
		}
		$pageinfo = $wpClassified->get_pageinfo();
		$rand = rand(1000, 50000);
		$fileUrl = $wpClassified->plugin_url . '/cache/'. $rand . '.html';
		wpcPrintAd($rand, $post->ads_id);
		$printAd .= '<a href="'. $fileUrl .'" target="_blank" onclick="return popp('. $fileUrl . ','. $wpcSettings['slug']. ');"><img src="' . $wpClassified->plugin_url . '/images/print.jpg" class="imgMiddle"></a></div>';
		echo $printAd;
		?>

		<?php
		echo "</div><div class=\"right\">";
		$sendAd = '<img src="' . $wpClassified->plugin_url . '/images/send.jpg" class="imgMiddle"><a href="'.get_bloginfo('wpurl').'/?page_id=' . $pageinfo["ID"].'&_action=sndad&aid=' . $post->ads_id.'">' . $lang['_SENDTOF'].'</a>'; 
		echo $sendAd . "</div>";
		?>
	</div><!-- show_ad -->
<?php
if ($wpcSettings['banner_code']) {
	echo "<div class=\"show_ad_banner\">" . stripslashes($wpcSettings['banner_code']) . "</div>";
}
echo '</div>';
wpcFooter();
?>