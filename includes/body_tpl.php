<?php
$wpcSettings = get_option('wpClassified_data');
if (($i+1)==$hm){ 
	echo "<a name='$post->ads_id'></a><a name='lastpost'></a>"; 
} else {
	echo "<a name='$post->ads_id'></a>";
}
?>

<div class="wpClassified_ads_container">
<div class="wpClassified_ads_header">
<?php
	if ($post->image_file!=''){
		echo "<div style='float: left; padding: 3px;'><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" .$post->image_file."\"></div>";
	}
?>
<div>
<strong><? echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a></strong> <small><?php echo __("Posted By:");?> <strong><?php echo get_post_author($post);?></strong> on <?php echo @date($wpcSettings['date_format'], $post->date);?></small>
	<?php
	if ($post->author>0){
		echo "<br /><small>".__("User Ad: ").($post->user_info_post_count*1)."</small><small>".$editlink."&lt;Edit&gt;</small>";
		if ($wpcSettings['wpClassified_display_titles']=='y'){
			echo "<br /><small id=\"wpClassified-usertitle\">&nbsp;&nbsp;".$post->user_info_title."</small>";
		}
	} else {
		echo "<br /><small>".$editlink."</small>";
	}
	?>
</div>
</div>

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
<div class="wpClassified_ads_body">
<?php echo create_post_html($post);?>
</div>
<div class="wpClassified_ads_footer">
<?php 
if ($adsInfo[email]) {
	echo '<a href="mailto:' . $adsInfo[email] . '"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/email.jpg"></a>';
}
if ($adsInfo[web]) {
	echo '<a href="' . $adsInfo[web] . '" target=_blank><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/web.jpg"></a>';
}
if ($adsInfo[phone]) {
	echo '<img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-classified/images/phone.jpg" title="'.$adsInfo[phone].'">';
}
?>
</div>
</div>
