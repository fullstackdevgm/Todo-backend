<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListNoteFilter extends TDOSmartListTextSearchFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, "note");
    $this->_filterName = "NoteFilter";
  }
}

?>
