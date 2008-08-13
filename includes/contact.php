<?php
// 
// ------------------------------------------------------------------------- //
//               E-Xoops: Content Management for the Masses                  //
//                       < http://www.e-xoops.com >                          //
// ------------------------------------------------------------------------- //
// Original Author: Pascal Le Boustouller
// Author Website : pascal.e-xoops@perso-search.com
// Licence Type   : GPL
// ------------------------------------------------------------------------- //



if ($_POST['submit']) {
	// Define Variables for register_globals Off. contribution by Peekay
	$id = !isset($_REQUEST['id'])? NULL : $_REQUEST['id'];
	$date = !isset($_REQUEST['date'])? NULL : $_REQUEST['date'];
	$namep = !isset($_REQUEST['namep'])? NULL : $_REQUEST['namep'];
	$ipnumber = !isset($_REQUEST['ipnumber'])? NULL : $_REQUEST['ipnumber'];
	$messtext = !isset($_REQUEST['messtext'])? NULL : $_REQUEST['messtext'];
	$typeprice = !isset($_REQUEST['typeprice'])? NULL : $_REQUEST['typeprice'];
	$price = !isset($_REQUEST['price'])? NULL : $_REQUEST['price'];
	$tele = !isset($_REQUEST['tele'])? NULL : $_REQUEST['tele'];
	// end define vars

	include("header.php");
	$mydirname = basename( dirname( __FILE__ ) ) ;
	$main_lang =  '_' . strtoupper( $mydirname ) ;

	$module_id = $xoopsModule->getVar('mid');

if (is_object($xoopsUser)) {
    $groups = $xoopsUser->getGroups();
} else {
	$groups = XOOPS_GROUP_ANONYMOUS;
}

$gperm_handler =& xoops_gethandler('groupperm');

if (isset($_POST['item_id'])) {
    $perm_itemid = intval($_POST['item_id']);
} else {
    $perm_itemid = 0;
}
//If no access
if (!$gperm_handler->checkRight("".$mydirname."_view", $perm_itemid, $groups, $module_id)) {
    redirect_header(XOOPS_URL."/index.php", 3, _NOPERM);
    exit();
}

	require_once( XOOPS_ROOT_PATH."/modules/$mydirname/include/gtickets.php" ) ;
	global $xoopsConfig, $xoopsDB, $myts, $meta, $xoopsModuleConfig, $mydirname, $main_lang, $_POST;

if ( ! $xoopsGTicket->check( true , 'token' ) ) {
		redirect_header(XOOPS_URL."/modules/$mydirname/index.php?pa=viewads&lid=".addslashes($id)."", 3,$xoopsGTicket->getErrors());
	}

	$lid = $_POST['id'];
	$result = $xoopsDB->query("select email, submitter, title, type, desctext, price, typeprice FROM  ".$xoopsDB->prefix("".$mydirname."_listing")." WHERE lid = ".mysql_real_escape_string($id)."");

	while(list($email, $submitter, $title, $type, $desctext, $price, $typeprice) = $xoopsDB->fetchRow($result)) {

		if ($_POST['tele'])  {
			$teles = $_POST['tele'];
		}  else {
			$teles = "";
		}

		if ($price) {
			$price = "".constant($main_lang."_PRICE")." ".$xoopsModuleConfig["".$mydirname."_money"]." $price";
 		}  else {
			$price = "";
		}

		$date = time();
		$r_usid = $xoopsUser->getVar("uid", "E");



	$tags=array();
	$tags['TITLE'] = $title;
	$tags['TYPE'] = $type;
	$tags['PRICE'] = $price;
	$tags['DESCTEXT'] = stripslashes($desctext);
	$tags['MY_SITENAME'] = $xoopsConfig['sitename'];
	$tags['REPLY_ON'] = constant($main_lang."_REMINDANN");
	$tags['DESCRIPT'] = constant($main_lang."_DESC");
	$tags['STARTMESS'] = constant($main_lang."_STARTMESS");
	$tags['MESSFROM'] = constant($main_lang."_MESSFROM");
	$tags['CANJOINT'] = constant($main_lang."_CANJOINT");
	$tags['NAMEP'] = $_POST['namep'];
	$tags['TO'] = constant($main_lang."_TO");
	$tags['POST'] = $_POST['post'];
	$tags['TELE'] = $teles;
	$tags['MESSAGE_END'] = constant($main_lang."_MESSAGE_END");
	$tags['ENDMESS'] = constant($main_lang."_ENDMESS");
	$tags['SECURE_SEND'] = constant($main_lang."_SECURE_SEND");
	$tags['SUBMITTER'] = $submitter;
	$tags['MESSTEXT'] = stripslashes($messtext);
	$tags['EMAIL'] = constant($main_lang."_EMAIL");
	$tags['TEL'] = constant($main_lang."_TEL");
	$tags['HELLO'] = constant($main_lang."_HELLO");
	$tags['REPLIED_BY'] = constant($main_lang."_REPLIED_BY");
	$tags['YOUR_AD'] = constant($main_lang."_YOUR_AD");
	$tags['THANKS'] = constant($main_lang."_THANK");
	$tags['WEBMASTER'] = constant($main_lang."_WEBMASTER");
	$tags['AT'] = constant($main_lang."_AT");
	$tags['LINK_URL'] = XOOPS_URL ."/modules/". $xoopsModule->getVar('dirname') ."/index.php?pa=viewads&lid=".addslashes($id)."";
	$tags['VIEW_AD'] = constant($main_lang."_VIEW_AD");

	$subject = "".constant($main_lang."_CONTACTAFTERANN")."";
	$mail =& getMailer();

	$mail->setTemplateDir(XOOPS_ROOT_PATH."/modules/". $xoopsModule->getVar('dirname') ."/language/".$xoopsConfig['language']."/mail_template/");
	$mail->setTemplate("listing_contact.tpl");

		$mail->useMail();
		$mail->setFromEmail($_POST['post']);
		$mail->setToEmails($email);
		$mail->setSubject($subject);
		$mail->assign($tags);
	//	$mail->setBody(stripslashes("$message"));
		$mail->send();
		echo $mail->getErrors();
	

	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("".$mydirname."_ip_log")." values ( '', '$lid', '$date', '$namep', '$ipnumber', '".$_POST['post']."')");

	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("".$mydirname."_replies")." values ('','$id', '$title', '$date', '$namep', '$messtext', '$tele', '".$_POST['post']."', '$r_usid')");
	
	redirect_header("index.php",3,constant($main_lang."_MESSEND"));
	exit();
}
} else {
	$lid = intval($_GET['lid']);
	
	include("header.php");
	
	$mydirname = basename( dirname( __FILE__ ) ) ;
	$main_lang =  '_' . strtoupper( $mydirname ) ;

$module_id = $xoopsModule->getVar('mid');

if (is_object($xoopsUser)) {
    $groups = $xoopsUser->getGroups();
} else {
	$groups = XOOPS_GROUP_ANONYMOUS;
}

$gperm_handler =& xoops_gethandler('groupperm');

if (isset($_POST['item_id'])) {
    $perm_itemid = intval($_POST['item_id']);
} else {
    $perm_itemid = 0;
}
//If no access
if (!$gperm_handler->checkRight("".$mydirname."_view", $perm_itemid, $groups, $module_id)) {
    redirect_header(XOOPS_URL."/index.php", 3, _NOPERM);
    exit();
}

	require_once( XOOPS_ROOT_PATH."/modules/$mydirname/include/gtickets.php" ) ;
	global $xoopsConfig, $xoopsDB, $myts, $meta, $mydirname;


	
	include(XOOPS_ROOT_PATH."/header.php");
	echo "<table width='100%' border='0' cellspacing='1' cellpadding='8'><tr class='bg4'><td valign='top'>\n";
	$time = time();
	$ipnumber = "$_SERVER[REMOTE_ADDR]";
	echo "<script type=\"text/javascript\">
          function verify() {
                var msg = \"".constant($main_lang."_VALIDERORMSG")."\\n__________________________________________________\\n\\n\";
                var errors = \"FALSE\";

			
				if (window.document.cont.namep.value == \"\") {
                        errors = \"TRUE\";
                        msg += \"".constant($main_lang."_VALIDSUBMITTER")."\\n\";
                }
				
				if (window.document.cont.post.value == \"\") {
                        errors = \"TRUE\";
                        msg += \"".constant($main_lang."_VALIDEMAIL")."\\n\";
                }
				
				if (window.document.cont.messtext.value == \"\") {
                        errors = \"TRUE\";
                        msg += \"".constant($main_lang."_VALIDMESS")."\\n\";
                }
				
  
                if (errors == \"TRUE\") {
                        msg += \"__________________________________________________\\n\\n".constant($main_lang."_VALIDMSG")."\\n\";
                        alert(msg);
                        return false;
                }
          }
          </script>";

	echo "<b>".constant($main_lang."_CONTACTAUTOR")."</b><br /><br />";
	echo "".constant($main_lang."_TEXTAUTO")."<br />";
	echo "<form onSubmit=\"return verify();\" method=\"post\" action=\"contact.php\" name=\"cont\">";
	echo "<input type=\"hidden\" name=\"id\" value=\"$lid\" />";
	echo "<input type=\"hidden\" name=\"submit\" value=\"1\" />";
	
	echo "<table width='100%' class='outer' cellspacing='1'>
    <tr>
      <td class='head'>".constant($main_lang."_YOURNAME")."</td>";
	if($xoopsUser) {
		$idd =$xoopsUser->getVar("uname", "E");
		$idde =$xoopsUser->getVar("email", "E");

	echo "<td class='even'><input type=\"text\" name=\"namep\" size=\"42\" value=\"$idd\" />";
	}else{
      echo "<td class='even'><input type=\"text\" name=\"namep\" size=\"42\" /></td>";
	}
    echo "</tr>
    <tr>
      <td class='head'>".constant($main_lang."_YOUREMAIL")."</td>
      <td class='even'><input type=\"text\" name=\"post\" size=\"42\" value=\"$idde\" /></font></td>
    </tr>
    <tr>
      <td class='head'>".constant($main_lang."_YOURPHONE")."</td>
      <td class='even'><input type=\"text\" name=\"tele\" size=\"42\" /></font></td>
    </tr>
    <tr>
      <td class='head'>".constant($main_lang."_YOURMESSAGE")."</td>
      <td class='even'><textarea rows=\"5\" name=\"messtext\" cols=\"40\" /></textarea></td>
    </tr>
	</table><table class='outer'><tr><td>".constant($main_lang."_YOUR_IP")."&nbsp;
        <img src=\"".XOOPS_URL."/modules/$mydirname/ip_image.php\" alt=\"\" /><br />".constant($main_lang."_IP_LOGGED")."
        </td></tr></table>
	<br />";

	echo "<input type=\"hidden\" name=\"ip_id\" value=\"\" />";
	echo "<input type=\"hidden\" name=\"lid\" value=\"$lid\" />";
	echo "<input type=\"hidden\" name=\"ipnumber\" value=\"$ipnumber\" />";
	echo "<input type=\"hidden\" name=\"date\" value=\"$time\" />";
      echo "<p><input type=\"submit\" name=\"submit\" value=\"".constant($main_lang."_SENDFR")."\" /></p>
".$GLOBALS['xoopsGTicket']->getTicketHtml( __LINE__ , 1800 , 'token')."
	</form>";
}
	echo "</td></tr></table>";
	include(XOOPS_ROOT_PATH."/footer.php");

?>