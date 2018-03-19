<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListModifiedDateFilter extends TDOSmartListDateFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, SMART_LIST_MODIFIED_DATE_KEY);
    $this->_filterName = "ModifiedDateFilter";
  }
}

?>
