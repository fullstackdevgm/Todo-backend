<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListPriorityFilter extends TDOSmartListFilter
{
  protected $_noPriority;
  protected $_lowPriority;
  protected $_mediumPriority;
  protected $_highPriority;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "PriorityFilter";

    $this->_noPriority = false;
    $this->_lowPriority = false;
    $this->_mediumPriority = false;
    $this->_highPriority = false;
    if (!empty($dictionary)) {
      $prioritiesA = $dictionary[SMART_LIST_PRIORITY_KEY];
      if (!empty($prioritiesA)) {
        $this->_noPriority = in_array("none", $prioritiesA);
        $this->_lowPriority = in_array("low", $prioritiesA);
        $this->_mediumPriority = in_array("med", $prioritiesA);
        $this->_highPriority = in_array("high", $prioritiesA);
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $prioritiesA = array();
    if ($this->_noPriority) {
      $prioritiesA[] = "none";
    }
    if ($this->_lowPriority) {
      $prioritiesA[] = "low";
    }
    if ($this->_mediumPriority) {
      $prioritiesA[] = "med";
    }
    if ($this->_highPriority) {
      $prioritiesA[] = "high";
    }

    if (count($prioritiesA) == 0) {
      return NULL;
    }

    $dictionary = array(SMART_LIST_PRIORITY_KEY => $prioritiesA);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    if (!$this->_noPriority && !$this->_lowPriority && !$this->_mediumPriority && !$this->_highPriority) {
      return "";
    }

    $sql = "";
    if ($this->_noPriority) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "((task_type != 1 AND priority = 4) OR (task_type = 1 AND project_priority = 4))";
    }
    if ($this->_lowPriority) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "((task_type != 1 AND priority = 3) OR (task_type = 1 AND project_priority = 3))";
    }
    if ($this->_mediumPriority) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "((task_type != 1 AND priority = 2) OR (task_type = 1 AND project_priority = 2))";
    }
    if ($this->_highPriority) {
      if (strlen($sql) > 0) {
        $sql .= " OR ";
      }
      $sql .= "((task_type != 1 AND priority = 1) OR (task_type = 1 AND project_priority = 1))";
    }

    $returnableSQL = "(". $sql . ")";
    return $returnableSQL;
  }
}


?>
