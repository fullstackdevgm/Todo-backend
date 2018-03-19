<?php
//      TDOSmartList

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');

/*
Adapting this from the Objective-C code, there are a couple things to note.
Normally, TDODBObjects store ALL of their values in an array kept by the super
class. In the case of a smart list, a lot of the properties are tied to the
JSON filter string. Since the Objective-C counterpart of this class stores those
properties off in a separate dictionary (array), we'll do the same here. The
dictionary is really only for convenience and it won't make a difference to the
consumers of this class since all the information is accessed through property
functions anyway.
*/

define ('SMART_LIST_NAME_LENGTH', 72);

define("SMART_LIST_MAX_FILTER_GROUP_COUNT", 2);
define("SMART_LIST_MAX_SEARCH_TERMS_PER_FILTER", 3);

define("SMART_LIST_EXCLUDE_LISTS_KEY", "excludeLists");
define("SMART_LIST_COMPLETED_TASKS_KEY", "completedTasks");
define("SMART_LIST_TYPE_KEY", "type");
define("SMART_LIST_PERIOD_KEY", "period");
define("SMART_LIST_FILTER_GROUPS_KEY", "filterGroups");

define("SMART_LIST_TAGS_KEY", "tags");
define("SMART_LIST_PRIORITY_KEY", "priority");
define("SMART_LIST_STARRED_KEY", "starred");
define("SMART_LIST_ASSIGNMENT_KEY", "assignment");
define("SMART_LIST_HAS_RECURRENCE_KEY", "hasRecurrence");
define("SMART_LIST_HAS_LOCATION_KEY", "hasLocation");
define("SMART_LIST_ACTION_TYPE_KEY", "actionType");
define("SMART_LIST_NAME_KEY", "name");
define("SMART_LIST_NOTE_KEY", "note");
define("SMART_LIST_TASK_TYPE_KEY", "taskType");
define("SMART_LIST_DUE_DATE_KEY", "dueDate");
define("SMART_LIST_START_DATE_KEY", "startDate");
define("SMART_LIST_MODIFIED_DATE_KEY", "modifiedDate");
define("SMART_LIST_COMPLETED_DATE_KEY", "completedDate");

define("SMART_LIST_SORT_TYPE_KEY", "sortType");
define("SMART_LIST_DEFAULT_DUE_DATE_KEY", "defaultDueDate");
define("SMART_LIST_DEFAULT_LIST_KEY", "defaultList");
define("SMART_LIST_SHOW_LIST_FOR_TASKS_KEY", "showListForTasks");
define("SMART_LIST_SHOW_SUBTASKS_KEY", "showSubtasks");
define("SMART_LIST_EXCLUDE_START_DATES_KEY", "excludeStartDates");

define("SMART_LIST_COMPARATOR_KEY", "comparator");
define("SMART_LIST_COMPARATOR_OR", "or");
define("SMART_LIST_COMPARATOR_AND", "and");

define("SMART_LIST_SEARCH_TERMS_KEY", "searchTerms");
define("SMART_LIST_CONTAINS_KEY", "contains");
define("SMART_LIST_TEXT_KEY", "text");

define("SMART_LIST_DATE_TYPE_KEY", "type");
define("SMART_LIST_DATE_TYPE_VALUE_NONE", "none");
define("SMART_LIST_DATE_TYPE_VALUE_ANY", "any");
define("SMART_LIST_DATE_TYPE_VALUE_IS", "is");
define("SMART_LIST_DATE_TYPE_VALUE_NOT", "not");
define("SMART_LIST_DATE_TYPE_VALUE_AFTER", "after");
define("SMART_LIST_DATE_TYPE_VALUE_BEFORE", "before");

define("SMART_LIST_DATE_RELATION_KEY", "relation");
define("SMART_LIST_DATE_RELATION_VALUE_EXACT", "exact");
define("SMART_LIST_DATE_RELATION_VALUE_RELATIVE", "relative");

define("SMART_LIST_DATE_KEY", "date");
define("SMART_LIST_DATE_RANGE_KEY", "dateRange");
define("SMART_LIST_DATE_RANGE_START_KEY", "start");
define("SMART_LIST_DATE_RANGE_END_KEY", "end");

define("SMART_LIST_DATE_INTERVAL_RANGE_START_KEY", "intervalRangeStart");
define("SMART_LIST_DATE_INTERVAL_RANGE_END_KEY", "intervalRangeEnd");

define("SMART_LIST_DATE_INTERVAL_PERIOD_KEY", "period");
define("SMART_LIST_DATE_INTERVAL_PERIOD_DAY", "day");
define("SMART_LIST_DATE_INTERVAL_PERIOD_WEEK", "week");
define("SMART_LIST_DATE_INTERVAL_PERIOD_MONTH", "month");
define("SMART_LIST_DATE_INTERVAL_PERIOD_YEAR", "year");

define("SMART_LIST_DATE_PERIOD_KEY", "period");
define("SMART_LIST_DATE_PERIOD_VALUE_KEY", "value");

define("SMART_LIST_COMPLETED_TASKS_FILTER_TYPE_ALL", "all");
define("SMART_LIST_COMPLETED_TASKS_FILTER_TYPE_ACTIVE", "active");
define("SMART_LIST_COMPLETED_TASKS_FILTER_TYPE_COMPLETED", "completed");

define("SMART_LIST_COMPLETED_TASKS_PERIOD_NONE", "none");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_ONE_DAY", "1day");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_TWO_DAYS", "2days");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_THREE_DAYS", "3days");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_ONE_WEEK", "1week");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_TWO_WEEKS", "2weeks");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_ONE_MONTH", "1month");
define("SMART_LIST_COMPLETED_TASKS_PERIOD_ONE_YEAR", "1year");

class TDOSmartList extends TDODBObject
{
  protected $_initialized;
  protected $_dictionary;
  protected $_filterGroups;

    public function __construct()
    {
        parent::__construct();

        $this->set_to_default();
        $this->_dictionary = array();
        $this->_initialized = true;
    }

    public function set_to_default()
    {
        parent::set_to_default();

    }

    // ------------------------
    // property Methods
    // ------------------------

	/*
	 Properties:
	 listId (tmpListId)
	 userId
	 listName
	 color
	 iconName
	 sortOrder
	 jsonFilter
	 sortType
	 defaultDueDate
	 defaultList
	 excludedListIDs
	 completedTasksFilter
	 */

	private function _propertyValue($columnName)
	{
		if(empty($this->_publicPropertyArray[$columnName]))
			return NULL;
		else
			return $this->_publicPropertyArray[$columnName];
	}
	private function _setPropertyValue($columnName, $val)
	{
		if(empty($val))
			unset($this->_publicPropertyArray[$columnName]);
		else
			$this->_publicPropertyArray[$columnName] = $val;
	}


	public function listId()
	{
		return $this->_propertyValue('listid');
	}
	public function setListId($val)
	{
		$this->_setPropertyValue('listid', $val);
	}

	public function userId()
	{
		return $this->_propertyValue('userid');
	}
	public function setUserId($val)
	{
		$this->_setPropertyValue('userid', $val);
	}

	public function color()
	{
		return $this->_propertyValue('color');
	}
	public function setColor($val)
	{
		$this->_setPropertyValue('color', $val);
	}

    public function iconName()
    {
		return $this->_propertyValue('icon_name');
    }
    public function setIconName($val)
    {
		$this->_setPropertyValue('icon_name', $val);
    }

	public function sortOrder()
	{
		return $this->_propertyValue('sort_order');
	}
	public function setSortOrder($val)
	{
		$this->_setPropertyValue('sort_order', $val);
	}

	public function jsonFilter()
	{
    // Convert $this->_dictionary (an array) to a JSON string
    $jsonString = json_encode($this->_dictionary);
    return $jsonString;
	}
	public function setJsonFilter($val)
	{
    // Build a new dictionary (an associative array)
    $jsonDictionary = json_decode($val, true);
    if (!empty($jsonDictionary)) {
      $this->_dictionary = $jsonDictionary;
    } else {
      $this->_dictionary = array(); // empty array
    }
	}

	public function sortType()
	{
    if (!isset($this->_dictionary[SMART_LIST_SORT_TYPE_KEY])) {
      return -1;
    }
    $sortTypeNumber = $this->_dictionary[SMART_LIST_SORT_TYPE_KEY];
    return intval($sortTypeNumber);
	}
	public function setSortType($val)
	{
    $sortTypeNumber = intval($val);
    if ($sortTypeNumber < 0) {
      if (isset($this->_dictionary[SMART_LIST_SORT_TYPE_KEY])) {
        unset($this->_dictionary[SMART_LIST_SORT_TYPE_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $this->_dictionary[SMART_LIST_SORT_TYPE_KEY] = $sortTypeNumber;
    }
	}

	public function defaultDueDate()
	{
    if (!isset($this->_dictionary[SMART_LIST_DEFAULT_DUE_DATE_KEY])) {
      return -1;
    }
    $defaultDueDateNumber = $this->_dictionary[SMART_LIST_DEFAULT_DUE_DATE_KEY];
    return intval($defaultDueDateNumber);
	}
	public function setDefaultDueDate($val)
	{
    if ($val < 0) {
      if (isset($this->_dictionary[SMART_LIST_DEFAULT_DUE_DATE_KEY])) {
        unset($this->_dictionary[SMART_LIST_DEFAULT_DUE_DATE_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $this->_dictionary[SMART_LIST_DEFAULT_DUE_DATE_KEY] = intval($val);
    }
	}

	public function defaultList()
	{
    if (!isset($this->_dictionary[SMART_LIST_DEFAULT_LIST_KEY])) {
      return NULL;
    }
    return $this->_dictionary[SMART_LIST_DEFAULT_LIST_KEY];
	}
	public function setDefaultList($val)
	{
    if (empty($val)) {
      if (isset($this->_dictionary[SMART_LIST_DEFAULT_LIST_KEY])) {
        unset($this->_dictionary[SMART_LIST_DEFAULT_LIST_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $this->_dictionary[SMART_LIST_DEFAULT_LIST_KEY] = $val;
    }
	}

  public function showSubtasks()
  {
    if (!isset($this->_dictionary[SMART_LIST_SHOW_SUBTASKS_KEY])) {
      return false;
    }
    $showSubtasks = $this->_dictionary[SMART_LIST_SHOW_SUBTASKS_KEY];
    if ($showSubtasks == true) {
      return true;
    }

    return false;
  }
  public function setShowSubtasks($val)
  {
    $this->_dictionary[SMART_LIST_SHOW_SUBTASKS_KEY] = $val == true;
  }

  public function excludeStartDates()
  {
    if (!isset($this->_dictionary[SMART_LIST_EXCLUDE_START_DATES_KEY])) {
      return false;
    }

    $excludeStartDates = $this->_dictionary[SMART_LIST_EXCLUDE_START_DATES_KEY];
    if ($excludeStartDates == true) {
      return true;
    }

    return false;
  }
  public function setExcludeStartDates($val)
  {
    $this->_dictionary[SMART_LIST_EXCLUDE_START_DATES_KEY] = $val == true;
  }

  public function showListForTasks()
  {
    if (!isset($this->_dictionary[SMART_LIST_SHOW_LIST_FOR_TASKS_KEY])) {
      return false;
    }
    $showListForTasks = $this->_dictionary[SMART_LIST_SHOW_LIST_FOR_TASKS_KEY];
    if ($showListForTasks == true) {
      return true;
    }

    return false;
  }
  public function setShowListForTasks($val)
  {
    $this->_dictionary[SMART_LIST_SHOW_LIST_FOR_TASKS_KEY] = ($val == true);
  }

	public function excludedListIDs()
	{
    if (!isset($this->_dictionary[SMART_LIST_EXCLUDE_LISTS_KEY])) {
      return array();
    }
    $listIDsA = $this->_dictionary[SMART_LIST_EXCLUDE_LISTS_KEY];
    if (!empty($listIDsA)) {
      return $listIDsA;
    } else {
      return array();
    }
	}
	public function setExcludedListIDs($val)
	{
    if (empty($val)) {
      if (isset($this->_dictionary[SMART_LIST_EXCLUDE_LISTS_KEY])) {
        unset($this->_dictionary[SMART_LIST_EXCLUDE_LISTS_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $this->_dictionary[SMART_LIST_EXCLUDE_LISTS_KEY] = $val;
    }
	}

	public function completedTasksFilter()
	{
    if (!isset($this->_dictionary[SMART_LIST_COMPLETED_TASKS_KEY])) {
      return NULL;
    }
    $dictionary = $this->_dictionary[SMART_LIST_COMPLETED_TASKS_KEY];
    $filter = new TDOSmartListCompletedTasksFilter($dictionary);
    return $filter;
	}
	public function setCompletedTasksFilter($val)
	{
    if (empty($val) || !is_a($val, "TDOSmartListCompletedTasksFilter")) {
      if (isset($this->_dictionary[SMART_LIST_COMPLETED_TASKS_KEY])) {
        unset($this->_dictionary[SMART_LIST_COMPLETED_TASKS_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $filterD = $val->dictionaryRepresentation();
      $this->_dictionary[SMART_LIST_COMPLETED_TASKS_KEY] = $filterD;
    }
	}

    // If we're passing in a link, it better already have a trasnsaction in place
	public static function deleteSmartList($listid, $link=NULL)
	{
        if(!isset($listid))
            return false;

		$closeDBLink = false;
        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList failed to get dblink");
                return false;
            }

            // Do all of this in a transaction so we won't end up with a partially deleted smart list
            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOSmartList::Couldn't start transaction".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeDBLink = false;

        $escapedListId = mysql_real_escape_string($listid, $link);

        //Delete the list
        if(!mysql_query("UPDATE tdo_smart_lists SET deleted=1, timestamp='" . time() . "' WHERE listid='$escapedListId'", $link))
        {
            error_log("TDOSmartList::Could not delete smart list, rolling back".mysql_error());
            if($closeDBLink)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if($closeDBLink)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOSmartList::Couldn't commit transaction".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }

        return true;
	}

    public static function permanentlyDeleteSmartList($listid, $link=NULL)
    {
        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOSmartList failed to get db link");
                return false;
            }

            if(!mysql_query("START TRANSACTION", $link))
            {
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;

        $escapedListId = mysql_real_escape_string($listid, $link);

        // Completely wipe out the smart list

        //Wipe out all change log items for the list
        if(TDOChangeLog::permanentlyDeleteAllChangeLogsForList($listid, $link) == false)
        {
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if(!mysql_query("DELETE FROM tdo_smart_lists WHERE listid='$escapedListId'", $link))
        {
            error_log("TDOSmartList::Could not delete smart list, rolling back ".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
            return false;
        }

        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOSmartList::Couldn't commit transaction ".mysql_error());
                mysql_query("ROLLBACK");
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }

        return true;
    }

	public function addSmartList($userid, $link=NULL)
	{
        if(empty($userid))
            return false;

		if($this->name() == NULL)
		{
			error_log("TDOSmartList::addSmartList() failed because name was not set");
			return false;
		}

        if(empty($link))
        {
            $closeTransaction = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList::addSmartList() failed to get dblink");
                return false;
            }

            if(!mysql_query("START TRANSACTION", $link))
            {
                error_log("TDOSmartList::addSmartList() failed to start transaction");
                TDOUtil::closeDBLink($link);
                return false;
            }
        }
        else
            $closeTransaction = false;

        $listId = $this->listId();
        if($listId == NULL)
        {
            $listId = TDOUtil::uuid();
            $this->setListId($listId);
        }

		$userid = mysql_real_escape_string($userid, $link);

        $name = mb_strcut($this->name(), 0, SMART_LIST_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);

		$color = NULL;
		if ($this->color()) {
			$color = mysql_real_escape_string($this->color(), $link);
		}

		$iconName = NULL;
		if ($this->iconName()) {
			$iconName = mysql_real_escape_string($this->iconName(), $link);
		}

		$sortOrder = intval($this->sortOrder());

		$jsonFilter = NULL;
		if ($this->jsonFilter()) {
			$jsonFilter = mysql_real_escape_string($this->jsonFilter(), $link);
		}

		$sortType = intval($this->sortType());
		$defaultDueDate = intval($this->defaultDueDate());

		$defaultList = NULL;
		if ($this->defaultList()) {
			$defaultList = mysql_real_escape_string($this->defaultList(), $link);
		}

		$excludedListIDs = NULL;
		if ($this->excludedListIDs()) {
      // Convert the array to a comma-separated string
      $listIDs = implode(',', $this->excludedListIDs());
			$excludedListIDs = mysql_real_escape_string($listIDs, $link);
		}

		$completedTasksFilter = NULL;
		if ($this->completedTasksFilter()) {
      $json = json_encode($this->completedTasksFilter()->dictionaryRepresentation());
      if (!empty($json)) {
        $completedTasksFilter = mysql_real_escape_string($json, $link);
      } else {
        $completedTasksFilter = "";
      }
		}

        if ($this->timestamp() == 0)
            $timestamp = time();
        else
            $timestamp = intval($this->timestamp());

        $deleted = intval($this->deleted());

		// Create the smart list
		$sql = "INSERT INTO tdo_smart_lists (listid, userid, name, color, icon_name, sort_order, json_filter, sort_type, default_due_date, default_list, excluded_list_ids, completed_tasks_filter, deleted, timestamp) VALUES ('$listId', '$userid', '$name', '$color', '$iconName', $sortOrder, '$jsonFilter', $sortType, $defaultDueDate, '$defaultList', '$excludedListIDs', '$completedTasksFilter', $deleted, $timestamp)";
		$result = mysql_query($sql, $link);
		if(!$result)
		{
			error_log("Failed to add smart list with error :".mysql_error());
            if($closeTransaction)
            {
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
            }
			return false;
		}

        if($closeTransaction)
        {
            if(!mysql_query("COMMIT", $link))
            {
                error_log("TDOSmartList::addSmartList() failed to commit transaction");
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }
            else
                TDOUtil::closeDBLink($link);
        }

		return true;
	}

	public function updateSmartList($userid, $link=NULL)
	{

		if($this->listId() == NULL || empty($userid))
		{
			error_log("Update failed: listid or UserID was emtpy");
			return false;
		}


        if(!$link)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList::updateSmartList() failed to get dblink");
                return false;
            }
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }

		$updateString = "";

        $listid = $this->listId();

		if($this->name() != NULL)
        {
            $name = mb_strcut($this->name(), 0, SMART_LIST_NAME_LENGTH, 'UTF-8');
			$name = mysql_real_escape_string($name, $link);

            if (strlen($updateString) > 0)
				$updateString .= ", ";

			$updateString = $updateString . " name='$name'";
        }

		if ($this->color() != NULL) {
			$color = mysql_real_escape_string($this->color());
			if (strlen($updateString) > 0) {
				$updateString .= ", ";
			}

			$updateString = $updateString . " color='$color'";
		}

		if ($this->iconName() != NULL) {
			$iconName = mysql_real_escape_string($this->iconName());
			if (strlen($updateString) > 0)
				$updateString .= ", ";

			$updateString = $updateString . " icon_name='$iconName'";
		}

		$sortOrder = intval($this->sortOrder());
		if (strlen($updateString) > 0)
			$updateString .= ", ";

		$updateString = $updateString . " sort_order=$sortOrder";

		if ($this->jsonFilter()) {
			if (strlen($updateString) > 0)
				$updateString .= ", ";

			$jsonFilter = mysql_real_escape_string($this->jsonFilter(), $link);
			$updateString = $updateString . " json_filter='$jsonFilter'";
		}

		if (strlen($updateString) > 0)
			$updateString .= ", ";

		$sortType = intval($this->sortType());
		$updateString = $updateString . " sort_type=$sortType";

		if (strlen($updateString) > 0)
			$updateString .= ", ";

		$defaultDueDate = intval($this->defaultDueDate());
		$updateString = $updateString . " default_due_date=$defaultDueDate";

		if ($this->defaultList()) {
			if (strlen($updateString) > 0)
				$updateString .= ", ";

			$defaultList = mysql_real_escape_string($this->defaultList(), $link);
			$updateString = $updateString . " default_list='$defaultList'";
		}

		if (strlen($updateString) > 0)
			$updateString .= ", ";

		if ($this->excludedListIDs()) {
      // Convert the array to a comma-separated string
      $listIDs = implode(',', $this->excludedListIDs());
			$excludedListIDs = mysql_real_escape_string($listIDs, $link);
			$updateString = $updateString . " excluded_list_ids='$excludedListIDs'";
		} else {
			$updateString = $updateString . " excluded_list_ids=''";
		}

		if ($this->completedTasksFilter()) {
			if (strlen($updateString) > 0) {
        $updateString .= ", ";
      }

      $completedTasksFilter = "";
      $json = json_encode($this->completedTasksFilter()->dictionaryRepresentation());
      if (!empty($json)) {
        $completedTasksFilter = mysql_real_escape_string($json, $link);
      }

      $updateString = $updateString . " completed_tasks_filter='$completedTasksFilter'";
		}

		if (strlen($updateString) > 0)
			$updateString .= ", ";

        $deleted = intval($this->deleted());
        $updateString .= " deleted=$deleted";

		if (strlen($updateString) > 0)
			$updateString .= ", ";

		$updateString .= " tdo_smart_lists.timestamp='" . time() . "' ";

        $sql = "UPDATE tdo_smart_lists SET " . $updateString . " WHERE tdo_smart_lists.listid='$listid'";
        $response = mysql_query($sql, $link);
        if($response)
        {
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);

            return true;
        }
        else
        {
			error_log("Unable to update smart list $listid :".mysql_error());
            if($shouldCloseLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

	}


	public function filterGroups()
	{
		if (empty($this->_filterGroups)) {
      $this->_filterGroups = array();

      if (isset($this->_dictionary[SMART_LIST_FILTER_GROUPS_KEY])) {
        $groupsA = $this->_dictionary[SMART_LIST_FILTER_GROUPS_KEY];
        if (!empty($groupsA)) {
          foreach($groupsA as $filterD) {
            $group = new TDOSmartListFilterGroup($filterD);
            if (!empty($group)) {
              $this->_filterGroups[] = $group;
            }
          }
        }
      }
    }

    return $this->_filterGroups;
	}

  public function addFilterGroup($filterGroup)
  {
    if (empty($filterGroup)) {
      return false;
    }

    $theFilterGroups = $this->filterGroups();
    if (count($theFilterGroups) >= SMART_LIST_MAX_FILTER_GROUP_COUNT) {
      // Cannot add more than what is allowed
      return false;
    }

    // Make sure this is a filter group class
    if (!is_a($filterGroup, "TDOSmartListFilterGroup")) {
      return false;
    }

    $this->_filterGroups[] = $filterGroup;
    $this->_rebuildFilterGroupsInDictionary();
    return true;
  }

  public function updateFilterGroup($filterGroup, $filterIndex = 0)
  {
    if (empty($filterGroup)) {
      return false;
    }

    $theFilterGroups = $this->filterGroups();
    $this->_filterGroups[$filterIndex] = $filterGroup;
    $this->_rebuildFilterGroupsInDictionary();
    return true;
  }

  public function removeFilterGroupAtIndex($index)
  {
    $theFilterGroups = $this->filterGroups();
    if ($index < 0 || $index >= count($theFilterGroups)) {
      // Invalid index specified
      return false;
    }

    unset($this->_filterGroups[$index]);
    // $this->_filterGroups = array_values($this->_filterGroups);
    $this->_rebuildFilterGroupsInDictionary();
    return true;
  }

  public function filterOfKind($className)
  {
    if (empty($className)) {
      return NULL;
    }
    $theFilterGroups = $this->filterGroups();
    foreach($theFilterGroups as $filterGroup) {
      if (is_a($filterGroup, $className)) {
        return $filterGroup;
      }
    }

    return NULL;
  }

  public function dictionaryRepresentation()
  {
    return $this->_dictionary;
  }

  public function sqlWhereStatementUsingStartDates($usingStartDates = false)
  {
    $sql = "";

    // Moving the excludedListIDs() filter up one level so it can integrate with
    // the params of getCompletedTasksAPI and not conflict.
    // $listFilters = "";
    //
    // $listIDsA = $this->excludedListIDs();
    // if (!empty($listIDsA)) {
    //   for ($i = 0, $size = count($listIDsA); $i < $size; $i++) {
    //     if ($i > 0) {
    //       $listFilters .= " AND ";
    //     }
    //     $listID = $listIDsA[$i];
    //     $listFilters .= " listid != '$listID' ";
    //   }
    // }
    // if (!empty($excludedListIDs) && count($excludedListIDs) > 0) {
    // }
    //
    // if (strlen($listFilters) > 0) {
    //   $sql .= $listFilters;
    // }

    $theFilterGroups = $this->filterGroups();
    if (!empty($theFilterGroups) && count($theFilterGroups) > 0) {
      $needToCloseSegment = false;
      if (strlen($sql) > 0) {
        $sql .= " AND (";
        $needToCloseSegment = true;
      }

      for ($idx = 0, $size = count($theFilterGroups); $idx < $size; $idx++) {
        $filterGroup = $theFilterGroups[$idx];
        $filterGroupSQL = "";
        $filtersA = $filterGroup->filters();
        if (!empty($filtersA) && count($filtersA) > 0) {
          foreach($filtersA as $filter) {
            if (strlen($filterGroupSQL) > 0) {
              $filterGroupSQL .= " AND ";
            }

            $filterSQL = NULL;
            if (is_a($filter, "TDOSmartListDueDateFilter")) {
              $filterSQL = $filter->buildSQLFilter($usingStartDates);
            } else {
              $filterSQL = $filter->buildSQLFilter();
            }

            if ($filterSQL) {
              $filterGroupSQL .= "($filterSQL)";
            }
          }
        }

        if ($idx > 0) {
          $sql .= " OR ";
        }

        $sql .= "($filterGroupSQL)";
      }

  		if ($needToCloseSegment) {
        $sql .= ")";
  		}
  	}

  	return $sql;
  }

  private function _rebuildFilterGroupsInDictionary()
  {
    $theFilterGroups = $this->filterGroups();
    if (empty($theFilterGroups) || count($theFilterGroups) == 0) {
      if (isset($this->_dictionary[SMART_LIST_FILTER_GROUPS_KEY])) {
        unset($this->_dictionary[SMART_LIST_FILTER_GROUPS_KEY]);
        // $this->_dictionary = array_values($this->_dictionary);
      }
    } else {
      $filterGroupDictionaries = array();
      foreach($theFilterGroups as $aFilterGroup) {
        $filterGroupD = $aFilterGroup->dictionaryRepresentation();
        if (!empty($filterGroupD)) {
          $filterGroupDictionaries[] = $filterGroupD;
        }
      }
      $this->_dictionary[SMART_LIST_FILTER_GROUPS_KEY] = $filterGroupDictionaries;
    }
  }

  // Call this if you want to mark a smart list as updated without doing a
	// full update (for example, if a color is added/removed)
    public static function updateTimestampForList($listid)
    {
        if(empty($listid))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOSmartList failed to get DB link");
            return false;
        }

        $listid = mysql_real_escape_string($listid, $link);
        $sql = "UPDATE tdo_smart_lists SET timestamp=".time()." WHERE listid='$listid'";

        if(!mysql_query($sql, $link))
        {
            error_log("TDOSmartList::updateTimestampForList failed: ".mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

        TDOUtil::closeDBLink($link);
        return true;
    }



	// this method is to sort the results of the lists once we get them back
	public static function smartListCompare($a, $b)
	{
		return strcasecmp($a->name(), $b->name());
	}

	public static function getSmartListIDsForUser($userid, $link = NULL)
	{
        if(!isset($userid))
            return false;

        if(empty($link))
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(empty($link))
            {
                error_log("TDOSmartList::getSmartListIDsForUser() failed to get db link");
                return false;
            }
        }
        else
            $closeLink = false;

        $escapedUserid = mysql_real_escape_string($userid, $link);

        $sql = "SELECT listid FROM tdo_smart_lists WHERE userid='$escapedUserid'";
        $result = mysql_query($sql);
        if($result)
        {
			$listIDs = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['listid']))
                {
                    $listid = $row['listid'];
					$listIDs[] = $listid;
                }
            }
            if($closeLink)
                TDOUtil::closeDBLink($link);

            return $listIDs;
        }
        else
        {
            error_log("Unable to get smart list ids for user ($userid): ".mysql_error());
        }

		if($closeLink)
			TDOUtil::closeDBLink($link);
        return false;
	}

	public static function getSmartListsForUser($userid, $includeDeleted=false, $link=NULL)
	{
        if(!isset($userid))
            return false;

        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList::getSmartListsForUser() failed to get dblink");
                return false;
            }
        }
        else
            $closeDBLink = false;

        $escapedUserid = mysql_real_escape_string($userid, $link);

        $sql = "SELECT listid FROM tdo_smart_lists WHERE userid='$escapedUserid'";
        $result = mysql_query($sql);
        if($result)
        {
            $lists = array();
            while($row = mysql_fetch_array($result))
            {
                if(isset($row['listid']))
                {
                    $listid = $row['listid'];
					$list = TDOSmartList::getSmartListForListid($listid, $link);
					if($list)
					{
						if($includeDeleted || !$list->deleted())
						{
							$lists[] = $list;
						}
					}
                }
            }

            if($closeDBLink)
                TDOUtil::closeDBLink($link);

			uasort($lists, 'TDOSmartList::smartListCompare');

            return $lists;
        }
        else
        {
            error_log("Unable to read user smart lists: ".mysql_error());
        }
        if($closeDBLink)
            TDOUtil::closeDBLink($link);
        return false;
	}

	public static function getListCount()
	{
		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOSmartList::getListCount() failed to get dblink");
			return false;
		}

        $sql = "SELECT COUNT(*) FROM tdo_smart_lists";
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
        else
        {
            error_log("Unable to get smart list count: ".mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
	}


	public static function getSmartListCountForUser($userid, $includeDeleted=false)
	{
        if(!isset($userid))
            return false;

		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOSmartList::getSmartListCountForUser() failed to get dblink");
			return false;
		}

        $escapedUserid = mysql_real_escape_string($userid, $link);

		$deletedSQL = " deleted IS NULL OR deleted != 1";
		if ($includeDeleted) {
			$deletedSQL = " deleted = 1";
		}

        $sql = "SELECT COUNT(*) FROM tdo_smart_lists WHERE userid='$escapedUserid' AND " . $deletedSQL;
        $result = mysql_query($sql);
        if($result)
        {
            $total = mysql_fetch_array($result);
            if($total && isset($total[0]))
            {
                TDOUtil::closeDBLink($link);
                return $total[0];
            }
        }
        else
        {
            error_log("Unable to get smart list count for user ($userid): ".mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
	}

    public static function smartListFromRow($row)
    {
        if ( (empty($row)) || (count($row) == 0) )
        {
            error_log("TDOSmartList::smartListFromRow() was passed a NULL row");
            return NULL;
        }

        if (empty($row['listid']))
        {
            error_log("TDOSmartList::smartListFromRow() did not contain an listid");
            return NULL;
        }

        $list = new TDOSmartList();
        if(isset($row['listid']))
            $list->setListId($row['listid']);
        if(isset($row['name']))
            $list->setName($row['name']);
		if(isset($row['color']))
			$list->setColor($row['color']);
		if(isset($row['icon_name']))
			$list->setIconName($row['icon_name']);
		if(isset($row['sort_order']))
			$list->setSortOrder($row['sort_order']);
		if(isset($row['json_filter']))
			$list->setJsonFilter($row['json_filter']);
		if(isset($row['sort_type']))
			$list->setSortType($row['sort_type']);
		if(isset($row['default_due_date']))
			$list->setDefaultDueDate($row['default_due_date']);
		if(isset($row['default_list']))
			$list->setDefaultList($row['default_list']);
		if(isset($row['excluded_list_ids'])) {
            $listsA = explode(',', $row['excluded_list_ids']);
			$list->setExcludedListIDs($listsA);
        }
		if(isset($row['completed_tasks_filter'])) {
      $filter = new TDOSmartListCompletedTasksFilter(json_decode($row['completed_tasks_filter'], true));
      $list->setCompletedTasksFilter($filter);
    }

        if(isset($row['deleted']))
            $list->setDeleted($row['deleted']);
        if(isset($row['timestamp']))
            $list->setTimestamp($row['timestamp']);

        return $list;
    }



	public static function getSmartListForListid($listid, $link=NULL)
    {
        if(!isset($listid))
            return false;

        if(!$link)
        {
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList::getSmartListForListid() failed to get dblink");
                return false;
            }
            $shouldCloseLink = true;
        }
        else
        {
            $shouldCloseLink = false;
        }

        $listid = mysql_real_escape_string($listid);

        $sql = "SELECT * FROM tdo_smart_lists WHERE listid = '$listid'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                $list = TDOSmartList::smartListFromRow($row);
                return $list;
            }

        }
        else
            error_log("Unable to get smart list: ".mysql_error());

        if($shouldCloseLink)
            TDOUtil::closeDBLink($link);
        return false;
    }


	public static function userCanEditSmartList($listid, $userid, $link=NULL)
	{
		$userCanEdit = false;

		if(!isset($listid) || !isset($userid))
			return false;

		if(empty($link))
		{
			$closeDBLink = true;
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOSmartList::userCanEditSmartList() failed to get dblink");
				return false;
			}
		}
		else
			$closeDBLink = false;

		$userid = mysql_real_escape_string($userid, $link);
		$listid = mysql_real_escape_string($listid, $link);

		$sql = "SELECT userid FROM tdo_smart_lists WHERE listid='$listid' AND userid='$userid'";
		$result = mysql_query($sql, $link);
		if($result)
		{
			$resultArray = mysql_fetch_array($result);
			if($resultArray)
			{
				if(isset($resultArray['userid']))
				{
					$dbUserId = $resultArray['userid'];
					if ($dbUserId == $userid) {
						$userCanEdit = true;
					}
					if($closeDBLink)
						TDOUtil::closeDBLink($link);
					return $userCanEdit;
				}
			}
		}
		else
		{
			error_log("TDOSmartList::userCanEditSmartList() unable to get userid: ".mysql_error());
		}
		if($closeDBLink)
			TDOUtil::closeDBLink($link);
		return false;
	}


	public static function smartListHashForUser($userid, $link=NULL)
    {
        if(!isset($userid))
        {
            error_log("TDOSmartList::smartListHashForUser() had invalid userId");
            return false;
        }

        if(empty($link))
        {
            $closeDBLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOSmartList::smartListHashForUser() failed to get dblink");
                return false;
            }
        }
        else
		{
            $closeDBLink = false;
		}

        $escapedUserid = mysql_real_escape_string($userid, $link);

        $sql = "SELECT DISTINCT timestamp FROM tdo_smart_lists WHERE userid='$escapedUserid' ORDER BY timestamp DESC";

        $timestamp = NULL;
        $result = mysql_query($sql);
        if($result)
        {
            $listString = "";
            $lists = array();
            while($row = mysql_fetch_array($result))
            {
                $listString .= strval($row['timestamp']);
            }
        }

        if($closeDBLink)
		{
            TDOUtil::closeDBLink($link);
		}

        if(!empty($listString))
        {
            $md5Value = md5($listString);

            return $md5Value;
        }

		// There must not yet be any smart lists. In this case, return a new
		// hash every single time so that the client will eventually send up
		// a new version of their smart lists. This should really only ever
		// happen with a beta customer that has been running Todo Cloud 9 beta
		// versions.
		$tempHash = md5(date(DATE_ISO8601));
		return $tempHash;
    }


	public static function nameForSmartListId($listid)
    {
        if(!isset($listid))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOSmartList::nameForSmartListId() failed to get dblink");
            return false;
        }
        $shouldCloseLink = true;

        $listid = mysql_real_escape_string($listid);

        $sql = "SELECT name FROM tdo_smart_lists WHERE listid='$listid'";

        $response = mysql_query($sql, $link);
        if($response)
        {
            $row =  mysql_fetch_array($response);
            if($row)
            {
                if(isset($row['name']))
                {
                    $listName = TDOUtil::ensureUTF8($row['name']);
                    return $listName;
                }
            }
        }

        error_log("Unable to get smart list name: ".mysql_error());

        TDOUtil::closeDBLink($link);
        return false;
    }



    public static function deleteSmartLists($listids)
    {
//		error_log("TDOSmartList::deleteLists");

        if(!isset($listids))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link)
        {
            error_log("TDOSmartList::deleteSmartLists() unable to get link");
           return false;
        }

        foreach($listids as $listid)
        {
            TDOSmartList::deleteSmartList($listid);
        }
        TDOUtil::closeDBLink($link);
        return true;

    }

    public static function getIsListDeleted($listid)
    {
        if(!isset($listid))
            return false;

		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOSmartList::getIsListDeleted() failed to get dblink");
			return false;
		}

        $listid = mysql_real_escape_string($listid, $link);

        $sql = "SELECT deleted from tdo_smart_lists WHERE listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            $resultArray = mysql_fetch_array($result);
            if(isset($resultArray['deleted']))
            {
                TDOUtil::closeDBLink($link);
                return ($resultArray['deleted'] != 0);
            }
        }

        TDOUtil::closeDBLink($link);
        return true;
    }

    public static function setNameForList($listid, $name)
    {
        if(empty($listid) || empty($name))
            return false;

		$link = TDOUtil::getDBLink();
		if(!$link)
		{
			error_log("TDOSmartList::setNameForList() failed to get dblink");
			return false;
		}

        $listid = mysql_real_escape_string($listid, $link);

        $name = mb_strcut($name, 0, SMART_LIST_NAME_LENGTH, 'UTF-8');
        $name = mysql_real_escape_string($name, $link);

        $sql = "UPDATE tdo_smart_lists SET name='$name',timestamp=" . time() . " WHERE listid='$listid'";
        $result = mysql_query($sql, $link);
        if($result)
        {
            TDOUtil::closeDBLink($link);
            return true;
        }

        TDOUtil::closeDBLink($link);
        return false;
    }
}
