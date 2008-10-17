<?php

/*
* sendAd_tpl template wordpress plugin
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* 
*/

global $lang;
$wpcSettings = get_option('wpClassified_data');
wpc_header();

$yourname = get_post_author($post);
if ($sendMsg){echo "<p class=\"error\">".$sendMsg."</p>";}
?>
<div class="wpc_container">
<div class="editform">
<?php echo $lang['_FRIENDSEND']; ?>(<?php echo $aid;?>) <b>'<?php echo $post->subject;?>'</b><?php echo $lang['_TOAFRIEND']; ?>
<form method="post" enctype="multipart/form-data" id="sndad" name="sndad" action="<?php echo $link_snd;?>">
<table>
<tr><td class="wpc_label_right"><?php echo $lang['_YOURNAME']; ?></td><td><input size=35 type="text" name="wpClassified_data[yourname]" value="<?php echo $yourname; ?>"/></td></tr>
<tr><td class="wpc_label_right"><?php echo $lang['_YOUREMAIL']; ?></td><td><input size=35 type="text" name="wpClassified_data[mailfrom]" value="<?php echo $post->email;?>" /></td></tr>
<tr><td></td><td><hr></td></tr>
<tr><td class="wpc_label_right"><?php echo $lang['_FRIENDNAME']; ?></td><td><input size=35 type="text" name="wpClassified_data[fname]" /></td></tr>
<tr><td class="wpc_label_right"><?php echo $lang['_FRIENDMAIL']; ?></td><td><input size=35 type="text" name="wpClassified_data[mailto]" /></td></tr>
<?php
if($wpcSettings['confirmation_code']=='y'){ 
  $aFonts = array(ABSPATH."wp-content/plugins/wp-classified/fonts/arial.ttf");
  $oVisualCaptcha = new _captcha($aFonts);
  $captcha = rand(1, 50) . ".png";
  $oVisualCaptcha->create(ABSPATH."wp-content/plugins/wp-classified/images/cpcc/" . $captcha);
?>
<tr><td></td><td><img src="<?php echo get_bloginfo('wpurl'). "/wp-content/plugins/wp-classified/images/cpcc/" .$captcha ?>" alt="ConfirmCode" align="middle"/></td></Tr>
<tr><td class="wpc_label_right"><?php echo $lang['_CONFIRM']; ?></td><td><input size=10 type="text" name="wpClassified_data[confirmCode]" id="wpClassified_data_confirmCode" size="10"></tr>
<?php
} ?>
<input type="hidden" name="wpClassified_send_ad" value="yes">
<tr><td></td><td><input type=submit value="<?php echo $lang['_SENDEMAIL']; ?>"></td></tr>
</form></table>
</div>
</div>
<?php
wpc_footer();

?>
