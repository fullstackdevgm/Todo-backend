<?php
//  TDORestAdapter
//  This is a utility class used when first servicing a request from a client
//  call. If the client is submitting POST/PUT data in JSON format, we want to
//  convert it to PHP $_POST parameters so the rest of our service will work
//  with minimal changes.

class TDORESTAdapter
{
  public static function adaptJSONPostIfNeeded()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
      // ONLY attempt to convert the POST/PUT variables to JSON *IF* the
      // Accept Header has "application/json" as one of the types. This _should_
      // allow the REST clients to authenticate correctly with JWT but also allow
      // the CalDAV clients to keep working as well. We had a problem where this
      // was munching the PUT data from CalDAV and preventing our siri.todo-cloud.com
      // interface from functioning.
      $acceptHeader = $_SERVER['HTTP_ACCEPT'];
      if ($acceptHeader && strstr(strtolower($acceptHeader), "application/json")) {
        TDORESTAdapter::convertJSONPostToVariables();
      }
    }
  }

  public static function convertJSONPostToVariables()
  {
    $inputJSON = file_get_contents('php://input');
		if (empty($inputJSON)) {
			return;
		}
		$input = json_decode($inputJSON);
		if (empty($input)) {
			return;
		}

		foreach($input as $key => $value) {
			$_POST[$key] = $value;
		}
  }
}


?>
