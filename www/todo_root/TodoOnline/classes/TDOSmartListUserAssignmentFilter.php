<?php

include_once('TodoOnline/base_sdk.php');

define("SMART_LIST_ALL_USER", "4BD35E04-8885-4546-8AC3-A42CCDCCEALL");
define("SMART_LIST_UNASSIGNED_USER", "142F63FA-F450-4F0E-A5E4-E0UNASSIGNED");
define("SMART_LIST_ME_USER", "ME");

/**
 *
 */
class TDOSmartListUserAssignmentFilter extends TDOSmartListFilter
{
  protected $_userIDs;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "UserAssignmentFilter";

    $this->_userIDs = NULL;

    if (!empty($dictionary)) {
      $this->_userIDs = $dictionary[SMART_LIST_ASSIGNMENT_KEY];
    }
  }

  public function dictionaryRepresentation()
  {
    if ($this->_userIDs = NULL || count($this->_userIDs) == 0) {
      return NULL;
    }

    $dictionary = array(SMART_LIST_ASSIGNMENT_KEY => $this->_starred);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    $sql = "";

    if ($this->_userIDs != NULL && count($this->_userIDs) > 0) {
      $sql .= "(";

      $idx = 0;
      foreach($this->_userIDs as $userID) {
        if ($idx > 0) {
          $sql .= " OR ";
        }

        if ($userID == SMART_LIST_ALL_USER) {
          // Assigned to anyone (not a null/empty string)
          $sql .= "assigned_userid IS NOT NULL AND assigned_userid <> '' ";
        } else if ($userID == SMART_LIST_UNASSIGNED_USER) {
          $sql .= "assigned_userid IS NULL OR assigned_userid = '' ";
        } else if ($userID == SMART_LIST_ME_USER) {
          $session = TDOSession::getInstance();
          $meUserID = $session->getUserId();
          $sql .= "assigned_userid = '" . $meUserID . "'";
        } else {
          $sql .= "assigned_userid = '" . $userID . "'";
        }

        $idx++;
      }

      $sql .= ")";
    }
    return $sql;
  }
}


?>
