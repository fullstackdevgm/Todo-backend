<?php

include_once 'Sabre/DAV/includes.php';
include_once('TodoOnline/base_sdk.php');	
	
/**
 * TDOCalDavBackend
 *
 */

class TDOCalDavBackend extends Sabre_CalDAV_Backend_Abstract
{

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     *
     * Add your own properties by simply adding on to this array
     * 
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}displayname'                          => 'name',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'cdavTimeZone',
        '{http://apple.com/ns/ical/}calendar-order'  => 'cdavOrder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'cdavColor',
    );

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is 
     *    accessed.
     *  * principalUri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'. 
     *
     * @param string $principalUri 
     * @return array 
     */
    public function getCalendarsForUser($principalUri)
	{
//		error_log("TDOCalDavBackend::getCalendarsForUser()");
		
		$pathArray = Sabre_DAV_URLUtil::splitPath($principalUri);
		if($pathArray == NULL)
			return;
		
		$userName = $pathArray[1];
		$user = TDOUser::getUserForUsername($userName);
		
		if($user == false)
        {
            error_log("User was false, returning");
			return;
		}

		// Todo Cloud doesn't really have a calendar for users, we just use
        // the userID for a fake calendar

//        error_log("returning array with user inbox as the item");

        $calendars = array();

        $calendar = array(
                          'id' => $user->userId(),
                          'uri' => $user->userId(),
                          'principaluri' => $principalUri,
                          '{DAV:}displayname' => "Todo Cloud",
                          '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VTODO')),
        );
        
        $boardDescription = "Todo Cloud Siri Calendar";
        $calendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = $boardDescription;
        $cdavTimeZone = TDOUserSettings::getTimezoneForUser($user->userId());
        if(isset($cdavTimeZone))
            $calendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone'] = $cdavTimeZone;
        $cdavOrder = 0;
        if(isset($cdavOrder))
            $calendar['{http://apple.com/ns/ical/}calendar-order'] = $cdavOrder;
//        $cdavColor = $board->getcdavColor();
//        if(isset($cdavColor))
            $calendar['{http://apple.com/ns/ical/}calendar-color'] = "0";
        
        $calendars[] = $calendar;

//        $boards = TDOList::getListsForUser($user->userId());
//		if($boards == NULL)
//			return;
//		
//        $calendars = array();
//		foreach($boards as $board)
//		{
////			error_log("         Board ID: " . $board->getListid());
////			error_log("              URI: " . $board->getcdavUri());
////			error_log("    Principal URI: " . $principalUri);
////			error_log("     Display Name: " . $board->getName());
//			
//            $calendar = array(
//							  'id' => $board->getListid(),
//							  'uri' => $board->getcdavUri(),
//							  'principaluri' => $principalUri,
//							  '{DAV:}displayname' => $board->getName(),
//							  '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT','VTODO')),
//            );
//			
//			$boardDescription = $board->getDescription();
//			if(isset($boardDescription))
//				$calendar['{urn:ietf:params:xml:ns:caldav}calendar-description'] = $boardDescription;
//			$cdavTimeZone = $board->getcdavTimeZone();
//			if(isset($cdavTimeZone))
//				$calendar['{urn:ietf:params:xml:ns:caldav}calendar-timezone'] = $cdavTimeZone;
//			$cdavOrder = $board->getcdavOrder();
//			if(isset($cdavOrder))
//				$calendar['{http://apple.com/ns/ical/}calendar-order'] = $cdavOrder;
//			$cdavColor = $board->getcdavColor();
//			if(isset($cdavColor))
//				$calendar['{http://apple.com/ns/ical/}calendar-color'] = $cdavColor;
//			
//            $calendars[] = $calendar;
//        }
		
//		error_log("    Returning " . count($calendars) . " calendars");
        return $calendars;

    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
	{
//		error_log("TDOCalDavBackend::createCalendar()");
		//error_log("createCalendar was called with calendarUri: ".$calendarUri);
		

        return;
        
        
//		$pathArray = Sabre_DAV_URLUtil::splitPath($principalUri);
//		if($pathArray == NULL)
//		{
//			error_log("createCalendar failed due to failed parse on URI: ". $principalUri);
//			return;
//		}
//
//		$userName = $pathArray[1];
//		$user = TDOUser::getUserForUsername($userName);
//		
//		if($user == false)
//		{	
//			error_log("createCalendar failed due to failed fetch on userName ". $userName);
//			return;
//		}
        
        
        
//
//		if(!isset($properties['{DAV:}displayname']))
//		{
//			error_log("createCalendar failed due to missing DAV:displayname");
//			return;
//		}
//
//		$board = new TDOList();
//		$board->setName($properties['{DAV:}displayname']);
//		$board->setCreator($user->userId());
//		$board->setcdavUri($calendarUri);
//
//		if(isset($properties['{urn:ietf:params:xml:ns:caldav}calendar-description']))
//			$board->setDescription($properties['{urn:ietf:params:xml:ns:caldav}calendar-description']);
//		if(isset($properties['{urn:ietf:params:xml:ns:caldav}calendar-timezone']))
//			$board->setcdavTimeZone($properties['{urn:ietf:params:xml:ns:caldav}calendar-timezone']);
//		if(isset($properties['{http://apple.com/ns/ical/}calendar-order']))
//			$board->setcdavOrder($properties['{http://apple.com/ns/ical/}calendar-order']);
//		if(isset($properties['{http://apple.com/ns/ical/}calendar-color']))
//			$board->setcdavColor($properties['{http://apple.com/ns/ical/}calendar-color']);
//
//		$result = $board->addList($user->userId());
//
////		sleep(1);		
//
//		if($result == true)
//		{
//			// log new board from caldav
//			$session = TDOSession::getInstance();
//			TDOChangeLog::addChangeLog($board->getListid(), $session->getUserId(), $board->getListid(), $board->getName(), ITEM_TYPE_LIST, CHANGE_TYPE_ADD, CHANGE_LOCATION_CALDAV);
//			
//			return $board->getListid();
//		}
//
//		error_log("createCalendar failed, returning null");
	}

    /**
     * Updates properties for a calendar.
     *
     * The mutations array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existant property is always succesful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname. 
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param string $calendarId
     * @param array $mutations 
     * @return bool|array 
     */
    public function updateCalendar($calendarId, array $mutations)
	{
//		error_log("TDOCalDavBackend::updateCalendar()");
		//error_log("updateCalendar was called");
		
//        $newValues = array();
//        $result = array(
//            200 => array(), // Ok
//            403 => array(), // Forbidden
//            424 => array(), // Failed Dependency
//        );
//
//        $hasError = false;

//		$board = new TDOList();
//		$board->setListid($calendarId);
//		
//		$oldBoard = TDOList::getListForListid($calendarId);
//		
//		
////        foreach($mutations as $propertyName=>$propertyValue)
////		{
////			error_log("Update calendar modify: ".$propertyName." == ".$propertyValue);
////        }
//
//		$hasUpdate = false;
//		$jsonChangedValues = "{ ";
//		
//		
//		if(isset($mutations['{DAV:}displayname']))
//		{
//			if($hasUpdate == true)
//				$jsonChangedValues = $jsonChangedValues.', ';
//			$jsonChangedValues = $jsonChangedValues.'"boardName":"'.$mutations['{DAV:}displayname'].'","old-boardName":"'.$oldBoard->getName().'"';
//			$hasUpdate = true;
//			
//			$board->setName($mutations['{DAV:}displayname']);
//		}
//
//		if(isset($mutations['{urn:ietf:params:xml:ns:caldav}calendar-description']))
//		{
//			if($hasUpdate == true)
//				$jsonChangedValues = $jsonChangedValues.', ';
//			$jsonChangedValues = $jsonChangedValues.'"description":"'.$mutations['{urn:ietf:params:xml:ns:caldav}calendar-description'].'","old-description":"'.$oldBoard->getDescription().'"';
//			$hasUpdate = true;
//
//			$board->setDescription($mutations['{urn:ietf:params:xml:ns:caldav}calendar-description']);
//		}
//
//		if(isset($mutations['{urn:ietf:params:xml:ns:caldav}calendar-timezone']))
//		{
//			$board->setcdavTimeZone($mutations['{urn:ietf:params:xml:ns:caldav}calendar-timezone']);
//			$hasUpdate = true;
//		}
//
//		if(isset($mutations['{http://apple.com/ns/ical/}calendar-order']))
//		{
//			$board->setcdavOrder($mutations['{http://apple.com/ns/ical/}calendar-order']);
//			$hasUpdate = true;
//		}
//
//		if(isset($mutations['{http://apple.com/ns/ical/}calendar-color']))
//		{
//			$board->setcdavColor($mutations['{http://apple.com/ns/ical/}calendar-color']);
//			$hasUpdate = true;
//		}
//		
//		if($hasUpdate == false)
//		{
//			error_log("updateCalendar was called with no editable attributes");
//			return false;
//		}
//        
//        $session = TDOSession::getInstance();
//        if($session->isLoggedIn() == false)
//        {
//            error_log("updateCalendar unsuccessful because not logged in");
//            return false;
//        }
//        
//        $userid = $session->getUserId();
//		if($board->updateList($userid) == true)
//		{
//			$boardName = $board->getName();
//			if(empty($boardName))
//				$boardName = $oldBoard->getName();
//			
//			$jsonChangedValues = $jsonChangedValues."}";
//			
//			TDOChangeLog::addChangeLog($board->getListid(), $session->getUserId(), $board->getListid(), $boardName, ITEM_TYPE_LIST, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_CALDAV, NULL, NULL, $jsonChangedValues);
//			
////			sleep(1);			
//			return true;
//		}
//		else
//		{
//			error_log("updateCalendar failed to update Board");
//		}

//        error_log("Todo Cloud does not support updaging calendars");
    }

    /**
     * Delete a calendar and all it's objects 
     * 
     * @param string $calendarId 
     * @return void
     */
    public function deleteCalendar($calendarId)
	{
//		error_log("TDOCalDavBackend::deleteCalendar()");
		//error_log("deleteCalendar was called on calendar: ". $calendarId);
		
//        error_log("Todo Cloud does not support deleting calendars");
        return false;
        
//		$session = TDOSession::getInstance();
//		if(TDOList::getRoleForUser($calendarId, $session->getUserId()) != LIST_MEMBERSHIP_OWNER)
//		{
//			error_log("PBCalDavBackend failed to delete board: user not owner");
//			return false;
//		}
//
//		$boardName = TDOList::getNameForList($calendarId);
//		
//		if(TDOList::deleteList($calendarId) == false)
//		{
//			error_log("deleteCalendar failed to delete Board");
//			return false;
//		}
//
//		TDOChangeLog::addChangeLog($calendarId, $session->getUserId(), $calendarId, $boardName, ITEM_TYPE_LIST, CHANGE_TYPE_DELETE, CHANGE_LOCATION_CALDAV);
//		
//		return true;
    }

    /**
     * Returns all calendar objects within a calendar. 
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calnedar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.: 
     *   '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *
     * Note that the etag is optional, but it's highly encouraged to return for 
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned 
     * 'getCalendarObject' will be called later, which *is* expected to return 
     * calendardata.
     * 
     * @param string $calendarId 
     * @return array 
     */
    public function getCalendarObjects($calendarId)
	{
        
		// Return both tasks and events
		
//		error_log("TDOCalDavBackend::getCalendarObjects()");
		//error_log("getCalendarObjects called: ".$calendarId);
        $calObjects = array();
		
//		$boardEvents = PBEvent::getEventsForList($calendarId);
//		if($boardEvents != NULL)
//		{
//			$this->addCalObjectsToArray($boardEvents, $calObjects);
//		}

        
//		$boardTasks = TDOTask::getTasksForList($calendarId, $this->getTaskFilter($calendarId));
//		if ($boardTasks != NULL)
//		{
//			$this->addCalObjectsToArray($boardTasks, $calObjects);
//		}
		
//		error_log("getCalendarObjects returned " . count($calObjects) . " objects");

        // Todo Cloud always returns nothing
        return $calObjects;
//
//        $stmt = $this->pdo->prepare('SELECT * FROM `'.$this->calendarObjectTableName.'` WHERE calendarid = ?');
//        $stmt->execute(array($calendarId));
//        return $stmt->fetchAll();
//
    }
    public function getTaskFilter($calendarId)
    {
//        $session = TDOSession::getInstance();
        $taskFilter = NULL;
//        if(TDOListSettings::shouldFilterSyncedTasksForList($calendarId, $session->getUserId()))
//        {
//            $taskFilter = $session->getUserId();
//        }
        return $taskFilter;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The returned array must have the same keys as getCalendarObjects. The 
     * 'calendardata' object is required here though, while it's not required 
     * for getCalendarObjects.
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    public function getCalendarObject($calendarId,$objectUri)
	{	
//		error_log("PBCalDavBackend::getCalendarObject()");
		
		//error_log("getCalendarObject was called");
		
		// Look for both events and tasks
		
//		$anObject = PBEvent::getEvent($calendarId, $objectUri);
//		if($anObject == NULL)
//		{
////			error_log("About to call getTask...");
//			
//			$anObject = TDOTask::getTask($calendarId, $objectUri, $this->getTaskFilter($calendarId));
//			if ($anObject == NULL)
//			{
//				// error_log("getCalendarObject did not find an event or task object");
//				return;
//			}
//		}
		
//		$calObject = array();
//		
//		$value = $anObject->getId();
//		if(isset($value))
//			$calObject['id'] = $value;
////		else
////			error_log("    no id");
//		
//		$value = $anObject->getcalDavData();
//		if(isset($value))
//			$calObject['calendardata'] = $value;
////		else
////			error_log("    no cal data");
//
//		$value = $anObject->getcalDavUri();
//		if(isset($value))
//			$calObject['uri'] = $value;
////		else
////			error_log("    no uri");
//
//		$value = $anObject->getLastModified();
//		if(isset($value))
//			$calObject['lastmodified'] = $value;
////		else
////			error_log("    no lastmodified");
//
//		$value = $anObject->getListid();
//		if(isset($value))
//			$calObject['calendarid'] = $value;
////		else
////			error_log("    no cal id");
//
//		return $calObject;
        
//        error_log("Todo Cloud doesn't return any calendar information");
		
//        $stmt = $this->pdo->prepare('SELECT * FROM `'.$this->calendarObjectTableName.'` WHERE calendarid = ? AND uri = ?');
//        $stmt->execute(array($calendarId, $objectUri));
//        return $stmt->fetch();
//
    }

    /**
     * Creates a new calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function createCalendarObject($calendarId,$objectUri,$calendarData)
	{
//		error_log("TDOCalDavBackend::createCalendarObject()");

//		error_log("    calendarId: " . $calendarId);
//		error_log("     objectUri: " . $objectUri);
//		error_log("  calendarData:");
//		error_log($calendarData);
		//error_log("createCalendarObject called");
		
        $session = TDOSession::getInstance();
        if($session->isLoggedIn() == false)
        {
            error_log("TDOCalDavBackend::createCalendarObject unsuccessful because not logged in");
            return;
        }

        if($calendarId != $session->getUserId())
        {
			error_log("TDOCalDavBackend::createCalendarObject used an invalid calendar Id: ".$calendarId);
			return;
        }
        
//		if(TDOList::userCanEditList($calendarId, $session->getUserId()) == false)
//		{
//			error_log("TDOCalDavBackend::createCalendarObject found that user cannot edit the board: ".$calendarId);
//			return;
//		}
		
		$parsedEvent = Sabre_VObject_Reader::read($calendarData);
		if (!$parsedEvent)
		{
			error_log("TDOCalDavBackend::createCalendarObject failed to parse CalDAV data.");
			return;
		}
		
		$calObj = NULL;
		
//		if (count($parsedEvent->vevent) > 0)
//		{
////			error_log("PBCalDavBackend::createCalendarObject creating EVENT");
//			$calObj = new PBEvent();
//		}
		if (count($parsedEvent->vtodo) > 0)
		{
//			error_log("PBCalDavBackend::createCalendarObject creating TASK");
			$calObj = new TDOTask();
            
            //if the user is only syncing tasks assigned to him and he creates
            //a task in ical, go ahead and assign it to him in plunkboard
//            if(($assignedUser = $this->getTaskFilter($calendarId)) != NULL)
//                $calObj->setAssignedUserid($assignedUser);
		}
		else
		{
			error_log("PBCalDavBackend::createCalendarObject was passed CalDAV data for an unsupported type");
			return;
		}
		
		
//		error_log("    setting board id");
        
        $userInboxId = TDOList::getUserInboxId($session->getUserId(), true);

        
		$calObj->setListId($userInboxId);
//		error_log("    setting cdav data");
        
        if(TDOSubscription::getSubscriptionLevelForUserID($session->getUserId()) > 1)
        {
            $calObj->setTaskValuesFromCaldavData($calendarData);
        }
        else
        {
            $calObj->setName("Siri tasks available with a premium account (see note)");
            $calObj->setNote("You attempted to create this task using Siri. Upgrade to a premium account (in Settings) and future tasks created in Siri will automatically appear in your Inbox.");
        }
        
		
//		error_log("    setting cdav uri");
//		$calObj->setcalDavUri($objectUri);
		
//		error_log("    adding event");
        
        $taskid = $calObj->taskid();
        
        $existingTask = TDOTask::getTaskFortaskId($taskid);
        if(empty($existingTask))
        {
            $result = $calObj->addObject();
            if($result == true)
            {
                //parse the date, priority, context, tags, and list from the task name 
                $calObj->updateValuesFromTaskName($session->getUserId());
            
                TDOChangeLog::addChangeLog($userInboxId, $session->getUserId(), $calObj->taskId(), $calObj->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_CALDAV);
                //			error_log("PBCalDavBackend::createCalendarObject() worked! returning event");
                return $calObj->taskId();
            }
        }
        else
        {
			// bht - Intentionally do nothing. In some cases, CalDAV isn't
			// deleting (or at least not quickly enough) the tasks from the
			// iOS device and so it's pushing the original old values back up
			// to the Todo Cloud service, which can reset task properties if
			// Todo Cloud users have already changed the task in Todo Cloud (or on
			// a device synchronizing with Todo Cloud).
			return $calObj->taskId();
			
//            if($existingTask->completionDate() == 0 && $calObj->completionDate() != 0)
//            {
//                $result = $calObj->moveToCompletedTable();
//            }
//            else if($existingTask->completionDate() != 0 && $calObj->completionDate() == 0)
//            {
//                $result = $calObj->moveFromCompletedTable();
//            }
//            else
//            {
//                $result = $calObj->updateObject();
//            }
//            if($result == true)
//            {
//                TDOChangeLog::addChangeLog($userInboxId, $session->getUserId(), $calObj->taskId(), $calObj->name(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_CALDAV);
//                //			error_log("PBCalDavBackend::createCalendarObject() worked! returning event");
//                return $calObj->taskId();
//            }
        }
//		error_log("    added event");
//		sleep(1);		
		
//		if($result == true)
//		{
//			TDOChangeLog::addChangeLog($userInboxId, $session->getUserId(), $calObj->taskId(), $calObj->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_CALDAV);
////			error_log("PBCalDavBackend::createCalendarObject() worked! returning event");
//			return $calObj->taskId();
//		}
		
		error_log("PBCalDavBackend::createCalendarObject() failed, returning null");		
//
//        $stmt = $this->pdo->prepare('INSERT INTO `'.$this->calendarObjectTableName.'` (calendarid, uri, calendardata, lastmodified) VALUES (?,?,?,?)');
//        $stmt->execute(array($calendarId,$objectUri,$calendarData,time()));
//        $stmt = $this->pdo->prepare('UPDATE `'.$this->calendarTableName.'` SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));
//
    }

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function updateCalendarObject($calendarId,$objectUri,$calendarData)
	{
//		error_log("PBCalDavBackend::updateCalendarObject()");
		//error_log("updateCalendarObject was called");
		
        
        return;
        
//        $session = TDOSession::getInstance();
//        if($session->isLoggedIn() == false)
//        {
//            error_log("PBCalDavBackend::updateCalendarObject unsuccessful because not logged in");
//            return;
//        }
//		
//		if(TDOList::userCanEditList($calendarId, $session->getUserId()) == false)
//		{
//			error_log("PBCalDavBackend::updateCalendarObject found that user cannot edit the board: ".$calendarId);
//			return;
//		}
//		
//		$eventIsTask = false;
//		
//		// Look for events and tasks
////		$anObject = PBEvent::getEvent($calendarId, $objectUri);
////		if($anObject == NULL)
////		{
//			$anObject = TDOTask::getTask($calendarId, $objectUri, $this->getTaskFilter($calendarId));
//			if ($anObject == NULL)
//				return;
//
//			$eventIsTask = true;
////		}
//
//		
//		// get original values to compare so we know what changed
//		if($eventIsTask)
//		{
//			// collect task properties here
//			$origTaskName = $anObject->getSummary();
//			$origTaskDueDate = $anObject->getDueDate();
//			$origTaskCompDate = $anObject->getCompletionDate();
//			$origTaskPriority = $anObject->getPriority();
//			$origTaskNote = $anObject->getDescription();
//		}
//		else
//		{
//			// collect event properties here
//			$origEventName = $anObject->getSummary();
//			$origEventStartDate = $anObject->getStartDate();
//			$origEventEndDate = $anObject->getEndDate();
//			$origEventNote = $anObject->getDescription();
//		}
//		
//		$eventId = $anObject->getId();
//
//		$anObject->set_to_default();
//		$anObject->setId($eventId);
//		$anObject->setListid($calendarId);
//		$anObject->setcalDavData($calendarData);
//		
//		$result = $anObject->updateObject();
//		
////		sleep(1);	
//		
//		if($result == true)
//		{
//			if($eventIsTask)
//			{
//				$haveChanges = false;
//				
//				$jsonValues = array();
//
//				$newTaskName = $anObject->getSummary();
//				$newTaskNote = $anObject->getDescription();
//				
//				if(strcmp($origTaskName, $newTaskName) != 0)
//				{
//					$jsonValues['old-taskName'] = (string)$origTaskName;
//					$jsonValues['taskName'] = (string)$newTaskName;
//					$haveChanges = true;
//				}
//				if($origTaskDueDate != $anObject->getDueDate())
//				{
//					$jsonValues['old-taskDueDate'] = (double)$origTaskDueDate;
//					$jsonValues['taskDueDate'] = (double)($anObject->getDueDate());
//					$haveChanges = true;					
//				}
//				if($origTaskCompDate != $anObject->getCompletionDate())
//				{
//					$jsonValues['old-completiondate'] = (double)$origTaskCompDate;
//					$jsonValues['completiondate'] = (double)($anObject->getCompletionDate());
//					$haveChanges = true;					
//				}
//				if(strcmp($origTaskNote, $newTaskNote) != 0)
//				{
//					$jsonValues['old-taskNote'] = (string)$origTaskNote;
//					$jsonValues['taskNote'] = (string)$newTaskNote;
//					$haveChanges = true;
//				}
//
//				$jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
//				
//				TDOChangeLog::addChangeLog($calendarId, $session->getUserId(), $anObject->getId(), $anObject->getSummary(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_CALDAV, NULL, NULL, $jsonChangedValues);
//			}
//			else
//			{
//				$haveChanges = false;
//				
//				$jsonValues = array();
//				
//				$newEventName = $anObject->getSummary();
//				$newEventNote = $anObject->getDescription();
//				
//				if(strcmp($origEventName, $newEventName) != 0)
//				{
//					$jsonValues['old-eventName'] = (string)$origEventName;
//					$jsonValues['eventName'] = (string)$newEventName;
//					$haveChanges = true;
//				}
//				if($origEventStartDate != $anObject->getStartDate())
//				{
//					$jsonValues['eventStartDate'] = (double)$origEventStartDate;
//					$jsonValues['eventStartDate'] = (double)($anObject->getStartDate());
//					$haveChanges = true;					
//				}
//				if($origEventEndDate != $anObject->getEndDate())
//				{
//					$jsonValues['old-eventEndDate'] = (double)$origEventEndDate;
//					$jsonValues['eventEndDate'] = (double)($anObject->getEndDate());
//					$haveChanges = true;					
//				}
//				if(strcmp($origEventNote, $newEventNote) != 0)
//				{
//					$jsonValues['old-eventNote'] = (string)$origEventNote;
//					$jsonValues['eventNote'] = (string)$newEventNote;
//					$haveChanges = true;
//				}
//				
//				$jsonChangedValues = json_encode($jsonValues, JSON_FORCE_OBJECT);
//				
//				TDOChangeLog::addChangeLog($calendarId, $session->getUserId(), $anObject->getId(), $anObject->getSummary(), ITEM_TYPE_EVENT, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_CALDAV, NULL, NULL, $jsonChangedValues);
//			}
//			
//			return true;
//		}
		
		error_log("updateCalendarObject failed, returning null");		
		
//
//        $stmt = $this->pdo->prepare('UPDATE `'.$this->calendarObjectTableName.'` SET calendardata = ?, lastmodified = ? WHERE calendarid = ? AND uri = ?');
//        $stmt->execute(array($calendarData,time(),$calendarId,$objectUri));
//        $stmt = $this->pdo->prepare('UPDATE `'.$this->calendarTableName.'` SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));
//
    }

    /**
     * Deletes an existing calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return void
     */
    public function deleteCalendarObject($calendarId,$objectUri)
	{
//		error_log("PBCalDavBackend::deleteCalendarObject()");
		//error_log("deleteCalendarObject was called");
		
		// Look for both events and tasks
		
//        $session = TDOSession::getInstance();
//        if($session->isLoggedIn() == false)
//        {
//            error_log("PBCalDavBackend::createCalendarObject unsuccessful because not logged in");
//            return;
//        }
//		
//		if(TDOList::userCanEditList($calendarId, $session->getUserId()) == false)
//		{
//			error_log("PBCalDavBackend::createCalendarObject found that user cannot edit the board: ".$calendarId);
//			return;
//		}
//		
//		$eventIsTask = false;
//		
//		
////		$anObject = PBEvent::getEvent($calendarId, $objectUri);
////		if($anObject == NULL)
////		{
//			$anObject = TDOTask::getTask($calendarId, $objectUri, $this->getTaskFilter($calendarId));
//			if ($anObject == NULL)
//				return;
//
//			$eventIsTask = true;
////		}
//		
//		if($anObject->deleteMe() == true)
//		{
//			if($eventIsTask)
//				TDOChangeLog::addChangeLog($calendarId, $session->getUserId(), $anObject->getId(), $anObject->getSummary(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_CALDAV);
//			else
//				TDOChangeLog::addChangeLog($calendarId, $session->getUserId(), $anObject->getId(), $anObject->getSummary(), ITEM_TYPE_EVENT, CHANGE_TYPE_DELETE, CHANGE_LOCATION_CALDAV);
//		
//			return true;
//		}
		
		return false;
//
//        $stmt = $this->pdo->prepare('DELETE FROM `'.$this->calendarObjectTableName.'` WHERE calendarid = ? AND uri = ?');
//        $stmt->execute(array($calendarId,$objectUri));
//        $stmt = $this->pdo->prepare('UPDATE `'. $this->calendarTableName .'` SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));

    }
	
	
//	// $objArray is passed by reference by using an ampersand so that this
//	// function can modify the array that is passed in.
//	private function addCalObjectsToArray($calObjects, &$objArray)
//	{
//		if ( (isset($calObjects) == false) || (isset($objArray) == false) )
//		{
//			error_log("PBCalDavBackend::addCalObjectsToArray() called with null parameters");
//			return;
//		}
//		
//		foreach($calObjects as $calObject)
//		{	
//			$formattedObj = array(
//								  'id' => $calObject->getId(),
//								  'calendardata' => $calObject->getcalDavData(),
//								  'uri' => $calObject->getcalDavUri(),
//								  'lastmodified' => $calObject->getLastModified(),
//								  'etag' => "\"" . TDOUtil::uuid() . "\"",
//								  'calendarid' => $calObject->getListid(),
//								  );
//			
//			$objArray[] = $formattedObj;
//		}
//	}
	

}
