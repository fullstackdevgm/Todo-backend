<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListDueDateFilter extends TDOSmartListDateFilter
{

  function __construct($dictionary)
  {
    parent::__construct($dictionary, SMART_LIST_DUE_DATE_KEY);
    $this->_filterName = "DueDateFilter";
  }
}

?>
