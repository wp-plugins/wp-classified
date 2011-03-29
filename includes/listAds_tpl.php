<?php

/*
* listAds_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* list all ads already exist under a defined category
*/

global $wpClassified;
//$wpcSettings = get_option('wpClassified_data');


wpcHeader();


?>

<div class="wpc_container">
  <?php
  if ($msg!='') echo "<p class=\"message\">" . $msg . "</p>";

  if ($numAds>$wpcSettings['count_ads_per_page']){
    echo "<div align=\"left;\">";
    echo "Pages: ";
    for ($i=0; $i<$numAds/$wpcSettings['count_ads_per_page']; $i++){
        if ($i*$wpcSettings['count_ads_per_page']==$start){
          echo " <b>".($i+1)."</b> ";
        } else {
          echo " ".wpcPublicLink("classified", array("name"=>($i+1), "lid"=>$lists["lists_id"], "name"=>$lists["name"], "start"=>($i*$wpcSettings['count_ads_per_page'])))." ";
        }
    }
    echo "</div>";
  }
  ?>
  <div class="list_ads">
    <div class="list_ads_top">
      <?php 
      $addtopicImg = '<img src="' . $wpClassified->plugin_url . '/images/addtopic.jpg">';
  	  echo $addtopicImg;
	  echo "<span sytle=\"font-size:13px\">".wpcPublicLink("pa", array("name"=>"Post New Ad", "lid"=>$_GET['lid'], "name"=>$lang['_ADDANNONCE'])) ."</span>";
      ?>
    </div><!--list_ads_top-->
    
    <table class="main" width="100%">
      <tr>
        <th scope="col" width="120"><?php echo $lang['_VIEWS']?></th>
        <th style="text-align: left;" scope="col"><?php echo $lang['_SUBJECT'];?></th>
        <th style="text-align: right;" scope="col"><?php echo $lang['_POSTON'];?></th>
      </tr>
    </table>
    <table class="main" width="100%">
      <?php
      for ($x=0; $x<count($ads); $x++){
        $ad = $ads[$x];
        $pstart = 0;
        $pstart = $ad->ads-($ad->ads%$wpcSettings["count_ads_per_page"]);
        ?>
        <tr class="odd"><td width="120" valign="top">
        <?php
        $rec = $wpdb->get_row("SELECT * FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = $ad->ads_subjects_id");
        $array = preg_split('/\#\#\#/', $rec->image_file);
        $img = $array[0];
        if ($img !='') {
            include (dirname(__FILE__).'/js/viewer.js.php');
            echo "<div class=\"show_ad_img1\"><a href=\"" . $wpClassified->public_url ."/" . $img . "\" rel=\"thumbnail\"><img src=\"". $wpClassified->public_url. "/" . $array[0] . "\" style=\"width:". $wpcSettings["thumbnail_image_width"]."px; height:". $wpcSettings["thumbnail_image_width"]."px;\"></a></div>";
        } else { echo "<img src=\"". $wpClassified->plugin_url . "/images/nophoto.gif\">";}
        ?>
        </td><td valign="top">
          <?php
          echo wpcPublicLink("ads_subject", array("name"=>$ad->subject, "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id));
          ?>
          <br />
          <?php 
          echo $lang['_FROM'];
          echo wpcAdAuthor($ad);
          ?>
        </td>
        <td nowrap="nowrap" align="right" valign="top">
        <?php
        if (!@in_array($ad->ads_subjects_id, $read) && $wpClassified->is_usr_loggedin()){
            $rour = "<img border=0 src=\"". $wpClassified->plugin_url . "/images/unread.gif\" class=\"imgMiddle\"> ";
        } else {$rour = "";} // fix me
        if ($ad->sticky=='y'){
            $sticky = "<img border=0 src=\"". $wpClassified->plugin_url . "/images/sticky.gif\" alt=\"".__("Sticky")."\"> ";
        }
        ?>
        <?php echo @date($wpcSettings['date_format'], $ad->date);?><br />
        <?php echo $rour; ?>&nbsp;<?php echo $sticky; ?>&nbsp;(<?php echo $lang['_VIEWS']?>: <?php echo $ad->views;?>)
        </td></tr>
        <?php
      }
      ?>
    </table>
  </div>
</div>
<?php
wpcFooter();
?>
