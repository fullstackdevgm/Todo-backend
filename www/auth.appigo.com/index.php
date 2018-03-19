<?php

if(isset($_GET['method']) || isset($_POST['method']))
{
    error_log("Method found calling authMethodHandler");
    include_once('authMethodHandler.php');
}
else
{
    error_log("Method not found calling page loader");
    include_once('authPageLoader.php');
}

?>






