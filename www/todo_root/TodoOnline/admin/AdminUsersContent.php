<h2>Todo Cloud Users</h2>
<table border="0">
	<tr>
		<td>Search (email, first/last name): </td>
		<td><input class="search_term" id="user_search_term" type="text" placeholder="Search email, first/last name..." /></td>
		<td><div class="labeled_control perform_search"><div class="button" id="perform_search_button">Search</div></div></td>
	</tr>
</table>
<hr/>

<div id="user_search_results" class="search_results" />

<script type="text/javascript">
<?php

    $adminLevel = TDOUser::adminLevel($session->getUserId());
    if($adminLevel >= ADMIN_LEVEL_ROOT)
        echo 'var adminIsRoot = true;';
    else
        echo 'var adminIsRoot = false;';

?>
</script>

<script type="text/javascript" src="<?php echo TP_JS_PATH_GIFT_CODE_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_USER_FUNCTIONS; ?>"></script>

