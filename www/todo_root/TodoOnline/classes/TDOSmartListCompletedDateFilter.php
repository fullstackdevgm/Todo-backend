<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListCompletedDateFilter extends TDOSmartListDateFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, SMART_LIST_COMPLETED_DATE_KEY);
    $this->_filterName = "CompletedDateFilter";
  }
}

?>
