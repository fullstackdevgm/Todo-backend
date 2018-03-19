<?php

include_once('TodoOnline/base_sdk.php');

class TDOSmartListFilter
{
  protected $_filterName;

  public function __construct($dictionary)
  {
    $this->_filterName = "Unknown";
  }

  public function filterName()
  {
    return $this->_filterName;
  }

  public function dictionaryRepresentation()
  {
    return array();
  }

  public function buildSQLFilter()
  {
    return "taskid IS NOT NULL";
  }

  public static function smartListFilterForDictionary($dictionary)
  {
    if (empty($dictionary)) {
      return NULL;
    }

    if (count($dictionary) == 0) {
      return NULL;
    }

    $key = key($dictionary);
    if (empty($key)) {
      return NULL;
    }

    $filter = NULL;

    if ($key == SMART_LIST_TAGS_KEY) {
      $filter = new TDOSmartListTagFilter($dictionary);
    } else if ($key == SMART_LIST_PRIORITY_KEY) {
      $filter = new TDOSmartListPriorityFilter($dictionary);
    } else if ($key == SMART_LIST_STARRED_KEY) {
      $filter = new TDOSmartListStarredFilter($dictionary);
    } else if ($key == SMART_LIST_ASSIGNMENT_KEY) {
      $filter = new TDOSmartListUserAssignmentFilter($dictionary);
    } else if ($key == SMART_LIST_HAS_RECURRENCE_KEY) {
      $filter = new TDOSmartListRecurrenceFilter($dictionary);
    } else if ($key == SMART_LIST_HAS_LOCATION_KEY) {
      $filter = new TDOSmartListLocationFilter($dictionary);
    } else if ($key == SMART_LIST_ACTION_TYPE_KEY) {
      $filter = new TDOSmartListTaskActionFilter($dictionary);
    } else if ($key == SMART_LIST_NAME_KEY) {
      $filter = new TDOSmartListNameFilter($dictionary);
    } else if ($key == SMART_LIST_NOTE_KEY) {
      $filter = new TDOSmartListNoteFilter($dictionary);
    } else if ($key == SMART_LIST_TASK_TYPE_KEY) {
      $filter = new TDOSmartListTaskTypeFilter($dictionary);
    } else if ($key == SMART_LIST_DUE_DATE_KEY) {
      $filter = new TDOSmartListDueDateFilter($dictionary);
    } else if ($key == SMART_LIST_START_DATE_KEY) {
      $filter = new TDOSmartListStartDateFilter($dictionary);
    } else if ($key == SMART_LIST_MODIFIED_DATE_KEY) {
      $filter = new TDOSmartListModifiedDateFilter($dictionary);
    } else if ($key == SMART_LIST_COMPLETED_DATE_KEY) {
      $filter = new TDOSmartListCompletedDateFilter($dictionary);
    }

    return $filter;
  }
}


?>
