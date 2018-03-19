<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListRecurrenceFilter extends TDOSmartListFilter
{
  protected $_hasRecurrence;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "RecurrenceFilter";

    $this->_hasRecurrence = false;

    if (!empty($dictionary)) {
      $value = $dictionary[SMART_LIST_HAS_RECURRENCE_KEY];
      if (!empty($value) && $value > 0) {
        $this->_hasRecurrence = true;
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $dictionary = array(SMART_LIST_HAS_RECURRENCE_KEY => $this->_hasRecurrence);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    $sql = "";
    if ($this->_hasRecurrence) {
      $sql .= "(recurrence_type > 0)";
    } else {
      $sql .= "(recurrence_type = 0)";
    }
    return $sql;
  }
}


?>
