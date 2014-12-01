<?php

/*
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.0-c
*/

wpcHeader();
$curcount = 0;
echo "<div class=\"wpc_container\">";
if ($wpcSettings['must_registered_user']=='y' && !$wpClassified->is_usr_loggedin()){
  ?>
  <br><br><?php echo $lang['_MUST_LOGIN'];?><br><br>
  <a href="<?php echo get_bloginfo('wpurl');?>/wp-register.php"><?php echo $lang['_MAY_REGISTER'];?></a><br><br>- <?php echo $lang['_OR'];?> -<br><br>
  <a href="<?php echo get_bloginfo('wpurl');?>/wp-login.php"><?php echo $lang['_LOGIN'];?></a>
  <?php
} else {
  echo $quicktags;
  if (isset($msg)){
    echo "<p class=\"error\">".$msg."</p>";
  }
  $displayform = true;
  preg_replace(array('/\s/'), '', $post->image_file);
  if (!empty($post->image_file) ) {
    $array = preg_split('/###/', $post->image_file);
    $curcount = count ($array);
  }
  
  ?>
  
  <div class="editform">
  <h3><?php echo $lang['_ADDIMAGE'];?></h3>
  <form method="post" id="addImg" name="addImg" enctype="multipart/form-data" action="<?php echo wpcPublicLink("miform", array("aid"=>$post->ads_id));?>">
    <table>
      <tr>
        <td class="wpc_label_right"><?php echo $lang['_PIC'];?></td>
        <td>
        <?php 
        if ($curcount <> $wpcSettings['number_of_image']) { ?>
          <input type="hidden" name="add_img" value="yes">
          <input name="addImage" type="file"><input type=submit value="<?php echo $lang['_SUBMIT']; ?>" id="wpc_submit">
          <p><span class="smallTxt"><?php echo "(maximum " . (int)$wpcSettings["image_width"]."x".(int)$wpcSettings["image_height"]. " pixel" ;?>)</p>
        <?php
        }
        if (!isset($curcount)) $curcount = 0;
        ?>
        You have placed <?php echo $curcount; ?> of <?php echo $wpcSettings['number_of_image']; ?> images</span></td>
      </tr>
    </table>
  </form>
  <br>
  <h3><?php echo $lang['_DELIMAGE'];?></h3>
  <table>
    <tr>
      <?php
      if (isset($curcount) and $curcount > 0)
        foreach($array as $f) {
        ?>
        <td align="center">
          <!-- Image Upload -->
          <img valign=absmiddle src="<?php echo $wpClassified->public_url; ?>/<?php echo $f; ?>" class="imgMiddle"  width="120" height="100"><br>
          <?php echo wpcPublicLink("di", array("aid"=>$post->ads_id, "name"=>$lang['_DELETE'], "file"=>$f ));
          echo "&nbsp;(" . $f . ")"; ?>
          </td>
        <?php
        }
      ?>
    </tr>
  </table>
  <p><hr>
  <b><?php echo $lang['_BACK']; ?> to <?php echo wpcPublicLink("ads_subject", array("name"=>$post->subject, "asid"=>$post->ads_ads_subjects_id)); ?></b></p>
  <p>&nbsp;</p>
  </div>
  </div>
<?php
}
wpcFooter();
?>
