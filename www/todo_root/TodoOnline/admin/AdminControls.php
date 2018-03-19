<div class="control_container">
<!--LOGO-->
<!--<div id="LOGO">
<a href="."><h2 style="color:white">Todo Cloud</h2></a>           	
</div> 
-->
<!--Admin-->
<div class="breath-20"></div>
<ul class="control_group">
<li><div class="group_title">Admin</div></li>

<?php
	$adminLevel = TDOUser::adminLevel($session->getUserId());
	if ( ($session->isLoggedIn()) && ($adminLevel >= ADMIN_LEVEL_ROOT) )
	{
?>

<!--		<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=system"><div class="option_name">System</div></a></li>-->
<!--        <li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=systemstats"><div class="option_name">Today Stats</div></a></li>-->
        <li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=systemnotification"><div class="option_name">System Notification</div></a></li>
		<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=giftcodes"><div class="option_name">Gift Codes</div></a></li>
        <li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=referrals"><div class="option_name">Referrals</div></a></li>
		<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=systemsettings"><div class="option_name">System Settings</div></a></li>
<!--        <li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=messagecenter"><div class="option_name">Message Center</div></a></li>-->

<?php
	}
?>

<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=users"><div class="option_name">Users</div></a></li>
<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=teams"><div class="option_name">Teams</div></a></li>
<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?section=promocodes"><div class="option_name">Promo Codes</div></a></li>

</ul>

<!--Account-->
<ul class="control_group">
<li><div class="group_title">Account</div></li>
<?php
	if($session->isLoggedIn() && !$session->isFB())
	{
		print '<li class="group_option"><span class="option_left_icon"></span><a class="control_link" href="?method=logout"><div class="option_name">Logout</div></a></li>';
	}
	?>
</ul>

</div>