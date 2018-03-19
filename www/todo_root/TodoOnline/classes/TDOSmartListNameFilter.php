<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListNameFilter extends TDOSmartListTextSearchFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, "name");
    $this->_filterName = "NameFilter";
  }
}

?>
