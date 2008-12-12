<?php

/*
* editAd_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* 
*/

global $lang, $quicktags;
$wpcSettings = get_option('wpClassified_data');

wpc_header();


if ($msg){echo "<p class=\"error\">".$msg."</p>";}
	echo $quicktags;
?>


<div class="wpc_container">
<div class="editform">
<h3><?php echo $adsInfo["subject"]; ?></h3>

<table>
<form method="post" id="ead_form" name="ead_form" enctype="multipart/form-data"
onsubmit="this.sub.disabled=true;this.sub.value='Saving Post...';" action="<?php echo create_public_link("eaform", array("lid"=>$lists["lists_id"], "name"=>$lists["name"], 'asid'=>$adsInfo['ads_subjects_id'], "name"=>$adsInfo["subject"], "aid"=>$_GET['aid']));?>">
<input type="hidden" name="wpClassified_edit_ad" value="yes">
<tr><td class="wpc_label_right"><?php echo $lang['_AUTHOR']; ?></td>
<td><?php echo get_post_author($postinfo); ?>
<input type="hidden" name="wpClassified_data[author_name]" value="<?php echo get_post_author($postinfo); ?>">
</td>
</tr>
<tr>
<td class="wpc_label_right"><?php echo $lang['_EMAIL']; ?></td>
<td><input type=text size=30 name="wpClassified_data[email]" id="wpClassified_data_email" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->email));?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td></tr>

<tr>
<td class="wpc_label_right"><?php echo $lang['_CONTACTBY']; ?></td>
<td>
<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_YES_CONTACT']; ?>" 
<?php if ($contactBy==$lang['_YES_CONTACT']) { echo " checked"; } ?>/><?php echo $lang['_YES_CONTACT']; ?></option>
<input type="radio" name="wpClassified_data[contactBy]" value="<?php echo $lang['_NO_CONTACT']; ?>" 
<?php if ($contactBy==$lang['_NO_CONTACT']) { echo " checked"; } ?>/><?php echo $lang['_NO_CONTACT']; ?></option>
</td></tr>

<tr>
<td class="wpc_label_right"><?php echo $lang['_WEB']; ?></td>
<td><input type=text size=30 name="wpClassified_data[web]" id="wpClassified_data_web" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->web));?>"><span class ="smallTxt"><?php echo $lang['_OPTIONAL']; ?></span></td></tr>

<tr>
<td class="wpc_label_right"><?php echo $lang['_TEL']; ?></td>
<td><input type=text size=30 name="wpClassified_data[phone]" id="wpClassified_data_phone" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->phone));?>"><span class ="smallTxt"><?php echo $lang['_OPTIONAL']; ?>&nbsp;e.g. +98(231)12345</span></td></tr>
<tr><td></td><td><hr></td></tr>
<tr>
<td class="wpc_label_right"><?php echo $lang['_TITLE']; ?></td>
<td><input type=text size=30 name="wpClassified_data[subject]" id="wpClassified_data_subject" value="<?php echo str_replace('"', "&quot;", stripslashes($postinfo->subject));?>"><span class="smallRed"><?php echo $lang['_REQUIRED'] ?></span></td></tr>
<tr><td colspan=2><p>Images:</p></td></tr>
<tr><td colspan=2>
<table width=90%><tr>
<?php

$array = split('###', $postinfo->image_file);
foreach($array as $f) {
	include (dirname(__FILE__).'/js/viewer.js.php');
	echo '<td align="center">';
	echo "<a href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $f . "\" rel=\"thumbnail\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $f . "\" style=\"width: 120px; height: 100px\"></a>";
?>
	<!-- Image Upload -->
	<br><?php echo $f; ?>
	</td>
<?php
}
?>
</tr></table>
<tr><td colspan=2 align="center">
<?php echo create_public_link("mi", array("name"=>"Add/Modify Image", "aid"=>$postinfo->ads_id)); ?>
</td></tr>

<tr><td colspan=2><?php echo $lang['_DESC']; ?><br>
<div style="text-align: center;"><?php create_ads_input($postinfo->post);?></div>
</td></tr>

<tr>
<td class="wpc_label_right"><?php echo $lang['_HOW_LONG']; ?></td>
<td><input type="text" name="wpClassified_data[adExpire]" size="3" maxlength="3" value="<?php if ($adExpire) {echo $adExpire;} else {echo (int)$wpcSettings["ad_expiration"];} ?>"/><br><span class ="smallTxt">default(<?php echo (int)$wpcSettings["ad_expiration"].$lang['_DAY']; ?>)</span></td>
</tr>
<?php
if($wpcSettings['confirmation_code']=='y') { 
  $aFonts = array(ABSPATH."wp-content/plugins/wp-classified/fonts/arial.ttf");
  $oVisualCaptcha = new _captcha($aFonts);
  $captcha = rand(1, 50) . ".png";
  $oVisualCaptcha->create(ABSPATH."wp-content/plugins/wp-classified/images/cpcc/" . $captcha);
?>
<tr>
<td class="wpc_label_right"><?php echo $lang['_CONFIRM']; ?></td>
<td><img src="<?php echo get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/cpcc/" .$captcha ?>" alt="ConfirmCode" align="middle"/><br>
<input type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10"></td>
</tr>
<?php
} ?>
<tr><td></td><td><br><input type=submit value="<?php echo $lang['_SAVEAD']; ?>" id="submit">&nbsp;&nbsp;<input type="reset" name="reset" value="Reset" /></td></tr>
</form></table>
</div>
</div>
<?php
wpc_footer();
?>
