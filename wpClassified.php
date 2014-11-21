<?php

/*
Plugin Name: wpClassified
Plugin URI: http://forgani.com/index.php/tools/wpclassified-plugins/
Description: This plugin allows you to add a simple information & advertising blackboard or classified page in to your wordpress blog.
Author:Mohammad Forgani
Version: 1.4.3
Requires at least:3.1.x
Author URI: http://www.forgani.com



Jan 30/04/2011
- added the facebook link button


Oct 05/10/2012
- bugfix: update the search function


Nov 18/11/2014
- fixed some security issues 

*/


//error_reporting(E_ALL);
//ini_set('display_errors', '1');

require_once(dirname(__FILE__).'/settings.php');

define('WPC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/wp-classified');
define('WPC_PLUGIN_URL', plugins_url('wp-classified'));

add_action('plugins_loaded', create_function('$a', 'global $wpClassified; $wpClassified = new WP_Classified();'));


class WP_Classified {
  // Sets the version number.
  var $version = "1.4.2-c";
  var $menu_name = 'wpClassified';
  var $plugin_name = 'wp-classified';
  var $plugin_home_url;
  var $plugin_dir;
  var $plugin_url;
  var $public_dir;
  var $public_url;
  var $country;
  var $cache_dir;
  var $cache_url;
  
  function WP_Classified() {
    // initialize all the variables
    $this->plugin_home_url = 'http://www.forgani.com/wp-classified';
    $this->plugin_dir = WP_CONTENT_DIR.'/plugins/'.$this->plugin_name;
    $this->plugin_url = get_option("siteurl").'/wp-content/plugins/'.$this->plugin_name;
    //$this->plugin_dir = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
    //$this->plugin_url = get_option("siteurl").'/wp-content/plugins/'.plugin_basename(dirname(__FILE__));
    $this->public_dir = WP_CONTENT_DIR.'/public/wp-classified/';
    $this->public_url = get_option("siteurl").'/wp-content/public/wp-classified';
    $this->cache_dir = $this->plugin_dir . '/cache/';
    $this->cache_url = get_option("siteurl").'/wp-content/plugins/wp-classified/cache/';

    add_action('init', array(&$this, 'widget_init'));
    add_action('admin_menu', array(&$this, 'add_admin_pages'));
    add_action('wp_head', array(&$this, 'add_head'));
    add_action('admin_head', array(&$this, 'add_admin_head'));
    //add_action('template_redirect', 'rss_feed');
    add_filter("the_content", array(&$this,"page_handle_content"));
    add_filter("the_title", array(&$this,"page_handle_title"));
    add_filter("wp_list_pages", array(&$this,"page_handle_titlechange"));
    add_filter("single_post_title", array(&$this,"page_handle_pagetitle"));
    //add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
    // todo
  }
  
  function add_admin_pages(){
    global $wpcAdminPages;
    // TODO Welcome
    add_menu_page($this->menu_name , $this->menu_name ,'administrator', __FILE__, array(&$this, 'welcome'), $this->plugin_url . '/images/wpc.gif');
    add_submenu_page(__FILE__, 'Settings', 'Settings', 'administrator', 'wpcSettings', array(&$this, 'wpcSettings'));
    for ($i=0; $i<count($wpcAdminPages); $i++){
      $link = $wpcAdminPages[$i];
      add_submenu_page(__FILE__, $link['name'], $link['name'], 'administrator', $link['arg'], $link['prg']);
    }
    //add_management_page($this->menu_name, $this->menu_name, 'administrator', $this->plugin_name, 'wpclassified_admin_page'); 
  }
   
  function welcome(){
    print wpcAdminMenu();
    ?>
    <div style="float:left;width:70%;">
    <div class="wrap">
    <h2>Welcome to Wordpress Classifieds</h2>
      <p>You are using version <?php echo $this->version; ?></p>
    <p>This plugin allows you to add a <b>simple classified page</b> into your wordpress blog.</p>

    <p>Thank you for using Wordpress Classifieds Plugin.<br />
      The plugin has been create and successfully tested on Wordpress version 3.1 with default and unchanged Permalink structure. It may work with earlier versions too I have not tested.<br />
    <p>Demo link: <a href="http://www.forgani.com/classified/" target=_blank>www.forgani.com/classified/</a></p>
    <?php
  }


  // wpClassified settings 
  function wpcSettings(){
    global $_GET, $_POST, $PHP_SELF, $wpdb, $table_prefix, $lang;
    print wpcAdminMenu();
      $error = false;
      $t = "<BR /><BR /><fieldset><legend style='font-weight: bold; color: #900;'>Directory Checker</legend>";
      $t .= "<p><b>Directory permissions problem. <br />Please check the write permission for the directories:</b></p><ul>";
    $cache_dir = $this->plugin_dir . '/cache/';
    if( ! is_writable( $cache_dir ) || ! is_readable( $cache_dir ) ) {
      $t .= "<li><code><font color='#900'>".$cache_dir."</code></font></li>\n";
         $error = true;
    }
    $public_dir = $this->public_dir;
    if( ! is_writable( $public_dir ) || ! is_readable( $public_dir ) ) {
      $t .= "<li><code><font color='#900'>".$public_dir."</font></code><p>You also have to create an a public directory under wp-content (writable by the webserver.)</p><code>wp-content/public/wp-classified/</code><li>" ;
      
         $error = true;
    }
      if ($error) { $t .= "</ul></fieldset>"; echo $t;}
    $this->showCategoryImg();
    $page = $this->get_pageinfo();
    if ( empty($page['post_title']) ) {
      $dt = date("Y-m-d");
      $sql = "INSERT INTO {$table_prefix}posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type) VALUES ('1', '$dt', '$dt', '[[WP_CLASSIFIED]]', '[[WP_CLASSIFIED]]',  '[[WP_CLASSIFIED]]', 'publish', 'closed', 'closed', '', 'wpcareers', '', '', '$dt', '$dt', '[[WP_CLASSIFIED]]', '0', '', '0', 'page')";
      $wpdb->query($sql);
    }
    switch ($_GET['adm_action']){
      case "saveSettings":
        foreach ($_POST["wpClassified_data"] as $k=>$v){
          $_POST["wpClassified_data"][$k] = stripslashes($v);
        }
        $null_values = array('must_registered_user', 'display_titles', 'filter_posts', 'view_must_register', 'approve');
        foreach ( $null_values as $v) {
          if (!isset($_POST['wpClassified_data'][$v])) $_POST['wpClassified_data'][$v]='n';
        }
        
        $_POST['wpClassified_data']['userfield'] =  $this->get_user_field();
        $_POST['wpClassified_data']['installed'] = 'y';
        update_option('wpClassified_data', $_POST['wpClassified_data']);
        $msg = "Settings Updated!";
      break;
      case "install":
        include("wpClassified_db.php");
        wpClassified_db();
      break;  
    }

    if ($msg!=''){
      ?>
      <p><div id="message" class="updated fade"><?php echo $msg; ?></div></p>
      <?php
    }

    $wpcSettings = get_option('wpClassified_data');
    $t = $table_prefix.'wpClassified';
    if(!$wpdb->get_col("SHOW TABLES LIKE '" . $t . "%'") ||
          !isset ($wpcSettings['slug'])) {
      // TODO
      $this->set_default_option();
    }
    

    $url = "<a href=\"".get_bloginfo('wpurl')."/index.php?pagename=classified\">".get_bloginfo('wpurl')."/index.php?pagename=classified</a>";
    ?>
    <div class="wrap">
    <p>
    <form method="post" id="wpcSettings" name="wpcSettings" action="<?php echo $PHP_SELF;?>?page=wpcSettings&adm_action=saveSettings">
    <h2>General Settings</h2>
    <table><tr valign="top"><td>
    <fieldset class="fieldset">
    <legend class="legend"><strong>Classifeds Page Details</strong></legend>
    <table width="99%">
    <tr>
      <th align="right" valign="top">wpClassified Version:</label></th>
      <td><?php echo $this->version; ?></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>wpClassified URL:</label></th>
      <td><?php echo $url;?></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Classified Top Image:</label></th>
      <td>
    <input type=hidden name="wpClassified_data[top_image]" value="<?php echo $wpcSettings['top_image'];?>">
    <?php
    echo "\n<select name=\"topImage\" onChange=\"showimage()\">";    
    $rep = $this->plugin_dir . "/images/";
    $handle=opendir($rep);
    while ($file = readdir($handle)) {
      $filelist[] = $file;
    }
    asort($filelist);
    while (list ($key, $file) = each ($filelist)) {
      if (!ereg(".gif|.jpg|.png",$file)) {
        if ($file == "." || $file == "..") $a=1;
      } else {
        if ($file == $wpcSettings['top_image']) {
           echo "\n<option value=\"$file\" selected>$file</option>\n";
        } else {
           echo "\n<option value=\"$file\">$file</option>\n";
        }
      }
    }
    echo "\n</select>&nbsp;&nbsp;<img name=\"avatar\" src=\"". $this->plugin_url . "/images/" . $wpcSettings['top_image'] ."\" class=\"imgMiddle\"><br />";
    ?>
    <span class="smallTxt">images from plugins/wp-classified/images directory</span></td>
    </tr>
    <tr>
    <?php
        if (!$wpcSettings['description']) $wpcSettings['description'] = '<h2>Free Information & Advertising Blackboard</h2><b>Feel free to submit announcement, event or report any issues on this blackboard.</b><br />
You do not have to pay any thing, it is totally FREE and your post will stay for 365 days<br /><br /><h3><span style="font-weight:bold; color:#380B61">Choose a topic and SUBMIT your classified ad.</span></h3><br />';
        if (!$wpcSettings['slug']) $wpcSettings['slug'] = 'classified';
     ?>
      <th align="right" valign="top"><label>Classifieds Description:</label></th>
      <td><textarea cols=80 rows=3 name="wpClassified_data[description]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['description']));?></textarea></td>
    </tr>
    <tr>
    <th><label></label></th>
    <td><input type=checkbox name="wpClassified_data[show_credits]" value="y"<?php echo ($wpcSettings['show_credits']=='y')?" checked":"";?>> Display wpClassified credit line at the bottom of page.</td>
    </tr>
    <tr>
    <th align="right" valign="top"><label>Classifieds Page Link Name:</label></th>
    <td><input type="text" name="wpClassified_data[slug]" value="<?php echo $wpcSettings['slug'];?>"></td>
    </tr>  

    <?php $imgPosition=array ('1' => 'Images on right'); ?>
    <tr>
      <th align="right" valign="top"><label>Number of image columns:</label></th>
      <td><input type="text" size="3" name="wpClassified_data[number_of_image]" value="<?php echo $wpcSettings['number_of_image'];?>"><br /><span class="smallTxt">example: 3</span></td>
    </tr>
    <th><label></label></th>
    <td><input type=checkbox name="wpClassified_data[approve]" value="y"<?php echo ($wpcSettings['approve']=='y')?" checked":"";?>> Posts must be pre-approved before being published.</td>
    </tr>

    <tr>
    <th align="right" valign="top"><label>Image Display</label></th>
    <td>
      <select name="wpClassified_data[image_position]">
      <?php
      foreach($imgPosition as $key=>$value)  {
        if ($key == $wpcSettings[image_position]) {
          echo "\n<option value='$key' selected='selected'>$value</option>\n";
        } else {
           echo "\n<option value='$key'>$value</option>\n";
        }
      }
      ?>
      </select>
      </td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Max. Ad image size:</label></th>
      <td>Width:<input type="text" size="5" name="wpClassified_data[image_width]" value="<?php echo $wpcSettings['image_width'];?>"> X Height:<input type="text" size="5" name="wpClassified_data[image_height]" value="<?php echo $wpcSettings['image_height'];?>"><br /><span class="smallTxt">example: 640x480</span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Thumbnail Width:</label></th>
      <td><input type="text" size="5" name="wpClassified_data[thumbnail_image_width]" value="<?php echo $wpcSettings['thumbnail_image_width'];?>"><br /><span class="smallTxt">example: 120</span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Ad first Image Alignment:</label></th>
      <td><input type=text size=11 name="wpClassified_data[image_alignment]" value="<?php echo ($wpcSettings['image_alignment']);?>"><br /><span class="smallTxt">choose: left or right</span></td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[must_registered_user]" value="y"<?php echo ($wpcSettings['must_registered_user']=='y')?" checked":"";?>> Unregistered visitors cannot post.</td>  
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[view_must_register]" value="y"<?php echo ($wpcSettings['view_must_register']=='y')?" checked":"";?>> Unregistered visitors cannot view.</td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[display_unregistered_ip]" value="y"<?php echo ($wpcSettings['display_unregistered_ip']=='y')?" checked":"";?>> Display first 3 octets of unregistered visitors ip.</td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_datadisplay_titles]" value="y"<?php echo ($wpcSettings['display_titles']=='y')?" checked":"";?>> Display user titles on classified.</td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[filter_posts]" value="y"<?php echo ($wpcSettings['filter_posts']=='y')?" checked":"";?>> Apply WP Ad/comment filters to classified posts.</td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Number of last post to show:</label></th>
      <td><input type="text" size="3" name="wpClassified_data[count_last_ads]" value="<?php echo $wpcSettings['count_last_ads'];?>"><br /><span class="smallTxt">example: 5</span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Banner Code:</label></th>
      <td><textarea cols=80 rows=3 name="wpClassified_data[banner_code]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['banner_code']));?></textarea></td>
    </tr>      
    </table>
    </fieldset>
    </td></tr><tr><td>
    <fieldset class="fieldset">
    <legend class="legend"><strong>Tools</strong></legend>
    <table width="99%"><tr><td>
    <?php
    //for upgrade versions
    if (!$wpcSettings['googleID']) $wpcSettings['googleID'] = 'pub-xx4437011269xxxx';
    if (!$wpcSettings['inform_user_subject']) 
       $wpcSettings['inform_user_subject'] = "!sitename reminder:classified ads expiring soon!";
    if (!$wpcSettings['inform_user_body']) 
       $wpcSettings['inform_user_body'] = "One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.";
    if (!$wpcSettings['ad_expiration']) $wpcSettings['ad_expiration'] = "180";
    $textarea=array ('tinymce' => 'Allow BBCode in ad text','plain' => 'No HTML, No BBCode');  
    ?>
    <tr>
      <th align="right"><label>Posting Style:</label></th>
      <td><select name="wpClassified_data[edit_style]">
      <?php
      foreach($textarea as $key=>$value)  {
        if ($key == $wpcSettings[edit_style]) {
           echo "\n<option value='$key' selected='selected'>$value</option>\n";
        } else {
           echo "\n<option value='$key'>$value</option>\n";
        }
      }
      ?>
      </select></td>
    </tr>
      
    <tr>
      <th align="right" valign="top"><label><?php echo $lang['_ADMAXLIMIT'];?></label></th>
      <td><input type=text size=4 name="wpClassified_data[maxchars_limit]" value="<?php echo ($wpcSettings['maxchars_limit']);?>"><br/><span class="smallTxt"><?php echo $lang['_ADMAXLIMITTXT']; ?></span></td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[editor_toolbar_basic]" value="y"<?php echo ($wpcSettings['editor_toolbar_basic']=='y')?" checked":"";?>> Use basic toolbars in editor.</td>
    </tr>
    <tr>
    <?php
    if (!$wpcSettings['notify']) $wpcSettings['notify'] = 'y';
    if (!$wpcSettings['date_format']) $wpcSettings['date_format'] = 'm-d-Y g:i a';
    ?>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[notify]" value="y"<?php echo ($wpcSettings['notify']=='y')?" checked":"";?>> Notify Admin (email) on new Topic/Post</td>
    </tr>
    <tr>
      <th align="right" valign="top"><label>Ads displayed per page:</label></th>
      <td><input type=text size=4 name="wpClassified_data[count_ads_per_page]" value="<?php echo ($wpcSettings['count_ads_per_page']);?>"><br /><span class="smallTxt">default:10</span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label><?php echo $lang['_DATEFORMAT'];?></label></th>
      <td><input type=text size=11 name="wpClassified_data[date_format]" value="<?php echo ($wpcSettings['date_format']);?>"><br><span class="smallTxt">example: m-d-Y g:i a</span></td>
    </tr>
    <tr>
      <th><label></label></th>
      <td><input type=checkbox name="wpClassified_data[rss_feed]" value="y"<?php echo ($wpcSettings['rss_feed']=='y')?" checked":"";?>> <?php echo $lang['_ALLOWRSS'];?></td>
    </tr>
    <tr>
         <th><label></label></th>
         <td><input type=checkbox name="wpClassified_data[fb_link]" value="y"<?php echo ($wpcSettings['fb_link']=='y')?" checked":"";?>> Show Facebook Link.</td>
      </tr>
    <tr>
      <th align="right" valign="top"><label><?php echo $lang['_NOPOSTS'];?></label></th>
      <td><input type=text size=4 name="wpClassified_data[rss_feed_num]" value="<?php echo ($wpcSettings['rss_feed_num']);?>"><br>
      <span class="smallTxt"> example: 15</span></td>
    </tr>
    <tr>
      <th><label></label></th>  
      <td><input type=checkbox name="wpClassified_data[confirmation_code]" value="y"<?php echo ($wpcSettings['confirmation_code']=='y')?" checked":"";?>> <?php echo $lang['_COMFCODE'];?></td>
    </tr>
    </table>
    </fieldset>
    </td></tr>
    <tr><td>
    <fieldset class="fieldset">
    <legend class="legend"><strong>Google AdSense for Classifieds</strong></legend>
    <?php
    //for upgrade versions
    if (!$wpcSettings[GADcolor_border]) $wpcSettings[GADcolor_border]= 'FFFFFF';
    if (!$wpcSettings[GADcolor_link]) $wpcSettings[GADcolor_link]= '0000FF';
    if (!$wpcSettings[GADcolor_bg]) $wpcSettings[GADcolor_bg]= 'FFFFFF';
    if (!$wpcSettings[GADcolor_text]) $wpcSettings[GADcolor_text]= '000000';
    if (!$wpcSettings[GADcolor_url]) $wpcSettings[GADcolor_url]= 'FF0000';
    if (!$wpcSettings[GADposition]) $wpcSettings[GADposition]= 'btn';
    if (!$wpcSettings[GADproduct]) $wpcSettings[GADproduct]= 'link';
    if (!$wpcSettings[googleID]) $wpcSettings[googleID] = 'pub-xxxxxx';
    $GADpos = array ('top' => 'top','btn' => 'bottom', 'bth' => 'both','no' => 'none');
    ?>
    <table width="99%"><tr>
      <th align="right" valign="top"><a href='https://www.google.com/adsense/' target='google'>Google AdSense Account ID:</a></label></th>
      <td><input type='text' name='wpClassified_data[googleID]' id='wpClassified_data[googleID]' value="<?php echo ($wpcSettings['googleID']);?>" size='22' /><br><span class="smallTxt"> example: no, pub-xxxxx or ...
      </span></td></tr>
      
      <tr>
        <th align="right" valign="top"><label>Google Ad Position:</label></th>
        <td>
          <select name="wpClassified_data[GADposition]" tabindex="1">
          <?php
          foreach($GADpos as $key=>$value)  {
            if ($key == $wpcSettings[GADposition]) {
               echo "\n<option value='$key' selected='selected'>$value</option>\n";
            } else {
               echo "\n<option value='$key'>$value</option>\n";
            }
          }
          ?>
          </select>&nbsp;&nbsp;<span class="smallTxt">(If this value is assigned to 'none' then the Google Ads will not show up)</small>
        </td>
      </tr>

      <?php
      $share = '10'; // my smallest cut on ad revenue is 10% -  
      while($share<101){
        if($share==$wpcSettings['share']){
          $share_list .= "<option value='$share' selected='selected'>$share%\n";
        }else{
          $share_list .= "<option value='$share'>$share%\n";
        }
        ++$share;
      }
      $products=array ('ad' => 'Ad Unit','link' => 'Link Unit');  
      $formats=array ('728x90' => '728 x 90  ' . 'Leaderboard', '468x60'  => '468 x 60  ' . 'Banner','234x60'  => '234 x 60  ' . 'Half Banner');
      $lformats=array ('728x15' => '728 x 15', '468x15' => '468 x 15');
      $adtypes=array ('text_image' => 'Text &amp; Image', 'image' => 'Image Only', 'text' => 'Text Only');
      ?>
      <tr><th align="left" colspan=2><label>Layout</label></th></tr>
      <tr><td colspan=2 align="center">
      <table style="border-width:thin; border-color:#888; border-style:solid; padding:5px; width:800px"><tr>
      <th><label>Ad Product:</label></th>
      <td><select name="wpClassified_data[GADproduct]">
        <?php
        foreach($products as $key=>$value)  {
          if ($key == $wpcSettings[GADproduct]) {
            echo "\n<option value='$key' selected='selected'>$value</option>\n";
          } else {
            echo "\n<option value='$key'>$value</option>\n";
          }
        }
        ?>
      </select></td>
      <th><label> Ad Format:</label></th>
      <td><select name="wpClassified_data[GADformat]">
        <optgroup label='Horizontal'>
        <?php
        foreach($formats as $key=>$value) {
          if ($key == $wpcSettings[GADformat]) {
            echo "\n<option value='$key' selected='selected'>$value</option>\n";
          } else {
            echo "\n<option value='$key'>$value</option>\n";
          }
        }
        ?>
        </optgroup>
      </select></td>  
      <th><label>Ad Type:</label></th>
      <td><select name="wpClassified_data[GADtype]">
        <?php
        foreach($adtypes as $key=>$value)  {
          if ($key == $wpcSettings[GADtype]) {
            echo "\n<option value='$key' selected='selected'>$value</option>\n";
          } else {
            echo "\n<option value='$key'>$value</option>\n";
          }
        }
        ?>
      </select></td>  
      <th><label>Link Format:</label></th>
      <td><select name="wpClassified_data[GADLformat]">
        <?php
        foreach($lformats as $key=>$value)  {
          if ($key == $wpcSettings[GADLformat]) {
            echo "\n<option value='$key' selected='selected'>$value</option>\n";
          } else {
            echo "\n<option value='$key'>$value</option>\n";
          }
        }
        ?>
      </select></td>  
      </tr>
      </td></tr></table>
    </td></tr>
    <tr><th align="left" colspan=2><label>Ad Colours</label></th></tr>
      <tr><td colspan=2 align="center">
      <table style="border-width:thin; border-color:#888; border-style:solid; padding:5px; width:800px"><tr>
      <th><label>Border:</label></th>
      <td><input name='wpClassified_data[GADcolor_border]' id='wpClassified_data[GADcolor_border]' size='6' value='<?php echo $wpcSettings[GADcolor_border]; ?>'/>
      </td>
      <th><label>Title/Link:</label></th>
      <td><input name='wpClassified_data[GADcolor_link]' id='wpClassified_data[GADcolor_link]' size='6' value='<?php echo $wpcSettings[GADcolor_link]; ?>'/>
      </td>
      <th><label>Background:</label></th>
      <td><input name='wpClassified_data[GADcolor_bg]' id='wpClassified_data[GADcolor_bg]' size='6' value='<?php echo $wpcSettings[GADcolor_bg]; ?>'/>
      </td>
      <th><label>Text:</label></th>
      <th><input name='wpClassified_data[GADcolor_text]' id='wpClassified_data[GADcolor_text]' size='6' value='<?php echo $wpcSettings[GADcolor_text]; ?>'/>
      </td>
      <th><label>URL:</label></th>
      <td><input name='wpClassified_data[GADcolor_url]' id='wpClassified_data[GADcolor_url]' size='6' value='<?php echo $wpcSettings[GADcolor_url]; ?>'/>
      </td>
      </tr>
      </td></tr></table>
    </td></tr>
    </table>
    </fieldset>
    </td></tr>

    <tr><td>
    <?php
    if (!$wpcSettings[inform_user_expiration]) $wpcSettings[inform_user_expiration]= 14;
    ?>
    <fieldset class="fieldset">
    <legend class="legend"><strong><?php echo $lang['_NEWADDURATION'];?></strong></legend>
    <table width="99%"><tr><td>
      <tr>
      <th align="right" valign="top"><label><?php echo $lang['_NEWADDEFAULT'];?></label></th>
      <?php
        if (!$wpcSettings['ad_expiration']) $wpcSettings['ad_expiration'] = '365';
      ?>
      <td><input type=text size=4 name="wpClassified_data[ad_expiration]" value="<?php echo ($wpcSettings['ad_expiration']);?>"><br><span class="smallTxt">Ads will be auto-removed after these
        number of days since creation. default:365 days<br />
        The expiration will be disabled if you set this value to 0.
    </span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label><?php echo $lang['_SENDREMIDE'];?></label></th>
      <td><input type=text size=4 name="wpClassified_data[inform_user_expiration]" value="<?php echo ($wpcSettings['inform_user_expiration']);?>"><br><span class="smallTxt">example:7 days</span></td>
    </tr>
    <tr>
      <th align="right" valign="top"><label><?php echo $lang['_NOTMESSAGE'];?></label></th>
      <td><?php echo $lang['_NOTMESSAGESUBJECT'];?>&nbsp;&nbsp;<span class="smallTxt">(is currently not implemented!)</span><br />
      <span class="smallTxt">Substitution variables: !sitename = your website name, !siteurl = your site's base URL, !user_ads_url = link to user's classified ads list.</span><br />
      <textarea cols=60 rows=5 name="wpClassified_data[inform_user_subject]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['inform_user_subject']));?></textarea><br/>
      <span class="smallTxt">example: !sitename reminder:classified ads expiring soon!</span></td></tr>
      <tr><th align="right" valign="top"><label><?php echo $lang['_NOTMESSAGEBODY'];?></label></th><td><textarea cols=60 rows=5 name="wpClassified_data[inform_user_body]"><?php echo str_replace("<", "&lt;", stripslashes($wpcSettings['inform_user_body']));?></textarea><br><span class="smallTxt">example: One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.</span></td>
    </tr>
    </table>
    </fieldset>
    </td></tr></table></div>
    <p><input type=submit value="Save Settings"></p>
    </form>
    </p>
    <?php
  }

  function create_ads_subject_author($ad){
    $wpcSettings = get_option('wpClassified_data');
    $userfield = $this->get_user_field();
    $out = "";
    if ($ad->author==0){
      $out .= $ad->author_name;
    } else {
      $out .= $ad->$userfield;
    }
    return $out;
  }

  
  function get_user_field(){
    global $wpdb, $table_prefix, $wpmuBaseTablePrefix, $wp_version;
    $sql = "SHOW COLUMNS FROM {$wpmuBaseTablePrefix}users";
    $tcols = $wpdb->get_results($sql, ARRAY_A);
    $cols = array();
    for ($i=0; $i<count($tcols); $i++){
      $cols[] = $tcols[$i]['Field'];
    }
    if (in_array("display_name", $cols)){
      $user_field = "display_name";
      $wp_version = "2";
    } elseif (in_array("user_nicename", $cols)){
      $user_field = "user_nicename";
      $wp_version = "WPMU";
    } else {
      $user_field = "nickname";
      $wp_version = "1";
    }
    return $user_field;
  }

  // install function 
  // create the db tables.
  function set_default_option(){
    $wpcSettings = array();
    $wpcSettings = $_POST['wpClassified_data'];
    update_option('wpClassified_data', $wpcSettings);
    $wpcSettings = get_option('wpClassified_data');
    $this->check_db();
    $wpcSettings['installed'] = 'y';
    $wpcSettings['userfield'] = $this->get_user_field();
    $wpcSettings['show_credits'] = 'n';
    $wpcSettings['approve]'] = 'y';
    $wpcSettings['slug'] = 'Classifieds';
    $wpcSettings['description'] = "<h2>Free Information & Advertising Blackboard</h2><b>Feel free to submit announcement, event or report any issues on this blackboard.</b><br />
You do not have to pay any thing, it is totally FREE and your post will stay for 365 days<br /><br /><h3><span style=\"font-weight:bold; color:#380B61\">choose a topics and SUBMIT your classified ad.</span></h3><br />";
    $wpcSettings['must_registered_user'] = 'n';
    $wpcSettings['view_must_register'] = 'n';
    $wpcSettings['display_unregistered_ip'] = 'y';
    $wpcSettings['notify'] = 'y';
    $wpcSettings['display_titles'] = 'y';
    $wpcSettings['editor_toolbar_basic'] = 'y';
    $wpcSettings['filter_posts'] = 'y';
    $wpcSettings['rss_feed'] = 'y';
    $wpcSettings['fb_like'] = 'y';
    $wpcSettings['rss_feed_num'] = 15;
    $wpcSettings['confirmation_code'] = 'y';
    $wpcSettings['count_ads_per_page'] = 10;
    $wpcSettings['maxchars_limit'] = 540;
    $wpcSettings['number_of_image'] = 3;
    $wpcSettings['image_position'] = 1;
    $wpcSettings['thumbnail_image_width'] = 120;
    $wpcSettings['inform_user_expiration'] = 7;
    $wpcSettings['image_width'] = 640;
    $wpcSettings['image_height'] = 480;
    $wpcSettings['date_format'] = 'm-d-Y g:i a';
    $wpcSettings['googleID'] = 'pub-xxxxx';
    $wpcSettings['GADproduct'] = 'link';
    $wpcSettings['GADformat'] = '468x60';
    $wpcSettings['GADLformat'] = '468x15';
    $wpcSettings['GADtype'] = 'text';
    $wpcSettings['GADcolor_border']= 'FFFFFF';
    $wpcSettings['GADcolor_link']= '0000FF';
    $wpcSettings['GADcolor_bg']= 'E4F2FD';
    $wpcSettings['GADcolor_text']= '000000';
    $wpcSettings['GADcolor_url']= 'FF0000';
    $wpcSettings['GADposition'] = 'btn';
    $wpcSettings['count_last_ads'] = 5;
    $wpcSettings['unread_color'] = '#FF0000';
    $wpcSettings['image_alignment'] = 'left';
    $wpcSettings['top_image'] = 'default.gif';
    $wpcSettings['read_user_level'] = -1;
    $wpcSettings['write_user_level'] = -1;
    $wpcSettings['banner_code'] = '';
    $wpcSettings['display_last_ads_subject'] = 'y';
    $wpcSettings['last_ads_subject_num'] = 5;
    $wpcSettings['edit_style'] = 'plain';
    $wpcSettings['last_ads_subjects_author'] = "y";
    $wpcSettings['inform_user_subject'] = "!sitename reminder:classified ads expiring soon!";
    $wpcSettings['inform_user_body'] = "One or more of your classified ads on !sitename (!siteurl) are expiring soon. Please sign in and visit !user_ads_url to check your ads.";
    $wpcSettings['ad_expiration'] = "180";
    //}
    update_option('wpClassified_data', $wpcSettings);
  }

  function check_db(){
    global $PHP_SELF;
    $activate_url = $PHP_SELF . '?page=wpcSettings&adm_action=install';
    echo '<div class="wrap"><h2>Installation the wpClassified</h2>';
    echo '<h3><strong>&nbsp;&nbsp;<a href='.$activate_url.'>Click Here</a> to install the wpClassified.</strong></h3></div>';
  }


  function page_handle_title($title){
    $wpcSettings = get_option('wpClassified_data');
    return str_replace("[[WP_CLASSIFIED]]", $wpcSettings['slug'], $title);
  }

  function page_handle_pagetitle($title){
    $wpcSettings = get_option('wpClassified_data');
    return str_replace("[[WP_CLASSIFIED]]", $wpcSettings['slug'], $title);
  }

  function page_handle_content($content){
      return preg_replace( "/\[\[WP_CLASSIFIED\]\]/ise", "wpClassified_process()", $content); 
   }


  function page_handle_titlechange($title){
    $wpcSettings = get_option('wpClassified_data');
    return str_replace("[[WP_CLASSIFIED]]", $wpcSettings["slug"], $title);
  }


  function update_ads_views($ads_subjects_id, $sign="+"){
    global $wpdb, $table_prefix;
    $wpdb->query("UPDATE {$table_prefix}wpClassified_ads_subjects SET views = views".$sign."1 WHERE ads_subjects_id = '".((int)$ads_subjects_id)."'");
  }

  function update_posts($lists_id, $sign="+", $num=1){
    global $wpdb, $table_prefix;
    $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads = ads".$sign.$num." WHERE lists_id = '".((int)$lists_id)."'");
  }

  function update_ads($lists_id, $sign="+"){
    global $wpdb, $table_prefix;
    $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_status = ads_status".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
  }

  function update_views($lists_id, $sign="+"){
    global $wpdb, $table_prefix;
    $wpdb->query("UPDATE {$table_prefix}wpClassified_lists SET ads_views = ads_views".$sign."1 WHERE lists_id = '".((int)$lists_id)."'");
  }

  function showCategoryImg() {
    echo "<script type=\"text/javascript\">\n";
    echo "<!--\n\n";
    echo "function showimage() {\n";
    echo "if (!document.images)\n";
    echo "return\n";
    echo "document.images.avatar.src=\n";
    echo "'". $this->plugin_url. "/images/' + document.wpcSettings.topImage.options[document.wpcSettings.topImage.selectedIndex].value;\n";
    echo 'document.wpcSettings.elements["wpClassified_data[top_image]"].value = document.wpcSettings.topImage.options[document.wpcSettings.topImage.selectedIndex].value;';
    echo "}\n\n";

    echo "function showCatimage() {\n";
    echo "if (!document.images)\n";
    echo "return\n";
    echo "document.images.avatar.src=\n";
    echo "'".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/' + document.admCatStructure.topImage.options[document.admCatStructure.topImage.selectedIndex].value;\n";
    echo 'document.admCatStructure.elements["wpClassified_data[photo]"].value = document.admCatStructure.topImage.options[document.admCatStructure.topImage.selectedIndex].value;';
    echo "}\n\n";
    echo "//-->\n";
    echo "</script>\n"; 
  }

  // Widget stuff
  function widget_init() {
    if ( !function_exists('wp_register_sidebar_widget') || !function_exists('register_widget_control') ) return;
    function widget($args) {
      extract($args);
      $wpcSettings = get_option('wpClassified_data');  
      echo $before_widget;
      echo $before_title . $wpcSettings['widget_title'] . $after_title;

      $fieldsPre="wpc_";
      $before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
      $after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));
      echo '<p><ul>' . widget_display($wpcSettings['widget_format']) . '</ul></p>'; 
    }

    function widget_control() {
      $wpcSettings = $newoptions = get_option('wpClassified_data');
      if ( $_POST["wpClassified-submit"] ) {
        $newoptions['widget_title'] = strip_tags(stripslashes($_POST['widget_title']));
        $newoptions['widget_format'] = $_POST['widget_format'];
        if ( empty($newoptions['widget_title']) ) $newoptions['widget_title'] = 'Last Classifieds Ads';
      }
      if ( $wpcSettings != $newoptions ) {
        $wpcSettings = $newoptions;
        update_option('wpClassified_data', $wpcSettings);
      }
      $title = htmlspecialchars($wpcSettings['widget_title'], ENT_QUOTES);
      if ( empty($newoptions['widget_title']) ) $newoptions['widget_title'] = 'Last Classifieds Ads';
      if ( empty($newoptions['widget_format']) ) $newoptions['widget_format'] = 'y';
      ?>
      <label for="wpClassified-widget_title"><?php _e('Title:'); ?><input style="width: 200px;" id="widget_title" name="widget_title" type="text" value="<?php echo htmlspecialchars($wpcSettings['widget_title']); ?>" /></label></p>
      <br />
      <label for="wpClassified-widget_format">
      <input class="checkbox" id="widget_format" name="widget_format" type="checkbox" value="y"<?php echo ($wpcSettings['widget_format']=='y')?" checked":"";?>>Small Format Output</label><br />
      <input type="hidden" id="wpClassified-submit" name="wpClassified-submit" value="1" />
      <?php
    }
    
    function widget_display() {
      $wpcSettings = get_option('wpClassified_data');
      $out = wpcLastAds($wpcSettings['widget_format']);
      return $out;
    }
    
    wp_register_sidebar_widget('wpClassified', 'widget', null, 'wpClassified');
    register_widget_control('wpClassified', 'widget_control');
  }

  

  function add_admin_head(){
    $wpcSettings = get_option('wpClassified_data');
    ?>
    <link rel="stylesheet" href="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/admin.css" type="text/css" media="screen" />
    <?php
  }

  function add_head(){
  $wpcSettings = get_option('wpClassified_data');
  ?>
  <link rel="stylesheet" href="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-classified/includes/wpClassified.css" type="text/css" media="screen" />
    <?php
    if($wpcSettings['edit_style']==null || $wpcSettings['edit_style']=='plain') {
      // nothing
    } elseif($wpcSettings['edit_style']=='tinymce') {
      // activate these includes if the user chooses tinyMCE on the settings page
         /*
      $mce_path = get_option('siteurl');
      $mce_path .= '/wp-includes/js/tinymce/tiny_mce.js';
      echo '<script type="text/javascript" src="' . $mce_path . '"></script>';
         */
    }
  }

  function get_pageinfo(){
    global $wpdb, $table_prefix;
    return  $wpdb->get_row("SELECT * FROM {$table_prefix}posts WHERE post_title = '[[WP_CLASSIFIED]]'", ARRAY_A);
  }

  function is_usr_admin(){
    global $userdata, $user_level;
    if ($user_level && $user_level>=8) return true;
    else return ($userdata->wp_user_level >= 8)?true:false;  
  }

  function is_usr_mod($classified=0){
    global $userdata, $user_level;
    If ($user_level && $user_level>4) return true;
    else return ($userdata->wp_user_level > 4)?true:false;
  }

  function is_usr_loggedin(){
    global $userdata, $user_level, $user_ID;
    if ($user_level && $user_level>=1) return true;
    elseif ($user_ID) return true;
    else return ($userdata->wp_user_level >=1)?true:false;
  }
  
  function last_octet($ip){
    $ip = explode(".", $ip);
    $ip[count($ip)-1] = "***";
    return @implode(".", $ip);
  }

  function getInitJS($debugMode=0) {
    global $locale;
    // TODO
    return 1;
  }

  function cleanUp() {
    $deleteTimeDiff= 5 * 60; // second
    if ( !($dh = opendir( $this->cache_dir )) )
      echo 'Unable to open cache directory "' . $this->cache_dir . '"';
    $result = true;
    while ( $file = readdir($dh) ) {
      if ( ($file != '.') && ($file != '..') ) {
        $f = $this->cache_dir . $file;
        if ( isset($f) &&  filemtime($f) <= time()-$deleteTimeDiff ) {
          @unlink( $f );
        }
      }
    }
  }

  function html2Text( $str ) {
    //remove PHP if it exists
    while( substr_count( $str, '<'.'?' ) && substr_count( $str, '?'.'>' ) && strpos( $str, '?'.'>', strpos( $str, '<'.'?' ) ) > strpos( $str, '<'.'?' ) ) {
      $str = substr( $str, 0, strpos( $str, '<'.'?' ) ) . substr( $str, strpos( $str, '?'.'>', strpos( $str, '<'.'?' ) ) + 2 ); }
    //remove comments
    while( substr_count( $str, '<!--' ) && substr_count( $str, '-->' ) && strpos( $str, '-->', strpos( $str, '<!--' ) ) > strpos( $str, '<!--' ) ) {
      $str = substr( $str, 0, strpos( $str, '<!--' ) ) . substr( $str, strpos( $str, '-->', strpos( $str, '<!--' ) ) + 3 ); }
    //now make sure all HTML tags are correctly written (> not in between quotes)
    for( $x = 0, $goodStr = '', $is_open_tb = false, $is_open_sq = false, $is_open_dq = false; strlen( $chr = $str{$x} ); $x++ ) {
      //take each letter in turn and check if that character is permitted there
      switch( $chr ) {
          case '<':
            if( !$is_open_tb && strtolower( substr( $str, $x + 1, 5 ) ) == 'style' ) {
              $str = substr( $str, 0, $x ) . substr( $str, strpos( strtolower( $str ), '</style>', $x ) + 7 ); $chr = '';
            } elseif( !$is_open_tb && strtolower( substr( $str, $x + 1, 6 ) ) == 'script' ) {
              $str = substr( $str, 0, $x ) . substr( $str, strpos( strtolower( $str ), '</script>', $x ) + 8 ); $chr = '';
            } elseif( !$is_open_tb ) { $is_open_tb = true; } else { $chr = '&lt;'; }
            break;
          case '>':
            if( !$is_open_tb || $is_open_dq || $is_open_sq ) { $chr = '&gt;'; } else { $is_open_tb = false; }
            break;
          case '"':
            if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_dq = true; }
            elseif( $is_open_tb && $is_open_dq && !$is_open_sq ) { $is_open_dq = false; }
            else { $chr = '&quot;'; }
            break;
          case "'":
            if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_sq = true; }
            elseif( $is_open_tb && !$is_open_dq && $is_open_sq ) { $is_open_sq = false; }
      } $goodStr .= $chr;
    }
    //now that the page is valid (I hope) for strip_tags, strip all unwanted tags
    $goodStr = strip_tags( $goodStr, '<title><hr><h1><h2><h3><h4><h5><h6><div><p><pre><sup><ul><ol><br><dl><dt><table><caption><tr><li><dd><th><td><a><area><img><form><input><textarea><button><select><option>' );
    //strip extra whitespace except between<pre> and<textarea> tags
    $str = preg_split( "/<\/?pre[^>]*>/i", $goodStr );
    for( $x = 0; is_string( $str[$x] ); $x++ ) {
      if( $x % 2 ) { 
        $str[$x] = '<pre>'.$str[$x].'</pre>'; } 
      else {
        $goodStr = preg_split( "/<\/?textarea[^>]*>/i", $str[$x] );
        for( $z = 0; is_string( $goodStr[$z] ); $z++ ) {
          if( $z % 2 ) { $goodStr[$z] = '<textarea>'.$goodStr[$z].'</textarea>'; } else {
            $goodStr[$z] = preg_replace( "/\s+/", ' ', $goodStr[$z] );
          } 
        }
        $str[$x] = implode('',$goodStr);
      } 
    }
    $goodStr = implode('',$str);
    //remove all options from select inputs
    $goodStr = preg_replace( "/<option[^>]*>[^<]*/i", '', $goodStr );
    //replace all tags with their text equivalents
    $goodStr = preg_replace( "/<(\/title|hr)[^>]*>/i", "\n          --------------------\n", $goodStr );
    $goodStr = preg_replace( "/<(h|div|p)[^>]*>/i", "\n\n", $goodStr );
    $goodStr = preg_replace( "/<sup[^>]*>/i", '^', $goodStr );
    $goodStr = preg_replace( "/<(ul|ol|br|dl|dt|table|caption|\/textarea|tr[^>]*>\s*<(td|th))[^>]*>/i", "\n", $goodStr );
    $goodStr = preg_replace( "/<li[^>]*>/i", "\n· ", $goodStr );
    $goodStr = preg_replace( "/<dd[^>]*>/i", "\n\t", $goodStr );
    $goodStr = preg_replace( "/<(th|td)[^>]*>/i", "\t", $goodStr );
    $goodStr = preg_replace( "/<a[^>]* href=(\"((?!\"|#|javascript:)[^\"#]*)(\"|#)|'((?!'|#|javascript:)[^'#]*)('|#)|((?!'|\"|>|#|javascript:)[^#\"'> ]*))[^>]*>/i", "[LINK: $2$4$6] ", $goodStr );
    $goodStr = preg_replace( "/<img[^>]* alt=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "[IMAGE: $2$3$4] ", $goodStr );
    $goodStr = preg_replace( "/<form[^>]* action=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "\n[FORM: $2$3$4] ", $goodStr );
    $goodStr = preg_replace( "/<(input|textarea|button|select)[^>]*>/i", "[INPUT] ", $goodStr );
    //strip all remaining tags (mostly closing tags)
    $goodStr = strip_tags( $goodStr );
    //convert HTML entities
    $goodStr = strtr( $goodStr, array_flip( get_html_translation_table( HTML_ENTITIES ) ) );
    preg_replace( "/&#(\d+);/me", "chr('$1')", $goodStr );
    //wordwrap
    $goodStr = wordwrap( $goodStr );
    //make sure there are no more than 3 linebreaks in a row and trim whitespace
    return preg_replace( "/^\n*|\n*$/", '', preg_replace( "/[ \t]+(\n|$)/", "$1", preg_replace( "/\n(\s*\n){2}/", "\n\n\n", preg_replace( "/\r\n?|\f/", "\n", str_replace( chr(160), ' ', $goodStr ) ) ) ) );
  }
  
}

?>
