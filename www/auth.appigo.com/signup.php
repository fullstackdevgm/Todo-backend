<?php

include_once('TodoOnline/base_sdk.php');

include_once('TodoOnline/php/SessionHandler.php');
include_once('ssoMethods.php');  

echo '<script src="js/AuthFunctions.js?v=3"></script>';

echo '<table width="50%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#CCCCCC">';
echo '<tr>';
echo '<form name="form1" method="post">';
echo '<td>';
echo '<table width="50%" border="0" align="center" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">';
echo '<tr>';
echo '  <td colspan="4"><strong>Mailing List Signup</strong></td>';
echo '</tr>';
echo '<tr>';
echo '<td>Email</td>';
echo '<td>:</td>';
echo '<td><input name="username" type="text" id="username" onchange="validateEmail()"></td>';
echo '<td id="username_status"></td>';
echo '</tr>';
echo '<tr>';
echo '<td>&nbsp;</td>';
echo '<td>&nbsp;</td>';
echo '<td><input type="button" onclick="emailSignup()" value="Login" id="button1"></td>';
echo '</tr>';
echo '<tr>';
echo '<td colspan="4" id="status_message"></td>';
echo '</tr>';
echo '</table>';
echo '</td>';
echo '</form>';
echo '</tr>';
echo '</table>';

?>






