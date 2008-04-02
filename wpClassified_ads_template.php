<?php

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
		echo "<div style='float: left; padding: 3px;'><img src=\"".$post->image_file."\"".$heightwidth."></div>";
	}
?>
<div>
<strong><? echo "<a href='".$_SERVER['SCRIPT_URI']."#$post->ads_id'>";?><?php echo str_replace("<", "&lt;", $post->subject);?></a></strong> <small><?php echo __("Posted By:");?> <strong><?php echo wpClassified_create_post_author($post);?></strong> on <?php echo @date($wpClassified_settings['wpClassified_date_string'], $post->date);?></small>

	<?php
	if ($post->author>0){
		echo "<br /><small>".__("Posts: ").($post->user_info_post_count*1)."</small><small>".$editlink."</small>";
		if ($wpClassified_settings['wpClassified_display_titles']=='y'){
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
		var newtext_<?php echo $post->ads_id;?> = "<?php echo wpClassified_create_post_author($post);?> said:\n\"<?php echo wpClassified_commment_quote($post);?>\"\n\n";
		document.xd_form_post["wpClassified_data[post]"].value += newtext_<?php echo $post->ads_id;?>;

	<?php
	if ($wpClassified_settings["wpClassified_ads_style"]=="tinymce"){
		echo "tinyMCE.triggerSave(true, true);";
		echo "document.getElementById('wpClassified_data[post]').value = newtext_".$post->ads_id.";";
		echo "tinyMCE.updateContent('wpClassified_data[post]');";
	}
	?>
	<?php
	if ($wpClassified_settings["wpClassified_ads_style"]=="fckeditor"){
		echo "var oEditor = FCKeditorAPI.GetInstance('wpClassified_data[post]');";
		echo "oEditor.SetHTML(newtext_".$post->ads_id.");";
	}
	?>
	}
</script>
<div class="wpClassified_ads_content<?php echo $iter++%2;?>">
<?php echo wpClassified_create_post_html($post);?>
</div>
</div>