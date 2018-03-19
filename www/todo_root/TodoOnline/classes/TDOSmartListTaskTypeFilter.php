<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListTaskTypeFilter extends TDOSmartListFilter
{
  protected $_normal;
  protected $_project;
  protected $_checklist;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "TaskTypeFilter";

    $this->_normal = false;
    $this->_project = false;
    $this->_checklist = false;

    if (!empty($dictionary)) {
      $typesA = $dictionary[SMART_LIST_TASK_TYPE_KEY];
      if (!empty($typesA)) {
        $this->_normal = in_array("normal", $typesA);
        $this->_project = in_array("project", $typesA);
        $this->_checklist = in_array("checklist", $typesA);
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $typesA = array();
    if ($this->_normal) {
      $typesA[] = "normal";
    }
    if ($this->_project) {
      $typesA[] = "project";
    }
    if ($this->_checklist) {
      $typesA[] = "checklist";
    }
    if (count($typesA) == 0) {
      return NULL;
    }

    $dictionary = array(SMART_LIST_TASK_TYPE_KEY => $typesA);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    $sql = "";
    if (!$this->_normal && !$this->_project && !$this->_checklist) {
      return "";
    }

    if ($this->_normal) {
      $sql .= "task_type <> " . SMART_LIST_TASK_TYPE_PROJECT . " AND task_type <> " . SMART_LIST_TASK_TYPE_CHECKLIST;
    }

    if ($this->_project) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "task_type = " . SMART_LIST_TASK_TYPE_PROJECT;
    }

    if ($this->_checklist) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "task_type = " . SMART_LIST_TASK_TYPE_CHECKLIST;
    }

    $sql = "(" . $sql . ")";
    return $sql;
  }
}


?>
