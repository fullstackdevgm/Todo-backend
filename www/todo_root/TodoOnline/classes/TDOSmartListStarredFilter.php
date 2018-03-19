<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListStarredFilter extends TDOSmartListFilter
{
  protected $_starred;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "StarredFilter";

    $this->_starred = false;

    if (!empty($dictionary)) {
      $value = $dictionary[SMART_LIST_STARRED_KEY];
      if (!empty($value) && $value > 0) {
        $this->_starred = true;
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $dictionary = array(SMART_LIST_STARRED_KEY => $this->_starred);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    $sql = "";
    if ($this->_starred) {
      $sql .= "((task_type != 1 AND starred != 0) OR (task_type = 1 AND project_starred != 0))";
    } else {
      $sql .= "((task_type != 1 AND (starred IS NULL OR starred = 0)) OR (task_type = 1 AND (project_starred IS NULL OR project_starred = 0)))";
    }
    return $sql;
  }
}


?>
