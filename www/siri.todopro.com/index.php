<?php

	//Mapping PHP errors to exceptions
	function exception_error_handler($errno, $errstr, $errfile, $errline )
	{
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	set_error_handler("exception_error_handler");

	// Files we need
	require_once 'Sabre/autoload.php';
	require_once 'TodoOnline/classes/TDODavPrincipal.php';
	require_once 'TodoOnline/classes/TDOCalDavBackend.php';
//	require_once 'TodoOnline/classes/TDOCalDavCalendarRootNode.php';
	require_once 'TodoOnline/classes/TDODavBasicAuth.php';

	$baseUri = '/';
	//$baseUri = '/~calvin/caldav/index.php';

	// settings
	date_default_timezone_set('Canada/Mountain');

	// Backends
	$authBackend = new TDODavBasicAuth();
	$principalBackend = new TDODavPrincipal();
	$calendarBackend = new TDOCalDavBackend();
	
	// Directory tree
	$tree = array(
				  new Sabre_DAVACL_PrincipalCollection($principalBackend),
//				  new TDOCalDavCalendarRootNode($principalBackend, $calendarBackend)
				  new Sabre_CalDAV_CalendarRootNode($principalBackend, $calendarBackend)
				  );


	// The object tree needs in turn to be passed to the server class
	$server = new Sabre_DAV_Server($tree);

	// You are highly encouraged to set your WebDAV server base url. Without it,
	// SabreDAV will guess, but the guess is not always correct. Putting the
	// server on the root of the domain will improve compatibility.
	$server->setBaseUri($baseUri);

	// Authentication plugin
	$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
	$server->addPlugin($authPlugin);

	// CalDAV plugin
	$caldavPlugin = new Sabre_CalDAV_Plugin();
	$server->addPlugin($caldavPlugin);

	// ACL plugin
	$aclPlugin = new Sabre_DAVACL_Plugin();
	$server->addPlugin($aclPlugin);

	// Support for html frontend
	$browser = new Sabre_DAV_Browser_Plugin();
	$server->addPlugin($browser);

	// And off we go!
	$server->exec();

?>
