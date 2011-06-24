<?php

/*
* newAd_tpl template wordpress plugin
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.3.1-b
* fixed by Jes Saxe MAJ 2011
* 
*/

wpcHeader();
?>
<div class="wpc_container">
<?php

if ($wpcSettings['must_registered_user']=='y' && !$wpClassified->is_usr_loggedin()){
	?>
	<div class="list_ads_top">
	<br><br><?php echo $lang['_MUST_LOGIN'];?><br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo $lang['_MAY_REGISTER'];?></a><br><br>
	- <?php echo $lang['_OR'];?> -<br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo $lang['_LOGIN']?></a>
	</div>
	<?php
} else {
	echo $quicktags;
	if (isset($msg)) echo "<p class=\"error\">".$msg."</p>";
	?>
	<div class="editform">
	  <form method="POST" enctype="multipart/form-data"  id="wpClassifiedForm" name="wpClassifiedForm" action="<?php echo wpcPublicLink("paform", 	array("lid"=>$_GET['lid'], "name"=>$lists["name"]));?>">
	  <h3><?php echo $lang['_DETAILS'];?></h3>
	  <input type="hidden" name="add_ad" value="yes">
		<table>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_AUTHOR']; ?></td>
			<td class="wpc_label_left"><?php 
			if (!$wpClassified->is_usr_loggedin()){?>
				<input type=text size=15 name="wpClassified_data[author_name]" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data'][author_name]));?>"><br>
				<span class="smallTxt">(<?php echo $lang['_GUEST']; ?> <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo $lang['_HERE'];?></a> <?php echo $lang['_TO_LOGIN'];?>.)</span>
				<?php
			} else {
				echo "<b>".$userdata->$userfield."</b>";
				echo '<input type="hidden" name="wpClassified_data[author_name]" value="'.$userdata->$userfield.'">';
			} ?></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_EMAIL']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[email]" onclick="checkEmail(this.form.wpClassified_data[email])" id="wpClassified_data_email" value="<?php echo $email; ?>"><span class="smallRed"><?php echo $lang['_REQUIRED']?></span></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_CONTACTBY']; ?></td>
			<td class="wpc_label_left">
			<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_YES_CONTACT']; ?>" checked/>
			<?php echo $lang['_YES_CONTACT']; ?></option>
			<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_NO_CONTACT']; ?>" />
			<?php echo $lang['_NO_CONTACT']; ?></option></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_WEB']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="<?php echo $web; ?>"><span class="smallTxt"><?php echo $lang['_OPTIONAL'];?></span></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_TEL']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="<?php echo $phone; ?>"><span class="smallTxt"><?php echo $lang['_OPTIONAL']; ?>&nbsp;<?php echo $lang[_PHONENO_EX];?></span></td>
		</tr>
		<tr><td></td><td><hr></td></tr>
		<tr>
		<!-- "the text input must contains only letters and numbers." -->
			<td class="wpc_label_right"><?php echo $lang['_TITLE']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo $subject; ?>"><span class="smallRed"><?php echo $lang['_REQUIRED']?></span></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_PIC']; ?></td>
			<td class="wpc_label_left"><input type="file" name="image_file" size="20"><br /><span class="smallTxt">
			<?php echo $lang['_OPTIONAL'].$lang['_INVALIDMSG4']." (".(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"] . " pixel )"; ?></span></td>
		</tr>
      <?php
		if (!isset($_POST['description'])) $_POST['description']='';
      wpcAdInput($_POST['description']); 
		if ( $wpcSettings['ad_expiration'] && $wpcSettings['ad_expiration'] > 0 ) {
			echo '<tr><td class="wpc_label_right">'. $lang['_HOW_LONG'] .'</td>';
			echo '<td class="wpc_label_left"><input type="text" name="wpClassified_data[ad_expiration]" size="3" maxlength="3" value="'.(int)$wpcSettings["ad_expiration"].'"/><br>';
			echo '<span class="smallTxt">('.(int)$wpcSettings["ad_expiration"].$lang['_DAY'].')</span></td></tr>';
		}
		?>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_TERM']; ?></td>
			<td class="wpc_label_left"><input value="1" type="checkbox" name="wpClassified_data[term]" checked /></td>
		</tr>
		<?php
		if($wpcSettings['confirmation_code']=='y'){ 
			$oVisualCaptcha = new wpcCaptcha();
			$captcha = rand(1, 50) . ".png";
			$oVisualCaptcha->create( $wpClassified->cache_dir ."/" . $captcha);
		?>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_CONFIRM']; ?></td>
			<td class="wpc_label_left"><img src="<?php echo $wpClassified->cache_url . "/" .$captcha ?>" alt="ConfirmCode" align="middle"/><br>
			<input type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10">
		</tr>
		<?php
		} ?>
			<tr><td></td>
			<td class="wpc_label_left"><br><input type=submit value="<?php echo $lang['_SAVEAD'];?>" id="submit">&nbsp;&nbsp;<input type="reset" name="reset"  id="reset" value="<?php echo $lang['_CANCEL']; ?>" /></td>
		</tr>
		</table>
		</form>
	</div>
</div>
	
<?php
}
wpcFooter();

?>
