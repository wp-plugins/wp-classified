<?php

/*
* editAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.3.1-a
* fixed by Jes Saxe MAJ 2011
* 
*/

wpcHeader();

if ($msg){echo "<p class=\"error\">".$msg."</p>";}
	echo $quicktags;
?>

<div class="wpc_container">
	<div class="editform">
		<h3><?php echo $adsInfo["subject"]; ?></h3>
		<form method="post" id="wpClassifiedForm" name="wpClassifiedForm" enctype="multipart/form-data"
		onsubmit="this.sub.disabled=true;this.sub.value='Saving Post...';" action="<?php echo wpcPublicLink("eaform", array("lid"=>$lists["lists_id"], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "aid"=>$_GET['aid']));?>">
		<input type="hidden" name="edit_ad" value="yes">
		<table>
		<tr><td class="wpc_label_right"><?php echo $lang['_AUTHOR']; ?></td>
			<td class="wpc_label_left"><?php echo wpcPostAuthor($postinfo); ?>
			<input type="hidden" name="wpClassified_data[author_name]" value="<?php echo wpcPostAuthor($postinfo); ?>">
		</td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_EMAIL']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[email]" id="wpClassified_data_email" value="<?php echo $postinfo->email; ?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td></tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_CONTACTBY']; ?></td>
			<td class="wpc_label_left">
			<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_YES_CONTACT']; ?>" 
			<?php if ($contactBy==$lang['_YES_CONTACT']) { echo " checked"; } ?>/><?php echo $lang['_YES_CONTACT']; ?></option>
			<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_NO_CONTACT']; ?>" 
			<?php if ($contactBy==$lang['_NO_CONTACT']) { echo " checked"; } ?>/><?php echo $lang['_NO_CONTACT']; ?></option></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_WEB']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="<?php echo $postinfo->web; ?>"><span class ="smallTxt"><?php echo $lang['_OPTIONAL']; ?></span></td>
		</tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_TEL']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="<?php echo $postinfo->phone; ?>"><span class ="smallTxt"><?php echo $lang['_OPTIONAL']; ?><br />e.g.+98(231)12345</span></td>
		</tr>
		<tr><td></td><td><hr></td></tr>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_TITLE']; ?></td>
			<td class="wpc_label_left"><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo $postinfo->subject; ?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td>
		</tr>
		<tr><td colspan=2><p><?php echo $lang['_PIC']; ?></p></td></tr>
		<tr><td colspan=2>
			<table width=90%>
				<tr>
					<?php
					preg_replace(array('/\s/'), '', $postinfo->image_file);
					if (!empty($postinfo->image_file) ) {
						$array = preg_split('/###/', $postinfo->image_file);
					}
					foreach($array as $f) {
						include (dirname(__FILE__).'/js/viewer.js.php');
						echo '<td align="center">';
						echo "<a href=\"". $wpClassified->public_url."/" . $f . "\" rel=\"thumbnail\"><img src=\"". $wpClassified->public_url."/" . $f . "\" style=\"width: 120px; height: 100px\"></a><br />";
						//Image Upload 
						echo $f;
						echo "</td>";
					}
					?>
				</tr>
			</table>
		</td></tr>
		<tr><td colspan=2 align="center">
			<?php echo wpcPublicLink("mi", array("name"=> $lang['_ADDMODDIMAGE'], "aid"=>$postinfo->ads_id)); ?>
		</td></tr>
      <?php wpcAdInput($postinfo->post);?>
		<?php 
		if ( isset($wpcSettings['ad_expiration']) && $wpcSettings['ad_expiration'] > 0 ) {
			echo '<tr><td class="wpc_label_right">'.$lang['_HOW_LONG']. '</td>';
			echo '<td class="wpc_label_left"><input type="text" name="wpClassified_data[ad_expiration]" size="3" maxlength="3" value="';
			if ($adExpire) {
				echo $adExpire;
			} else {
				echo (int)$wpcSettings['ad_expiration'];
			} 
			echo '"/><br><span class ="smallTxt">('.(int)$wpcSettings['ad_expiration'].$lang['_DAY'].')</span></td></tr>';
		}
		if($wpcSettings['confirmation_code']=='y') { 
			$oVisualCaptcha = new wpcCaptcha();
			$captcha = rand(1, 50) . ".png";
			$oVisualCaptcha->create( $wpClassified->cache_dir ."/" . $captcha);
		?>
		<tr>
			<td class="wpc_label_right"><?php echo $lang['_CONFIRM']; ?></td>
			<td  class="wpc_label_left"><img src="<?php echo $wpClassified->cache_url. "/" .$captcha ?>" alt="ConfirmCode" align="middle"/><br>
			<input type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10"></td>
		</tr>
		<?php
		} ?>
		<tr>
			<td></td><td class="wpc_label_left"><br><input type="submit" name="submit" value="<?php echo $lang['_SAVEAD']; ?>" id="submit">&nbsp;&nbsp;<input type="reset"  id="reset" name="reset" value="<?php echo $lang['_CANCEL']; ?>" /></td>
		</tr>
		
		</table>
		</form>
	</div>
</div>
<?php
wpcFooter();
?>
