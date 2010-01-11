<?php

/*
* newAd_tpl template wordpress plugin
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.3.1-b
* 
*/

//global $lang, $quicktags, $wpClassified;
#$wpcSettings = get_option('wpClassified_data');
wpcHeader();
if ($wpcSettings['must_registered_user']=='y' && !$wpClassified->is_usr_loggedin()){
	?>
	<br><br><?php echo __("Sorry, you must be registered and logged in to post in these classifieds.");?><br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo __("Register Here");?></a><br><br>
		- <?php echo __("OR");?> -<br><br>
	<a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("Login Here");?></a>
	<?php
} else {
	echo $quicktags;
	if (isset($msg)) echo "<p class=\"error\">".$msg."</p>";
	?>
	<div class="wpc_container">
		<div class="editform">
		<form method="POST" enctype="multipart/form-data"  id="wpClassifiedForm" name="wpClassifiedForm" 
			action="<?php echo wpcPublicLink("paform", 	array("lid"=>$_GET['lid'], "name"=>$lists["name"]));?>">
		<h3>Details</h3>
		<input type="hidden" name="add_ad" value="yes">
			<table>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_AUTHOR']; ?></td>
				<td><?php 
				if (!$wpClassified->is_usr_loggedin()){?>
					<input type=text size=15 name="wpClassified_data[author_name]" value="<?php echo str_replace('"', "&quot;", stripslashes($_POST['wpClassified_data'][author_name]));?>"><br>
					(<?php echo $lang['_GUEST']; ?> <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo __("here");?></a> <?php echo __("to log in");?>.)
					<?php
				} else {
					echo "<b>".$userdata->$userfield."</b>";
					echo '<input type="hidden" name="wpClassified_data[author_name]" value="'.$userdata->$userfield.'">';
				} ?></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_EMAIL']; ?></td>
				<td><input type=text size=30 name="wpClassified_data[email]" onclick="checkEmail(this.form.wpClassified_data[email])" id="wpClassified_data_email" value="
				<?php 
				if (!isset($_POST['wpClassified_data']) || !isset($_POST['wpClassified_data'][email])) {
					$_POST['wpClassified_data']['email']='';
					$txt = '';
				} else {$txt = stripslashes($_POST['wpClassified_data'][email]);}
					echo str_replace('"', '&quot;', $txt); 
				?>"><span class="smallRed"><?php echo $lang['_REQUIRED']?></span></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_CONTACTBY']; ?></td>
				<td>
				<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_YES_CONTACT']; ?>" checked/>
				<?php echo $lang['_YES_CONTACT']; ?></option>
				<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_NO_CONTACT']; ?>" />
				<?php echo $lang['_NO_CONTACT']; ?></option></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_WEB']; ?></td>
				<td><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="
				<?php 
				if (!isset($_POST['wpClassified_data']) || !isset($_POST['wpClassified_data']['web'])) 
					$_POST['wpClassified_data']['web']='';
				$txt = stripslashes($_POST['wpClassified_data']['web']);
				echo str_replace('"', '&quot;', $txt); 
				?>"><span class="smallTxt"><?php echo $lang['_OPTIONAL'];?></span></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_TEL']; ?></td>
				<td><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="
				<?php 
				if (!isset($_POST['wpClassified_data']) || !isset($_POST['wpClassified_data']['phone'])) 
					$_POST['wpClassified_data']['phone']='';
				$txt = stripslashes($_POST['wpClassified_data']['phone']);
				echo str_replace('"', '&quot;', $txt); 
				?>"><span class="smallTxt"><?php echo $lang['_OPTIONAL']; ?>&nbsp; e.g. +98(231)12345</span></td>
			</tr>
			<tr><td></td><td><hr></td></tr>
			<tr>
				<!-- "the text input must contains only letters and numbers." -->
				<td class="wpc_label_right"><?php echo $lang['_TITLE']; ?></td>
				<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="
				<?php 
				if (!isset($_POST['wpClassified_data']) || !isset($_POST['wpClassified_data']['subject'])) 
					$_POST['wpClassified_data']['subject']='';
				$txt = stripslashes($_POST['wpClassified_data']['subject']);
				echo str_replace('"', '&quot;', $txt); 
				?>"><span class="smallRed"><?php echo $lang['_REQUIRED']?></span></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_PIC']; ?></td>
				<td><input type="file" name="image_file" size="20"><br /><span class="smallTxt">
				<?php echo $lang['_OPTIONAL'].$lang['_INVALIDMSG4']." (".(int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"] . " pixel )"; ?></span></td>
			</tr>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_DESC']; ?></td>
				<td>
				<?php
				if (!isset($_POST['wpClassified_data']['post'])) $_POST['wpClassified_data']['post']='';
				wpcAdInput($_POST['wpClassified_data']['post']); ?>
				</td>
			</tr>
			<?php 
			if ( $wpcSettings['ad_expiration'] && $wpcSettings['ad_expiration'] > 0 ) {
				echo '<tr><td class="wpc_label_right">'. $lang['_HOW_LONG'] .'</td>';
				echo '<td><input type="text" name="wpClassified_data[ad_expiration]" size="3" maxlength="3" value="'.(int)$wpcSettings["ad_expiration"].'"/><br>';
				echo '<span class="smallTxt">('.(int)$wpcSettings["ad_expiration"].$lang['_DAY'].')</span></td></tr>';
			}
			?>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_TERM']; ?></td>
				<td><input value="1" type="checkbox" name="wpClassified_data[term]" checked /></td>
			</tr>
			<?php
			if($wpcSettings['confirmation_code']=='y'){ 
				$oVisualCaptcha = new wpcCaptcha();
				$captcha = rand(1, 50) . ".png";
				$oVisualCaptcha->create(ABSPATH."wp-content/plugins/wp-classified/images/cpcc/" . $captcha);
			?>
			<tr>
				<td class="wpc_label_right"><?php echo $lang['_CONFIRM']; ?></td>
				<td><img src="<?php echo get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/cpcc/" .$captcha ?>" alt="ConfirmCode" align="middle"/><br>
				<input type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10">
			</tr>
			<?php
			} ?>

			<tr><td></td>
				<td><br><input type=submit value="<?php echo $lang['_SAVEAD'];?>" id="submit">&nbsp;&nbsp;<input type="reset" name="reset" value="<?php echo $lang['_CANCEL']; ?>" /></td>
			</tr>
			</table>
			</form>
		</div>
	</div>
	
	<?php
}
wpcFooter();

?>
