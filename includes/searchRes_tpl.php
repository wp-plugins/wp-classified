<?php

/*
* searchRes_tpl template
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* 
*/

//global $lang, $wpClassified;
//$wpcSettings = get_option('wpClassified_data');
wpcHeader();

?>
<div class="wpc_container">
  <div class="list_ads">
  <?php
  $wpcSettings = get_option('wpClassified_data');
  if ($wpcSettings['view_must_register']=="y" && !$wpClassified->is_usr_loggedin()){
    wpc_read_not_allowed();
    echo "</div></div>";
    wpc_footer();
    return;
  }

  if(! $results) {
    echo "<h3>No posts matched your search terms.</h3><br />";
    echo '<input type="button" value="' .$lang['_BACK']. '" onClick="history.back();">';
    echo '</div>';
  } else {
    ?>
      <table class="main" width="100%">
      <tr class="col">
        <th width="120" class="col"><?php echo $lang['_PIC']?></th>
        <th style="text-align: left;" class="col"><?php echo $lang['_SUBJECT'];?></th>
        <th style="text-align: right;" class="col"><?php echo $lang['_POSTON'];?></th>
      </tr>
      </table>
    </div>
    <table class="main" width="100%">
      <tr><th colspan="3" class="col"><hr /></th></tr>
      <?php
      $post_pstart=='0';
      foreach($results as $ad) {
        $re_find = '/RE: /';
        $re_strip = '';
        $new_subject_name = preg_replace($re_find, $re_strip, $ad->subject);
        $pstart = $wpdb->get_row("SELECT count(*) as count FROM {$table_prefix}wpClassified_ads WHERE ads_ads_subjects_id = '".$ad->ads_subjects_id."' AND ads_id < '".$ad->ads_id."'", ARRAY_A);
        $post_pstart = ($pstart['count'])/$wpcSettings['count_ads_per_page'];
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
        </td>
        <td valign="top" style="text-align: left;"><BR />
          <?php
          echo wpcPublicLink("ads_subject", array("name"=>$ad->subject, "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id));
          ?>
          <br />
          <?php 
          echo '<small>' . $lang['_FROM'] . ' ' . wpcAdAuthor($ad) . '</small>';
          ?>
          <P>
          <?php 
          $txt = wpcCommmentQuote($rec);
          $string = substr($txt, 0, 120); 
          $t = wordwrap($string, 55, "\n", true);
          $t = getTheHtml($t);
          $l= wpcPublicLink("ads_subject", array("name"=>  $lang['_READ_MORE'], "lid"=>$_GET['lid'], "asid"=>$ad->ads_subjects_id));
          $t .= '... ' . '<span class="readmore">' . $l . '</span>';
          echo $t; 
          ?>
        </td>
        <td nowrap="nowrap" align="right" valign="top" width="120">
        <?php
        $sticky = '';
        if (!@in_array($ad->ads_subjects_id, $read) && $wpClassified->is_usr_loggedin()){
           $rour = "<img border=0 src=\"". $wpClassified->plugin_url . "/images/unread.gif\" class=\"imgMiddle\"> ";
        } else {$rour = "";} // fix me
        if ($ad->sticky=='y'){
           $sticky = "<img border=0 src=\"". $wpClassified->plugin_url . "/images/sticky.gif\" alt=\"".__("Sticky")."\"> ";
        }
        ?>
        <?php echo @date($wpcSettings['date_format'], $ad->date);?><br />
        <?php echo $rour; ?>&nbsp;<?php echo $sticky; ?>&nbsp;<small>(<?php echo $lang['_VIEWS']?>: <?php echo $ad->views;?>)</small>
        </td></tr>
        <tr><th colspan="3" class="col"><hr /></th></tr>
        <?php
      }
      ?>
    </table>
  </div>
  <?php
  }
  ?>
</div>
<?php

wpcFooter();

?>