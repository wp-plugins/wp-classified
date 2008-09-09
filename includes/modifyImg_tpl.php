<?php

global $lang, $quicktags;
$wpcSettings = get_option('wpClassified_data');
wpc_header();
echo "<div class=\"wpc_container\">";
if ($wpcSettings['must_registered_user']=='y' && !_is_usr_loggedin()){
	?>
	<br><br><?php echo __("Sorry, you must be registered and logged in to post in these classifieds.");?><br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo __("Register Here");?></a><br><br>- <?php echo __("OR");?> -<br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("Login Here");?></a>
	<?php
} else {			
	echo $quicktags;
	if ($msg){
		echo "<p class=\"error\">".$msg."</p>";
	}
	$displayform = true;
	$array = split('###', $post->image_file);
	$curcount = count ($array);
	?>
	
	<div class="editform">
	<h3>Add Images</h3>
	<form method="post" id="addImg" name="addImg" enctype="multipart/form-data" action="<?php echo create_public_link("miform", array("aid"=>$post->ads_id));?>">
	<table><tr>
	<td class="wpc_label_right">Image: </td>
	<td>
	<?php if ($curcount <> 3) { ?>
	<input type="hidden" name="add_img" value="yes">
	<input name="addImage" type="file">&nbsp;<input type=submit value="<?php echo $lang['_SUBMIT']; ?>" id="submit">
	<br /><span class="smallTxt"><?php echo "(maximum " . (int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. " pixel" ;?>)<br>
	<?php
	}
	?>
	You have placed <?php echo $curcount; ?> of 3 images</span></td>
	</tr>
	</table>
	</form>
	<br>
	<h3>Delete Images</h3>
	<table><tr>
<?php
foreach($array as $f) {
?>
<td align="center">
	<!-- Image Upload -->
	<img valign=absmiddle src="<?php echo get_bloginfo('wpurl') ?>/wp-content/plugins/wp-classified/images/<?php echo $f; ?>" class="imgMiddle"  width="120" height="100"><br>
	<?php echo create_public_link("di",array("aid"=>$post->ads_id, "name"=> $lang['_DELETE'], "file"=> $f ));
	echo "&nbsp;(" . $f . ")"; ?>
	</td>
<?php
}
?>
</tr>
	
	</table>
	<p><hr><b><?php echo $lang['_BACK']; ?> to <?php echo create_public_link("ads_subject", array("name"=>$post->subject, "lid"=>$_GET['lid'], "asid"=>$post->ads_subjects_id)); ?></b></p>
	</div>
	</div>
<?php
}
wpc_footer();
?>
