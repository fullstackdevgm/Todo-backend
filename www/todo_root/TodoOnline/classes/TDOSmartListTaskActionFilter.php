<?php

include_once('TodoOnline/base_sdk.php');

define("SMART_LIST_TASK_TYPE_NORMAL", 0);
define("SMART_LIST_TASK_TYPE_PROJECT", 1);
define("SMART_LIST_TASK_TYPE_CALL_CONTACT", 2);
define("SMART_LIST_TASK_TYPE_SMS_CONTACT", 3);
define("SMART_LIST_TASK_TYPE_EMAIL_CONTACT", 4);
define("SMART_LIST_TASK_TYPE_VISIT_LOCATION", 5);
define("SMART_LIST_TASK_TYPE_URL", 6);
define("SMART_LIST_TASK_TYPE_CHECKLIST", 7);
define("SMART_LIST_TASK_TYPE_CUSTOM", 8);
define("SMART_LIST_TASK_TYPE_INTERNAL", 9);

class TDOSmartListTaskActionFilter extends TDOSmartListFilter
{
  protected $_none;
  protected $_contact;
  protected $_location;
  protected $_url;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "TaskActionFilter";

    $this->_none = false;
    $this->_contact = false;
    $this->_location = false;
    $this->_url = false;

    if (!empty($dictionary)) {
      $actionsA = $dictionary[SMART_LIST_ACTION_TYPE_KEY];
      if (!empty($actionsA)) {
        $this->_none = in_array("none", $actionsA);
        $this->_contact = in_array("contact", $actionsA);
        $this->_location = in_array("location", $actionsA);
        $this->_url = in_array("url", $actionsA);
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $actionsA = array();

    if ($this->_none) {
      $actionsA[] = "none";
    }
    if ($this->_contact) {
      $actionsA[] = "contact";
    }
    if ($this->_location) {
      $actionsA[] = "location";
    }
    if ($this->_url) {
      $actionsA[] = "url";
    }

    $dictionary = array(SMART_LIST_ACTION_TYPE_KEY => $actionsA);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    if (!$this->_none && !$this->_contact && !$this->_location && !$this->_url) {
      return "";
    }

    $sql = "(";

    if ($this->_none) {
      $sql .= "task_type != " . SMART_LIST_TASK_TYPE_CALL_CONTACT . " AND task_type != " . SMART_LIST_TASK_TYPE_SMS_CONTACT . " AND task_type != " . SMART_LIST_TASK_TYPE_EMAIL_CONTACT . " AND task_type != " . SMART_LIST_TASK_TYPE_VISIT_LOCATION . " AND task_type != " . SMART_LIST_TASK_TYPE_URL;
    }

    if ($this->_contact) {
      if (strlen($sql) > 1) { // 1 because it's accounting for the initial '('
        $sql .= " OR ";
      }
      $sql .= "task_type = " . SMART_LIST_TASK_TYPE_CALL_CONTACT . " OR task_type = " . SMART_LIST_TASK_TYPE_SMS_CONTACT . " OR task_type = " . SMART_LIST_TASK_TYPE_EMAIL_CONTACT;
    }

    if ($this->_location) {
      if (strlen($sql) > 1) { // 1 because it's accounting for the initial '('
        $sql .= " OR ";
      }
      $sql .= "task_type = " . SMART_LIST_TASK_TYPE_VISIT_LOCATION;
    }

    if ($this->_url) {
      if (strlen($sql) > 1) { // 1 because it's accounting for the initial '('
        $sql .= " OR ";
      }
      $sql .= "task_type = " . SMART_LIST_TASK_TYPE_URL;
    }

    $sql .= ")";
    return $sql;
  }
}


?>
