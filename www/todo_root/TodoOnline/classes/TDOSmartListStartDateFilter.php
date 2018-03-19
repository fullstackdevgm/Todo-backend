<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListStartDateFilter extends TDOSmartListDateFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, SMART_LIST_START_DATE_KEY);
    $this->_filterName = "StartDateFilter";
  }
}

?>
