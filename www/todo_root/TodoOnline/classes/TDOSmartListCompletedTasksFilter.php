<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListCompletedTasksFilter
{
  protected $_type;
  protected $_completedTasksPeriod;

  function __construct($dictionary)
  {
    $this->_type = SMART_LIST_COMPLETED_TASKS_FILTER_TYPE_ALL;
    $this->_completedTasksPeriod = SMART_LIST_COMPLETED_TASKS_PERIOD_NONE;

    if (!empty($dictionary)) {
      if (isset($dictionary[SMART_LIST_TYPE_KEY])) {
        $type = $dictionary[SMART_LIST_TYPE_KEY];
        $this->_type = $type;
      }

      if (isset($dictionary[SMART_LIST_PERIOD_KEY])) {
        $period = $dictionary[SMART_LIST_PERIOD_KEY];
        $this->_completedTasksPeriod = $period;
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $dictionary = array(
      SMART_LIST_TYPE_KEY => $this->_type,
      SMART_LIST_PERIOD_KEY => $this->_completedTasksPeriod
    );
    return $dictionary;
  }
}


?>
