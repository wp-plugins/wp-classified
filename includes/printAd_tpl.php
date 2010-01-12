<?php

/*
* sendAd_tpl template wordpress plugin
* This file is part of wp-classified
* @author Mohammad Forgani 2008
* Author Website : http://www.forgani.com
* @version 1.2.1
* 
*/

?>

<html>
<head>
<title><?php echo $wpcSettings['wpClassified_slug']; ?></title>
</head>
<body bgcolor="#FFFFFF" text="#000000">

<H2><?php echo $subject; ?></H2><BR />

<?php
	if (isset($error)){echo '<p class="error">' .$error. '</p>';}
?>

<div class="wpc_container">
	<table width="650" border=0>
		<tr>
			<td><?php echo $lang['_CLASSIFIED_AD']; ?> (No. <?php echo  $aid; ?>)<br /><?php echo $lang['_FROM']; ?><br /><br />
			<b><?php echo $lang['_TITLE']; ?></b> <i><?php echo $subject; ?></i></td></tr>
			<?php
			foreach($array as $f) {
				if ($f !=''){
					echo "<tr><td>";
					echo "<div class=\"show_ad_img12\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-classified/images/" . $f . "\" style=\"width: 120px; height: 100px\"></a><br>" .$f . "</div>";
					echo "</td></tr>";
				} 
			} 
			echo "<tr><td>";
			echo "<p class=\"justify\"><b>".$lang['_DESC']."</b><br /><br />".$message."</p>";
			if ($phone) {
				echo "<br /><b>".$lang['_TEL']."</b>" . $phone . "<br />";
			}
			if ($web) {
				echo"<b>".$lang['_WEB']."</b> ". $web ;
			}
			?>
			<hr /><p>&nbsp;</p>
			<?php echo $lang['_TOCONTACTBY'];?>
			<?php
			$url = get_bloginfo('wpurl') . "/?page_id=" . $pageinfo["ID"]. "&_action=va&asid=" . $post->ads_subjects_id;
			echo '<br /><i>' . $url . '</li>';
			echo "<br /><br />".$lang['_ADSADDED']. " " . "<nobr>" . @date($wpcSettings['date_format'], $post->date) ."</nobr>";
			?>
			<br /><?php echo $lang['_ADLINKINFO'];?> <a href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?>.</a>
			</td>
		</tr>
		<tr><td class="wpc_label_right"></td><td>
			<p><input type="button" value="Print" onClick="window.print(); parent.fb.end(true); return false;" /></p>
		</td></tr>
	</table>		
</diV>


</body>
</html>

