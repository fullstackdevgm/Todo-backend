<?php
	// TDOTask
	//
	// Created by Boyd Timothy on 2/22/2012.
	// Copyright (C) 2012 Plunkboard, Inc. All rights reserved.

    /*

     taskid
     listid

     name (title)
     note
     duedate INT
     completiondate INT (Use NULL to indicate it is not completed)
     priority (INT)

     timestamp (last modified by the user)
     caldavuri VARCHAR(255)
     caldavdata BLOB


     CalDAV Definition (from http://tools.ietf.org/html/rfc2445):
     BEGIN:VTODO
     <Todo CloudPERTIES>
     *<ALARM>
     END:VTODO

     Todo CloudPERTIES (All Optional):
     Must NOT occur more than once:
     CLASS
     class		= "CLASS" classparam ":" classvalue CRLF
     classparam	= *(";" xparam)
     classvalue	= "PUBLIC" / "PRIVATE" / "CONFIDENTIAL" / iana-token
     (Default is PUBLIC)
     Example:
     CLASS:PUBLIC
     COMPLETED		; Defines when a to-do was actually completed
     completed	= "COMPLETED" compparam ":" date-time CRLF
     ; MUST be in UTC format

     Example:
     COMPLETED:19960401T235959Z

     CREATED			; Date/time stamp of when the USER or the
     ; calendar user agent created the object
     created		= "CREATED" creaparam ":" date-time CRLF
     (MUST be specified in UTC)
     Example:
     CREATED:19960329T133000Z
     DESCRIPTION
     description	= "DESCRIPTION" descparam ":" text CRLF
     DTSTAMP			; Indicates the date/time that the INSTANCE of the calendar object was created
     dtstamp		= "DTSTAMP" stmparam ":" date-time CRLF
     (MUST be specified in UTC)

     Example:
     DTSTAMP:19971201T080000Z
     DTSTART			; Specifies when the to-do begins (start date)
     dtstart		= "DTSTART" dstparam ":" dtstval CRLF
     dtstval		= date-time / date
     (Value MUST match value type ... DATE-TIME/DATE)

     Example:
     DTSTART:19980118T073000Z
     GEO
     geo			= "GEO" geoparam ":" geovalue CRLF
     geovalue	= float ";" float (Latitude and Longitude Components)

     Example:
     GEO:37.386013;-122.082932
     LAST-MODIFIED	; Date and time when the to-do was last revised
     last-mod	= "LAST-MODIFIED" lstparam ":" date-time CRLF
     (MUST be specified in UTC)
     Example:
     LAST-MODIFIED:19960817T133000Z
     LOCATION
     location	= "LOCATION" locparam ":" text CRLF

     Example:
     LOCATION:Conference Room - F123, Bldg. 002
     LOCATION;ALTREP="http://xyzcorp.com/conf-rooms/f123.vcf":Conference Room - F123, Bldg. 002
     ORGANIZER
     PERCENT-COMPLETE
     percent		= "PERCENT-COMPLETE" pctparam ":" integer CRLF

     0		- not started
     100		- completed

     Exmample (47% complete):
     PERCENT-COMPLETE:47
     PRIORITY
     priority	= "PRIORITY" prioparam ":" privalue CRLF
     (Default is 0)

     privalue	= integer		;Must be in the range [0..9]
     ;All other values are reserved
     ;for future use

     Examples:
     PRIORITY:1				;highest priority
     PRIORITY:2				;next highest priority
     PRIORITY:0				;no priority (equivalent to not specifying a PRIORITY property)

     RECURID
     SEQUENCE		; MUST be incremented when a significant change
     ; to one of the following properties:
     ;	DTSTART, DTEND, DUE, RDATE, RRULE, EXDATE,
     ;	EXRULE, STATUS
     ; CAN be incremented when other changes are made
     ; that would affect the participation status of
     ; the "ATTENDEES."
     seq			= "SEQUENCE" seqparam ":" integer CRLF
     (Default is 0)
     STATUS
     status		= "STATUS" statparam ":" statvalue CRLF
     statvalue	= "NEEDS-ACTION" / "COMPLETED" / "IN-PROGRESS" / "CANCELLED"
     SUMMARY
     summary		= "SUMMARY" summparam  ":" text CRLF
     UID
     URL

     ONE and ONLY ONE of the following
     DUE				; Date and time that a to-do is expected to be completed
     due			= "DUE" dueparam ":" dueval CRLF
     ; MUST be date/time >= DTSTART (if specified)
     dueparam	= *(
     ; the following are optional, but MUST NOT
     ; occur more than once

     (";" "VALUE" "=" ("DATE-TIME" / "DATE")) /
     (";" tzidparam) /

     ; the following is optional and MAY occur
     ; more than once

     *(";" xparam)
     )
     dueval		= date-time / date
     (Value MUST match value type)

     Example:
     DUE:19980430T235959Z

     DURATION		; Defines a positive duration of the to-do, instead of an explicit due date/time.
     duration	= "DURATION" durparam ":" dur-value CRLF

     Examples:
     DURATION:PT1H0M0S		; 1 hour, 0 min, 0 sec
     DURATION:PT15M			; 15 minutes

     MAY occur more than once
     ATTACH
     ATTENDEE
     CATEGORIES		(comma separated tags)
     Definition: text *("," text)

     Examples:
     CATEGORIES:APPOINTMENT,EDUCATION
     CATEGORIES:MEETING
     COMMENT
     comment		= "COMMENT" commparam ":" text CRLF
     CONTACT			; Specifies a single person as the "contact"
     EXDATE
     EXRULE
     RSTATUS
     RELATED
     RESOURCES
     RDATE
     RRULE
     X-PROP

     */

	// include files
	include_once('TodoOnline/base_sdk.php');
	include_once('Sabre/VObject/includes.php');

    abstract class TaskType
    {
        const Normal = 0;
        const Project = 1;
        const CallContact = 2;
        const SMSContact = 3;
        const EmailContact = 4;
        const VisitLocation = 5;
        const URL = 6;
        const Checklist = 7;
        const Custom = 8; // imported via third party app (AppigoPasteboard)
        const Internal = 9;
    }

    abstract class TaskRecurrenceType
    {
        const None = 0;
        const Weekly = 1;
        const Monthly = 2;
        const Yearly = 3;
        const Daily = 4;
        const Biweekly = 5;
        const Bimonthly = 6;
        const Semiannually = 7;
        const Quarterly = 8;
        const WithParent = 9;
        const Advanced = 50;
    }

    abstract class AdvancedRecurrenceType
    {
        const EveryXDaysWeeksMonths = 0;
        const TheXOfEachMonth = 1;
        const EveryMonTueEtc = 2;
        const Unknown = 3;
    }

    abstract class TaskLocationAlertType
    {
        const None = 0;
        const Arriving = 1;
        const Leaving = 2;
    }

    abstract class TaskSortOrder
    {
        const DatePriorityAlpha = 0;
        const PriorityDateAlpha = 1;
        const Alphabetical = 2;
    }

    const MON_SELECTION = 0x0001;
    const TUE_SELECTION	= 0x0002;
    const WED_SELECTION	= 0x0004;
    const THU_SELECTION	= 0x0008;
    const FRI_SELECTION	= 0x0010;
    const SAT_SELECTION	= 0x0020;
    const SUN_SELECTION	= 0x0040;
    const WEEKDAY_SELECTION = 0x001F;
    const WEEKEND_SELECTION = 0x0060;
    
    define ('TASK_NAME_LENGTH', 510);
    
    //This is used when sorting tasks with no due date, to ensure they come after tasks with a due date.
    //I plugged in the equivalent of NSDate distantFuture on iOS
    define ('NO_DATE_SORT_VALUE', 64092211200);

    /* #define kDBDueDateFirstDueDateSortExpression @" (CASE 1 WHEN (type=1) THEN (ds_due_date + (CASE 1 WHEN ((flags & 4) = 4) THEN 0 ELSE (%d - time_zone_offset + 43170) END)) ELSE (due_date + (CASE 1 WHEN ((flags & 1) = 1) THEN 0 ELSE (%d - time_zone_offset + 43170) END)) END)"
    */


    const PRIORITY_ORDER_BY_STATEMENT = "priority=0,priority ASC";
    

	class TDOTask extends TDODBObject
	{

		public function __construct()
		{
            parent::__construct();

			$this->set_to_default();
		}

		public function set_to_default()
		{
            parent::set_to_default();

            $this->setCompletionDate(0);
            $this->setCompStartDate(0);
            $this->setCompDueDate(0);
            $this->setCompDueDateHasTime(0);
            $this->setCompPriority(0);
            $this->setRecurrenceType(0);
//            $this->setChecklistUncompletedCount(0);
            $this->setTaskType(0);
            $this->setTypeData(NULL);
            $this->setProjectPriority(NULL);
            $this->setProjectStartDate(NULL);
            $this->setProjectDueDate(NULL);
            $this->setProjectDueDateHasTime(NULL);
            $this->setProjectStarred(NULL);
            $this->setLocationAlert(NULL);
            $this->setSortOrder(0);
		}


        // ------------------------
        // property Methods
        // ------------------------


		public function taskId()
		{
            if(empty($this->_publicPropertyArray['taskid']))
                return NULL;
            else
                return $this->_publicPropertyArray['taskid'];
		}
		public function setTaskId($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['taskid']);
            else
                $this->_publicPropertyArray['taskid'] = $val;

//			if ($updateCalDav == false)
//				return;
//
//			if ($this->caldavData() != NULL)
//				return;
//
//			$this->setCaldavData($this->newOrExistingCalDavString());
		}

		public function listId()
		{
            if(empty($this->_publicPropertyArray['listid']))
                return NULL;
            else
                return $this->_publicPropertyArray['listid'];
		}
		public function setListId($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['listid']);
            else
                $this->_publicPropertyArray['listid'] = $val;

//			if ($updateCalDav == false)
//				return;
//
//			if ($this->caldavData() != NULL)
//				return;
//
//			$this->setCaldavData($this->newOrExistingCalDavString());
		}

		// public function timestamp() - handled in TDODBObject

//		public function setTimestamp($val, $updateCalDav = false)
//		{
//            parent::setTimestamp($val);
//
////			if ($updateCalDav == false)
////				return;
////
////			$this->setCaldavData($this->newOrExistingCalDavString());
////
////			$calDavObj = Sabre_VObject_Reader::read($this->caldavData());
////			$calDavObj->vtodo[0]->lastmodified = date("Ymd\THis\Z", $this->timestamp());
////
////			$newDavData = $calDavObj->serialize();
////			if (empty($newDavData) == false)
////                $this->setCaldavData($newDavData);
//		}

		public function caldavUri()
		{
            if(empty($this->_privatePropertyArray['caldavuri']))
                return NULL;
            else
                return $this->_privatePropertyArray['caldavuri'];
		}
		public function setCaldavUri($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['caldavuri']);
            else
                $this->_privatePropertyArray['caldavuri'] = $val;

//			if ($updateCalDav == false)
//				return;
//
//			$this->setCaldavData($this->newOrExistingCalDavString());
		}

		public function caldavData()
		{
            if(empty($this->_privatePropertyArray['caldavdata']))
                return NULL;
            else
                return $this->_privatePropertyArray['caldavdata'];
		}
		public function setCaldavData($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['caldavdata']);
            else
                $this->_privatePropertyArray['caldavdata'] = $val;

//            if($this->caldavData() != NULL)
//                $this->setExtraInfoFromCalDAVData($this->caldavData());
		}
        
        
        public function setTaskValuesFromCaldavData($val)
        {
            //$this->setExtraInfoFromCalDAVData($val);
            $this->updateTaskParsingCalDavData($val);
        }

   		// public function name() - Handled in TDODBObject

//		public function setName($val, $updateCalDav = false)
//		{
//            parent::setName($val);
//
//			if ($updateCalDav == false)
//				return;
//
//            $this->setCaldavData($this->newOrExistingCalDavString());
//
//			$calDavObj = Sabre_VObject_Reader::read($this->caldaveData());
//			$calDavObj->vtodo[0]->summary = $this->name();
//			$this->incrementCalDavSequenceNumber($calDavObj);
//
//			$newDavData = $calDavObj->serialize();
//			if (empty($newDavData) == false)
//			{
//                $this->setCaldavData($newDavData);
//			}
//		}


		public function note()
		{
            if(empty($this->_publicPropertyArray['note']))
                return NULL;
            else
                return $this->_publicPropertyArray['note'];
		}
		public function setNote($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['note']);
            else
                $this->_publicPropertyArray['note'] = TDOUtil::ensureUTF8($val);

//			if ($updateCalDav == false)
//				return;
//
//			$this->setCaldavData($this->newOrExistingCalDavString());
//
//			$calDavObj = Sabre_VObject_Reader::read($this->caldavData());
//			$calDavObj->vtodo[0]->description = $this->note();
//			$this->incrementCalDavSequenceNumber($calDavObj);
//
//			$newDavData = $calDavObj->serialize();
//			if (empty($newDavData) == false)
//                $this->setCaldavData($newDavData);
        }

         //The start date stored in the database is the compStartDate, because we store the earliest
        //child start date of the project in that field to speed up our queries. To get the actual
        //start date of the task, we look at the start date field if it's a normal task or the project start date
        //field if it's a project
        
        public function compStartDate()
        {
            if(empty($this->_privatePropertyArray['comp_startdate']))
                return 0;
            else
                return $this->_privatePropertyArray['comp_startdate'];
        }
        
        public function setCompStartDate($val)
        {
            if(empty($val))
                unset($this->_privatePropertyArray['comp_startdate']);
            else
                $this->_privatePropertyArray['comp_startdate'] = $val;
        }
        
        public function startDate()
		{
            if($this->isProject())
                return $this->projectStartDate();
            else
                return $this->compStartDate();
		}
        
		public function setStartDate($val)
		{
            if($this->isProject())
                $this->setProjectStartDate($val);
            else
                $this->setCompStartDate($val);
		}
        
        //The due date stored in the database is the compDueDate, because we store the earliest
        //child due date of the project in that field to speed up our queries. To get the actual
        //due date of the task, we look at the due date field if it's a normal task or the project due date
        //field if it's a project
        
        public function compDueDate()
        {
            if(empty($this->_privatePropertyArray['comp_duedate']))
                return 0;
            else
                return $this->_privatePropertyArray['comp_duedate'];
        }

        public function setCompDueDate($val)
        {
            if(empty($val))
                unset($this->_privatePropertyArray['comp_duedate']);
            else
                $this->_privatePropertyArray['comp_duedate'] = $val;
        }

		public function dueDate()
		{
            if($this->isProject())
                return $this->projectDueDate();
            else
                return $this->compDueDate();
		}
        
		public function setDueDate($val)
		{
            if($this->isProject())
                $this->setProjectDueDate($val);
            else
                $this->setCompDueDate($val);
		}

        public function compDueDateHasTime()
        {
            if(empty($this->_privatePropertyArray['comp_duedatehastime']))
                return 0;
            else
                return $this->_privatePropertyArray['comp_duedatehastime'];
        }

        public function setCompDueDateHasTime($val)
        {
            if(empty($val))
                unset($this->_privatePropertyArray['comp_duedatehastime']);
            else
                $this->_privatePropertyArray['comp_duedatehastime'] = $val;
        }

        public function dueDateHasTime()
        {
            if($this->isProject())
                return $this->projectDueDateHasTime();
            else
                return $this->compDueDateHasTime();
        }
        
        public function setDueDateHasTime($val)
        {
            if($this->isProject())
                $this->setProjectDueDateHasTime($val);
            else
                $this->setCompDueDateHasTime($val);
        }
        

		public function completionDate()
		{
            if(empty($this->_publicPropertyArray['completiondate']))
                return 0;
            else
                return $this->_publicPropertyArray['completiondate'];
		}
		public function setCompletionDate($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['completiondate']);
            else
                $this->_publicPropertyArray['completiondate'] = $val;

//			if ($updateCalDav == false)
//				return;
//
//			$this->setCaldavData($this->newOrExistingCalDavString());
//
//			$calDavObj = Sabre_VObject_Reader::read($this->caldavData());
//
//			if ($val == 0)
//				$calDavObj->vtodo[0]->__unset('completed');
//			else
//				$calDavObj->vtodo[0]->completed = date("Ymd\THis\Z", $this->completionDate());
//			$this->incrementCalDavSequenceNumber($calDavObj);
//
//			$newDavData = $calDavObj->serialize();
//			if (empty($newDavData) == false)
//				$this->setCaldavData($newDavData);
		}
        
        //The priority stored in the database is the compPriority, because we store the highest
        //child priority of the project in that field to speed up our queries. To get the actual
        //priority of the task, we look at the priority field if it's a normal task or the project priority
        //field if it's a project

		public function compPriority()
		{
            if(empty($this->_privatePropertyArray['comp_priority']))
                return 0;
            else
                return $this->_privatePropertyArray['comp_priority'];
		}
		public function setCompPriority($val, $updateCalDav = false)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['comp_priority']);
            else
                $this->_privatePropertyArray['comp_priority'] = $val;
		}

        public function priority()
        {
            if($this->isProject())
                return $this->projectPriority();
            else
                return $this->compPriority();
        }
        
        public function setPriority($val)
        {
            if($this->isProject())
                $this->setProjectPriority($val);
            else
                $this->setCompPriority($val);
        }
    

		public function recurrenceType()
		{
            if(empty($this->_publicPropertyArray['recurrence_type']))
                return 0;
            else
                return $this->_publicPropertyArray['recurrence_type'];
		}
        public function setRecurrenceType($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['recurrence_type']);
            else
                $this->_publicPropertyArray['recurrence_type'] = intval($val);
        }


		public function advancedRecurrenceString()
		{
            if(empty($this->_publicPropertyArray['advanced_recurrence_string']))
                return NULL;
            else
                return $this->_publicPropertyArray['advanced_recurrence_string'];
		}
		public function setAdvancedRecurrenceString($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['advanced_recurrence_string']);
            else
                $this->_publicPropertyArray['advanced_recurrence_string'] = TDOUtil::ensureUTF8($val);
        }


		public function taskType()
		{
            if(empty($this->_publicPropertyArray['task_type']))
                return false;
            else
                return $this->_publicPropertyArray['task_type'];
		}
        public function setTaskType($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['task_type']);
            else
                $this->_publicPropertyArray['task_type'] = intval($val);
        }

        public function typeData()
		{
            if(empty($this->_publicPropertyArray['type_data']))
                return NULL;
            else
                return $this->_publicPropertyArray['type_data'];
		}
        public function setTypeData($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['type_data']);
            else
                $this->_publicPropertyArray['type_data'] = TDOUtil::ensureUTF8($val);
        }

        public function isProject()
        {
            $taskType = $this->taskType();
            if($taskType == TaskType::Project)
                return true;

            return false;
        }

        public function isChecklist()
        {
            $taskType = $this->taskType();
            if($taskType == TaskType::Checklist)
                return true;

            return false;
        }

        public function parentId()
        {
            if(empty($this->_publicPropertyArray['parentid']))
                return NULL;
            else
                return $this->_publicPropertyArray['parentid'];
        }
        public function setParentId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['parentid']);
            else
                $this->_publicPropertyArray['parentid'] = $val;
        }

        public function contextId()
        {
            if(empty($this->_publicPropertyArray['contextid']))
                return NULL;
            else
                return $this->_publicPropertyArray['contextid'];
        }
        public function setContextId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['contextid']);
            else
                $this->_publicPropertyArray['contextid'] = $val;
        }

		public function contextLastModified()
		{
            if(empty($this->_privatePropertyArray['context_last_modified']))
                return 0;
            else
                return $this->_privatePropertyArray['context_last_modified'];
		}
		public function setContextLastModified($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['context_last_modified']);
            else
                $this->_privatePropertyArray['context_last_modified'] = $val;
		}


        public function assignedUserId()
        {
            if(empty($this->_publicPropertyArray['assigneduserid']))
                return NULL;
            else
                return $this->_publicPropertyArray['assigneduserid'];
        }
        public function setAssignedUserId($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['assigneduserid']);
            else
                $this->_publicPropertyArray['assigneduserid'] = $val;
        }
    
        public function compStarredVal()
        {
            if(empty($this->_privatePropertyArray['comp_starred']))
                return false;
            else
                return $this->_privatePropertyArray['comp_starred'];
        }
        
		public function setCompStarredVal($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['comp_starred']);
            else
                $this->_privatePropertyArray['comp_starred'] = $val;
		}
        
        public function projectStarred()
        {
            if(empty($this->_privatePropertyArray['project_starred']))
                return false;
            else
                return $this->_privatePropertyArray['project_starred'];
        }
        
		public function setProjectStarred($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['project_starred']);
            else
                $this->_privatePropertyArray['project_starred'] = $val;
		}

		public function starred()
		{
            if($this->isProject())
                return $this->projectStarred();
            else
                return $this->compStarredVal();
		}
		public function setStarred($val)
		{
            if($this->isProject())
                $this->setProjectStarred($val);
            else
                $this->setCompStarredVal($val);
		}

		public function projectPriority()
		{
            if(empty($this->_privatePropertyArray['project_priority']))
                return 0;
            else
                return $this->_privatePropertyArray['project_priority'];
		}
		public function setProjectPriority($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['project_priority']);
            else
                $this->_privatePropertyArray['project_priority'] = $val;
		}

		public function projectStartDate()
		{
            if(empty($this->_privatePropertyArray['project_startdate']))
                return 0;
            else
                return $this->_privatePropertyArray['project_startdate'];
		}
		public function setProjectStartDate($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['project_startdate']);
            else
                $this->_privatePropertyArray['project_startdate'] = $val;
		}
		
		public function projectDueDate()
		{
            if(empty($this->_privatePropertyArray['project_duedate']))
                return 0;
            else
                return $this->_privatePropertyArray['project_duedate'];
		}
		public function setProjectDueDate($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['project_duedate']);
            else
                $this->_privatePropertyArray['project_duedate'] = $val;
		}

        public function projectDueDateHasTime()
		{
            if(empty($this->_privatePropertyArray['project_duedate_has_time']))
                return 0;
            else
                return $this->_privatePropertyArray['project_duedate_has_time'];
		}
		public function setProjectDueDateHasTime($val)
		{
            if(empty($val))
                unset($this->_privatePropertyArray['project_duedate_has_time']);
            else
                $this->_privatePropertyArray['project_duedate_has_time'] = $val;
		}

        public function locationAlert()
        {
            if(empty($this->_publicPropertyArray['location_alert']))
                return NULL;
            else
                return $this->_publicPropertyArray['location_alert'];
        }

        public function setLocationAlert($val)
        {
            if(empty($val))
                unset($this->_publicPropertyArray['location_alert']);
            else
                $this->_publicPropertyArray['location_alert'] = TDOUtil::ensureUTF8($val);
        }

        public function sortOrder()
        {
            if(empty($this->_publicPropertyArray['sort_order']))
                return 0;
            else
                return $this->_publicPropertyArray['sort_order'];
        }

        public function setSortOrder($val)
        {
            if($val != 0 && empty($val))
                unset($this->_publicPropertyArray['sort_order']);
            else
                $this->_publicPropertyArray['sort_order'] = $val;
        }

		public function getPropertiesArray($getDisplayInfo=true, $link=NULL)
		{
			$this->_publicPropertyArray['startdate'] = $this->startDate();
            $this->_publicPropertyArray['duedate'] = $this->dueDate();
            $this->_publicPropertyArray['duedatehastime'] = $this->dueDateHasTime();
            $this->_publicPropertyArray['priority'] = $this->priority();
            $this->_publicPropertyArray['starred'] = $this->starred();
        
            //TODO: Right now all the methods inside $getDisplayInfo ignore the passed link,
            //because we're trying to get sync done quickly, but we should go through later and fix those up
            
            if($getDisplayInfo)
            {
                //Workaround for bug 7410. For users whose timezone offset is +12 or greater, the normalized
                //GMT due date is a day ahead of the due date in their timezone. When we're returning task data
                //for display purposes, we should de-normalize dates if the timezone offset is greater
                //than +12
                $timezone = new DateTimeZone(date_default_timezone_get());
                if($this->dueDateHasTime() == false)
                {
                    $this->_publicPropertyArray['duedate'] = TDOUtil::gmtAdjustedDate($this->dueDate(), $timezone);
                }
                $this->_publicPropertyArray['startdate'] = TDOUtil::gmtAdjustedDate($this->startDate(), $timezone);

            
                //Bug 7023 - if the project's child date is earlier than it's due date, we should send
                //that back to the client so it can display it
                if($this->isProject() && $this->completionDate() == 0)
                {
                    if($this->compDueDate() != 0 && $this->compDueDate() != $this->dueDate())
                    {
                        $this->_publicPropertyArray['childduedate'] = $this->compDueDate();
                        $this->_publicPropertyArray['childduedatehastime'] = $this->compDueDateHasTime();
                        
                        if($this->compDueDateHasTime() == false)
                        {
                            $this->_publicPropertyArray['childduedate'] = TDOUtil::gmtAdjustedDate($this->compDueDate(), $timezone);
                        }
                        
                    }
                    if($this->compStartDate() != 0 && $this->compStartDate() != $this->startDate())
                    {
                        $this->_publicPropertyArray['childstartdate'] = TDOUtil::gmtAdjustedDate($this->compStartDate(), $timezone);
                    }
                }
            
                if($this->assignedUserId() != NULL)
                {
                    $userName = TDOUser::displayNameForUserId($this->assignedUserId());
                    if(!empty($userName))
                    {
                        $this->_publicPropertyArray['assigned_username'] = $userName;
                    }
                }
                if($this->listId() != NULL)
                {
                    $listName = TDOList::nameForListId($this->listId());
                    if(!empty($listName))
                    {
                        $this->_publicPropertyArray['listname'] = $listName;
                    }
                }                
                $session = TDOSession::getInstance();
                $listSettings = TDOListSettings::getListSettingsForUser($this->listId(), $session->getUserId());
                if($listSettings)
                {
                    $listColor = $listSettings->color();
                    if($listColor)
                        $this->_publicPropertyArray['listcolor'] = $listColor;
                }
                if($this->contextId() != NULL)
                {
                    $ctxName = TDOContext::getNameForContext($this->contextId());
                    if(!empty($ctxName))
                    {
                        $this->_publicPropertyArray['contextname'] = $ctxName;
                    }
                }
                $notificationsCount = TDOTaskNotification::getNotificationCountForTask($this->taskId());
                if(!empty($notificationsCount))
                {
                    $this->_publicPropertyArray['notificationscount'] = $notificationsCount;
                }
                
                if($this->taskType() == TaskType::Project)
                {
                    $subtaskCount = TDOTask::subtaskCountForProject($this->taskId());
                    if(!empty($subtaskCount))
                    {
                        $this->_publicPropertyArray['subtaskcount'] = $subtaskCount;
                    }
                }
                elseif($this->taskType() == TaskType::Checklist);
                {
                    $taskitoCount = TDOTaskito::taskitoCountForTask($this->taskId());
                    if(!empty($taskitoCount))
                    {
                        $this->_publicPropertyArray['taskitocount'] = $taskitoCount;
                    }
                }
                
            }

            $tagsCount = TDOTag::getTagCountForTask($this->taskId(), $link);
            if(!empty($tagsCount))
            {
                $this->_publicPropertyArray['tagscount'] = $tagsCount;
                $tagString = TDOTag::getTagStringForTask($this->taskId(), $link);
                if(!empty($tagString))
                    $this->_publicPropertyArray['tags'] = $tagString;
            }

            $commentCount = TDOComment::getCommentCountForItem($this->taskId(), false, $link);
            if(!empty($commentCount))
            {
                $this->_publicPropertyArray['commentcount'] = $commentCount;
            }


//            $fullTypeDataString = $this->typeData();
//
//            if(!empty($fullTypeDataString))
//            {
//                $lines = explode("\n", $fullTypeDataString);
//                $parsedDataString = "";
//
//                foreach($lines as $line)
//                {
//                    if( (strpos($line, "---") !== false) && (strpos($line, "name:") !== false) && (strpos($line, "contact:") !== false) )
//                    {
//                        $colonIndex = strpos($line, ":");
//                        if($colonIndex !== false && $colonIndex + 1 < strlen($line))
//                        {
//                            $parsedDataString = substr($line, $colonIndex + 1);
//                            break;
//                        }
//                    }
//                }
//
////                error_log('fulltypedatastring: '. $fullTypeDataString);
////                error_log('strlen: '. strlen($fullTypeDataString ));
////
////                error_log('parsedDataString: '. $parsedDataString);
////                error_log('strlen: '. strlen($parsedDataString ));
//                
//                
//                if(strlen($parsedDataString) > 0)
//                {
//                    $this->_publicPropertyArray['type_data'] = $parsedDataString;
//                }
//                else if (strlen($fullTypeDataString) > 0)
//                {
//	                $this->_publicPropertyArray['type_data'] = $fullTypeDataString;
//
//                }
//            }

            $locationAlertType = $this->parseLocationAlertType();
            if($locationAlertType != TaskLocationAlertType::None)
            {
                $locationAlertAddress = $this->parseReadableLocationAlertAddress();
                $this->_publicPropertyArray['location_alert_address'] = $locationAlertAddress;
                $this->_publicPropertyArray['location_alert_type'] = $locationAlertType;
            }


            return parent::getPropertiesArray();
		}

        
        public static function deleteChildrenOfChildrenOfTask($taskid, $link = NULL)
		{
			if(!isset($taskid))
				return false;
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::deleteChildrenOfChildrenOfTask() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;			
            
			$escapedTaskID = mysql_real_escape_string($taskid, $link);
			$sql = "UPDATE tdo_taskitos SET deleted=1, timestamp=" . time() ." WHERE parentid IN ";
            $sql .= "(SELECT taskid FROM tdo_tasks WHERE parentid = '$escapedTaskID' UNION SELECT taskid FROM tdo_completed_tasks WHERE parentid = '$escapedTaskID')";
            
			if(mysql_query($sql, $link))
			{

                if(mysql_affected_rows($link) > 0)
                {
                    $listId = TDOTask::getListIdForTaskId($taskid, $link);
                    if($listId != false)
                    {
                        TDOList::updateTaskitoTimestampForList($listId, time(), $link);
                    }
                }
                
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTaskito::deleteChildrenOfChildrenOfTask() could not delete $taskid: " . mysql_error());
			}
			
			if($closeLink == true)
				TDOUtil::closeDBLink($link);
            
			return false;
		}
        
//      CRG - I was adding timestamp updates and found that nobody called this method so I'm commenting it out
//		public static function deleteAllChildrenTasks($taskid, $link = NULL)
//		{
//			if(!isset($taskid))
//				return false;
//            
//			if($link == NULL)
//			{
//				$closeLink = true;
//				$link = TDOUtil::getDBLink();
//				if(!$link)
//				{
//					error_log("TDOTask::deleteAllChildrenTasks() could not get DB connection.");
//					return false;
//				}
//			}
//			else
//				$closeLink = false;
//            
//            
//            // Go to delete the children of the children of the task
//            if(TDOTask::deleteChildrenOfChildrenOfTask($taskid, $link) == false)
//            {
//				error_log("TDOTask::deleteAllChildrenTasks failed call to deleteChildrenOfChildrenOfTask");
//                if($closeLink == true)
//                    TDOUtil::closeDBLink($link);                
//                return false;
//            }
//
//            // Go and delete all of the notifications for all child tasks
//            if(TDOTaskNotification::deleteAllTaskNotificationsForChildrenOfTask($taskid, $link) == false)
//            {
//				error_log("TDOTask::deleteAllChildrenTasks failed call to TDOTaskNotification::deleteAllTaskNotificationsForChildrenOfTask");
//                if($closeLink == true)
//                    TDOUtil::closeDBLink($link);                
//                return false;
//            }
//
//            // Go and delete all of the comments for all child tasks
//            if(TDOComment::deleteAllCommentsForChildrenOfTask($taskid, $link) == false)
//            {
//				error_log("TDOTask::deleteAllChildrenTasks failed call to TDOComment::deleteAllCommentsForChildrenOfTask");
//                if($closeLink == true)
//                    TDOUtil::closeDBLink($link);                
//                return false;
//            }
//            
//			$escapedTaskID = mysql_real_escape_string($taskid, $link);
//
////			$sql = "UPDATE tdo_tasks SET deleted=1, timestamp='" . time() . "' WHERE parentid = '$escapedTaskID'";
//            
//            //Move the children to the deleted tasks table
//            $sql = "INSERT INTO tdo_deleted_tasks (".TASK_TABLE_FIELDS_STATIC.", completiondate,deleted,timestamp) SELECT ".TASK_TABLE_FIELDS_STATIC.", completiondate,'1','".time()."' FROM tdo_tasks WHERE parentid = '$escapedTaskID' UNION SELECT ".TASK_TABLE_FIELDS_STATIC.", completiondate, '1', '".time()."' FROM tdo_completed_tasks WHERE parentid = '$escapedTaskID'";
//            
////            error_log("SQL IS: ".$sql);
//            
//			if(!mysql_query($sql, $link))
//			{
//				error_log("TDOTask::deleteAllChildrenTasks() failed to delete children of task: ". $taskid . " with Error: " . mysql_error());
//
//				if($closeLink == true)
//					TDOUtil::closeDBLink($link);
//				return false;
//			}
//            //Now delete the children from the old table
//            $sql = "DELETE FROM tdo_tasks WHERE parentid='$escapedTaskID'";
//            if(!mysql_query($sql, $link))
//            {
//                error_log("TDOTask::deleteAllChildrenTasks() failed to delete children of task: ". $taskid . " with Error: " . mysql_error());
//
//				if($closeLink == true)
//					TDOUtil::closeDBLink($link);
//				return false;
//            }
//            
//            $sql = "DELETE FROM tdo_completed_tasks WHERE parentid='$escapedTaskID'";
//            if(!mysql_query($sql, $link))
//            {
//                error_log("TDOTask::deleteAllChildrenTasks() failed to delete children of task: ". $taskid . " with Error: " . mysql_error());
//
//				if($closeLink == true)
//					TDOUtil::closeDBLink($link);
//				return false;
//            }
//            
//            
//			if($closeLink == true)
//				TDOUtil::closeDBLink($link);
//            
//			return true;
//		}  
        
        public static function permanentlyDeleteTask($taskid, $tableName, $link = NULL)
        {
            if(empty($link))
            {
                $closeTransaction = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                    return false;
                
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("permanentlyDeleteTask couldn't start transaction");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            else
            {
                $closeTransaction = false;
            }
            
            if(TDOTaskito::permanentlyDeleteTaskitoChildrenOfTask($taskid, $link) == false)
            {
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            if(TDOTaskNotification::permanentlyDeleteAllTaskNotificationsForTask($taskid, $link) == false)
            {
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            if(TDOComment::permanentlyDeleteAllCommentsForTask($taskid, $link) == false)
            {
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            $escapedTaskId = mysql_real_escape_string($taskid, $link);
            
            $sql = "DELETE FROM ".$tableName." WHERE taskid='$escapedTaskId'";
            if(!mysql_query($sql, $link))
            {
                error_log("permanentlyDeleteTask failed with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;                
            }
    
            $sql = "DELETE FROM tdo_tag_assignments WHERE taskid='$escapedTaskId'";
            if(!mysql_query($sql, $link))
            {
                error_log("permanentlyDeleteTask failed with error: ".mysql_error());
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
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return false;
                }
                else
                    TDOUtil::closeDBLink($link);
            }
            
            return true;
            
        }
        

		public static function deleteObject($taskid, $link = NULL, $transactionInPlace = false)
		{
//			error_log("TDOTask::deleteObject('" . $taskid . "')");
			if(!isset($taskid))
				return false;

			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::deleteObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;
            
            // there are other methods like delete list that may already have a transation in place
            // so there is no need to start a transaction here
            if($transactionInPlace == false)
            {
                // First start a transaction since we'll be deleting a lot of children tasks and notifications
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("TDOTask::deleteObject Couldn't start transaction: ".mysql_error());
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
            $task = TDOTask::getTaskForTaskId($taskid, $link);
            
            //If the task is already deleted, just return true
            if($task->deleted())
            {
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
        
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);
                
                return true;
            }
            
            if($task->isProject())
            {
                $subtasks = TDOTask::getAllNondeletedSubtasksForTask($taskid, $link);
                if($subtasks === false)
                {
                    error_log("TDOTask delete subtasks could not get subtasks: ".mysql_error());
                    if($transactionInPlace == false)
                        mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);                
                    return false;
                }
                
                foreach($subtasks as $subtask)
                {
                    if(TDOTask::deleteObject($subtask->taskId(), $link, true) == false)
                    {
                        error_log("TDOTask delete subtasks failed: ".mysql_error());
                        if($transactionInPlace == false)
                            mysql_query("ROLLBACK", $link);
                        if($closeLink == true)
                            TDOUtil::closeDBLink($link);                
                        return false;
                    }
                }
            }
            else if($task->isChecklist())
            {
                // if this is a checklist, we need to delete all of it's subtasks
                // doing this will delete nothing on normal tasks
                if(TDOTaskito::deleteTaskitoChildrenOfTask($taskid, $link) == false)
                {
                    error_log("TDOTaskito::deleteTaskitoChildrenOfTask could not delete children tasks, rolling back transaction: ".mysql_error());
                    if($transactionInPlace == false)
                        mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);                
                    return false;
                }
            }
            
            
            if(TDOTaskNotification::deleteAllTaskNotificationsForTask($taskid, $link) == false)
            {
                error_log("TDOTask::deleteObject could not delete task notifications for task, rolling back transaction: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }

            if(TDOComment::deleteAllCommentsForTask($taskid, $link) == false)
            {
                error_log("TDOTask::deleteObject could not delete task comment for task, rolling back transaction: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }
            

			$escapedTaskID = mysql_real_escape_string($taskid, $link);

            //Set the task as deleted and re-add it, causing it to be added to the deleted tasks table
            $task->setDeleted(1);
            $task->setTimestamp(time());
            if($task->addObject($link) == false)
            {
                error_log("TDOTask::deleteObject could not add the object to the deleted table: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }

            //Now delete the task from its old table
            $sql = "DELETE FROM tdo_tasks WHERE taskid='$escapedTaskID'";
			if(!mysql_query($sql, $link))
			{
                error_log("TDOTask::deleteObject could not delete the object: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
			}
            
            $sql = "DELETE FROM tdo_completed_tasks WHERE taskid='$escapedTaskID'";
			if(!mysql_query($sql, $link))
			{
                error_log("TDOTask::deleteObject could not delete the object: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
			}
            
            TDOList::updateTaskTimestampForList(mysql_real_escape_string($task->listId()), time(), $link);

            if($transactionInPlace == false)
            {
                if(!mysql_query("COMMIT", $link))
                {
                    error_log("TDOTask::deleteObject Couldn't commit transaction deleting object: " . $taskid. " Error: ".mysql_error());
                    mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
			if($closeLink == true)
				TDOUtil::closeDBLink($link);

			return true;
		}

		public function deleteMe()
		{
			return TDOTask::deleteObject($this->taskId());
		}
        
        
        
        // this will move a task out of the tdo_task or tdo_completed_task or tdo_deleted_task table and put it into the tdo_archived_tasks
		public static function archiveObject($taskid, $link = NULL, $transactionInPlace = false)
		{
            //			error_log("TDOTask::deleteObject('" . $taskid . "')");
			if(!isset($taskid))
				return false;
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::archiveObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;
            
            // there are other methods like delete list that may already have a transation in place
            // so there is no need to start a transaction here
            if($transactionInPlace == false)
            {
                // First start a transaction since we'll be deleting a lot of children tasks and notifications
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("TDOTask::archiveObject Couldn't start transaction: ".mysql_error());
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
            $task = TDOTask::getTaskForTaskId($taskid, $link);
            if(!$task)
            {
                // if we didn't find the task, check to see if it's been archived already
                $foundTask = false;
                $taskid = mysql_real_escape_string($taskid, $link);

                $sql = "SELECT taskid FROM tdo_archived_tasks WHERE taskid='$taskid'";
                
                $result = mysql_query($sql);
                if($result)
                {
                    $resultArray = mysql_fetch_array($result);
                    if(isset($resultArray['taskid']))
                    {
                        // this means we already have archived it, return true
                        $foundTask = true;
                    }
                }
                
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);
                
                return $foundTask;
            }
            
            if($task->isProject())
            {
                $subtasks = TDOTask::getAllSubtasksForTask($taskid, $link);
                if($subtasks === false)
                {
                    error_log("TDOTask::archiveObject could not get subtasks: ".mysql_error());
                    if($transactionInPlace == false)
                        mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);                
                    return false;
                }
                
                foreach($subtasks as $subtask)
                {
                    if(TDOTask::archiveObject($subtask->taskId(), $link, true) == false)
                    {
                        error_log("TDOTask::archiveObject subtasks failed: ".mysql_error());
                        if($transactionInPlace == false)
                            mysql_query("ROLLBACK", $link);
                        if($closeLink == true)
                            TDOUtil::closeDBLink($link);                
                        return false;
                    }
                }
            }
            else if($task->isChecklist())
            {
                // if this is a checklist, we need to delete all of it's subtasks
                // doing this will delete nothing on normal tasks
                if(TDOTaskito::archiveTaskitoChildrenOfTask($taskid, $link, true) == false)
                {
                    error_log("TDOTaskito::archiveTaskitoChildrenOfTask could not archive children tasks, rolling back transaction: ".mysql_error());
                    if($transactionInPlace == false)
                        mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);                
                    return false;
                }
            }
            
            
            if(TDOTaskNotification::deleteAllTaskNotificationsForTask($taskid, $link) == false)
            {
                error_log("TDOTask::deleteObject could not delete task notifications for task, rolling back transaction: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }
            
            if(TDOComment::deleteAllCommentsForTask($taskid, $link) == false)
            {
                error_log("TDOTask::deleteObject could not delete task comment for task, rolling back transaction: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }
            
            
			$escapedTaskID = mysql_real_escape_string($taskid, $link);
            
            //Add the task to the tdo_archived_tasks and then delete it
            $task->setTimestamp(time());
            if($task->addObject($link, true) == false)
            {
                error_log("TDOTask::archiveObject could not add the object to the archive table: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
            }
            
            //Now delete the task from its old table
            $sql = "DELETE FROM tdo_tasks WHERE taskid='$escapedTaskID'";
			if(!mysql_query($sql, $link))
			{
                error_log("TDOTask::archiveObject could not delete the object: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
			}
            
            $sql = "DELETE FROM tdo_completed_tasks WHERE taskid='$escapedTaskID'";
			if(!mysql_query($sql, $link))
			{
                error_log("TDOTask::archiveObject could not delete the object: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
			}

            $sql = "DELETE FROM tdo_deleted_tasks WHERE taskid='$escapedTaskID'";
			if(!mysql_query($sql, $link))
			{
                error_log("TDOTask::archiveObject could not delete the object: " . $taskid. " Error: ".mysql_error());
                if($transactionInPlace == false)
                    mysql_query("ROLLBACK", $link);
                if($closeLink == true)
                    TDOUtil::closeDBLink($link);                
                return false;
			}
            
            TDOList::updateTaskTimestampForList(mysql_real_escape_string($task->listId()), time(), $link);
            
            if($transactionInPlace == false)
            {
                if(!mysql_query("COMMIT", $link))
                {
                    error_log("TDOTask::archiveObject Couldn't commit transaction deleting object: " . $taskid. " Error: ".mysql_error());
                    mysql_query("ROLLBACK", $link);
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
			if($closeLink == true)
				TDOUtil::closeDBLink($link);
            
			return true;
		}        
        

		public function addObject($link = NULL, $addToArchive=false)
		{
//			error_log("TDOTask::addObject()");
//			if($this->caldavData() == NULL)
//			{
//				error_log("TDOTask::addObject failed because task had no data");
//				return false;
//			}

			if($this->listId() == NULL)
			{
				error_log("TDOTask::addObject failed because list was not set");
				return false;
			}

            // CRG - Added for migration from legacy Todo Online
			if($this->taskId() == NULL) {
                $this->setTaskId(TDOUtil::uuid());
			}

			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::addObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;

			$taskid = mysql_real_escape_string($this->taskId(), $link);
			$listid = mysql_real_escape_string($this->listId(), $link);

			if ($this->name() == NULL) {
				$name = NULL;
			} else {
                $name = mb_strcut($this->name(), 0, TASK_NAME_LENGTH, 'UTF-8');
				$name = mysql_real_escape_string($name, $link);
            }

			if ($this->note() == NULL)
				$note = NULL;
			else
            {
				$note = mysql_real_escape_string($this->note(), $link);
                
                //Bug 7226 - Make sure the note is not too large
                if(TDOTask::noteIsTooLarge($note))
                {
                    error_log("TDOTask::addObject attempting to add oversized note to database");
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);
                    
                    return false;
                }
                
            }

			// default values are 0 so just set them
			$startDate = intval($this->compStartDate());
			$dueDate = intval($this->compDueDate());
			$completionDate = intval($this->completionDate());
            $dueDateHasTime = intval($this->compDueDateHasTime());

			if ($this->compPriority() == 0)
				$priority = 0;
			else
				$priority = mysql_real_escape_string($this->compPriority(), $link);

			if ($this->timestamp() == 0)
				$timestamp = time();
			else
				$timestamp = intval($this->timestamp());

			if ($this->caldavUri() == NULL)
				$caldavuri = mysql_real_escape_string($this->taskId(), $link);
			else
				$caldavuri = mysql_real_escape_string($this->caldavUri(), $link);

			if ($this->caldavData() == NULL)
				$caldavdata = NULL;
			else
				$caldavdata = mysql_real_escape_string($this->caldavData(), $link);

			if($this->assignedUserId() == NULL)
                $assignedUser = NULL;
            else
                $assignedUser = mysql_real_escape_string($this->assignedUserId(), $link);

            if($this->recurrenceType() == 0)
                $recurrenceType = 0;
            else
                $recurrenceType = intval($this->recurrenceType());

            if($this->advancedRecurrenceString() == NULL)
                $recurrenceString = NULL;
            else
                $recurrenceString = mysql_real_escape_string($this->advancedRecurrenceString(), $link);

//			if($this->checklistUncompletedCount() == 0)
//				$checklistUncompletedCount = 0;
//			else
//				$checklistUncompletedCount = intval($this->checklistUncompletedCount());

			if($this->parentId() == NULL)
				$parentid = NULL;
			else
				$parentid = mysql_real_escape_string($this->parentId(), $link);

			if($this->taskType() == 0)
				$taskType = 0;
			else
				$taskType = intval($this->taskType());

            if($this->typeData() == NULL)
                $typeData = NULL;
            else
                $typeData = mysql_real_escape_string($this->typeData(), $link);

            if($taskType != TaskType::Project)
            {
                $projectDueDate = 'NULL';
                $projectDueDateHasTime = 'NULL';
                $projectPriority = 'NULL';
                $projectStarred = 'NULL';
                $projectStartDate = 'NULL';
            }
            else
            {

                $projectDueDate = intval($this->projectDueDate());
                $projectDueDateHasTime = intval($this->projectDueDateHasTime());
                $projectPriority = intval($this->projectPriority());
                $projectStarred = intval($this->projectStarred());
                $projectStartDate = intval($this->projectStartDate());
            }
            
            if($this->compStarredVal() == 0)
                $starred = 0;
            else
                $starred = intval($this->compStarredVal());


            if($this->locationAlert() == NULL)
                $locationAlert = NULL;
            else
                $locationAlert = mysql_real_escape_string($this->locationAlert(), $link);

            if($this->sortOrder() == NULL)
                $sortOrder = 0;
            else
                $sortOrder = intval($this->sortOrder());

            $deleted = intval($this->deleted());
            
            $table = "tdo_tasks";
            if($addToArchive)
                $table = "tdo_archived_tasks";
            elseif($deleted)
                $table = "tdo_deleted_tasks";
            elseif($completionDate != 0)
                $table = "tdo_completed_tasks";
            
			// Create the task
            $sql_count = "SELECT COUNT(taskid) AS count FROM $table WHERE taskid='$taskid'";
            $result_count = mysql_query($sql_count);
            if ($result_count) {
                $resultArray = mysql_fetch_array($result_count);
                if (isset($resultArray['count']) && $resultArray['count'] > 0) {
                    return false;
                }
            }

			$sql = "INSERT INTO $table (taskid, listid, name, parentid, note, duedate, due_date_has_time, completiondate, priority, timestamp, caldavuri, caldavdata, deleted, task_type, type_data, assigned_userid, recurrence_type, advanced_recurrence_string, starred, project_priority, project_duedate, project_duedate_has_time, project_starred, location_alert, sort_order, startdate, project_startdate) VALUES ('$taskid', '$listid', '$name', '$parentid', '$note', '$dueDate', $dueDateHasTime, '$completionDate', '$priority', '$timestamp', '$caldavuri', '$caldavdata', $deleted, '$taskType', '$typeData', '$assignedUser', $recurrenceType, '$recurrenceString', $starred, $projectPriority, $projectDueDate, $projectDueDateHasTime, $projectStarred, '$locationAlert', $sortOrder, $startDate, $projectStartDate)";
			$result = mysql_query($sql, $link);
			if(!$result)
			{
				error_log("TDOTask::addObject() failed with error :" . mysql_error());
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return false;
			}

            TDOList::updateTaskTimestampForList(mysql_real_escape_string($this->listId()), time(), $link);
            
			if($closeLink == true)
				TDOUtil::closeDBLink($link);

			return true;
		}

        //Bug 7226 - Check the size of the note to make sure it's not greater than 1 MB before
        //trying to save it to the db
        public static function noteIsTooLarge($note)
        {
            if(!empty($note))
            {
                $numBytes = mb_strlen($note, '8bit');
                if($numBytes > 1048576) // number of bytes in a megabyte
                {
                    return true;
                }
            }
            
            return false;
        }


		public function updateObject($link = NULL, $updateTimeStamp = true)
		{
//			error_log("TDOTask::updateObject()");
			if($this->taskId() == NULL)
				return false;

			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::updateObject() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;

            $table = "tdo_tasks";
            if($this->deleted())
                $table = "tdo_deleted_tasks";
            elseif($this->completionDate() != 0)
                $table = "tdo_completed_tasks";

            $modTimeStamp = time();

            if($updateTimeStamp)
                $sql = "UPDATE $table SET timestamp='" . $modTimeStamp . "', ";
            else
                $sql = "UPDATE $table SET ";
                

			if($this->listId() == NULL)
            {
				error_log("TDOTask::updateObject() failed due to no listid being set on task: ".$this->taskId());
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return false;
            }
            else
				$sql = $sql . " listid = '" . mysql_real_escape_string($this->listId(), $link) . "'";

            
			if($this->caldavData() != NULL)
			{
				$sql = $sql . ",caldavdata = '" . mysql_real_escape_string($this->caldavData(), $link) . "'";

//				// We've received a CalDAV object, so fill out the other items
//				$this->setExtraInfoFromCalDAVData($this->caldavData());
			}
			else
				$sql = $sql . ",caldavdata=null";


			////
			if ($this->name() != NULL)
            {
                $name =  mb_strcut($this->name(), 0, TASK_NAME_LENGTH, 'UTF-8');
				$sql = $sql . ",name='" . mysql_real_escape_string($name, $link) . "'";
            }
			else
				$sql = $sql . ",name=null";

			if ($this->note() != NULL)
            {
                //Bug 7226 - Make sure the note is not too large
                if(TDOTask::noteIsTooLarge($this->note()))
                {
                    error_log("TDOTask::updateObject attempting to add oversized note to database");
                    if($closeLink == true)
                        TDOUtil::closeDBLink($link);
                    
                    return false;
                }
            
				$sql = $sql . ",note='" . mysql_real_escape_string($this->note(), $link) . "'";
            }
			else
				$sql = $sql . ",note=null";
            
            if($this->compStartDate() == 0)
            	$sql = $sql . ",startdate=0";
            else
            	$sql = $sql . ",startdate=". (double)$this->compStartDate();

			if($this->compDueDate() == 0)
				$sql = $sql . ",duedate=0";
			else
				$sql = $sql . ",duedate=" . (double)$this->compDueDate();

            $sql = $sql . ",due_date_has_time=".intval($this->compDueDateHasTime());

			$sql = $sql . ",completiondate='" . intval($this->completionDate()) . "'";

			if ($this->compPriority() != 0)
				$sql = $sql . ",priority='" . intval($this->compPriority()) . "'";
            else
				$sql = $sql . ",priority=0";

            $sql = $sql . ",deleted=" . intval($this->deleted());
            $sql = $sql . ",starred=" . intval($this->compStarredVal());

			if($this->parentId() != NULL)
                $sql = $sql . ",parentid = '" . mysql_real_escape_string($this->parentId(), $link) . "'";
			else
                $sql = $sql . ",parentid = null";

			if($this->taskType() != 0)
                $sql = $sql . ",task_type=" . intval($this->taskType());
			else
                $sql = $sql . ",task_type=0";

            if($this->typeData() != NULL)
                $sql = $sql . ",type_data = '" . mysql_real_escape_string($this->typeData(), $link) . "'";
			else
                $sql = $sql . ",type_data = null";

            if($this->assignedUserId() != NULL)
                $sql = $sql . ",assigned_userid='" . mysql_real_escape_string($this->assignedUserId(), $link) . "'";
            else
                $sql = $sql . ",assigned_userid=NULL";

            if ($this->recurrenceType() != 0)
				$sql = $sql . ",recurrence_type=" . intval($this->recurrenceType());
			else
				$sql = $sql . ",recurrence_type=0";

            if($this->advancedRecurrenceString() != NULL)
                $sql = $sql . ",advanced_recurrence_string='" . mysql_real_escape_string($this->advancedRecurrenceString(), $link) . "'";
            else
                $sql = $sql . ",advanced_recurrence_string=null";
        

            if($this->taskType() != TaskType::Project)
            {
                $sql = $sql . ",project_priority=NULL,project_duedate=NULL,project_duedate_has_time=NULL,project_starred=NULL,project_startdate=NULL";
            }
            else
            {

                $sql .= ",project_priority=".intval($this->projectPriority());
                $sql .= ",project_duedate=".intval($this->projectDueDate());
                $sql .= ",project_duedate_has_time=".intval($this->projectDueDateHasTime());
                $sql .= ",project_starred=".intval($this->projectStarred());
                $sql .= ",project_startdate=".intval($this->projectStartDate());

            }

            if($this->locationAlert() != NULL)
                $sql = $sql . ",location_alert='".mysql_real_escape_string($this->locationAlert(), $link). "'";
            else
                $sql = $sql . ",location_alert=null";

            if($this->sortOrder() != NULL)
                $sql = $sql . ",sort_order=".intval($this->sortOrder());
            else
                $sql = $sql . ",sort_order=0";

			$sql = $sql . " WHERE taskid = '" . mysql_real_escape_string($this->taskId(), $link) . "'";


//			error_log(" updateObject SQL: " . $sql);

			$response = mysql_query($sql, $link);
			if($response)
			{
                if($updateTimeStamp)
                    // the task timestamp is now stored on the list so be sure to update it
                    TDOList::updateTaskTimestampForList(mysql_real_escape_string($this->listId()), $modTimeStamp, $link);
                
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return true;
			}
			else
			{
				error_log("TDOTask::updateObject() failed to update task: ".$this->taskId());
				if($closeLink == true)
					TDOUtil::closeDBLink($link);
				return false;
			}
		}
        
        //This method should only be called from moveTaskToList, once we determine that the task doesn't need to be deleted and re-added
        public static function assignChildrenOfTaskToList($task, $listId, $link)
        {
            if(empty($task) || empty($listId) || empty($link))
            {
                error_log("assignChildrenOfTaskToList missing parameter");
                return false;
            }
            
            $tables = array("tdo_tasks", "tdo_completed_tasks", "tdo_deleted_tasks");
            foreach($tables as $table)
            {
                $sql = "UPDATE $table SET listid='".mysql_real_escape_string($listId, $link)."' WHERE parentid='".mysql_real_escape_string($task->taskId(), $link)."'";
                
                if(!mysql_query($sql, $link))
                {
                   error_log("assignChildrenOfTaskToList failed with error: ".mysql_error());
                   return false;
                }
            }
            
            return true;
        }
        
        
        //This will delete a task from its current list and add a copy of it to the new list
        //It recursively moves subtasks to the new list, and also deletes and recreates taskitos and notifications
        //for the task. It returns the newly created copy of the task that belongs to the new list
        public static function moveTaskToList($task, $listId, $link=NULL)
        {
            if(empty($task) || empty($listId))
            {
                error_log("TDOTask::moveTaskToList called with missing parameters");
                return false;
            }
            
            //We need to determine if there are any members of the new list
            //that weren't in the old list and if there were any members in the old list that aren't in the new list
            $oldListUsers = TDOList::getPeopleAndRolesForlistid($task->listId(), false, NULL, $link);
            $newListUsers = TDOList::getPeopleAndRolesForlistid($listId, false, NULL, $link);
        
            if($oldListUsers === false || $newListUsers === false)
            {
                error_log("moveTaskToList failed to get list memberships");
                return false;
            }
            
            //First, check if there are users in the original list that aren't in the new list, because
            //in this case we need to delete and re-add the task
            foreach($oldListUsers as $oldUserId => $role)
            {
                if(!isset($newListUsers[$oldUserId]))
                {
                    return TDOTask::deleteTaskFromOldListAndAddToNewList($task, $listId, $link);
                }
            }
            
            //If we didn't find any users above, check if there are any users in the new list that weren't
            //in the old list, because in that case we need to update timestamps on notifications and subtasks
            //belonging to this task
            foreach($newListUsers as $newUserId => $role)
            {
                if(!isset($oldListUsers[$newUserId]))
                {
                    return TDOTask::updateTaskListAndUpdateTimestamps($task, $listId, $link);
                }
            }
            
            //If we got to this point, the list membership for the old list is the same as the new list,
            //so just update the task and its subtasks
            $task->setListId($listId);
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOTask failed to get db link");
                    return false;
                }
                
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("TDOTask failed to start transaction");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            if($task->updateObject($link) == false)
            {
                error_log("moveTaskToList failed to update task with error: ".mysql_error());
                if($closeDBLink)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }

            if($task->isProject())
            {
                if(TDOTask::assignChildrenOfTaskToList($task, $listId, $link) == false)
                {
                    if($closeDBLink)
                    {
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                    }
                    return false;
                }
            }

            if($closeDBLink)
            {
                if(!mysql_query("COMMIT", $link))
                {
                    error_log("TDOTask failed to commit transaction");
                    
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return false;
                }
                else
                    TDOUtil::closeDBLink($link);
            }
        
            return true;

        }

        //This will delete a task from its current list and add a copy of it to the new list
        //It recursively moves subtasks to the new list, and also deletes and recreates taskitos and notifications
        //for the task. This method should be called when moving a task to a list when there are users in the old list
        //who are not in the new list.
        private static function deleteTaskFromOldListAndAddToNewList($task, $listId, $link=NULL)
        {
            $notifications = TDOTaskNotification::getNotificationsForTask($task->taskId(), false, $link);
            if($notifications === false)
            {
                return false;
            }
            $subTasks = NULL;
            $taskitos = NULL;
            if($task->isProject())
            {
                $subTasks = TDOTask::getAllNondeletedSubtasksForTask($task->taskId(), $link);
                if($subTasks === false)
                    return false;
            }
            else if($task->isChecklist())
            {
                $taskitos = TDOTaskito::getTaskitosForTask($task->taskId(), true, true, $link);
                if($taskitos === false)
                    return false;
            }
            
            if($link == NULL)
            {
                $closeTransaction = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOTask unable to get DBLink");
                    return false;
                }
                //Start a transaction so we don't end up with a partially moved task
                if(mysql_query("START TRANSACTION", $link) == false)
                {
                    error_log("TDOTask::deleteTaskFromOldListAndAddToNewList failed to start transaction");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            else
            {
                $closeTransaction = false;
            }
            
            //Delete the task that belongs to the old list
            //            $sql = "UPDATE tdo_tasks SET deleted=1, timestamp=".time()." WHERE taskid='".mysql_real_escape_string($task->taskId())."'";
            $oldTableName = "tdo_tasks";
            if($task->completionDate() != 0)
                $oldTableName = "tdo_completed_tasks";
            
            $modTimeStamp = time();
            
            $sql = "INSERT INTO tdo_deleted_tasks (".TASK_TABLE_FIELDS_STATIC.",completiondate,deleted,timestamp) SELECT ".TASK_TABLE_FIELDS_STATIC.", completiondate, '1', '".$modTimeStamp."' FROM $oldTableName WHERE taskid='".mysql_real_escape_string($task->taskId())."'";
            
            if(!mysql_query($sql, $link))
            {
                error_log("deleteTaskFromOldListAndAddToNewList failed to delete task with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            $sql = "DELETE FROM $oldTableName WHERE taskid='".mysql_real_escape_string($task->taskId())."'";
            if(!mysql_query($sql, $link))
            {
                error_log("deleteTaskFromOldListAndAddToNewList failed to delete task with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            //Update the task timestamp on the list we're deleting from
            TDOList::updateTaskTimestampForList($task->listId(), time());
            
            //Give the task a new id and assign it to the new list, then add it to the db
            $oldTaskId = $task->taskId();
            
            $newTaskId = TDOUtil::uuid();
            $task->setTaskId($newTaskId);
            $task->setListId($listId);
            $task->setTimeStamp(time());
            
            //If the assigned user for this task doesn't belong to the new list, clear the assigned user
            if($task->assignedUserId() != NULL)
            {
                if(TDOList::userCanEditList($listId, $task->assignedUserId()) == false)
                {
                    $task->setAssignedUserId(NULL);
                }
            }
            
            if($task->addObject($link) == false)
            {
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            // the task timestamp is now stored on the list so be sure to update it
            if(TDOList::updateTaskTimestampForList(mysql_real_escape_string($task->listId()), time(), $link) == false)
            {
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            
            //We need to update the tdo_context_assignments table and the tdo_tag_assignments table so the task will keep its context and tags
            //We should only make new context entries for users who will still be able to see this task (i.e. they are members of the new list)
            $sql = "INSERT INTO tdo_context_assignments (taskid, userid, contextid, context_assignment_timestamp) SELECT '$newTaskId', userid, contextid, '".time()."' FROM tdo_context_assignments WHERE taskid='$oldTaskId' AND userid IN (SELECT userid FROM tdo_list_memberships WHERE listid='$listId')";
            if(!mysql_query($sql, $link))
            {
                error_log("deleteTaskFromOldListAndAddToNewList failed to update contexts for task with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            $sql = "INSERT INTO tdo_tag_assignments (taskid, tagid) SELECT '$newTaskId', tagid FROM tdo_tag_assignments WHERE taskid='$oldTaskId'";
            if(!mysql_query($sql, $link))
            {
                error_log("deleteTaskFromOldListAndAddToNewList failed to update tags for task with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            //We also need to update all comments for this task to point to the new task id
            $sql = "UPDATE tdo_comments SET itemid='$newTaskId' WHERE item_type=7 AND itemid='$oldTaskId'";
            if(!mysql_query($sql, $link))
            {
                error_log("deleteTaskFromOldListAndAddToNewList failed to update comments for task with error: ".mysql_error());
                if($closeTransaction)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            //Now go through all notifications that used to belong to this task. Delete them,
            //then create copies assigned to the new parent task
            if($notifications != NULL)
            {
                foreach($notifications as $notification)
                {
                    if(TDOTaskNotification::deleteTaskNotification($notification->notificationId(), $link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                    
                    $notification->setNotificationId(TDOUtil::uuid());
                    $notification->setTaskId($newTaskId);
                    $notification->setTimeStamp(time());
                    
                    if($notification->addTaskNotification($link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                }
            }
            
            //Go through all taskitos that used to belong to this task. Delete them, then
            //create copies assigned to the new parent task
            if($taskitos != NULL)
            {
                foreach($taskitos as $taskito)
                {
                    if(TDOTaskito::deleteObject($taskito->taskitoId(), $link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                    $taskito->setTaskitoId(TDOUtil::uuid());
                    $taskito->setParentId($newTaskId);
                    $taskito->setTimeStamp(time());
                    
                    if($taskito->addObject($link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                }
            }
            
            //Go through all the subtasks and recursively call this method on them, so that we'll
            //also delete notifications and taskitos belonging to subtasks of this task
            if($subTasks != NULL)
            {
                foreach($subTasks as $subtask)
                {
                    $subtask->setParentId($newTaskId);
                    if(TDOTask::deleteTaskFromOldListAndAddToNewList($subtask, $listId, $link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                }
            }
            
            //If we get to this point, everything was successful!
            if($closeTransaction)
            {
                if(mysql_query("COMMIT", $link) == false)
                {
                    error_log("deleteTaskFromOldListAndAddToNewList failed to commit transaction: ".mysql_error());
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return false;
                }
                else
                {
                    TDOUtil::closeDBLink($link);
                }
            }
            
            return true;
        }
        
        //This method will update the list assignment for a task and also update timestamps for all
        //taskitos and notifications for that task. This should be called when moving a task to a new list
        //when there are users in the new list who weren't in the old list.
        private static function updateTaskListAndUpdateTimestamps($task, $listId, $link=NULL)
        {
            //If we got here, then all users from the old list are in the new list. Reassign the list id
            $task->setListId($listId);
            if(empty($link))
            {
                $closeTransaction = true;
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(empty($link))
                {
                    error_log("TDOTask failed to get db link");
                    return false;
                }
                
                if(!mysql_query("START TRANSACTION", $link))
                {
                    error_log("TDOTask failed to start transaction");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            else {
                $closeDBLink = false;
                $closeTransaction = false;
            }
            
            if($task->updateObject($link) == false)
            {
                error_log("updateTaskListAndUpdateTimestamps failed to update task with error: ".mysql_error());
                if($closeDBLink)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            //Update the timestamps for taskitos belonging to this task so they will
            //be synced
            if($task->isChecklist())
            {
                if(mysql_query("UPDATE tdo_taskitos SET timestamp=".time()." WHERE parentid='".mysql_real_escape_string($task->taskId(), $link)."'", $link) == false)
                {
                    if($closeDBLink)
                    {
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                    }
                    return false;
                }
                
                if(mysql_affected_rows($link) > 0)
                    TDOList::updateTaskitoTimestampForList($task->listId(), time(), $link);
            }
            
            //Update the timestamps for notifications belonging to this task so they will
            //be synced
            if(mysql_query("UPDATE tdo_task_notifications SET timestamp=".time()." WHERE taskid='".mysql_real_escape_string($task->taskId(), $link)."'", $link) == false)
            {
                if($closeDBLink)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            if(mysql_affected_rows($link) > 0)
                TDOList::updateNotificationTimestampForList($task->listId(), time(), $link);
            
            //If the task is a project, we need to call this method recursively on all subtasks so that
            //notifications and taskitos belonging to subtasks will be updated
            if($task->isProject())
            {
                $subTasks = TDOTask::getAllNondeletedSubtasksForTask($task->taskId(), $link);
                foreach($subTasks as $subtask)
                {
                    if(TDOTask::updateTaskListAndUpdateTimestamps($subtask, $listId, $link) == false)
                    {
                        if($closeTransaction)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        }
                        return false;
                    }
                }
            }
            
            if($closeDBLink)
            {
                if(!mysql_query("COMMIT", $link))
                {
                    error_log("TDOTask failed to commit transaction");
                    
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return false;
                }
                else
                    TDOUtil::closeDBLink($link);
            }
            
            return true;
        }
        
        //Call this if you want to mark a task as updated without doing a full update (for
        //example if a comment is added or removed)
        public static function updateTimestampForTask($taskid)
        {
            if(empty($taskid))
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask failed to get DB link");
                return false;
            }
            $taskModTime = time();
            
            $taskid = mysql_real_escape_string($taskid, $link);
            $sql = "UPDATE tdo_tasks SET timestamp=".$taskModTime." WHERE taskid='$taskid'";
            
            if(!mysql_query($sql, $link))
            {
                error_log("TDOTask::updateTimestampForTask failed: ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $sql = "UPDATE tdo_completed_tasks SET timestamp=".$taskModTime." WHERE taskid='$taskid'";
            if(!mysql_query($sql, $link))
            {
                error_log("TDOTask::updateTimestampForTask failed: ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            $sql = "UPDATE tdo_deleted_tasks SET timestamp=".$taskModTime." WHERE taskid='$taskid'";
            if(!mysql_query($sql, $link))
            {
                error_log("TDOTask::updateTimestampForTask failed: ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            $listId = TDOTask::getListIdForTaskId($taskid, $link);
            if($listId != false)
            {
                TDOList::updateTaskTimestampForList($listId, $taskModTime, $link);
            }
            
            TDOUtil::closeDBLink($link);
            return true;
        }

        //Returns the former subtasks of the project
        public function removeSubtasksFromProject($link=NULL)
        {
           if($this->taskType() != TaskType::Project)
            return false;

            if($link == NULL)
            {
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask failed to get dblink");
                    return false;
                }
                $closeDBLink = true;
            }
            else
                $closeDBLink = false;
            $subTasks = TDOTask::getAllNondeletedSubtasksForTask($this->taskId(), $link);

			if(!empty($subTasks))
			{
				foreach($subTasks as $subTask)
				{
                    $subTask->setParentId(NULL);
                    $subTask->setSortOrder(0);
                    if($subTask->recurrenceType() == TaskRecurrenceType::WithParent || $subTask->recurrenceType() == TaskRecurrenceType::WithParent + 100)
                        $subTask->setRecurrenceType(TaskRecurrenceType::None);

                    if($subTask->updateObject() == false)
                    {
                        error_log("removeSubtasksFromProject failed");
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return false;
                    }
                }
			}
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return $subTasks;

        }

        //Returns the new tasks created from the taskitos
        public function convertTaskitosToTasks($preserveParentID, $link=NULL)
        {
           if($this->taskType() != TaskType::Checklist)
            return false;

            if($link == NULL)
            {
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask failed to get dblink");
                    return false;
                }
                $closeDBLink = true;
            }
            else
                $closeDBLink = false;

            $taskitos = TDOTaskito::getTaskitosForTask($this->taskId(), true, true, $link);
            $createdTasks = array();
			if(!empty($taskitos))
			{
				foreach($taskitos as $taskito)
				{
					$TDOTask = new TDOTask();

					$TDOTask->setListId($this->listId());
                    if($preserveParentID)
                    {
                        $TDOTask->setParentId($this->taskId());
                        $TDOTask->setSortOrder($taskito->sortOrder());
                    }
                    else
                    {
                        $TDOTask->setParentId($this->parentId());
                        $TDOTask->setSortOrder(0);
                    }

					$TDOTask->setName($taskito->name(), true);
                    $TDOTask->setRecurrenceType(TaskRecurrenceType::WithParent);

					if($taskito->completionDate() != "0")
						$TDOTask->setCompletionDate($taskito->completionDate(), true);

					if($TDOTask->addObject($link) == false)
					{
						error_log("TDOTask::Could not add subtask to project, rolling back".mysql_error());

                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
						return false;
					}
                    $createdTasks[] = $TDOTask;

					if(TDOTaskito::deleteObject($taskito->taskitoId(), $link) == false)
					{
						error_log("TDOTask::Could not remove subtask, rolling back".mysql_error());

                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
						return false;
					}
				}
			}
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return $createdTasks;

        }
        //Returns the uncompleted child count
        public function convertTasksToTaskitos($preserveParentID, $link=NULL)
        {
            $uncompletedCount = 0;

			// first read the subtasks outside of the sql transaction
            $subTasks = TDOTask::getAllNondeletedSubtasksForTask($this->taskId(), $link);

            if($link == NULL)
            {
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOList failed to get dblink");
                    return false;
                }
                $closeDBLink = true;
            }
            else
                $closeDBLink = false;

			if(empty($subTasks) == false)
			{
				foreach($subTasks as $subtask)
				{
                    //If the subtask is a checklist, we need to move all of its taskitos to the parent checklist so we don't lose them
                    if($subtask->isChecklist())
                    {
                        $subtaskitos = TDOTaskito::getTaskitosForTask($subtask->taskId(), true, true, $link);
                        if(!empty($subtaskitos))
                        {
                            foreach($subtaskitos as $subtaskito)
                            {
                                $subtaskito->setParentId($this->taskId());
                                $subtaskito->setSortOrder(0);
                                if($subtaskito->updateObject($link) == false)
                                {
                                    error_log("TDOTask::Could not move taskito to new checklist");
                                    if($closeDBLink)
                                        TDOUtil::closeDBLink($link);
                                    return false;
                                }
                            }
                        }
                    }

					$taskito = new TDOTaskito();

                    if($preserveParentID)
                    {
                        $taskito->setParentId($this->taskId());
                        $taskito->setSortOrder($subtask->sortOrder());
                    }
                    else
                    {
                        $taskito->setParentId(NULL);
                        $taskito->setSortOrder(0);
                    }

					$taskito->setName($subtask->name(), true);
					if($subtask->completionDate() != "0")
						$taskito->setCompletionDate($subtask->completionDate(), true);

					if($taskito->addObject($link) == false)
					{
						error_log("TDOTask::Could not add subtask to project, rolling back".mysql_error());
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
						return false;
					}

					if(TDOTask::deleteObject($subtask->taskId(), $link) == false)
					{
						error_log("TDOTask::Could not remove subtask from project, rolling back".mysql_error());
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
						return false;
					}
				}
			}
            if($closeDBLink)
                TDOUtil::closeDBLink($link);

			return true;
        }

        public static function taskitoFromTask($subtask)
        {
            $taskito = new TDOTaskito();

            $taskito->setParentId(NULL);
            $taskito->setSortOrder(0);
            
            $taskito->setName($subtask->name(), true);
            if($subtask->completionDate() != "0")
                $taskito->setCompletionDate($subtask->completionDate(), true);
            
            return $taskito;
        }
        
        public static function taskFromTaskito($taskito)
        {
            $subtask = new TDOTask();
            $subtask->setParentId(NULL);
            $subtask->setSortOrder(0);
            
            $subtask->setName($taskito->name());
            $subtask->setCompletionDate($taskito->completionDate(), true);
            
            return $subtask;
        }

		public function setParentValuesForProject()
		{
            //none of the children have due dates at this point, so we are safe assigning our own due date
            $this->setProjectStartDate($this->compStartDate());
            $this->setProjectDueDate($this->compDueDate());
            $this->setProjectPriority($this->compPriority());
            $this->setProjectDueDateHasTime($this->compDueDateHasTime());
            $this->setProjectStarred($this->compStarredVal());
        }

		public function removeParentValuesFromProject()
		{
			$this->setCompStartDate($this->projectStartDate());
            $this->setCompDueDate($this->projectDueDate());
            $this->setCompDueDateHasTime($this->projectDueDateHasTime());
            $this->setCompPriority($this->projectPriority());
            $this->setCompStarredVal($this->projectStarred());
            $this->setProjectDueDate(NULL);
            $this->setProjectDueDateHasTime(NULL);
            $this->setProjectPriority(NULL);
            $this->setProjectStarred(NULL);
		}

        //This method returns any new tasks that were created as a result of switching a project/checklist to a normal task
        public function updateTaskType($newTaskType, $newTypeData=NULL)
        {
            $newTasks = NULL;

            $oldTaskType = $this->taskType();
            $oldTypeData = $this->typeData();

            if($newTaskType == $oldTaskType && $newTypeData == $oldTypeData)
                return false;

            if($newTaskType == TaskType::Checklist || $newTaskType == TaskType::Project || $newTaskType == TaskType::Normal)
                $newTypeData = NULL;

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask unable to get DB link");
                return false;
            }

			// Do all of this in a transaction so we won't end up with a partially converted project
			if(!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTask::Couldn't start transaction".mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}

            switch($newTaskType)
            {
                case TaskType::Project:
                {
                    if($oldTaskType == TaskType::Checklist)
                    {
                        $result = $this->convertTaskitosToTasks(true, $link);
                        if($result === false)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            return false;
                        }
                    }

                    $this->setParentValuesForProject();
                    break;
                }
                case TaskType::Checklist:
                {
                    if($oldTaskType == TaskType::Project)
                    {
                        $result = $this->convertTasksToTaskitos(true, $link);
                        if($result === false)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            return false;
                        }
                        $this->removeParentValuesFromProject();
                    }
                    
                    break;
                }
                default:
                {
                    if($oldTaskType == TaskType::Checklist)
                    {
                        $newTasks = $this->convertTaskitosToTasks(false, $link);
                        if($newTasks === false)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            return false;
                        }
                    }
                    elseif($oldTaskType == TaskType::Project)
                    {
                        $newTasks = $this->removeSubtasksFromProject($link);
                        if($newTasks === false)
                        {
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            return false;
                        }
                        $this->removeParentValuesFromProject();
                    }
                    
                    break;
                }
            }

            $this->setTaskType($newTaskType);
            $this->setTypeData($newTypeData);

            if($this->updateObject($link) == false)
			{
				error_log("TDOTask::Could not update task type, rolling back".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}

			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTask::Couldn't commit transaction changing task type".mysql_error());
				mysql_query("ROLLBACK");
				TDOUtil::closeDBLink($link);
				return false;
			}

            TDOUtil::closeDBLink($link);
            if(!empty($newTasks))
                return $newTasks;
            else
                return true;
        }

        public static function fullTypeDataStringFromTypeData($typeData, $type)
        {
            switch ($type)
            {
                case TaskType::CallContact:
                {
                    $string = "contact: ".$typeData."\n";
                    $string .= "other: ".$typeData;

                    return $string;
                }
                case TaskType::SMSContact:
                {
                    $string = "contact: ".$typeData."\n";
                    $string .= "other: ".$typeData;

                    return $string;
                }
                case TaskType::EmailContact:
                {

                    $string = "contact: ".$typeData."\n";
                    $string .= "other: ".$typeData;

                    return $string;
                }
                case TaskType::VisitLocation:
                {
                    $string = "contact: ".$typeData."\n";
                    $string .= "location: ".$typeData;

                    return $string;
                }
                case TaskType::URL:
                {
                    $string = "url: ".$typeData;

                    return $string;
                }
                case TaskType::Normal:
                case TaskType::Project:
                default:
                {
                    return NULL;
                }
            }
        }

        // This method takes the id of a parent task and fixes up its priority, duedate, and starred
        // The values being updated here are for display only.  They are not properties that will be synced
        // they are only for display purposes.
        public static function fixupChildPropertiesForTask($task, $updateTask=true, $link=NULL)
        {
            if(empty($task))
            {
                error_log("fixupChildPropertiesForTask found no task");
                return false;
            }
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask::fixupChildPropertiesForTask could not get DB connection.");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            $newCompPriority = $task->projectPriority();

            $escapedTaskId = mysql_real_escape_string($task->taskId(), $link);
//            $sql = "SELECT priority FROM tdo_tasks WHERE completiondate=0 AND deleted=0 AND priority != 0 AND parentid='".$escapedTaskId."' ORDER BY priority ASC LIMIT 1";
            $sql = "SELECT priority FROM tdo_tasks WHERE priority != 0 AND parentid='".$escapedTaskId."' ORDER BY priority ASC LIMIT 1";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['priority']))
                    {
                        $bestChildPriority = $row['priority'];
                        if($newCompPriority == 0 || ($bestChildPriority != 0 && $bestChildPriority < $newCompPriority))
                            $newCompPriority = $bestChildPriority;
                    }
                }
            }
            else
            {
                error_log("TDOTask::fixupChildProperties() could not get child priority" . mysql_error());
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }
            $task->setCompPriority($newCompPriority);

            $newCompDueDate = $task->projectDueDate();
            $newCompDueDateHasTime = $task->projectDueDateHasTime();

            $dateTime = new DateTime();
            $dateTime->setTimeStamp(time());
            $timezoneOffset = $dateTime->getOffset() * -1 + 43170;

//            $sql = "SELECT duedate, due_date_has_time FROM tdo_tasks WHERE completiondate=0 AND deleted=0 AND duedate != 0 AND parentid='".$escapedTaskId."' ORDER BY (duedate + (CASE 1 WHEN (due_date_has_time=1) THEN 0 ELSE ($timezoneOffset) END)) LIMIT 1";

            $sql = "SELECT duedate, due_date_has_time FROM tdo_tasks WHERE duedate != 0 AND parentid='".$escapedTaskId."' ORDER BY (duedate + (CASE 1 WHEN (due_date_has_time=1) THEN 0 ELSE ($timezoneOffset) END)) LIMIT 1";

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['duedate']))
                    {
                        $bestChildDueDate = $row['duedate'];
                        if($newCompDueDate == 0 || ($bestChildDueDate != 0 && $bestChildDueDate < $newCompDueDate))
                        {

                            $newCompDueDate = $bestChildDueDate;
                            $newCompDueDateHasTime = $row['due_date_has_time'];
                        }
                    }
                }
            }
            else
            {
                error_log("TDOTask::fixupChildProperties() could not get lowest child due date" . mysql_error());
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }
            $task->setCompDueDate($newCompDueDate);
            $task->setCompDueDateHasTime($newCompDueDateHasTime);

            $newCompStartDate = $task->projectStartDate();
            $sql = "SELECT startdate FROM tdo_tasks WHERE startdate != 0 AND parentid='".$escapedTaskId."' ORDER BY startdate LIMIT 1";
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['startdate']))
                    {
                        $bestChildStartDate = $row['startdate'];
                        if($newCompStartDate == 0 || ($bestChildStartDate != 0 && $bestChildStartDate < $newCompStartDate))
                        {
                            $newCompStartDate = $bestChildStartDate;
                        }
                    }
                }
            }
            else
            {
                error_log("TDOTask::fixupChildProperties() could not get lowest child start date" . mysql_error());
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }
            $task->setCompStartDate($newCompStartDate);

            $compStarred = $task->projectStarred();
            
            if(!$compStarred)
            {
//                $sql = "SELECT COUNT(taskid) FROM tdo_tasks WHERE starred!=0 AND deleted=0 AND completiondate=0 AND parentid='".$escapedTaskId."'";
                $sql = "SELECT COUNT(taskid) FROM tdo_tasks WHERE starred!=0 AND parentid='".$escapedTaskId."'";
                
                $result = mysql_query($sql, $link);
                if($result)
                {
                    if($row = mysql_fetch_array($result))
                    {
                        if(isset($row['0']) && $row['0'] > 0)
                        {
                            $compStarred = 1;
                        }
                    }
                }
                else
                {
                    error_log("TDOTask::fixupChildProperties() could not get starred child count" . mysql_error());
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            $task->setCompStarredVal($compStarred);
            
            if($updateTask)
            {
                // update the parent object but don't change it's timestamp
                if(!$task->updateObject($link, false))
                {
                    error_log("Update task failed to fix up child values");
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return true;

        }

        public static function getDueDateOrderByStatement()
        {
        
        //Changing this to consider start dates
//            $dateTime = new DateTime();
//            $dateTime->setTimeStamp(time());
//            $timezoneOffset = $dateTime->getOffset() * -1 + 43170;
//
//            $noDateOrder = "duedate=0,";
//            $normalTaskDateOrder = "duedate + (CASE 1 WHEN (due_date_has_time=1) THEN 0 ELSE ($timezoneOffset) END)";
//
//            return $noDateOrder . $normalTaskDateOrder;


            $endTodayInterval = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y"));
            
            $dateTime = new DateTime();
            $dateTime->setTimeStamp(time());
            $timezoneOffset = $dateTime->getOffset() * -1 + 43170;
            
            $dueDateSort = "(CASE WHEN duedate=0 THEN ".NO_DATE_SORT_VALUE." ELSE duedate + (CASE WHEN (due_date_has_time=1) THEN 0 ELSE ($timezoneOffset) END) END)";
            $startDateSubSort = "(CASE WHEN startdate > $endTodayInterval THEN startdate + ".($timezoneOffset + 1)." ELSE ".($endTodayInterval + 1)." END)";
            
            $startDateSort = "CASE WHEN (startdate != 0 AND (duedate = 0 OR ((startdate + $timezoneOffset) < duedate AND duedate > $endTodayInterval))) THEN $startDateSubSort ELSE $dueDateSort END";

            //If the task is sorting by start date, add a secondary sort by due date in case the start dates are equal
            $secondaryDateSort = "CASE WHEN (startdate != 0 AND (duedate = 0 OR ((startdate + $timezoneOffset) < duedate AND duedate > $endTodayInterval))) THEN $dueDateSort ELSE 0 END";
            
            return $startDateSort.",".$secondaryDateSort;



        }

        public static function buildOrderByStatementForUser($userid, $completed)
        {
            $dueDateOrderByStatement = TDOTask::getDueDateOrderByStatement();

            if($completed)
				return " ORDER BY completiondate DESC,sort_order, ".PRIORITY_ORDER_BY_STATEMENT.", name ASC, taskid ASC";
			else
            {
                if(!empty($userid))
                {
                    $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
                    if(!empty($userSettings))
                    {
                        $taskSortOrder = $userSettings->taskSortOrder();
                        if(!empty($taskSortOrder))
                        {
                            switch($userSettings->taskSortOrder())
                            {
                                case TaskSortOrder::PriorityDateAlpha:
                                {
                                    return " ORDER BY ".PRIORITY_ORDER_BY_STATEMENT.", ".$dueDateOrderByStatement.", sort_order, name ASC";
                                }
                                case TaskSortOrder::Alphabetical:
                                {
                                    return " ORDER BY name ASC, ".$dueDateOrderByStatement.", ".PRIORITY_ORDER_BY_STATEMENT;
                                }
                                case TaskSortOrder::DatePriorityAlpha:
                                default:
                                {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            return " ORDER BY ".$dueDateOrderByStatement.", ".PRIORITY_ORDER_BY_STATEMENT.", sort_order, name ASC";
        }
        
        private static function sqlForDatedSection($sectionBeginDate, $sectionEndDate, $useStartDate, $useActualProjectProperties, $startDateBounded = true)
        {
            
            $dueDateSql = "";
            $projectDateSql = "";
            

            if($sectionBeginDate && $sectionEndDate)
            {
                $dueDateSql = "((duedate >= $sectionBeginDate AND duedate <= $sectionEndDate)";
                $projectDateSql = "((project_duedate >= $sectionBeginDate AND project_duedate <= $sectionEndDate)";
            }
            else if($sectionEndDate)
            {
                $dueDateSql = "((duedate != 0 AND duedate <= $sectionEndDate)";
                $projectDateSql = "((project_duedate !=0 AND project_duedate <= $sectionEndDate)";
            }
            else if($sectionBeginDate)
            {
                $dueDateSql = "((duedate != 0 AND duedate >= $sectionBeginDate)";
                $projectDateSql = "((project_duedate !=0 AND project_duedate >= $sectionBeginDate)";
            }
            else
            {
                $dueDateSql = "((duedate = 0)";
                $projectDateSql = "((project_duedate = 0)";
            }
            
            if($useStartDate)
            {
                if($sectionBeginDate && $startDateBounded && $sectionEndDate)
                {
                    $dueDateSql .= " OR (startdate >= $sectionBeginDate AND startdate <= $sectionEndDate)";
                    $projectDateSql .= " OR (project_startdate >= $sectionBeginDate AND project_startdate <= $sectionEndDate)";
                }
                else if($sectionEndDate)
                {
                    $dueDateSql .= " OR (startdate != 0 AND startdate <= $sectionEndDate)";
                    $projectDateSql .= " OR (project_startdate != 0 AND project_startdate <= $sectionEndDate)";
                }
                else
                {
                    $dueDateSql .= " OR (startdate != 0)";
                    $projectDateSql .= " OR (project_startdate != 0)";
                }
            }
            
            $dueDateSql .= ")";
            $projectDateSql .= ")";
            
            
            if($useActualProjectProperties)
            {
                return "( (task_type=1 AND $projectDateSql) OR (task_type!=1 AND $dueDateSql) )";
            }
            else
            {
                return $dueDateSql;
            }
        }

		public static function buildSQLFilterForSectionID($sectionID, $useActualProjectProperties, $completedTaskLimit, $userid)
		{
			$sqlFilter = "";

            $offset = TDOUtil::filterOffsetForCurrentGMTOffset();
            
            if($sectionID == "overdue_tasks_container")
            {
                //If we got to here, we know the overdue section is being shown
                //Overdue section goes from distant past to yesterday at 23:59:59
				$endDate = mktime(0, 0, -1, date("n"), date("j"), date("Y")) + $offset;
                
                $sqlFilter = TDOTask::sqlForDatedSection(0, $endDate, false, $useActualProjectProperties);
            }
			else if($sectionID == "today_tasks_container")
			{
                //If the overdue section is showing, today is just today. Otherwise, it's today and overdue
                $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
                if(empty($userSettings) || $userSettings->showOverdueSection() == false)
                {
                    $endDate = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y")) + $offset;
                    $sqlFilter = TDOTask::sqlForDatedSection(0, $endDate, true, $useActualProjectProperties, false);

                }
                else
                {
                    $startDate = mktime(0, 0, 0, date("n"), date("j"), date("Y")) + $offset;
                    $endDate = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y")) + $offset;
                    
                    $sqlFilter = TDOTask::sqlForDatedSection($startDate, $endDate, true, $useActualProjectProperties, false);
                }
			}
			else if($sectionID == "tomorrow_tasks_container")
			{
				$startDate = mktime(0, 0, 0, date("n"), (date("j")+1), date("Y")) + $offset;
				$endDate = mktime(0, 0, -1, date("n"), (date("j")+2), date("Y")) + $offset;

                $sqlFilter = TDOTask::sqlForDatedSection($startDate, $endDate, true, $useActualProjectProperties);
            }
			else if($sectionID == "nextsevendays_tasks_container")
			{
				$startDate = mktime(0, 0, 0, date("n"), (date("j")+2), date("Y")) + $offset;
				$endDate = mktime(0, 0, -1, date("n"), (date("j")+8), date("Y")) + $offset;

                $sqlFilter = TDOTask::sqlForDatedSection($startDate, $endDate, true, $useActualProjectProperties);
			}
			else if($sectionID == "future_tasks_container")
			{
				$startDate = time(0, 0, 0, date("n"), (date("j")+8), date("Y")) + $offset;

                $sqlFilter = TDOTask::sqlForDatedSection($startDate, 0, true, $useActualProjectProperties);
			}
			else if($sectionID == "noduedate_tasks_container")
			{
                $sqlFilter = TDOTask::sqlForDatedSection(0, 0, false, $useActualProjectProperties);
			}
            else if($sectionID == "high_tasks_container")
            {
                //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
                if($useActualProjectProperties)
                    $sqlFilter = " ( (task_type=1 AND (project_priority = 1)) OR (task_type!=1 AND (priority = 1))) ";
                else
                    $sqlFilter = " (priority = 1) ";
            }
            else if($sectionID == "medium_tasks_container")
            {
                //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
                if($useActualProjectProperties)
                    $sqlFilter = " ( (task_type=1 AND (project_priority = 5)) OR (task_type!=1 AND (priority = 5))) ";
                else
                    $sqlFilter = " (priority = 5) ";
            }
            else if($sectionID == "low_tasks_container")
            {
                //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
                if($useActualProjectProperties)
                    $sqlFilter = " ( (task_type=1 AND (project_priority = 9)) OR (task_type!=1 AND (priority = 9))) ";
                else
                    $sqlFilter = " (priority = 9) ";
            }
            else if($sectionID == "none_tasks_container")
            {
                //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
                if($useActualProjectProperties)
                    $sqlFilter = " ( (task_type=1 AND (project_priority = 0)) OR (task_type!=1 AND (priority = 0))) ";
                else
                    $sqlFilter = " (priority = 0) ";
            }
            else if($sectionID == "incomplete_tasks_container")
            {
                //we don't need filtering for alphabetical tasks
            }
            else if($sectionID == "completed_tasks_container")
            {
                //If there's no limit on completed tasks, just show those completed today
                if($completedTaskLimit == NULL)
                {
                    $todayMidnightDate = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
                    $sqlFilter = " (completiondate > ".$todayMidnightDate.")";
                }
            }
			else
			{
				$sqlFilter = " (listid = 'BAD_SECTION_REQUEST')";
			}

			return $sqlFilter;
		}


		public static function buildSQLFilterForUserForListID($userID, $listID, $showProjectSubtasks=false, $filterAllList=false, $link=NULL)
		{
            $excludedListIdString = "";
			switch ($listID)
			{
				case "focus":
				{
                    $userSettings = TDOUserSettings::getUserSettingsForUserid($userID, $link);
                    if($userSettings)
                    {
                        $focusListFilter = $userSettings->focusListFilterString();
                        if($focusListFilter)
                            $excludedListIdString = $focusListFilter;
                    }
                }
                case "all":
                {
                    if($filterAllList)
                    {
                        $userSettings = TDOUserSettings::getUserSettingsForUserid($userID, $link);
                        if($userSettings)
                        {
                            $allListFilter = $userSettings->allListFilter();
                            if($allListFilter)
                                $excludedListIdString = $allListFilter;
                        }
                    }
                }
                case "starred":
				case "today":
				{
					$listFilter = " (listid IN (";
					$lists = TDOList::getListsForUser($userID, false, $link);
					$firstTime = true;
					foreach($lists as $list)
					{
                        $listId = $list->listId();
                        if(strpos($excludedListIdString, $listId) === false)
                        {
                            if(!$firstTime)
                                $listFilter = $listFilter . ", ";
                            $listFilter = $listFilter . "'" . $listId . "'";
                            $firstTime = false;
                        }
					}
                    //If we didn't find any lists, stick an empty string to avoid sql syntax error
                    if($firstTime)
                        $listFilter .= "''";
					$listFilter = $listFilter . ")) ";
					break;
				}
			}

			if($listID == "all")
			{
				$sqlFilter = $listFilter;
			}
			else if($listID == "focus")
			{
                $focusFilterString = TDOTask::getFocusFilterStringForUserId($userID, $showProjectSubtasks, $link);
                if($focusFilterString && strlen($focusFilterString) > 0)
                   $sqlFilter = $listFilter . " AND ". $focusFilterString;
                else
                    $sqlFilter = $listFilter;

			}
			else if($listID == "starred")
			{
				//$sqlFilter = $listFilter;
                if($showProjectSubtasks) //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
                    $sqlFilter = $listFilter . " AND ((task_type=1 AND project_starred > 0) OR (task_type!=1 AND starred > 0))";
                else
                    $sqlFilter = $listFilter . " AND (starred > 0)";
			}
			else if($listID == "inbox")
			{
				$defaultListID = TDOList::getUserInboxId($userID, false, $link);
				if($defaultListID != "")
					$sqlFilter = " listid='" . $defaultListID . "'";
				else
					$sqlFilter = " (listid = 'NO_DEFAULT_LIST')";
			}
			else if($listID == "today")
			{
				$startDate = 0;
				$endDate = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y"));

				$sqlFilter = $listFilter . "AND ((duedate != 0) AND (duedate <= " . $endDate . ")) ";

//				$startDate = time(0, 0, 0, date("n"), date("j"), date("Y"));
//				$endDate = time(0, 0, -1, date("n"), (date("j")+1), date("Y"));
//
//				$sqlFilter = $listFilter . "AND ((duedate >= " . $startDate . ") AND (duedate <= " . $endDate . "))";
			}
			else
			{
				if(TDOList::userCanViewList($listID, $userID, $link))
					$sqlFilter = " listid='" . $listID ."'";
				else
					$sqlFilter = " listid='" . $userID . "'";
			}

			return $sqlFilter;
		}
		
		public static function buildSQLFilterForUserForSmartListID($userID, $smartListID, $hiddenListIDs=NULL, $link=NULL)
		{
			if (empty($userID) || empty($smartListID)) {
				return ""; // can't do anything with bad parameters
			}
			
			// First read all of the user's lists and we'll exclude the ones we
			// don't need later.
			$lists = TDOList::getListsForUser($userID, false, $link);
			
			$smartList = TDOSmartList::getSmartListForListid($smartListID, $link);
			if (empty($smartList)) {
				error_log("TDOTask::buildSQLFilterForUserForSmartList() No smart list found for user($userID) with ID ($smartListID)");
				return "";
			}
			
			// TODO: Make this be a set in case the smart list and the hidden
			// lists both specify the same list.
			
			$sql = "";
			
			$listFilters = "";
			$excludedListIDs = $smartList->excludedListIDs();
			if (!empty($excludedListIDs)) {
				$idx = 0;
				foreach($excludedListIDs as $excludedListID) {
					if (idx > 0) {
						$listFilters .= " AND ";
					}
					$listFilters .= " listid != '" . $excludedListID . "' ";
					$idx++;
				}
			}
			
			// Each client has the ability to hide certain lists to exclude
			// tasks from appearing anywhere. If those are specified, make sure
			// that those are excluded.
			if (!empty($hiddenListIDs)) {
				foreach($hiddenListIDs as $hiddenListID) {
					if (strlen($listFilters) > 0) {
						$listFilters .= " AND ";
					}
					$listFilters .= " listid != '" . $hiddenListID . "' ";
				}
			}
			
			if (strlen($listFilters) > 0) {
				$sql .= $listFilters;
			}
			
			$filterGroups = $smartList->getFilterGroups();
			if (!empty($filterGroups)) {
				$needToCloseSegment = false;
				if (strlen($sql) > 0) {
					$sql .= " AND (";
					$needToCloseSegment = true;
				}
				
				foreach($filterGroups as $filterGroup) {
					$filterGroupSQL = "";
					$filters = $filterGroup->filters();
					if (!empty($filters)) {
						foreach($filters as $filter) {
							if (strlen($filterGroupSQL) > 0) {
								$filterGroupSQL .= " AND ";
							}
							
							$filterSQL = NULL;
							if ($filter instanceof TDOSmartListDueDateFilter) {
								$filterSQL = $filter->buildSQLFilterUsingStartDates(!$smartList->excludeStartDates());
							} else {
								$filterSQL = $filter->buildSQLFilter();
							}
							
							if (strlen($filterSQL)) {
								$filterGroupSQL .= "(" . $filterSQL . ")";
							}
						}
					}
					
					if (strlen($sql) > 0) {
						$sql .= " OR ";
					}
					
					$sql .= "(" . $filterGroupSQL . ")";
				}
				
				if ($needToCloseSegment) {
					$sql .= ")";
				}
			}
			
			return $sql;
		}

        public static function getFocusFilterStringForUserId($userID, $showProjectSubtasks, $link=NULL)
        {

            $userSettings = TDOUserSettings::getUserSettingsForUserid($userID, $link);
            if($userSettings)
            {
                $offset = TDOUtil::filterOffsetForCurrentGMTOffset();

                $closeStatement = false;
                $focusFilterString = "";

                //Focus Date filter
                $filterDate = mktime(0, 0, -1, date("n"), (date("j")+1), date("Y")) + $offset;

//                $startTodayTimeStamp = strtotime('today');
//
//                $filterDate = new DateTime();
//                $filterDate->setTimeStamp($startTodayTimeStamp);

                $filterValue = $userSettings->focusHideTaskDate();
                if($filterValue != FOCUS_DUE_FILTER_NONE)
                {
                    switch($filterValue)
                    {
                        case FOCUS_DUE_FILTER_TODAY:
                        {
                            $filterDate = strtotime('today +1 Day') + $offset;
//                            $filterDate->modify("+ 1 day");
                            break;
                        }
                        case FOCUS_DUE_FILTER_TOMORROW:
                        {
                            $filterDate = strtotime('today +2 Day') + $offset;
//                            $filterDate->modify("+ 2 day");
                            break;
                        }
                        case FOCUS_DUE_FILTER_THREE_DAYS:
                        {
                            $filterDate = strtotime('today +3 Day') + $offset;
//                            $filterDate->modify("+ 3 days");
                            break;
                        }
                        case FOCUS_DUE_FILTER_ONE_WEEK:
                        {
                            $filterDate = strtotime('today +1 Week') + $offset;
//                            $filterDate->modify("+ 1 week");
                            break;
                        }
                        case FOCUS_DUE_FILTER_TWO_WEEKS:
                        {
                            $filterDate = strtotime('today +2 Week') + $offset;
//                            $filterDate->modify("+ 2 weeks");
                            break;
                        }
                        case FOCUS_DUE_FILTER_ONE_MONTH:
                        {
                            $filterDate = strtotime('today +1 Month') + $offset;
//                            $filterDate->modify("+ 1 month");
                            break;
                        }
                        case FOCUS_DUE_FILTER_TWO_MONTHS:
                        {
                            $filterDate = strtotime('today +2 Month') + $offset;
//                            $filterDate->modify("+ 2 months");
                            break;
                        }
                        default:
                        {
                            break;
                        }
                    }
                    
                    //Due to bug 6786, we always query the children when building the focus list now, so always treat the project like a normal task

                    if($userSettings->focusUseStartDates())
                        $focusFilterString .= " ( (((task_type=1 AND ((project_duedate <= ".$filterDate." AND project_duedate != 0) OR (project_startdate <= ".$filterDate." AND project_startdate != 0))) OR (task_type!=1 AND ((duedate <= ".$filterDate." AND duedate != 0) OR (startdate <= ".$filterDate." AND startdate != 0)))) ";
                    else
                        $focusFilterString .= "( (((task_type=1 AND project_duedate <= ".$filterDate." AND project_duedate != 0) OR (task_type!=1 AND duedate <= ".$filterDate." AND duedate != 0)) ";

                    $closeStatement = true;

                    if($userSettings->focusShowUndueTasks())
                    {
                        //Due to bug 6786, we always query the children when building the focus list now, so always treat the project like a normal task
                        if($userSettings->focusUseStartDates())
                            $focusFilterString .= " OR ((task_type=1 AND project_duedate=0 AND project_startdate=0) OR (task_type!=1 AND duedate=0 AND startdate=0)) ";
                        else
                            $focusFilterString .= " OR ((task_type=1 AND project_duedate=0) OR (task_type!=1 AND duedate=0)) ";

                    }

                    $focusFilterString .= ") ";
                }
                else
                {
                    if(!$userSettings->focusShowUndueTasks())
                    {
                        //Due to bug 6786, we always query the children when building the focus list now, so always treat the project like a normal task
                        if($userSettings->focusUseStartDates())
                            $focusFilterString .= " ( ((task_type=1 AND (project_duedate != 0 OR project_startdate != 0)) OR (task_type!=1 AND (duedate != 0 OR startdate != 0)))";
                        else
                            $focusFilterString .= " ( ((task_type=1 AND project_duedate != 0) OR (task_type!=1 AND duedate != 0 ))";

                        $closeStatement = true;
                    }
                }

                //Priority Filter
                $priorityFilterValue = $userSettings->focusHideTaskPriority();
                if($priorityFilterValue > 0)
                {
                    if(!$closeStatement)
                        $focusFilterString .= "( ";
                    else
                        $focusFilterString .= "AND ";
    
                    //Due to bug 6786, we always query the children when building the focus list now, so always treat the project like a normal task
//                    //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
//                    if($showProjectSubtasks)
                        $focusFilterString .= " ((task_type=1 AND project_priority <= ".intval($priorityFilterValue)." AND project_priority != 0) OR (task_type!=1 AND priority <= ".intval($priorityFilterValue)." AND priority != 0))";
//                    else
//                        $focusFilterString .= " (priority <= ".intval($priorityFilterValue)." AND priority != 0) ";
                    $closeStatement = true;
                }

                $starredFilterSetting = $userSettings->focusShowStarredTasks();
                if($starredFilterSetting)
                {
                    //Bug 7463 - If there is nothing being hidden in the focus list (i.e. closeStatement is NO), we don't need to add any sql to handle
                    //starred tasks because all the starred tasks will already be shown
                    if($closeStatement)
                    {
                    
                        //Due to bug 6786, we always query the children when building the focus list now, so always treat the project like a normal task
    //                    //If we're showing project subtasks, we treat the project like a normal task and ignore its child properties
    //                    if($showProjectSubtasks)
                            $focusFilterString .= " OR ((task_type=1 AND project_starred != 0) OR (task_type!=1 AND starred!=0))";
    //                    else
    //                        $focusFilterString .= " ( starred != 0 )";
                    }

                }

                if($closeStatement)
                {
                    $focusFilterString .= ")";
                }

                if(strlen($focusFilterString) > 0)
                    $focusFilterString .= " AND ";
                
                $completionFilterSetting = $userSettings->focusShowCompletedDate();
                if($completionFilterSetting == FOCUS_COMPLETED_FILTER_NONE)
                {
                    $focusFilterString .= " completiondate=0 ";
                }
                else
                {
                    $focusFilterString .= " (completiondate=0 OR ";

                    $todayEndTimeStamp = strtotime('tomorrow') - 1;
                    $baseDate = new DateTime();
                    $baseDate->setTimeStamp($todayEndTimeStamp);

                    switch($completionFilterSetting)
                    {
                        case FOCUS_COMPLETED_FILTER_ONE_DAY:
                        {
                            $baseDate->modify("- 1 day");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_TWO_DAYS:
                        {
                            $baseDate->modify("- 2 days");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_THREE_DAYS:
                        {
                            $baseDate->modify("- 3 days");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_ONE_WEEK:
                        {
                            $baseDate->modify("- 1 week");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_TWO_WEEKS:
                        {
                            $baseDate->modify("- 2 weeks");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_ONE_MONTH:
                        {
                            $baseDate->modify("- 1 month");
                            break;
                        }
                        case FOCUS_COMPLETED_FILTER_ONE_YEAR:
                        {
                            $baseDate->modify("- 1 year");
                            break;
                        }
                        default:
                        {
                            break;
                        }

                    }
                    $focusFilterString .= "completiondate > ".$baseDate->getTimestamp();


                    $focusFilterString .= ")";
                }

//                error_log($focusFilterString);
                return $focusFilterString;
            }
            return NULL;

        }
        
        public static function allTasksContainingText($userid, $searchString)
        {
            $allTasks = array();
            
            $incompleteTasks = TDOTask::tasksContainingText($userid, $searchString, false);
            if(!empty($incompleteTasks))
                $allTasks = array_merge($allTasks, $incompleteTasks);

            $completedTasks = TDOTask::tasksContainingText($userid, $searchString, true);
            if(!empty($completedTasks))
                $allTasks = array_merge($allTasks, $completedTasks);
            
//            $incompleteTaskitos = TDOTask::taskitosContainingText($userid, $searchString, false);
//            if(!empty($incompleteTaskitos))
//                $allTasks = array_merge($allTasks, $incompleteTaskitos);
//                
//            $completedTaskitos = TDOTask::taskitosContainingText($userid, $searchString, true);
//            if(!empty($completedTaskitos))
//                $allTasks = array_merge($allTasks, $completedTaskitos);
                
//            foreach($allTasks as $task)
//            {
//                error_log("Found task: ". $task->name());
//            }
                
            return $allTasks;
        }
        
        public static function tasksContainingText($userid, $searchString, $completed)
        {
            if(empty($userid) || empty($searchString) )
                return NULL;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask failed to get DBLink");
                return NULL;
            }
            $userid = mysql_real_escape_string($userid, $link);
            
            $listSQL = TDOTask::buildSQLFilterForUserForListID($userid, "all");
            $searchSQL = TDOTask::searchSQLForSearchString($searchString);
            $taskitoSearchSQL = TDOTask::searchSQLForSearchString($searchString, false);
            
//            if($completed)
//                $sql = "SELECT * FROM tdo_tasks WHERE $listSQL AND ((deleted=0 AND completiondate > 0 AND $searchSQL) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND completiondate > 0 AND $taskitoSearchSQL)))";
//            else
//                $sql = "SELECT * FROM tdo_tasks WHERE $listSQL AND ((deleted=0 AND completiondate = 0 AND $searchSQL) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND completiondate = 0 AND $taskitoSearchSQL)))";
       
            if($completed)
                $sql = "SELECT * FROM tdo_completed_tasks WHERE $listSQL AND (($searchSQL) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND $taskitoSearchSQL)))";
            else
                $sql = "SELECT * FROM tdo_tasks WHERE $listSQL AND (($searchSQL) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND $taskitoSearchSQL)))";
                                  
            if($result = mysql_query($sql, $link))
            {
                $tasks = array();
                while($row=mysql_fetch_array($result))
                {
                    $task = TDOTask::taskFromRow($row);
                    $tasks[] = $task;
                }
                
                TDOUtil::closeDBLink($link);
                return $tasks;
            }
            else
                error_log("tasksContaintingText failed with error: ".mysql_error());
                
            TDOUtil::closeDBLink($link);
            return NULL;
        }
		

		public static function getTaskCount()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOUser::getTaskCount() failed to get dblink");
				return false;
			}
			
            $sql = "SELECT COUNT(*) FROM tdo_tasks";
			
			$taskCount = 0;
            if($result = mysql_query($sql, $link))
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskCount = $row['0'];
                }
            }
            else
            {
                error_log("TDOTask::getTaskCount could not get the task count " . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return $taskCount;
		}        
        
        public static function getCompletedTaskCount()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOUser::getCompletedTaskCount() failed to get dblink");
				return false;
			}
			
            $sql = "SELECT COUNT(*) FROM tdo_completed_tasks";
			
			$taskCount = 0;
            if($result = mysql_query($sql, $link))
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskCount = $row['0'];
                }
            }
            else
            {
                error_log("TDOTask::getCompletedTaskCount() could not get the task count " . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return $taskCount;
		}        

        
		public static function getTaskCountForUser($userid, $completed)
		{
			if(!isset($userid))
				return false;
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOUser::getTaskCountForUser($userid) failed to get dblink");
				return false;
			}
			
			$escapedUserid = mysql_real_escape_string($userid, $link);
			
            $listSQL = TDOTask::buildSQLFilterForUserForListID($userid, "all");
            
//            if($completed)
//                $sql = "SELECT COUNT(*) FROM tdo_tasks WHERE $listSQL AND ((deleted=0 AND completiondate > 0) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND completiondate > 0)))";
//            else
//                $sql = "SELECT COUNT(*) FROM tdo_tasks WHERE $listSQL AND ((deleted=0 AND completiondate = 0) OR (taskid IN (SELECT parentid FROM tdo_taskitos WHERE deleted=0 AND completiondate = 0)))";

            if($completed)
                $sql = "SELECT COUNT(*) FROM tdo_completed_tasks WHERE $listSQL";
            else
                $sql = "SELECT COUNT(*) FROM tdo_tasks WHERE $listSQL";
			
			$taskCount = 0;
            if($result = mysql_query($sql, $link))
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskCount = $row['0'];
                }
            }
            else
            {
                error_log("TDOTask::getTaskCountForUser($userid) could not get the task count " . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return $taskCount;
			
		}
        
        public static function searchSQLForSearchString($searchString, $includeNote=true)
        {
            $sql = "";
            
            $searchArray = preg_split('/\s+/', $searchString);
            
            foreach($searchArray as $searchItem)
            {
                if(strlen($searchItem) > 0)
                {
                    if(strlen($sql) > 0)
                        $sql .= " AND";
                    // This removes magic on LIKE wildchars
                    $searchItem = preg_replace('#(%|_)#', '\\$1', $searchItem);
                    $searchItem = mysql_real_escape_string($searchItem);
                    
                    $sql .= " (name LIKE '%".$searchItem."%'";
                    if($includeNote)
                        $sql .= " OR note LIKE '%".$searchItem."%'";
                    $sql .= ")";
                }
            }
            
            return $sql;
        }

		public static function getTasksForSectionID($sectionID, $userid, $listid, $contextid=NULL, $tagsFilter=NULL, $tagsFilterByAnd=false, $assignedUser=NULL, $completed=false, $showProjectSubtasksSetting=false, $completedTaskLimit=NULL, $completedBeforeTask=NULL, $oldestCompletedTaskDate=NULL)
		{
//			error_log("TDOTask::getTasksForSectionID('" . $sectionID . "')");

			if (empty($listid))
			{
				error_log("TDOTask::getTasksForSectionID() called with a NULL listid");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask::getTasksForSectionID() could not get DB connection.");
				return false;
			}
            
            $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
            
            //If this is the overdue section and the user is hiding it, return an empty array
            if($sectionID == "overdue_tasks_container")
            {
                if(empty($userSettings) || $userSettings->showOverdueSection() == false)
                    return array();
               
            }

			$escapedListID = mysql_real_escape_string($listid, $link);

            $showProjectSubtasks = false;
            if($showProjectSubtasksSetting && ($listid == 'focus' || $listid == 'starred'))
                $showProjectSubtasks = true;

			$listsql = TDOTask::buildSQLFilterForUserForListID($userid, $escapedListID, $showProjectSubtasks, true);
            
            $sqlWhereStatement = $listsql;
            
            //If we are just filtering by dates or priorities, then we don't need to query project children to determine if the
            //parent should be shown, because we store the earliest child due date and highest child priority on the project.
            //However, if we're filtering by tags, contexts, or users, we need to query the project children so that a project
            //will show up if it has a child matching the filter.
            $needToQueryProjectChildren = false;
            
            if($listid == "focus")
            {
                $needToQueryProjectChildren = true;
            }
            
            if(!empty($assignedUser) && $assignedUser != 'all')
			{
                $needToQueryProjectChildren = true;
                
				if($assignedUser == "none")
					$sqlWhereStatement .= " AND (assigned_userid = '' OR assigned_userid IS NULL)";
				else
					$sqlWhereStatement .= " AND assigned_userid = '" . mysql_real_escape_string($assignedUser, $link)."'";
			}
            
            $usingContextFilter = false;
			if(!empty($contextid))
			{
				if($contextid != "all")
				{
                    $needToQueryProjectChildren = true;
                    $usingContextFilter = true;
					if($contextid == "nocontext")
					{
						$sqlWhereStatement .= " AND (tdo_context_assignments.contextid = '' OR tdo_context_assignments.contextid IS NULL)";
					}
					else
					{
						$escapedContextID = mysql_real_escape_string($contextid, $link);
						$sqlWhereStatement .= " AND (tdo_context_assignments.contextid = '" . $escapedContextID . "')";
					}
				}
			}
            
            $tableName = "tdo_tasks";
            if($completed)
                $tableName = "tdo_completed_tasks";
            
            $tagSQL = TDOTask::buildSQLFilterForTags($tagsFilter, $tagsFilterByAnd, $tableName);
            if(strlen($tagSQL) > 0)
            {
                $needToQueryProjectChildren = true;
                $sqlWhereStatement .= " AND ($tagSQL) ";
            }
            
            //If we're showing subtasks (in the focus list or starred list) or if we're checking whether the parent
            //should be shown because of a child, we should go off of the project's actual dates instead of the earliest
            //child date. This way we make sure a project does not get shown if it has one child matching the context
            //and different child matching the date range.
            $useActualProjectDates = ($showProjectSubtasks || $needToQueryProjectChildren);
			$sectionsql = TDOTask::buildSQLFilterForSectionID($sectionID, $useActualProjectDates, $completedTaskLimit, $userid);
			if($sectionsql != "")
            {
                $sqlWhereStatement .= " AND " . $sectionsql;
            }


            if($completed && $completedBeforeTask != NULL)
            {
                //Get only tasks that come after $completedBeforeTask. The order by statement for completed tasks is:
                //ORDER BY completiondate DESC,sort_order, priority=0, priority ASC, name ASC, taskid ASC";
                
                $timestamp = intval($completedBeforeTask->completiondate());
                $sortorder = intval($completedBeforeTask->sortOrder());
                $priority = intval($completedBeforeTask->priority());
                
                $name =  mb_strcut($completedBeforeTask->name(), 0, TASK_NAME_LENGTH, 'UTF-8');
				$name = mysql_real_escape_string($name, $link);
                $taskid = mysql_real_escape_string($completedBeforeTask->taskId(), $link);
            
                //This stuff is kind of complicated, but it's only going to be called when the user clicks on 'get more completed tasks' in the completed tasks view
                $sqlWhereStatement .= " AND (";
                
                $sqlWhereStatement .= " (completiondate < $timestamp)";
                $sqlWhereStatement .= " OR (completiondate = $timestamp AND sort_order > $sortorder)";
                
                if($priority != 0)
                    $sqlWhereStatement .= " OR (completiondate = $timestamp AND sort_order = $sortorder AND (priority = 0 OR priority > $priority))";
                    
                $sqlWhereStatement .= " OR (completiondate = $timestamp AND sort_order = $sortorder AND priority = $priority AND name > '$name')";
                $sqlWhereStatement .= " OR (completiondate = $timestamp AND sort_order = $sortorder AND priority = $priority AND name = '$name' AND tdo_completed_tasks.taskid > '$taskid')";
                
                $sqlWhereStatement .= ")";
            
            }
            
            if($completed && $oldestCompletedTaskDate != NULL)
            {
                $sqlWhereStatement .= " AND completiondate >= ".intval($oldestCompletedTaskDate);
            }
			
            $hideStartDateSQL = "";
			if ($completed == false)
			{
				$startDateFilterInterval = 0;
				if (!empty($userSettings))
				{
					$startDateFilterInterval = $userSettings->startDateFilterInterval();
				}
				
				if ($startDateFilterInterval > 0)
				{
					$todayInterval = mktime(0, 0, 0, date("n"), (date("j")), date("Y"));
					$filterInterval = $todayInterval + $startDateFilterInterval;
					
                    if($showProjectSubtasks)
                        $hideStartDateSQL = " AND ((task_type != 1 AND (startdate = 0 OR startdate < $filterInterval)) OR (task_type = 1 AND (project_startdate = 0 OR project_startdate < $filterInterval)))  AND ((task_type != 1 AND (duedate = 0 OR duedate < $filterInterval)) OR (task_type = 1 AND (project_duedate = 0 OR project_duedate < $filterInterval)))";
                    else
                    {
                        //Project should not be hidden if it has no start date but has a child with a hidden start date
                        $hideStartDateSQL = " AND ((task_type = 1 AND project_startdate = 0) OR startdate = 0 OR startdate < $filterInterval) AND ((task_type = 1 AND project_duedate = 0) OR duedate = 0 OR duedate < $filterInterval) ";
                    }
				}
			}

            $sql = "SELECT $tableName.*, tdo_context_assignments.contextid FROM $tableName LEFT JOIN tdo_context_assignments ON ($tableName.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') ";

            if(!$tagsFilterByAnd && !empty($tagsFilter))
            {
                $sql .= " LEFT JOIN tdo_tag_assignments ON (tdo_tag_assignments.taskid=$tableName.taskid)";
            }
            $sql .= " WHERE (($sqlWhereStatement";
            
            //If we're showing project subtasks, we can skip all this!
            if($showProjectSubtasks == false)
            {
                $sql .= " AND (parentid = '' OR parentid IS NULL))";
                
                if($needToQueryProjectChildren)
                {
                    //Bug 7091 - We are going to query the children separately and build a list of parent ids, then throw that in
                    //the 'OR' clause to avoid crippling the database with this query
                    
                    $fetchChildSQL = "SELECT parentid FROM $tableName";
                     if($usingContextFilter)
                        $fetchChildSQL .= " LEFT JOIN tdo_context_assignments ON ($tableName.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') ";
                    if(!$tagsFilterByAnd && !empty($tagsFilter))
                    {
                        $fetchChildSQL .= " LEFT JOIN tdo_tag_assignments ON (tdo_tag_assignments.taskid=$tableName.taskid) ";
                    }
                     
                    $fetchChildSQL .= " WHERE ($sqlWhereStatement AND parentid IS NOT NULL AND parentid != '')";
                    
                    $parentIds = array();
                    $fetchChildResult = mysql_query($fetchChildSQL, $link);
                    if($fetchChildResult)
                    {
                        while($row = mysql_fetch_array($fetchChildResult))
                        {
                            if(isset($row['parentid']) && !empty($row['parentid']))
                            {
                                $parentId = $row['parentid'];
                                if(!isset($parentIds[$parentId]))
                                {
                                    $parentIds[$parentId] = $parentId;
                                }
                            }
                        }
                    }
                    else
                    {
                        error_log("Error reading children from database: ".mysql_error());
                        TDOUtil::closeDBLink($link);
                        return false;
                    }
                    
                    if(!empty($parentIds))
                    {
                        $parentIdList = "";
                        foreach($parentIds as $parentId)
                        {
                            if(strlen($parentIdList) > 0)
                                $parentIdList .= ", ";
                                    
                            $parentIdList.= "'". mysql_real_escape_string($parentId, $link) . "'";
                        }
                        
                        if(strlen($parentIdList) > 0)
                        {
                            $sql .= " OR (task_type=1 AND $tableName.taskid IN ($parentIdList))";
                        }
                    }
                    
                    
                    // The old way of doing this that caused major db performance issues
//                    $sql .= " OR (task_type=1 AND $tableName.taskid IN (SELECT $tableName.parentid FROM $tableName";
//                    if($usingContextFilter)
//                        $sql .= " LEFT JOIN tdo_context_assignments ON ($tableName.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') ";
//                    if(!$tagsFilterByAnd && !empty($tagsFilter))
//                    {
//                        $sql .= " LEFT JOIN tdo_tag_assignments ON (tdo_tag_assignments.taskid=$tableName.taskid) ";
//                    }
//                     
//                    $sql .= " WHERE ($sqlWhereStatement AND parentid IS NOT NULL AND parentid != '')) )";
                }
            }
            else
            {
                $sql .= ")";
            }
            
            $sql .= ")";


            $sql .= $hideStartDateSQL;
            $sql .= TDOTask::buildOrderByStatementForUser($userid, $completed);

            if($completed && $completedTaskLimit != NULL)
                $sql = $sql." LIMIT ".intval($completedTaskLimit);

//			error_log("SQL query string is: " . $sql);

			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						//						$taskid = $row['taskid'];
						//						$task = TDOTask::getTaskForTaskId($taskid, $link);
						//						if ($task)
						//						{
						//							$tasks[] = $task;
						//						}
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
				TDOUtil::closeDBLink($link);
				return array_values($tasks);
			}
            else
                error_log("TDOTask::getTasksForSectionID() could not get tasks for the specified list '$listid' " . mysql_error());

			TDOUtil::closeDBLink($link);

			return false;
		}
        
        
		public static function getArchivedTasksForUserModifiedSince($userid, $timestamp, $listid, $offset=0, $limit=0, $returnSubtasks=false, $link=NULL)
		{
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask::getArchivedTasksForUserModifiedSince() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeDBLink = false;
            
            $listsql = TDOTask::buildSQLFilterForUserForListID($userid, $listid, false, false, $link);
            if($returnSubtasks == false)
                $parentsql = " AND (parentid = '' OR parentid IS NULL)";
			else
                $parentsql = " AND (parentid != '' AND parentid IS NOT NULL)";
            
            $tasks = array();
            
            // if the timestamp is empty and we're asking for deleted tasks
            // we return none since they have never synced
            if(empty($timestamp))
            {
                if($closeDBLink)
                    TDOUtil::closeDBLink($link);
                return $tasks;
            }                    
            
            $sql = "SELECT * FROM tdo_archived_tasks WHERE ".$listsql.$parentsql;
			
            $sql = $sql." AND timestamp > ".intval($timestamp);
			
			if (isset($limit) && $limit != 0)
			{
				$sql = $sql . " LIMIT $limit OFFSET $offset";
			}
			
            $result = mysql_query($sql);
            if($result)
            {
                while($row = mysql_fetch_array($result))
                {
                    if ( (empty($row['taskid']) == false) && (count($row) > 0) )
                    {
                        $task = TDOTask::taskFromRow($row);
                        if (empty($task) == false)
                        {
                            $tasks[$row['taskid']] = $task;
                        }
                    }
                }
				
				$taskResults = array();
				
				$taskResults['tasks'] = array_values($tasks);
				
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				return $taskResults;
            }
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            
			error_log("TDOTask::getArchivedTasksForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
            
			return false;
		}
		
		
		public static function getActiveTasksForUserModifiedSince($userid, $timestamp, $listid, $offset=0, $limit=0, $returnSubtasks=false, $link=NULL)
		{
			if(empty($link))
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::getActiveTasksForUserModifiedSince() could not get DB connection.");
					return false;
				}
			}
			else
				$closeDBLink = false;
			
			$listsql = TDOTask::buildSQLFilterForUserForListID($userid, $listid, false, false, $link);
			if($returnSubtasks == false)
				$parentsql = " AND (parentid = '' OR parentid IS NULL)";
			else
				$parentsql = " AND (parentid != '' AND parentid IS NOT NULL)";
			
			$tasks = array();
			
			$sql = "SELECT tdo_tasks.*, tdo_context_assignments.contextid FROM tdo_tasks LEFT JOIN tdo_context_assignments ON (tdo_tasks.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') WHERE ".$listsql.$parentsql;
			if(!empty($timestamp))
			{
				$sql = $sql." AND tdo_tasks.timestamp > ".intval($timestamp);
			}
			
			if (isset($limit) && $limit != 0)
			{
				$sql = $sql . " LIMIT $limit OFFSET $offset";
			}
			
			//error_log($sql);
			
			$taskResults = array();
			$result = mysql_query($sql);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
				
				$taskResults['tasks'] = array_values($tasks);
				
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				return $taskResults;
			}
			else
			{
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				error_log("TDOTask::getActiveTasksForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
				return false;
			}
		}
		
		
		public static function getCompletedTasksForUserModifiedSince($userid, $timestamp, $listid, $offset=0, $limit=0, $link=NULL)
		{
			if(empty($link))
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::getCompletedTasksForUserModifiedSince() could not get DB connection.");
					return false;
				}
			}
			else
				$closeDBLink = false;
			
			$listsql = TDOTask::buildSQLFilterForUserForListID($userid, $listid, false, false, $link);
//			$parentsql = " AND (parentid = '' OR parentid IS NULL)";
			
			$sql = "SELECT tdo_completed_tasks.*, tdo_context_assignments.contextid FROM tdo_completed_tasks LEFT JOIN tdo_context_assignments ON (tdo_completed_tasks.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') WHERE ".$listsql;
			if(!empty($timestamp))
			{
				$sql = $sql." AND tdo_completed_tasks.timestamp > ".intval($timestamp);
			}
			else
			{
				//Limit completed tasks to one year
				$limitDate = mktime(date("H"), date("i"), date("s"), date("n"), date("j"), date("Y") - 1);
				$sql = $sql." AND tdo_completed_tasks.completiondate > ".intval($limitDate);
			}
			
			if (isset($limit) && $limit != 0)
			{
				$sql = $sql . " LIMIT $limit OFFSET $offset";
			}
			
			//error_log($sql);
			
			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
				
				$taskResults['tasks'] = array_values($tasks);
				
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				return $taskResults;
			}
			else
			{
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				error_log("TDOTask::getCompletedTasksForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
				return false;
			}
		}
	
	
		public static function getDeletedTasksForUserModifiedSince($userid, $timestamp, $listid, $offset=0, $limit=0, $returnSubtasks=false, $link=NULL)
		{
			if(empty($link))
			{
				$closeDBLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::getDeletedTasksForUserModifiedSince() could not get DB connection.");
					return false;
				}
			}
			else
				$closeDBLink = false;
			
			$listsql = TDOTask::buildSQLFilterForUserForListID($userid, $listid, false, false, $link);
			if($returnSubtasks == false)
				$parentsql = " AND (parentid = '' OR parentid IS NULL)";
			else
				$parentsql = " AND (parentid != '' AND parentid IS NOT NULL)";
			
			$tasks = array();
			
			// if the timestamp is empty and we're asking for deleted tasks
			// we return none since they have never synced
			if(empty($timestamp))
			{
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				return $tasks;
			}
			
			$sql = "SELECT tdo_deleted_tasks.*, tdo_context_assignments.contextid FROM tdo_deleted_tasks LEFT JOIN tdo_context_assignments ON (tdo_deleted_tasks.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') WHERE ".$listsql.$parentsql;
			
			$sql = $sql." AND tdo_deleted_tasks.timestamp > ".intval($timestamp);
			
			if (isset($limit) && $limit != 0)
			{
				$sql = $sql . " LIMIT $limit OFFSET $offset";
			}
			
			$result = mysql_query($sql);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
				
				$taskResults = array();
				$taskResults['tasks'] = array_values($tasks);
				
				if($closeDBLink)
					TDOUtil::closeDBLink($link);
				return $taskResults;
			}
			
			if($closeDBLink)
				TDOUtil::closeDBLink($link);
			
			error_log("TDOTask::getDeletedTasksForUserModifiedSince() could not get tasks for the user '$userid'" . mysql_error());
			
			return false;
		}
		
		
        public static function buildSQLFilterForTags($tagsFilter, $tagsFilterByAnd, $tableName)
        {
            $tagSQL = "";
            if(!$tagsFilterByAnd && !empty($tagsFilter))
            {
                //Filter by OR
                foreach($tagsFilter as $tagId)
                {
                    if($tagId == "all")
                    {
                        $tagSQL = "";
                        break;
                    }
                    elseif($tagId == "notag")
                    {
                        if(strlen($tagSQL) > 0)
                            $tagSQL .= " OR";
                        $tagSQL .= " (tagid IS NULL OR tagid='') ";
                    }
                    else
                    {
                        if(strlen($tagSQL) > 0)
                            $tagSQL .= " OR";
                        $tagSQL .= " tagid='$tagId' ";
                    }
                }
            }
            else if(!empty($tagsFilter))
            {
                //Filter by AND
                $tagSQL = "(";
                $specialTagFound = false;
                foreach($tagsFilter as $tagId)
                {
                    if($tagId == "all")
                    {
                        $tagSQL = "";
                        $specialTagFound = true;
                        break;
                    }
                    elseif($tagId == "notag")
                    {
                        $specialTagFound = true;
                        $tagSQL = " (SELECT COUNT(tagid) FROM tdo_tag_assignments WHERE tdo_tag_assignments.taskid=$tableName.taskid)=0 ";
                        break;
                    }
                    else
                    {
                        if(strlen($tagSQL) > 1)
                        {
                            $tagSQL .= " AND ";
                        }

                        $tagSQL .= "'$tagId' IN (SELECT tagid FROM tdo_tag_assignments WHERE tdo_tag_assignments.taskid=$tableName.taskid) ";
                    }
                }

                if(!$specialTagFound)
                {
                    $tagSQL .= ") ";
                }
            }
            
            return $tagSQL;

        }

        
        public static function taskCountForList($listid, $userid, $includeCompleted=false, $overdue=false, $contextid=NULL, $tagsFilter=NULL, $tagsFilterByAnd=false, $assignedUser=NULL)
		{
			if (empty($listid))
			{
				error_log("TDOTask::taskCountForList() called with a NULL listid");
				return false;
			}
            
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask::taskCountForList() could not get DB connection.");
				return false;
			}
            
            
//            $sql = $sql." AND deleted != 1  ";
//
//            if($includeCompleted == false)
//                $sql = $sql." AND completiondate = 0";
//            if($includeIncomplete == false)
//                $sql = $sql." AND completiondate != 0";

            $tableName = "tdo_tasks";
            if($includeCompleted)
                $tableName = "tdo_completed_tasks";

            $sql = "SELECT count(*) FROM $tableName";
            
            if(!empty($contextid) && $contextid != "all")
                $sql .= " LEFT JOIN tdo_context_assignments ON ($tableName.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid, $link) ."') ";
            if(!$tagsFilterByAnd && !empty($tagsFilter))
                $sql .= " LEFT JOIN tdo_tag_assignments ON (tdo_tag_assignments.taskid=$tableName.taskid) ";
            
            $listSQL = TDOTask::buildSQLFilterForUserForListID($userid, mysql_real_escape_string($listid), true, true);
            $sql .= " WHERE ".$listSQL;
            
            if(!empty($assignedUser) && $assignedUser != 'all')
			{
                if($assignedUser == "none")
					$sql .= " AND (assigned_userid = '' OR assigned_userid IS NULL)";
				else
					$sql .= " AND assigned_userid = '" . mysql_real_escape_string($assignedUser, $link)."'";
			}

			if(!empty($contextid))
			{
				if($contextid != "all")
				{
					if($contextid == "nocontext")
					{
						$sql .= " AND (tdo_context_assignments.contextid = '' OR tdo_context_assignments.contextid IS NULL)";
					}
					else
					{
						$escapedContextID = mysql_real_escape_string($contextid, $link);
						$sql .= " AND (tdo_context_assignments.contextid = '" . $escapedContextID . "')";
					}
				}
			}

            $tagSQL = TDOTask::buildSQLFilterForTags($tagsFilter, $tagsFilterByAnd, $tableName);
            if(strlen($tagSQL) > 0)
            {
                $sql .= " AND ($tagSQL) ";
            }
            
            if($overdue)
            {
                $dateTime = new DateTime();
                $dateTime->setTimeStamp(time());
                $timezoneOffset = $dateTime->getOffset() * -1 + 43170;
                $sql .= " AND ((task_type != 1 AND ((duedate != 0) AND ((duedate + (CASE 1 WHEN (due_date_has_time = 1) THEN 0 ELSE ($timezoneOffset) END)) < ".time().")))";
                $sql .= " OR (task_type = 1 AND ((project_duedate != 0) AND ((project_duedate + (CASE 1 WHEN (project_duedate_has_time = 1) THEN 0 ELSE ($timezoneOffset) END)) < ".time()."))))";
            }
			
			// Start Date Filtering

            if($includeCompleted == false)
            {
                $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
                if (!empty($userSettings))
                {
                    $showProjectSubtasks = false;
                    if($userSettings->focusShowSubtasks() && ($listid == 'focus' || $listid == 'starred'))
                        $showProjectSubtasks = true;

                    $startDateFilterInterval = $userSettings->startDateFilterInterval();
                    if ($startDateFilterInterval > 0)
                    {
                        $todayInterval = mktime(0, 0, 0, date("n"), (date("j")), date("Y"));
                        $filterInterval = $todayInterval + $startDateFilterInterval;
                        
                        //If the task is a subtask we have to make sure the parent hasn't been hidden, unless we're showing subtasks,
                        //in which case we won't have to worry about it
                        if(!$showProjectSubtasks)
                        {
                            //Project should not be hidden if it has no start date but has a child with a hidden start date
                            $sql .= " AND ((task_type = 1 AND project_startdate = 0) OR startdate = 0 OR startdate < $filterInterval) AND ((task_type = 1 AND project_duedate = 0) OR duedate = 0 OR duedate < $filterInterval) ";
                            
                            /**** Query for hidden parents separately and build a list of tasks to exclude ****/

                            //We only need to exclude parents that are part of the current list, but don't filter by focus list properties or star
                            //or we might miss some
                            $excludeListSQL =  $listSQL;
                            if($listid == 'focus' || $listid == 'starred' || $listid == 'today')
                                $excludeListSQL =  TDOTask::buildSQLFilterForUserForListID($userid, 'all');
                            
                            $fetchParentSQL = "SELECT taskid FROM $tableName WHERE task_type=1 AND project_startdate != 0 AND startdate >= $filterInterval AND $excludeListSQL";
                            
                            $parentIds = array();
                            $fetchChildResult = mysql_query($fetchParentSQL, $link);
                            if($fetchChildResult)
                            {
                                while($row = mysql_fetch_array($fetchChildResult))
                                {
                                    if(isset($row['taskid']) && !empty($row['taskid']))
                                    {
                                        $parentId = $row['taskid'];
                                        if(!isset($parentIds[$parentId]))
                                        {
                                            $parentIds[$parentId] = $parentId;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                error_log("Error reading tasks from database: ".mysql_error());
                                TDOUtil::closeDBLink($link);
                                return false;
                            }
                            
                            if(!empty($parentIds))
                            {
                                $parentIdList = "";
                                foreach($parentIds as $parentId)
                                {
                                    if(strlen($parentIdList) > 0)
                                        $parentIdList .= ", ";
                                    
                                    $parentIdList.= "'". mysql_real_escape_string($parentId, $link) . "'";
                                }
                                
                                if(strlen($parentIdList) > 0)
                                {
                                    $sql .= " AND (parentid IS NULL OR parentid = '' OR parentid NOT IN ($parentIdList)) ";
                                }
                            }
                                
                        }
                        else
                        {
                            $sql .= " AND ((task_type != 1 AND (startdate = 0 OR startdate < $filterInterval)) OR (task_type = 1 AND (project_startdate = 0 OR project_startdate < $filterInterval)))  AND ((task_type != 1 AND (duedate = 0 OR duedate < $filterInterval)) OR (task_type = 1 AND (project_duedate = 0 OR project_duedate < $filterInterval)))";
                        }
                            
                        
                    }
                }
            }
            

            $taskCount = 0;
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskCount = $row['0'];
                }
            }
            else
            {
                error_log("TDOTask::taskCountForList could not get the list count " . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return $taskCount;
		}

        
        
        //This method is only called during CalDav sync
        public static function getTasksForList($listid, $assignedUser=NULL)
		{
//			error_log("TDOTask::getTasksForList('" . $listid . "')");

			if (empty($listid))
			{
				error_log("TDOTask::getTasksForList() called with a NULL listid");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask::getTasksForList() could not get DB connection.");
				return false;
			}

            $whereStatement = "WHERE listid='" . mysql_real_escape_string($listid, $link)."'";
            $whereStatement = $whereStatement." AND (parentid = '' OR parentid IS NULL)";

            if(!empty($assignedUser) && $assignedUser != 'all')
			{
				if($assignedUser == "none")
					$whereStatement = $whereStatement." AND (assigned_userid = '' OR assigned_userid IS NULL)";
				else
					$whereStatement = $whereStatement." AND assigned_userid = '" . mysql_real_escape_string($assignedUser, $link)."'";
			}

			$sql = "SELECT * FROM tdo_tasks ".$whereStatement." UNION SELECT * from tdo_completed_tasks ".$whereStatement;

//            $sql = $sql." AND deleted != 1 ";
			

			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
//						$taskid = $row['taskid'];
//						$task = TDOTask::getTaskForTaskId($taskid, $link);
//						if ($task)
//						{
//							$tasks[] = $task;
//						}
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[] = $task;
						}
					}
				}
				TDOUtil::closeDBLink($link);
				return $tasks;
			}
			else
			{
				error_log("TDOTask::getTasksForList() could not get tasks for the specified list '$listid'" . mysql_error());
			}

			TDOUtil::closeDBLink($link);
			return false;
		}

        
        // this is initial written for the delete list method which needs all of the task ids to delete them
        public static function getAllTaskIdsForListId($listid, $link = NULL)
		{
			if (empty($listid))
			{
				error_log("TDOTask::getTasksForList() called with a NULL listid");
				return false;
			}
            
			if($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if(!$link)
				{
					error_log("TDOTask::getAllTaskIdsForListId() could not get DB connection.");
					return false;
				}
			}
			else
				$closeLink = false;			
            
            $whereStatement = "WHERE listid='" . mysql_real_escape_string($listid, $link)."'";
            $whereStatement = $whereStatement." AND (parentid = '' OR parentid IS NULL)";
            
			$sql = "SELECT taskid FROM tdo_tasks ".$whereStatement." UNION SELECT taskid FROM tdo_completed_tasks ".$whereStatement;
            
//            $sql = $sql." AND deleted != 1 ";
            
            
			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
                        $taskid = $row['taskid'];
						$tasks[] = $taskid;
					}
				}
                if($closeLink)
                    TDOUtil::closeDBLink($link);

				return $tasks;
			}
			else
			{
				error_log("TDOTask::getTasksForList() could not get tasks for the specified list '$listid'" . mysql_error());
			}
            
            if($closeLink)
                TDOUtil::closeDBLink($link);

			return false;
		}        
        

		public static function getAllTaskTimestampsForUser($userid, $lists=NULL, $link=NULL)
		{
            $timestamp = 0;

            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask::getAllTaskTimestampsForUser() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeDBLink = false;

            $timeStamps = array();
            
            // first get all of the lists and build timestamps for each one
            if(empty($lists))
                $lists = TDOList::getListsForUser($userid, false, $link);
            foreach($lists as $list)
            {
                $listId = $list->listId();

                if($list->taskTimeStamp() > 0)
                {
                    // If a value of 1 is stored, then we've already done the calculation (see below) and
                    // stored a 1 in the timestamp.  Nothing as changed so don't return a timestamp in
                    // this case.
                    if($list->taskTimeStamp() != 1)
                        $timeStamps[$listId] = $list->taskTimeStamp();
                }
                // if we didn't have a timestamp stored on the list for tasks, go figure it out and then store it
                else    
                {

                    // error_log("Long Task timestamp query being called on list: " . $list->name());
                    
                    $listsql = " listid='" . $listId ."'";
                    
                    $maxTimestamp = 0;
                    $sql = "SELECT MAX(timestamp) AS timestamp FROM tdo_tasks WHERE ".$listsql;
                    $result = mysql_query($sql);
                    if($result)
                    {
                        $row = mysql_fetch_array($result);
                        if(!empty($row['timestamp']))
                        {
                            $maxTimestamp = $row['timestamp'];
                        }
                    }
                    
                    $sql = "SELECT MAX(timestamp) AS timestamp FROM tdo_deleted_tasks WHERE ".$listsql;
                    $result = mysql_query($sql);
                    if($result)
                    {
                        $row = mysql_fetch_array($result);
                        if(!empty($row['timestamp']))
                        {
                            $tmpTimestamp = $row['timestamp'];
                            if($tmpTimestamp > $maxTimestamp)
                                $maxTimestamp = $tmpTimestamp;
                        }
                    }
                    
                    $sql = "SELECT MAX(timestamp) AS timestamp FROM tdo_completed_tasks WHERE ".$listsql;
                    $result = mysql_query($sql);
                    if($result)
                    {
                        $row = mysql_fetch_array($result);
                        if(!empty($row['timestamp']))
                        {
                            $tmpTimestamp = $row['timestamp'];
                            if($tmpTimestamp > $maxTimestamp)
                                $maxTimestamp = $tmpTimestamp;
                        }
                    }
                    
                    if($maxTimestamp > 0)
                    {
                        TDOList::updateTaskTimestampForList($listId, $maxTimestamp, $link);
                        $timeStamps[$listId] = $maxTimestamp;
                    }
                    else
                    {
                        // if we go to calculate the timestamp and it's 0, store a 1 so we at least
                        // know we've calculated it once, otherwise we'll keep running this expensive
                        // query for no reason
                        TDOList::updateTaskTimestampForList($listId, 1, $link);
                    }                        
                }
                
            }
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return $timeStamps;
		}
        

        public static function getAllNondeletedSubtasksForTask($taskid, $link)
        {
            //First get the incomplete subtasks
            $subtasks = TDOTask::getSubTasksForTask($taskid, NULL, NULL, NULL, false, NULL, false, false, $link);
            
            if($subtasks === false)
                return false;
                
            //then merge in the completed subtasks
            $completedSubtasks = TDOTask::getSubTasksForTask($taskid, NULL, NULL, NULL, false, NULL, false, true, $link);
            
            if($completedSubtasks === false)
                return false;
            
            $subtasks = array_merge($subtasks, $completedSubtasks);
            return $subtasks;
            
        }
        
        
        public static function getAllSubtasksForTask($taskid, $link)
        {
            $tasks = array();

			$sql = "SELECT * FROM tdo_tasks WHERE parentid='" . mysql_real_escape_string($taskid, $link)."' ";
			$result = mysql_query($sql);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
			}
			else
			{
				error_log("TDOTask::getAllSubtasksForTask() could not get tasks for the specified task '$taskid'" . mysql_error());
                return false;
			}

			$sql = "SELECT * FROM tdo_completed_tasks WHERE parentid='" . mysql_real_escape_string($taskid, $link)."' ";
			$result = mysql_query($sql);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
			}
			else
			{
				error_log("TDOTask::getAllSubtasksForTask() could not get tasks for the specified task '$taskid'" . mysql_error());
                return false;
			}

			$sql = "SELECT * FROM tdo_deleted_tasks WHERE parentid='" . mysql_real_escape_string($taskid, $link)."' ";
			$result = mysql_query($sql);
			if($result)
			{
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
			}
			else
			{
				error_log("TDOTask::getAllSubtasksForTask() could not get tasks for the specified task '$taskid'" . mysql_error());
                return false;
			}

            return array_values($tasks);
        }        
        
        

        //This returns either completed or incomplete subtasks. If you want to get all subtasks, call getAllNondeletedSubtasksForTask
		public static function getSubTasksForTask($taskid, $userid=NULL, $contextid=NULL, $tagsFilter=NULL, $tagsFilterByAnd=false, $assignedUser=NULL, $starredOnly=false, $completed=false, $link = NULL, $archived=false)
		{
			//			error_log("TDOTask::getSubTasksForTask('" . $listid . "')");

			if(empty($taskid))
			{
				error_log("TDOTask::getSubTasksForTask() called with a NULL taskid");
				return false;
			}
			if(!$link)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    error_log("TDOTask::getSubTasksForTask() could not get DB connection.");
                    return false;
                }
            }
            else
                $closeLink = false;
                
            $tableName = "tdo_tasks";
            if($completed)
                $tableName = "tdo_completed_tasks";
            else if($archived)
                $tableName = "tdo_archived_tasks";

			$sql = "SELECT $tableName.*, tdo_context_assignments.contextid FROM $tableName LEFT JOIN tdo_context_assignments ON ($tableName.taskid = tdo_context_assignments.taskid AND tdo_context_assignments.userid = '" . mysql_real_escape_string($userid) ."') ";
            
            if(!$tagsFilterByAnd && !empty($tagsFilter))
            {
                 $sql .= " LEFT JOIN tdo_tag_assignments ON (tdo_tag_assignments.taskid=$tableName.taskid) "; 
            }
            
            
            $sql .= " WHERE parentid='" . mysql_real_escape_string($taskid, $link)."' ";

            if(!empty($assignedUser) && $assignedUser != 'all')
			{
				if($assignedUser == "none")
					$sql = $sql." AND (assigned_userid = '' OR assigned_userid IS NULL) ";
				else
					$sql = $sql." AND assigned_userid = '" . mysql_real_escape_string($assignedUser, $link)."' ";
			}
            if(!empty($contextid) && $contextid != "all")
            {
                if($contextid == "nocontext")
                {
                    $sql .= " AND (tdo_context_assignments.contextid = '' OR tdo_context_assignments.contextid IS NULL) ";
                }
                else
                {
                    $escapedContextID = mysql_real_escape_string($contextid, $link);
                    $sql .= " AND (tdo_context_assignments.contextid = '" . $escapedContextID . "') ";
                }
            }
            
            $tagSQL = TDOTask::buildSQLFilterForTags($tagsFilter, $tagsFilterByAnd, $tableName);
            if(strlen($tagSQL) > 0)
                $sql .= " AND ($tagSQL) ";
            
            if($starredOnly == true)
            {
                $sql .= " AND starred!=0 ";
            }
            
            //Start date filter
            if ($completed == false)
			{
				$startDateFilterInterval = 0;
                $userSettings = TDOUserSettings::getUserSettingsForUserid($userid);
				if (!empty($userSettings))
				{
					$startDateFilterInterval = $userSettings->startDateFilterInterval();
				}
				
				if ($startDateFilterInterval > 0)
				{
					$todayInterval = mktime(0, 0, 0, date("n"), (date("j")), date("Y"));
					$filterInterval = $todayInterval + $startDateFilterInterval;

                    $sql .= " AND (startdate = 0 OR startdate < $filterInterval) ";
				}
			}

            
			$sql .= TDOTask::buildOrderByStatementForUser($userid, $completed);

//			error_log("Query string for subtasks: " . $sql);

			$result = mysql_query($sql);
			if($result)
			{
				$tasks = array();
				while($row = mysql_fetch_array($result))
				{
					if ( (empty($row['taskid']) == false) && (count($row) > 0) )
					{
						$task = TDOTask::taskFromRow($row);
						if (empty($task) == false)
						{
							$tasks[$row['taskid']] = $task;
						}
					}
				}
                if($closeLink)
                {
                    TDOUtil::closeDBLink($link);
                }
				return array_values($tasks);
			}
			else
			{
				error_log("TDOTask::getSubTasksForTask() could not get tasks for the specified task '$taskid'" . mysql_error());
			}

            if($closeLink)
            {
                TDOUtil::closeDBLink($link);
            }
			return false;
		}


		public static function getTask($listid, $calDavUri, $assignedUser=NULL, $includeDeleted=false)
		{
//			error_log("TDOTask::getTask('" . $listid . "', '" . $calDavUri . "')");
			if (!isset($listid))
			{
				error_log("TDOTask::getTask() called with NULL listid");
				return false;
			}

			if (!isset($calDavUri))
			{
				error_log("TDOTask::getTask() called with NULL $calDavUri");
				return false;
			}

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask::getTask() could not get DB connection.");
				return false;
			}
            
            $whereStatement = " WHERE listid = '" . mysql_real_escape_string($listid, $link) . "' AND caldavuri = '" . mysql_real_escape_string($calDavUri, $link) . "'";
            if($assignedUser != NULL && $assignedUser != 'all')
            {
                $assignedUser = mysql_real_escape_string($assignedUser, $link);
                $whereStatement .= " AND assigned_userid='g'";
            }
            
			$sql = "SELECT * FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT * from tdo_completed_tasks ".$whereStatement;

            if($includeDeleted)
            {
                $sql .= " UNION SELECT * FROM tdo_deleted_tasks ".$whereStatement;
            }

//			error_log("    SQL: " . $sql);
			$result = mysql_query($sql);
			if($result)
			{
				$row = mysql_fetch_array($result);
				if ( (empty($row)) || (count($row) == 0) )
				{
//					error_log("TDOTask::getTask() resulting row is blank");
					TDOUtil::closeDBLink($link);
					return NULL;
				}

				TDOUtil::closeDBLink($link);
				return TDOTask::taskFromRow($row);
			}

			error_log("TDOTask::getTask() could not find task: " . mysql_error());

			TDOUtil::closeDBLink($link);
			return NULL;
		}


		public static function deleteObjects($taskids)
		{
            // CRG - Disabled this method, I don't think it's used but it
            // needs to check for projects and child subtasks when deleting
////			error_log("TDOTask::deleteObjects()");
//			if (!isset($taskids))
//			{
//				error_log("TDOTask::deleteObjects() called with NULL array");
//				return false;
//			}
//
//			if (count($taskids) == 0)
//				return true; // Nothing to do
//
//			$link = TDOUtil::getDBLink();
//			if(!$link)
//			{
//				error_log("TDOTask::deleteObjects() could not get DB connection.");
//				return false;
//			}
//
//			$sql = "UPDATE tdo_tasks SET deleted=1 WHERE ";
//
//			$firstItem = true;
//			foreach($taskids as $taskid)
//			{
//				if ($firstItem)
//					$firstItem = false;
//				else
//				{
//					$sql = $sql . " OR ";
//				}
//
//				$sql = $sql . "taskid='" . mysql_real_escape_string($taskid, $link) . "'";
//			}
//
//			if(mysql_query($sql, $link))
//			{
//				TDOUtil::closeDBLink($link);
//				return true;
//			}
//			else
//			{
//				error_log("TDOTask::deleteObjects() failed" . mysql_error());
//			}
//
//			TDOUtil::closeDBLink($link);
			return false;
		}


		public static function getTaskForTaskId($taskid, $link=NULL)
		{
//			error_log("TDOTask::getTaskForTaskId('" . $taskid . "')");
			if (!isset($taskid))
			{
				error_log("TDOTask::getTaskForTaskId() called with NULL taskid");
				return false;
			}
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
            }
            else
                $closeDBLink = false;
            
            $whereStatement = " WHERE taskid = '" . mysql_real_escape_string($taskid, $link) . "'";

			$sql = "SELECT * FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT * from tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT * from tdo_deleted_tasks ".$whereStatement;
            
			$result = mysql_query($sql);
			if($result)
			{
				$row = mysql_fetch_array($result);
				if ( (empty($row) == false) && (count($row) > 0) )
				{
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return TDOTask::taskFromRow($row);
				}
				else
				{
					//error_log("TDOTask::getTaskForTaskId() empty row for task id: " . $taskid);
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
				}
			}

			error_log("TDOTask::getTaskForTaskId() no result for task id: " . $taskid);
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}
        
        
		public static function getArchivedTaskForTaskId($taskid, $link=NULL)
		{
            //			error_log("TDOTask::getTaskForTaskId('" . $taskid . "')");
			if (!isset($taskid))
			{
				error_log("TDOTask::getArchivedTaskTaskId() called with NULL taskid");
				return false;
			}

            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
            }
            else
                $closeDBLink = false;
            
            $whereStatement = " WHERE taskid = '" . mysql_real_escape_string($taskid, $link) . "'";
            
			$sql = "SELECT * FROM tdo_archived_tasks ".$whereStatement;
            
			$result = mysql_query($sql);
			if($result)
			{
				$row = mysql_fetch_array($result);
				if ( (empty($row) == false) && (count($row) > 0) )
				{
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return TDOTask::taskFromRow($row);
				}
				else
				{
					//error_log("TDOTask::getTaskForTaskId() empty row for task id: " . $taskid);
                    if($closeDBLink)
                        TDOUtil::closeDBLink($link);
					return false;
				}
			}
            
			error_log("TDOTask::getArchivedTaskTaskId() no result for task id: " . $taskid);
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
			return false;
		}        

        
		public static function notificationPropertiesForTask($taskid)
		{
            $displayProperties = array();
			
            $task = TDOTask::getTaskForTaskId($taskid);
			if(empty($task))
				return false;
            
			$name = htmlspecialchars($task->name());
            if(!empty($name))
                $displayProperties['Name'] = $name;
            
            $compDate = $task->completionDate();
            if(!empty($compDate) && $compDate != 0)
                $displayProperties['Completed'] = TDOUtil::taskDueDateStringFromTimestamp($compDate);
            else
            {
                $dueDate = $task->dueDate();
                if(!empty($dueDate) && $dueDate != 0)
                    $displayProperties['Due'] = TDOUtil::taskDueDateStringFromTimestamp($dueDate);
            }
            
			$assignString = NULL;
			$assignedUserid = $task->assignedUserId();
            
			if($assignedUserid)
			{
				$userName = htmlspecialchars(TDOUser::displayNameForUserId($assignedUserid));
				if($userName)
				{
					$assignString = $userName;
				}
			}
            
            if($assignString != NULL)
                $displayProperties['Assigned'] = $assignString;
            
			$note = htmlspecialchars($task->note());
            if(!empty($note))
                $displayProperties['Note'] = $note;
            
			return $displayProperties;
		}

        
		public static function changedNotificationPropertiesForTask($tdoChange)
		{
            $displayProperties = array();
			
            $task = TDOTask::getTaskForTaskId($tdoChange->itemId());
			if(empty($task))
				return false;
            
            $changes = json_decode($tdoChange->changeData());
            
            if(isset($changes->{'completiondate'}))
            {
                if($changes->{'completiondate'} == "0")
                {
                    $dueDate = $task->dueDate();
                    if(!empty($dueDate) && $dueDate != 0)
                        $displayProperties['Due'] = TDOUtil::taskDueDateStringFromTimestamp($dueDate);
                }
                else
                {
                    $compDate = $task->completionDate();                    
                    $displayProperties['Completed'] = TDOUtil::taskDueDateStringFromTimestamp($compDate);
                }
            }
            
            if(isset($changes->{'taskName'}))
            {
                $name = htmlspecialchars($task->name());
                if(!empty($name))
                    $displayProperties['Name'] = $name;
            }
            
            if(isset($changes->{'taskNote'}))
            {
                $note = htmlspecialchars($task->note());
                if(!empty($note))
                    $displayProperties['Note'] = $note;
                else
                    $displayProperties['Note was removed'] = " ";
            }
            
            if(isset($changes->{'taskDueDate'}))
            {
                $dueDate = $task->dueDate();
                if(!empty($dueDate) && $dueDate != 0)
                    $displayProperties['Due'] = TDOUtil::taskDueDateStringFromTimestamp($dueDate);
                else
                    $displayProperties['Due'] = "No Date";
            }
            
            if(isset($changes->{'taskStartDate'}))
            {
                $startDate = $task->startDate();
                if(!empty($startDate))
                    $displayProperties['Start Date'] = TDOUtil::taskDueDateStringFromTimestamp($startDate);
                else
                    $displayProperties['Start Date'] = "No Date";
            }
            
            if( (isset($changes->{'assignedUserId'})) || (isset($changes->{'old-assignedUserId'})) )
            {
                if(empty($changes->{'assignedUserId'}) )
                {
                    $displayProperties['Assignment'] = "unassigned";
                }
                else
                {
                    $assignString = NULL;
                    $assignedUserid = $task->assignedUserId();
                    
                    if($assignedUserid)
                    {
                        $userName = htmlspecialchars(TDOUser::displayNameForUserId($assignedUserid));
                        if($userName)
                        {
                            $assignString = $userName;
                        }
                    }
                    
                    if($assignString != NULL)
                        $displayProperties['Assigned'] = $assignString;
                }
            }

			return $displayProperties;
		}
        

        public static function getListIdForTaskId($taskid, $link=NULL)
        {
            if(empty($taskid))
                return false;
            
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                    return false;
            }
            else
                $closeDBLink = false;
            
            $taskid = mysql_real_escape_string($taskid, $link);
            $whereStatement = " WHERE taskid='$taskid'";
            
            $sql = "SELECT listid FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT listid FROM tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT listid FROM tdo_deleted_tasks ".$whereStatement;
            
            $result = mysql_query($sql);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['listid']))
                    {
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return $row['listid'];
                    }
                }
            }
            else
                error_log("getListIdForTaskId failed ".mysql_error());
            
            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }
        
        public static function getContextIdForTaskIdForUser($taskid, $userid)
        {
            if(empty($taskid) || empty($userid))
            {
                return false;
            }
            
            $link = TDOUtil::getDBLink();
            if(empty($link))
                return false;
            
            $taskid = mysql_real_escape_string($taskid, $link);
            $userid = mysql_real_escape_string($userid, $link);
            
            $sql = "SELECT contextid FROM tdo_context_assignments WHERE taskid='$taskid' AND userid='$userid'";
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['contextid']))
                    {
                        $contextid = $row['contextid'];
                        TDOUtil::closeDBLink($link);
                        return $contextid;
                    }
                }
            }
            else
                error_log("getContextIdForTaskIdForUser failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }

        public static function getParentIdForTaskId($taskid, $link=NULL)
        {
            if(empty($taskid))
                return false;
            
            if(empty($link))
            {
                $closeDBLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                    return false;
            }
            else
                $closeDBLink = false;
            
            $taskid = mysql_real_escape_string($taskid, $link);
            $whereStatement = " WHERE taskid='$taskid'";
            
            $sql = "SELECT parentid FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT parentid FROM tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT parentid FROM tdo_deleted_tasks ".$whereStatement;
            
            $result = mysql_query($sql);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['parentid']))
                    {
                        if($closeDBLink)
                            TDOUtil::closeDBLink($link);
                        return $row['parentid'];
                    }
                }
            }
            else
                error_log("getParentIdForTaskId failed".mysql_error());

            if($closeDBLink)
                TDOUtil::closeDBLink($link);
            return false;
        }


        public static function getAssignedUserForTask($taskid)
        {
            if(empty($taskid))
                return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
                return false;
            $taskid = mysql_real_escape_string($taskid, $link);
            $whereStatement = " WHERE taskid='$taskid'";
            
            $sql = "SELECT assigned_userid FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT assigned_userid FROM tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT assigned_userid FROM tdo_deleted_tasks ".$whereStatement;         
            
            $result = mysql_query($sql, $link);
            if($result)
            {
                $row = mysql_fetch_array($result);
                if($row && isset($row['assigned_userid']))
                {
                    TDOUtil::closeDBLink($link);
                    return $row['assigned_userid'];
                }
            }
            else
                error_log("getAssignedUserForTask failed:".mysql_error());

            TDOUtil::closeDBLink($link);
            return false;
        }

		public static function getNameForTask($taskid)
		{
			if(!isset($taskid))
				return false;

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask failed to get dblink");
				return false;
			}

			$taskid = mysql_real_escape_string($taskid, $link);
            $whereStatement = " WHERE taskid='$taskid'";
            
            $sql = "SELECT name FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT name FROM tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT name FROM tdo_deleted_tasks ".$whereStatement;
            
			$result = mysql_query($sql);
			if($result)
			{
				$resultArray = mysql_fetch_array($result);
				if(isset($resultArray['name']))
				{
					TDOUtil::closeDBLink($link);
					return TDOUtil::ensureUTF8($resultArray['name']);
				}

			}

			TDOUtil::closeDBLink($link);
			return false;
		}


		public static function isTaskIdAProject($taskid)
		{
            $isProject = false;

			if(!isset($taskid))
				return false;

			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask failed to get dblink");
				return false;
			}

			$taskid = mysql_real_escape_string($taskid, $link);
            $whereStatement = " WHERE taskid='$taskid'";
            
            $sql = "SELECT task_type FROM tdo_tasks ".$whereStatement;
            $sql .= " UNION SELECT task_type FROM tdo_completed_tasks ".$whereStatement;
            $sql .= " UNION SELECT task_type FROM tdo_deleted_tasks ".$whereStatement;
            
			$result = mysql_query($sql);
			if($result)
			{
				$row = mysql_fetch_array($result);

                if(isset($row['task_type']))
                {
                    if($row['task_type'] == 1)
                        $isProject = true;
                }
			}

			TDOUtil::closeDBLink($link);

            return $isProject;
		}


		public static function taskFromRow($row)
		{
//			error_log("TDOTask::taskFromRow()");

			if ( (empty($row)) || (count($row) == 0) )
			{
				error_log("TDOTask::createTaskFromRow() was passed a NULL row");
				return NULL;
			}

			if (empty($row['taskid']))
			{
				error_log("TDOTask::createTaskFromRow() did not contain an taskid");
				return NULL;
			}

			$task = new TDOTask();
			$task->setTaskId($row['taskid']);

			if (isset($row['listid']))
				$task->setListId($row['listid']);

			if (isset($row['name']))
				$task->setName(strval($row['name']));

			if (isset($row['note']))
				$task->setNote($row['note']);
				
			if (isset($row['startdate']))
				$task->setCompStartDate($row['startdate']);	

			if (isset($row['duedate']))
				$task->setCompDueDate($row['duedate']);

            if(isset($row['due_date_has_time']))
                $task->setCompDueDateHasTime($row['due_date_has_time']);

			if (isset($row['completiondate']))
				$task->setCompletionDate($row['completiondate']);

			if (isset($row['priority']))
				$task->setCompPriority($row['priority']);

			if (isset($row['timestamp']))
				$task->setTimestamp($row['timestamp']);

//			if (isset($row['caldavuri']))
//				$task->setcalDavUri($row['caldavuri']);
//
//			if (isset($row['caldavdata']))
//				$task->setcalDavData($row['caldavdata']);

            if(isset($row['deleted']))
                $task->setDeleted($row['deleted']);

            if(isset($row['starred']))
                $task->setCompStarredVal($row['starred']);

            if(isset($row['task_type']))
                $task->setTaskType($row['task_type']);

            if(isset($row['type_data']))
                $task->setTypeData($row['type_data']);

            if(isset($row['parentid']))
                $task->setParentId($row['parentid']);

            if(isset($row['assigned_userid']))
                $task->setAssignedUserid($row['assigned_userid']);

            if(isset($row['recurrence_type']))
                $task->setRecurrenceType($row['recurrence_type']);
            if(isset($row['advanced_recurrence_string']))
                $task->setAdvancedRecurrenceString($row['advanced_recurrence_string']);

//            if(isset($row['task_checklist_uncompleted_count']))
//                $task->setChecklistUncompletedCount($row['task_checklist_uncompleted_count']);
//            if(isset($row['task_checklist']))
//                $task->setChecklist($row['task_checklist']);

            if(isset($row['project_duedate']))
                $task->setProjectDueDate($row['project_duedate']);
            if(isset($row['project_duedate_has_time']))
                $task->setProjectDueDateHasTime($row['project_duedate_has_time']);
            if(isset($row['project_priority']))
                $task->setProjectPriority($row['project_priority']);
            if(isset($row['project_starred']))
                $task->setProjectStarred($row['project_starred']);
            if(isset($row['project_startdate']))
                $task->setProjectStartDate($row['project_startdate']);

            if(isset($row['contextid']))
                $task->setContextId($row['contextid']);

            if(isset($row['context_assignment_timestamp']))
                $task->setContextLastModified($row['context_assignment_timestamp']);

            if(isset($row['location_alert']))
                $task->setLocationAlert($row['location_alert']);

            if(isset($row['sort_order']))
                $task->setSortOrder($row['sort_order']);

			return $task;
		}

        public static function subtaskCountForProject($taskid, $includeCompleted = false)
        {
            if(empty($taskid))
            {
                error_log("TDOTask::subtaskCountForProject called with an empty taskid");
                return false;
            }

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTask::subtaskCountForProject could not get DB connection.");
                return false;
            }

            $taskCount = 0;
            $escapedTaskId = mysql_real_escape_string($taskid, $link);
//            $sql = "SELECT COUNT(taskid) FROM tdo_tasks WHERE ";
//
//            if($includeCompleted == false)
//            {
//                $sql .= " completiondate = 0 AND ";
//            }
//
//            $sql .= "deleted=0 AND parentid='".$escapedTaskId."'";

            $whereStatement = " WHERE parentid='".$escapedTaskId."'";
            
            $sql = "SELECT COUNT(taskid) FROM tdo_tasks ".$whereStatement;
            if($includeCompleted)
            {
                $sql .= " UNION SELECT COUNT(taskid) FROM tdo_completed_tasks ".$whereStatement;
            }

            $result = mysql_query($sql, $link);
            if($result)
            {
                if($row = mysql_fetch_array($result))
                {
                    if(isset($row['0']))
                        $taskCount = $row['0'];
                        
                    if(isset($row['1']))
                        $taskCount += $row['1'];
                }
            }
            else
            {
                error_log("TDOTask::subtaskCountForProject could not get child count" . mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }

            TDOUtil::closeDBLink($link);
            return $taskCount;
        }






        /********* Recurrence Methods **************/

        public static function localizedStringForTaskRecurrenceType($type)
        {
            switch($type)
            {
                case TaskRecurrenceType::Daily:
                case TaskRecurrenceType::Daily + 100:
                {
                    return "Every Day";
                }
                case TaskRecurrenceType::None:
                {
                    return "None";
                }
                case TaskRecurrenceType::Weekly:
                case TaskRecurrenceType::Weekly + 100:
                {
                    return "Every Week";
                }
                case TaskRecurrenceType::Biweekly:
                case TaskRecurrenceType::Biweekly + 100:
                {
                    return "Every 2 Weeks";
                }
                case TaskRecurrenceType::Monthly:
                case TaskRecurrenceType::Monthly + 100:
                {
                    return "Every Month";
                }
                case TaskRecurrenceType::Quarterly:
                case TaskRecurrenceType::Quarterly + 100:
                {
                    return "Quarterly";
                }
                case TaskRecurrenceType::Semiannually:
                case TaskRecurrenceType::Semiannually + 100:
                {
                    return "Semiannually";
                }
                case TaskRecurrenceType::Yearly:
                case TaskRecurrenceType::Yearly + 100:
                {
                    return "Every Year";
                }
                case TaskRecurrenceType::WithParent:
                case TaskRecurrenceType::WithParent + 100:
                {
                    return "Repeat With Parent Task";
                }
                case TaskRecurrenceType::Advanced:
                case TaskRecurrenceType::Advanced + 100:
                {
                    return "Other";
                }
                default:
                    return "Unknown";
            }
        }

        public static function localizedGenericStringForAdvancedRecurrenceType($type)
        {
            switch($type)
            {
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths:
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths + 100:
                {
                    return "Every X days";
                }
                case AdvancedRecurrenceType::EveryMonTueEtc:
                case AdvancedRecurrenceType::EveryMonTueEtc + 100:
                {
                    return "On days of the week";
                }
                case AdvancedRecurrenceType::TheXOfEachMonth:
                case AdvancedRecurrenceType::TheXOfEachMonth + 100:
                {
                    return "The X day of each month";
                }
                default:
                    return "Unknown";
            }
        }

        public static function localizedStringForAdvancedRecurrenceStringOfType($advancedString, $advancedType)
        {
            switch($advancedType)
            {
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths:
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths + 100:
                {
                    return TDOTask::localizedStringForRepeatEveryXDaysString($advancedString);
                }
                case AdvancedRecurrenceType::EveryMonTueEtc:
                case AdvancedRecurrenceType::EveryMonTueEtc + 100:
                {
                    return TDOTask::localizedStringForRepeatEveryXEtcString($advancedString);
                }
                case AdvancedRecurrenceType::TheXOfEachMonth:
                case AdvancedRecurrenceType::TheXOfEachMonth + 100:
                {
                    return TDOTask::localizedStringForRepeatOnTheXOfTheMonthString($advancedString);
                }
                default:
                    return "Unknown";
            }

        }

        public static function defaultLocalizedStringForAdvancedRecurrenceType($advancedType)
        {
            switch($advancedType)
            {
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths:
                case AdvancedRecurrenceType::EveryXDaysWeeksMonths + 100:
                {
                    return "Every 1 Days";
                }
                case AdvancedRecurrenceType::EveryMonTueEtc:
                case AdvancedRecurrenceType::EveryMonTueEtc + 100:
                {
                    return "Unknown";
                }
                case AdvancedRecurrenceType::TheXOfEachMonth:
                case AdvancedRecurrenceType::TheXOfEachMonth + 100:
                {
                    return "The 1st Monday of each month";
                }
                default:
                    return "Unknown";
            }

        }

        public static function localizedStringForRepeatEveryXDaysString($advancedString)
        {
            if( ($advancedString == NULL) || (strlen($advancedString) == 0) )
                return "Unknown";

            $components = preg_split('/\s+/', $advancedString);
            if(count($components) < 3)
                return "Unknown";

            if(strcasecmp($components[0], "Every") == 0)
            {
                $number = intval($components[1]);
                if($number < 0)
                    return "Unknown";

                $dayMonthYearVal = $components[2];

                if(strcasecmp($dayMonthYearVal, "Weeks") == 0 || strcasecmp($dayMonthYearVal, "Week") == 0)
                {
                    return "Every $number Weeks";
                }
                else if(strcasecmp($dayMonthYearVal, "Months") == 0 || strcasecmp($dayMonthYearVal, "Month") == 0)
                {
                    return "Every $number Months";
                }
                else if(strcasecmp($dayMonthYearVal, "Years") == 0 || strcasecmp($dayMonthYearVal, "Year") == 0)
                {
                    return "Every $number Years";
                }
                else
                {
                    return "Every $number Days";
                }
            }

            return "Unknown";
        }


        public static function localizedStringForRepeatOnTheXOfTheMonthString($advancedString)
        {
            if( ($advancedString == NULL) || (strlen($advancedString) == 0) )
                return "Unknown";

            $localizedFormatString = NULL;
            $localizedDayString = NULL;

            $components = preg_split('/\s+/', $advancedString);
            $compZero = $components[0];
            $compOne = $components[1];
            $compTwo = $components[2];

            if(strcasecmp($compZero, "The") == 0)
            {
                if( (strcasecmp($compTwo, "Monday") == 0) || (strcasecmp($compTwo, "Mon") == 0))
                    $localizedDayString = "Monday";
                else if( (strcasecmp($compTwo, "Tuesday") == 0) || (strcasecmp($compTwo, "Tue") == 0) || (strcasecmp($compTwo, "Tues") == 0))
                    $localizedDayString = "Tuesday";
                else if( (strcasecmp($compTwo, "Wednesday") == 0) || (strcasecmp($compTwo, "Wed") == 0) )
                    $localizedDayString = "Wednesday";
                else if( (strcasecmp($compTwo, "Thursday") == 0) || (strcasecmp($compTwo, "Thu") == 0)
                        || (strcasecmp($compTwo, "Thur") == 0) || (strcasecmp($compTwo, "Thurs") == 0))
                    $localizedDayString = "Thursday";
                else if( (strcasecmp($compTwo, "Friday") == 0) || (strcasecmp($compTwo, "Fri") == 0) )
                    $localizedDayString = "Friday";
                else if( (strcasecmp($compTwo, "Saturday") == 0) || (strcasecmp($compTwo, "Sat") == 0) )
                    $localizedDayString = "Saturday";
                else if( (strcasecmp($compTwo, "Sunday") == 0) || (strcasecmp($compTwo, "Sun") == 0) )
                    $localizedDayString = "Sunday";

                if($localizedDayString != NULL)
                {
                    if( (strcasecmp($compOne, "first") == 0) || (strcasecmp($compOne, "1st") == 0))
                        $localizedFormatString = "The 1st $localizedDayString of each month";
                    else if( (strcasecmp($compOne, "second") == 0) || (strcasecmp($compOne, "2nd") == 0))
                        $localizedFormatString = "The 2nd $localizedDayString of each month";
                    else if( (strcasecmp($compOne, "third") == 0) || (strcasecmp($compOne, "3rd") == 0))
                        $localizedFormatString = "The 3rd $localizedDayString of each month";
                    else if( (strcasecmp($compOne, "fourth") == 0) || (strcasecmp($compOne, "4th") == 0))
                        $localizedFormatString = "The 4th $localizedDayString of each month";
                    else if( (strcasecmp($compOne, "fifth") == 0) || (strcasecmp($compOne, "5th") == 0))
                        $localizedFormatString = "The 5th $localizedDayString of each month";
                    else if( (strcasecmp($compOne, "last") == 0) || (strcasecmp($compOne, "final") == 0))
                        $localizedFormatString = "The last $localizedDayString of each month";

                    return $localizedFormatString;
                }
            }
            return "Unknown";
        }


        public static function localizedStringForRepeatEveryXEtcString($advancedString)
        {
            if( ($advancedString == NULL) || (strlen($advancedString) == 0) )
                return "Unknown";

            $selectedDays = 0;
            $weekdaySelected = false;
            $localizedString = NULL;
            $addSecond = false;
            $addThird = false;
            $addFourth = false;

            // This is for RTM only, Toodledo doesn't support second, third or fourth
            if(stripos($advancedString, "Second") !== false)
            {
                $addSecond = true;
                $addThird = false;
                $addFourth = false;
            }
            if(stripos($advancedString, "Third") !== false)
            {
                $addSecond = false;
                $addThird = true;
                $addFourth = false;
            }
            if(stripos($advancedString, "Fourth") !== false)
            {
                $addSecond = false;
                $addThird = false;
                $addFourth = true;
            }


            if(stripos($advancedString, "Monday") !== false)
                $selectedDays |= MON_SELECTION;
            if(stripos($advancedString, "mon") !== false)
                $selectedDays |= MON_SELECTION;

            if(stripos($advancedString, "Tuesday") !== false)
                $selectedDays |= TUE_SELECTION;
            if(stripos($advancedString, "Tue") !== false)
                $selectedDays |= TUE_SELECTION;
            if(stripos($advancedString, "Tues") !== false)
                $selectedDays |= TUE_SELECTION;

            if(stripos($advancedString, "Wednesday") !== false)
                $selectedDays |= WED_SELECTION;
            if(stripos($advancedString, "Wed") !== false)
                $selectedDays |= WED_SELECTION;
            if(stripos($advancedString, "Wensday") !== false)
                $selectedDays |= WED_SELECTION;

            if(stripos($advancedString, "Thursday") !== false)
                $selectedDays |= THU_SELECTION;
            if(stripos($advancedString, "Thu") !== false)
                $selectedDays |= THU_SELECTION;
            if(stripos($advancedString, "Thurs") !== false)
                $selectedDays |= THU_SELECTION;

            if(stripos($advancedString, "Friday") !== false)
                $selectedDays |= FRI_SELECTION;
            if(stripos($advancedString, "Fri") !== false)
                $selectedDays |= FRI_SELECTION;
            if(stripos($advancedString, "Fryday") !== false)
                $selectedDays |= FRI_SELECTION;

            if(stripos($advancedString, "Saturday") !== false)
                $selectedDays |= SAT_SELECTION;
            if(stripos($advancedString, "Sat") !== false)
                $selectedDays |= SAT_SELECTION;

            if(stripos($advancedString, "Sunday") !== false)
                $selectedDays |= SUN_SELECTION;
            if(stripos($advancedString, "Sun") !== false)
                $selectedDays |= SUN_SELECTION;

            if(stripos($advancedString, "Weekday") !== false)
                $selectedDays |= WEEKDAY_SELECTION;

            if(stripos($advancedString, "Weekend") !== false)
                $selectedDays |= WEEKEND_SELECTION;

            if(stripos($advancedString, "Every Day") !== false)
                $selectedDays |= (WEEKEND_SELECTION | WEEKDAY_SELECTION);


            if( ($selectedDays & (WEEKEND_SELECTION | WEEKDAY_SELECTION)) == (WEEKEND_SELECTION | WEEKDAY_SELECTION) )
                $localizedString = "Every Day";
            else
            {
                $localizedString = "Every ";

                if($addSecond)
                {
                    $localizedString .= "2nd ";
                }
                else if($addThird)
                {
                    $localizedString .= "3rd ";
                }
                else if($addFourth)
                {
                    $localizedString .= "4th ";
                }

                if( ( ($selectedDays & WEEKDAY_SELECTION) == WEEKDAY_SELECTION) &&
                   ( !($selectedDays & SAT_SELECTION) ) &&
                   ( !($selectedDays & SUN_SELECTION) ) )
                {
                    $localizedString .= "Weekday";
                    $localizedString .= ", ";
                    $weekdaySelected = true;
                }
                else
                {
                    if($selectedDays & MON_SELECTION)
                    {
                        $localizedString .= "Monday";
                        $localizedString .= ", ";
                        $weekdaySelected = true;
                    }
                    if($selectedDays & TUE_SELECTION)
                    {
                        $localizedString .= "Tuesday";
                        $localizedString .= ", ";
                        $weekdaySelected = true;
                    }
                    if($selectedDays & WED_SELECTION)
                    {
                        $localizedString .= "Wednesday";
                        $localizedString .= ", ";
                        $weekdaySelected = true;
                    }
                    if($selectedDays & THU_SELECTION)
                    {
                        $localizedString .= "Thursday";
                        $localizedString .= ", ";
                        $weekdaySelected = true;
                    }
                    if($selectedDays & FRI_SELECTION)
                    {
                        $localizedString .= "Friday";
                        $localizedString .= ", ";
                        $weekdaySelected = true;
                    }
                }

                if( ( ($selectedDays & WEEKEND_SELECTION) == WEEKEND_SELECTION) && (!$weekdaySelected) )
                {
                    $localizedString .= "Weekend";
                    $localizedString .= ", ";
                }
                else
                {
                    if($selectedDays & SAT_SELECTION)
                    {
                        $localizedString .= "Saturday";
                        $localizedString .= ", ";
                    }

                    if($selectedDays & SUN_SELECTION)
                    {
                        $localizedString .= "Sunday";
                        $localizedString .= ", ";
                    }
                }
                if(strcmp($localizedString,"Every ") == 0)
                    return "Unknown";
                else
                {
                    //take off the tailing comma space
                    if(strlen($localizedString) > 1)
                    {
                        $lastChar = substr($localizedString, strlen($localizedString) - 2, 2);
                        if(strcmp($lastChar, ", ") == 0)
                        {
                            $localizedString = substr($localizedString, 0, strlen($localizedString) - 2);
                        }
                    }
                }
            }

            return $localizedString;
        }



        public static function advancedRecurrenceTypeForString($string)
        {
            if(empty($string) || strlen($string) == 0)
            {
               return AdvancedRecurrenceType::Unknown;
            }

            $components = preg_split('/\s+/', $string);

            if(count($components) == 0)
            {
               return AdvancedRecurrenceType::Unknown;
            }
            $firstWord = $components[0];

            if(strcasecmp($firstWord, "Every") == 0)
            {
                if(count($components) < 2)
                    return AdvancedRecurrenceType::Unknown;

                $secondWord = $components[1];
                if(strcasecmp($secondWord, "0") == 0)
                    return AdvancedRecurrenceType::Unknown;

                if(intval($secondWord) != 0)
                {
                    return AdvancedRecurrenceType::EveryXDaysWeeksMonths;
                }

                if(strlen($secondWord) > 0)
                {
                    $char = substr($secondWord, 0, 1);
                    if(ctype_alpha($char) == false)
                    {
                        return AdvancedRecurrenceType::Unknown;
                    }
                }
                return AdvancedRecurrenceType::EveryMonTueEtc;

            }

            if(strcasecmp($firstWord, "On") == 0 || strcasecmp($firstWord, "the") == 0)
            {
               return AdvancedRecurrenceType::TheXOfEachMonth;
            }

            return AdvancedRecurrenceType::Unknown;

        }

        public function uncompleteTask()
        {
            $link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask failed to get dblink");
				return false;
			}

            // Do all of this in a transaction so we won't end up with uncompleted subtask with completed parents
			if(!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTask::Couldn't start transaction".mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
            
            $this->setCompletionDate(0, true);
            if($this->moveFromCompletedTable($link) == false)
            {
                error_log("TDOTask::Could not update uncompleted task, rolling back ".mysql_error());
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return false;
            }

//            // Check to see if this is a subtask.  If it is, we need to see if this
//            // subtask will be the only one that's now marked as incomplete.  If
//            // this is the case, automatically mark the parent task incomplete.
//            if (task.isSubtask == YES)
//            {
//                Task *parentTask = [self taskForTaskID:task.parentID];
//                if ( (parentTask != nil) && (parentTask.complete == YES) )
//                {
//                    // Check to see if the project's subtasks are all completed
//                    NSInteger incompleteSubtaskCount = [self incompleteChildTaskCountForParentTaskID:parentTask.taskID];
//                    if (incompleteSubtaskCount > 0)
//                    {
//                        // Mark the parent task incomplete
//                        parentTask.completionDate = [NSDate distantPast];
//                        if(parentTask.locationAlert != nil && parentTask.locationAlert.length > 0)
//                            uncompletedTaskHasLocationAlert = YES;
//
//                        [self updateTask:parentTask setDirty:YES];
//                    }
//                }
//            }
//
//            [self updateNotificationsForTask:task];

            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOList::Couldn't commit transaction completing task ".mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}
            
            TDOUtil::closeDBLink($link);
            return true;
        }

        //Returns an array where 'success' indicates whether completing the task succeeded and 'completedTask'
        //holds the new completed task if there was a recurrence
        public function completeTask($completionDate = NULL)
        {
            if(empty($completionDate))
                $completionDate = time();

            $results = array();

            $link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTask failed to get dblink");
				return false;
			}

            // Do all of this in a transaction so we won't end up with completed task with uncompleted subtasks
			if(!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTask::Couldn't start transaction".mysql_error());
				TDOUtil::closeDBLink($link);
				$results['success'] = false;
                return $results;
			}

            $this->setCompletionDate($completionDate, true);

            if($this->isProject() == true)
            {
                if(!$this->completeAllChildTasks($link))
                {
                    error_log("TDOTask::unable to complete child tasks for project ".mysql_error());
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    $results['success'] = false;
                    return $results;
                }
//                NSSet *children = [self childTasksForParentTask:task];
//                for(Task *childTask in children)
//                {
//                    [self setNotificationsNeedUpdateForTask:childTask];
//                }

            }
            $this->completeChecklistItems();

            // If the task is a recurring task, the original will be marked as
            // completed and a new task will be created complete with recurrence
            // data.

            $completedTask = $this->processRecurrence($link);
            $results['completedTask'] = $completedTask;

            //If this is a recurring task, it will not be complete after we processRecurrence and we can just update it in
            //the db
            if($this->completionDate() == 0)
            {
                if($this->updateObject($link) == false)
                {
                    error_log("TDOTask::Could not update completed task, rolling back ".mysql_error());
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    $results['success'] = false;
                    return $results;
                }
            }
            else
            {
                //This was not a recurring task, so we should delete it and add it to the completed table
                if($this->moveToCompletedTable($link) == false)
                {
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    $results['success'] = false;
                    return $results;
                }
            }

//            if (completedTask != nil)
//                [self setNotificationsNeedUpdateForTask:completedTask];
//
//            [self setNotificationsNeedUpdateForTask:task];


            if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOList::Couldn't commit transaction completing task ".mysql_error());
				mysql_query("ROLLBACK");
				TDOUtil::closeDBLink($link);
				$results['success'] = false;
                return $results;
			}
            
            TDOUtil::closeDBLink($link);

            $results['success'] = true;
            return $results;
        }

        public function completeAllChildTasks($link)
        {
            if($this->isProject() == false)
                return false;

            //We have to loop through and complete these by hand because we also have to complete
            //checklist items on each of the subtasks. Otherwise we could just do one database call like
            //in Todo
            $subTasks = TDOTask::getSubTasksForTask($this->taskId(), NULL, NULL, NULL, false, NULL, false, false, $link);

            foreach($subTasks as $subTask)
            {
                $subTask->setCompletionDate($this->completionDate(), true);
                
                if($subTask->completeChecklistItems($link) == false)
                {
                    return false;
                }
                
                if($subTask->moveToCompletedTable($link) == false)
                {
                    return false;
                }
            }

            return true;
        }

        public function moveToCompletedTable($link=NULL)
        {
           if($link == NULL)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    return false;
                }
            }
            else
            {
                $closeLink = false;
            }
            
            $this->setTimestamp(time());
            if($this->addObject($link) == false)
            {
                if($closeLink)
                {
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            $sql = "DELETE FROM tdo_tasks WHERE taskid='".mysql_real_escape_string($this->taskId(), $link)."'";
            
            if(mysql_query($sql, $link) == false)
            {
                error_log("TDOTask::Could not update completed task".mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return false;
                
            }

            if($closeLink)
                TDOUtil::closeDBLink($link);

            return true;            
        }
        
        public function moveFromCompletedTable($link=NULL)
        {
            if($link == NULL)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    return false;
                }
            }
            else
            {
                $closeLink = false;
            }
            
            $this->setTimestamp(time());
            if($this->addObject($link) == false)
            {
                if($closeLink)
                {
                    TDOUtil::closeDBLink($link);
                }
                return false;
            }
            
            $sql = "DELETE FROM tdo_completed_tasks WHERE taskid='".mysql_real_escape_string($this->taskId(), $link)."'";
            
            if(mysql_query($sql, $link) == false)
            {
                error_log("TDOTask::Could not update incomplete task".mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return false;
                
            }

            if($closeLink)
                TDOUtil::closeDBLink($link);

            return true;     
        }
		
		public function moveFromDeletedTable($link=NULL)
		{
			$closeLink = false;
			if ($link == NULL) {
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link) {
					return false;
				}
			}
			$this->setTimestamp(time());
			$this->setDeleted(0);
			if ($this->addObject($link) == false) {
				if ($closeLink) {
					TDOUtil::closeDBLink($link);
				}
				return false;
			}
			
			$sql = "DELETE FROM tdo_deleted_tasks WHERE taskid='" . mysql_real_escape_string($this->taskId(), $link) . "'";
			if (mysql_query($sql, $link) == false) {
				error_log("TDOTask::Could not update a deleted task: " . mysql_error());
				if ($closeLink) {
					TDOUtil::closeDBLink($link);
				}
				return false;
			}
			
			if ($closeLink) {
				TDOUtil::closeDBLink($link);
			}
			
			return true;
		}

        public function completeChecklistItems($link=NULL)
        {
            if($link == NULL)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    return false;
                }
            }
            else
            {
                $closeLink = false;
            }
            
            
            $sql = "UPDATE tdo_taskitos SET completiondate=".intval($this->completionDate()).", timestamp=".time()." WHERE parentid='".mysql_real_escape_string($this->taskId(), $link)."'";
        
            if(!mysql_query($sql, $link))
            {
                error_log("completeChecklistItems failed: ".mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                    
                return false;
            }
            
            if(mysql_affected_rows($link) > 0)
                TDOList::updateTaskitoTimestampForList($this->listId(), time(), $link);
        
            if($closeLink)
                TDOUtil::closeDBLink($link);

            return true;

        }

        public function uncompleteChecklistItems($link=NULL)
        {
            if($link == NULL)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    return false;
                }
            }
            else
            {
                $closeLink = false;
            }
            
            
            $sql = "UPDATE tdo_taskitos SET completiondate=0, timestamp=".time()." WHERE parentid='".mysql_real_escape_string($this->taskId(), $link)."'";
        
            if(!mysql_query($sql, $link))
            {
                error_log("uncompleteChecklistItems failed: ".mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                    
                return false;
            }
            
            if(mysql_affected_rows($link) > 0)
                TDOList::updateTaskitoTimestampForList($this->listId(), time(), $link);
        
            if($closeLink)
                TDOUtil::closeDBLink($link);

            return true;
        }

        public function processRecurrence($link)
        {
            if($this->completionDate() == 0)
                return NULL;

            $recurrenceType = $this->recurrenceType();
            $recurrenceString = $this->advancedRecurrenceString();

            if($recurrenceType == TaskRecurrenceType::None || $recurrenceType == TaskRecurrenceType::None + 100)
                return NULL;

            if($recurrenceType == TaskRecurrenceType::WithParent || $recurrenceType == TaskRecurrenceType::WithParent + 100)
                return NULL;

            $advancedType = -1;
            if($recurrenceType == TaskRecurrenceType::Advanced || $recurrenceType == TaskRecurrenceType::Advanced + 100)
            {
                $advancedType = TDOTask::advancedRecurrenceTypeForString($recurrenceString);
                if($advancedType == AdvancedRecurrenceType::Unknown)
                    return NULL;
            }
            $completedTask = clone $this;
            $completedTask->setRecurrenceType(TaskRecurrenceType::None);
            $completedTask->setAdvancedRecurrenceString(NULL);
            $completedTask->setTaskId(NULL);
            $completedTask->setTimestamp(time());
            $completedTask->addObject();


            if($this->isProject() == true)
            {
                $subTasks = TDOTask::getAllNondeletedSubtasksForTask($this->taskId(), $link);

                foreach($subTasks as $childTask)
                {
                    $childTaskOriginalDueDate = $childTask->dueDate();

                    // for tasks that repeat with the parent or that are children
                    // of a checklist, process recurrence this way
                    if ( ( ($childTask->recurrenceType() != TaskRecurrenceType::None) && ($childTask->recurrenceType() != TaskRecurrenceType::None+100) ))
                    {
                        if($childTask->completionDate() != 0)
                        {
                            $completedChildTask = clone $childTask;
                            $completedChildTask->setParentId($completedTask->taskId());
                            $completedChildTask->setRecurrenceType(TaskRecurrenceType::None);
                            $completedChildTask->setAdvancedRecurrenceString(NULL);
                            $completedChildTask->setTaskId(NULL);
                            $completedChildTask->setTimestamp(time());
                            $completedChildTask->addObject($link);
                        }

                        $childTask->setParentId($this->taskId());

                        if ( ($childTask->recurrenceType() == TaskRecurrenceType::WithParent) || ($childTask->recurrenceType() == TaskRecurrenceType::WithParent+100) )
                        {
                            // only process the due date if the task actually has a due date to start with
                            // otherwise leave it alone
                            if ($childTask->dueDate() != 0)
                            {
                                $childTask->setRecurrenceType($this->recurrenceType());
                                $childTask->setAdvancedRecurrenceString($this->advancedRecurrenceString());
                                $childTask->fixupRecurrenceData();
                            }
                            $childTask->setRecurrenceType(TaskRecurrenceType::WithParent);
                            $childTask->setAdvancedRecurrenceString(NULL);
                        }

                        $childTask->setCompletionDate(0, true);
                        $childTask->uncompleteChecklistItems($link);
                        
                        //This task got moved to the completed table, so we should move it back
                        $childTask->moveFromCompletedTable($link);
                        TDOTaskNotification::updateNotificationsForTask($childTask->taskId(), $childTaskOriginalDueDate);
                    }
                    else
                    {
                        // if it's not complete, and it doesn't recur with the parent, delete it
                        if($childTask->completionDate() == 0)
                        {
                            TDOTask::deleteObject($childTask->taskId(), $link);
                        }
                        else
                        {
                            // if it's complete and doesn't recur, set it to be joined with the completed parent
                            $childTask->setParentId($completedTask->taskId());

                            // Update the task without modifying the parent.  The parent will be modifed at the end of this
                            // method so skip modifying here because it wastes time
                            $childTask->updateObject($link);
                        }
                    }
                }
            }
            $this->uncompleteChecklistItems($link);

            $offset = $this->fixupRecurrenceData();


            TDOTaskNotification::createNotificationsForRecurringTask($this->taskId(), $completedTask->taskId(), $offset);

            return $completedTask;

        }

        public function fixupRecurrenceData()
        {

            if($this->recurrenceType() == TaskRecurrenceType::Advanced || $this->recurrenceType() == TaskRecurrenceType::Advanced + 100)
            {
                $advancedType = TDOTask::advancedRecurrenceTypeForString($this->advancedRecurrenceString());
                if($advancedType == AdvancedRecurrenceType::Unknown)
                    return 0;

                switch($advancedType)
                {
                    case AdvancedRecurrenceType::EveryXDaysWeeksMonths:
                    {
                        $offset = $this->processRecurrenceForTaskAdvancedEveryXDaysWeeksMonths();
                        break;
                    }
                    case AdvancedRecurrenceType::TheXOfEachMonth:
                    {
                        $offset = $this->processRecurrenceForTaskAdvancedTheXOfEachMonth();
                        break;
                    }
                    case AdvancedRecurrenceType::EveryMonTueEtc:
                    {
                        $offset = $this->processRecurrenceForTaskAdvancedEveryMonTueEtc();
                        break;
                    }
                }
            }
            else
            {
                $offset = $this->processRecurrenceSimple();
            }
            $this->setCompletionDate(0, true);
            if ($this->startDate() > 0) {
                $this->setStartDate($this->startDate() + $offset);
            }
            return $offset;
        }

        public function processRecurrenceSimple()
        {
            $recurrenceType = $this->recurrenceType();
            $baseDateTimeStamp = '';
            $offsetTimeStamp = '';
            $actualRecurrenceType = '';

            if($this->dueDate() == 0)
                $offsetTimeStamp = time();
            else
                $offsetTimeStamp = $this->dueDate();

            if($recurrenceType < 100)
            {
                $actualRecurrenceType = $recurrenceType;
                $baseDateTimeStamp = $offsetTimeStamp;
            }
            else
            {
                $actualRecurrenceType = $recurrenceType - 100;

                $baseDateTimeStamp = time();

                if($this->dueDate() && $this->dueDateHasTime())
                    $baseDateTimeStamp = TDOUtil::dateWithTimeFromDate($baseDateTimeStamp, $this->dueDate());
            }
            $baseDate = new DateTime();
            $baseDate->setTimeStamp($baseDateTimeStamp);
            $startDay = $baseDate->format('j');

            $monthlyRecurrence = false;
            switch($actualRecurrenceType)
            {
                case TaskRecurrenceType::Weekly:
                    $baseDate->modify("+ 1 week");
                    break;
                case TaskRecurrenceType::Yearly:
                    $baseDate->modify("+ 1 year");
                    break;
                case TaskRecurrenceType::Daily:
                    $baseDate->modify("+ 1 day");
                    break;
                case TaskRecurrenceType::Biweekly:
                    $baseDate->modify("+ 2 weeks");
                    break;
                case TaskRecurrenceType::Monthly:
                    $baseDate->modify("+ 1 month");
                    $monthlyRecurrence = true;
                    break;
                case TaskRecurrenceType::Bimonthly:
                    $baseDate->modify("+ 2 months");
                    $monthlyRecurrence = true;
                    break;
                case TaskRecurrenceType::Semiannually:
                    $baseDate->modify("+ 6 months");
                    $monthlyRecurrence = true;
                    break;
                case TaskRecurrenceType::Quarterly:
                    $baseDate->modify("+ 3 months");
                    $monthlyRecurrence = true;
                    break;
                case TaskRecurrenceType::WithParent:
                case TaskRecurrenceType::Advanced:
                case TaskRecurrenceType::None:
                    // This shouldn't happen but don't change the due date
                    error_log("An invalid option was found while calculating the recurrence data");
                    return 0;
            }

            //This will get us the same behavior as Todo. Adding one month to May 31 in php will add 31 days
            //and give you July 1, so go back to the last day of the previous month if we overshoot
            if($monthlyRecurrence)
            {
                $endDay = $baseDate->format('j');
                if($startDay != $endDay)
                {
                    $baseDate->modify('last day of last month');
                }
            }

            $newTimeStamp = $baseDate->getTimestamp();

            if(!$this->dueDateHasTime())
                $newTimeStamp = TDOUtil::normalizeDateToNoonGMT($newTimeStamp);
            elseif($this->dueDate())
                $newTimeStamp = TDOUtil::dateWithTimeFromDate($newTimeStamp, $this->dueDate());

            $this->setDueDate($newTimeStamp, true);



            return $newTimeStamp - $offsetTimeStamp;

        }


        public function processRecurrenceForTaskAdvancedEveryXDaysWeeksMonths()
        {
            $recurrenceType = $this->recurrenceType();
            $baseDateTimeStamp = '';
            $offsetTimeStamp = '';

            if($this->dueDate() == 0)
                $offsetTimeStamp = time();
            else
                $offsetTimeStamp = $this->dueDate();

            if($recurrenceType < 100)
            {
                $baseDateTimeStamp = $offsetTimeStamp;
            }
            else
            {
                $baseDateTimeStamp = time();

                if($this->dueDate() && $this->dueDateHasTime())
                    $baseDateTimeStamp = TDOUtil::dateWithTimeFromDate($baseDateTimeStamp, $this->dueDate());
            }

            $baseDate = new DateTime();
            $baseDate->setTimeStamp($baseDateTimeStamp);
            $startDay = $baseDate->format('j');

            $components =  $components = preg_split('/\s+/', $this->advancedRecurrenceString());

            if(strcasecmp($components[0], "Every") == 0)
            {
                $interval = intval($components[1]);
                $unit = $components[2];

                $baseDate->modify("+ $interval $unit");
            }
            else
            {
                error_log("Not able to match up the advanced type");
                return 0;
            }

            //This will get us the same behavior as Todo. Adding one month to May 31 in php will add 31 days
            //and give you July 1, so go back to the last day of the previous month if we overshoot
            if(stripos($unit, "month") !== false)
            {
                $endDay = $baseDate->format('j');
                if($startDay != $endDay)
                {
                    $baseDate->modify('last day of last month');
                }
            }

            $newTimeStamp = $baseDate->getTimestamp();

            if(!$this->dueDateHasTime())
                $newTimeStamp = TDOUtil::normalizeDateToNoonGMT($newTimeStamp);
            elseif($this->dueDate())
                $newTimeStamp = TDOUtil::dateWithTimeFromDate($newTimeStamp, $this->dueDate());

            $this->setDueDate($newTimeStamp, true);

            return $newTimeStamp - $offsetTimeStamp;
        }


        public function processRecurrenceForTaskAdvancedTheXOfEachMonth()
        {
            $recurrenceType = $this->recurrenceType();
            $baseDateTimeStamp = '';
            $offsetTimeStamp = '';

            if($this->dueDate() == 0)
                $offsetTimeStamp = time();
            else
                $offsetTimeStamp = $this->dueDate();

            if($recurrenceType < 100)
            {
                $baseDateTimeStamp = $offsetTimeStamp;
            }
            else
            {
                $baseDateTimeStamp = time();

                if($this->dueDate() && $this->dueDateHasTime())
                    $baseDateTimeStamp = TDOUtil::dateWithTimeFromDate($baseDateTimeStamp, $this->dueDate());

            }

            $baseDate = new DateTime();
            $baseDate->setTimeStamp($baseDateTimeStamp);

            $components =  $components = preg_split('/\s+/', $this->advancedRecurrenceString());

            if(strcasecmp($components[0], "The") == 0)
            {
                $week = $components[1];
                $weekday = $components[2];

                if(strcasecmp($week,"1st") == 0 || strcasecmp($week, "first") == 0)
                    $week = "first";
                elseif(strcasecmp($week, "2nd") == 0 || strcasecmp($week, "second") == 0)
                    $week = "second";
                elseif(strcasecmp($week, "3rd") == 0 || strcasecmp($week, "third") == 0)
                    $week = "third";
                elseif(strcasecmp($week, "4th") == 0 || strcasecmp($week, "fourth") == 0)
                    $week = "fourth";
                elseif(strcasecmp($week, "5th") == 0 || strcasecmp($week, "fifth") == 0)
                    $week = "fifth";
                elseif(strcasecmp($week, "last") == 0 || strcasecmp($week, "final") == 0)
                    $week = "last";
                else
                {
                    error_log("Not able to match up the advanced type");
                    return 0;
                }

                if(strcasecmp($weekday, "Monday") == 0 || strcasecmp($weekday, "Mon") == 0)
                    $weekday = "Monday";
                elseif(strcasecmp($weekday, "Tuesday") == 0 || strcasecmp($weekday, "Tues") == 0 || strcasecmp($weekday, "Tue") == 0)
                    $weekday = "Tuesday";
                elseif(strcasecmp($weekday, "Wednesday") == 0 || strcasecmp($weekday, "Wed") == 0)
                    $weekday = "Wednesday";
                elseif(strcasecmp($weekday, "Thursday") == 0 || strcasecmp($weekday, "Thu") == 0 || strcasecmp($weekday, "Thur") == 0 || strcasecmp($weekday, "Thurs") == 0)
                    $weekday = "Thursday";
                elseif(strcasecmp($weekday, "Friday") == 0 || strcasecmp($weekday, "Fri") == 0)
                    $weekday = "Friday";
                elseif(strcasecmp($weekday, "Saturday") == 0 || strcasecmp($weekday, "Sat") == 0)
                    $weekday = "Saturday";
                elseif(strcasecmp($weekday, "Sunday") == 0 || strcasecmp($weekday, "Sun") == 0)
                    $weekday = "Sunday";
                else
                {
                    error_log("Not able to match up the advanced type");
                    return 0;
                }

                $modifyString = "$week $weekday of this month";

                $startMonth = $baseDate->format('m');
                $baseDate->modify($modifyString);
                $newMonth = $baseDate->format('m');
                $newTimeStamp = $baseDate->getTimestamp();

                while($newTimeStamp <= $baseDateTimeStamp || $startMonth != $newMonth)
                {
                    if($newTimeStamp <= $baseDateTimeStamp)
                    {
                        $baseDate->modify("first day of next month");
                    }
                    $startMonth = $baseDate->format('m');
                    $baseDate->modify($modifyString);
                    $newMonth = $baseDate->format('m');
                    $newTimeStamp = $baseDate->getTimestamp();
                }
            }
            else
            {
                error_log("Not able to match up the advanced type");
                return 0;
            }


            $newTimeStamp = $baseDate->getTimestamp();

            if(!$this->dueDateHasTime())
                $newTimeStamp = TDOUtil::normalizeDateToNoonGMT($newTimeStamp);
            elseif($this->dueDate())
                $newTimeStamp = TDOUtil::dateWithTimeFromDate($newTimeStamp, $this->dueDate());

            $this->setDueDate($newTimeStamp, true);

            return $newTimeStamp - $offsetTimeStamp;
        }

        public function processRecurrenceForTaskAdvancedEveryMonTueEtc()
        {
            $recurrenceType = $this->recurrenceType();
            $baseDateTimeStamp = '';
            $offsetTimeStamp = '';

            if($this->dueDate() == 0)
                $offsetTimeStamp = time();
            else
                $offsetTimeStamp = $this->dueDate();

            if($recurrenceType < 100)
            {
                $baseDateTimeStamp = $offsetTimeStamp;
            }
            else
            {
                $baseDateTimeStamp = time();

                if($this->dueDate() && $this->dueDateHasTime())
                    $baseDateTimeStamp = TDOUtil::dateWithTimeFromDate($baseDateTimeStamp, $this->dueDate());
            }
            $baseDate = new DateTime();
            $baseDate->setTimeStamp($baseDateTimeStamp);

            $selectedDays = 0;
            $addWeeks = 0;
            $advancedString = $this->advancedRecurrenceString();

            // This is for RTM only, Toodledo doesn't support second, third or fourth
            if(stripos($advancedString, "Second") !== false)
                $addWeeks = 1;
            if(stripos($advancedString, "Third") !== false)
                $addWeeks = 2;
            if(stripos($advancedString, "Fourth") !== false)
                $addWeeks = 3;

            if(stripos($advancedString, "Monday") !== false || stripos($advancedString, "mon") !== false)
                $selectedDays |= MON_SELECTION;

            if(stripos($advancedString, "Tuesday") !== false || stripos($advancedString, "Tue") !== false || stripos($advancedString, "Tues") !== false)
                $selectedDays |= TUE_SELECTION;

            if(stripos($advancedString, "Wednesday") !== false || stripos($advancedString, "Wed") !== false || stripos($advancedString, "Wendsday") !== false)
                $selectedDays |= WED_SELECTION;

            if(stripos($advancedString, "Thursday") !== false || stripos($advancedString, "Thu") !== false || stripos($advancedString, "Thurs") !== false)
                $selectedDays |= THU_SELECTION;

            if(stripos($advancedString, "Friday") !== false || stripos($advancedString, "Fri") !== false || stripos($advancedString, "Fryday") !== false)
                $selectedDays |= FRI_SELECTION;

            if(stripos($advancedString, "Saturday") !== false || stripos($advancedString, "Sat") !== false)
                $selectedDays |= SAT_SELECTION;

            if(stripos($advancedString, "Sunday") !== false || stripos($advancedString, "Sun") !== false)
                $selectedDays |= SUN_SELECTION;

            if(stripos($advancedString, "Weekday") !== false)
                $selectedDays |= WEEKDAY_SELECTION;

            if(stripos($advancedString, "Weekend") !== false)
                $selectedDays |= WEEKEND_SELECTION;

            if(stripos($advancedString, "Every Day") !== false)
                $selectedDays |= (WEEKDAY_SELECTION | WEEKEND_SELECTION);

            if($selectedDays == 0)
            {
                error_log("Found invalid recurrence of type EveryMonTueEtc");
                return 0;
            }

            $dayCount = 1;

            $tmpDateTimeStamp = strtotime(date("Y-m-d", $baseDateTimeStamp) . " + 1 day");
            $tmpDayOfWeek = date("N", $tmpDateTimeStamp);

            $nextLoop = true;
            while($nextLoop == true)
            {
                switch($tmpDayOfWeek)
                {
                    case 7:
                        if($selectedDays & SUN_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 1:
                        if($selectedDays & MON_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 2:
                        if($selectedDays & TUE_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 3:
                        if($selectedDays & WED_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 4:
                        if($selectedDays & THU_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 5:
                        if($selectedDays & FRI_SELECTION)
                            $nextLoop = false;
                        else
                            $dayCount++;
                        break;
                    case 6:
                        if($selectedDays & SAT_SELECTION)
                            $nextLoop = false;
                        else
                        {
                            // if we need to add weeks, add them after Sunday!
                            if($addWeeks > 0)
                                $dayCount += ($addWeeks * 7);

                            $dayCount++;
                        }
                        break;
                }

                $tmpDateTimeStamp = strtotime(date("Y-m-d", $baseDateTimeStamp) . " + $dayCount days");
                $tmpDayOfWeek = date("N", $tmpDateTimeStamp);
            }

            $newTimeStamp = strtotime(date("Y-m-d", $baseDateTimeStamp) . " + $dayCount days");

            if(!$this->dueDateHasTime())
                $newTimeStamp = TDOUtil::normalizeDateToNoonGMT($newTimeStamp);
            elseif($this->dueDate())
                $newTimeStamp = TDOUtil::dateWithTimeFromDate($newTimeStamp, $this->dueDate());

            $this->setDueDate($newTimeStamp, true);
            return $newTimeStamp - $offsetTimeStamp;
        }


        /********* Location Alert Methods **************/

        public function parseLocationAlertType()
        {
            $locationAlertString = $this->locationAlert();
            if(empty($locationAlertString) || strlen($locationAlertString) == 0)
                return TaskLocationAlertType::None;

            $firstChar = substr($locationAlertString, 0, 1);
            if($firstChar == "<")
                return TaskLocationAlertType::Leaving;
            elseif($firstChar == ">")
                return TaskLocationAlertType::Arriving;

            return TaskLocationAlertType::None;
        }

        public function setLocationAlertType($type)
        {
            if($type == TaskLocationAlertType::None)
            {
                $this->setLocationAlert(NULL);
                return true;
            }

            if($type == TaskLocationAlertType::Leaving)
                $taskTypeChar = "<";
            elseif($type == TaskLocationAlertType::Arriving)
                $taskTypeChar = ">";
            else
                return false;

            $locationAlertString = $this->locationAlert();
            if(empty($locationAlertString) || strlen($locationAlertString) == 0)
            {
                $this->setLocationAlert($taskTypeChar."::INVALID");
                return true;
            }


            $locationAlertString = substr_replace($locationAlertString, $taskTypeChar, 0, 1);
            $this->setLocationAlert($locationAlertString);

            return true;
        }

        public function parseLocationAlertAddress()
        {
            $locationAlertString = $this->locationAlert();
            if(empty($locationAlertString) || strlen($locationAlertString) < 3)
                return NULL;

            $locationString = substr($locationAlertString, 2);
            $locationComponents = explode(":", $locationString);
            if(empty($locationComponents) || count($locationComponents) < 2)
                return NULL;

            return $locationComponents[1];
        }

        public function parseReadableLocationAlertAddress()
        {
            $addressString = $this->parseLocationAlertAddress();
            if(empty($addressString) || $addressString == "INVALID")
                return "No Address";

            return $addressString;
        }

        //When setting the location alert address from the web, we will wipe out any coordinates
        //in the locationalert string, because we don't know if they're valid any more
        public function setLocationAlertAddress($locationAlertAddress)
        {
            if(empty($locationAlertAddress))
                return false;

            $locationAlertString = $this->locationAlert();
            if(!empty($locationAlertString) && strlen($locationAlertString) > 0)
                $alertTypeChar = substr($locationAlertString, 0, 1);
            else
                $alertTypeChar = "<";

            $locationAlertString = $alertTypeChar . "::" . $locationAlertAddress;
            $this->setLocationAlert($locationAlertString);
            return true;

        }

		//
		// PRIVATE FUNCTIONS BELOW
		//


		private function setExtraInfoFromCalDAVData($calDAVData)
		{
            //error_log("TDOTask:setExtraInfoFromCalDAVData was called");
            
			if (!isset($calDAVData))
			{
				error_log("TDOTask::setExtraInfoFromCalDAVData failed because it was passed an empty CalDAV parameter.");
				return;
			}

			$parsedTask = Sabre_VObject_Reader::read($calDAVData);
			if (!$parsedTask)
			{
				error_log("TDOTask::setExtraInfoFromCalDAVData failed to parse CalDAV data.");
				return;
			}

			if (count($parsedTask->vtodo) <= 0)
			{
				// Nothing to parse
				error_log("TDOTask::setExtraInfoFromCalDAVData could not find VTODO in the CalDAV data.");
				return;
			}

			// TODO: Eventually figure out what we should do if there are more
			// than ONE tasks found.

            
            error_log($calDAVData);
            
			$vobject_task = $parsedTask->vtodo[0];

//            Log the object out to error_log
            ob_start();
            //var_dump($vobject_task);
            $contents = ob_get_contents();
            ob_end_clean();
            error_log($contents);
            
            
			if (!empty($vobject_task->summary))
			{
				$this->setName($vobject_task->summary);
			}

			if (!empty($vobject_task->note))
			{
				$this->setNote($vobject_task->note);
			}

			if (!empty($vobject_task->due))
			{
                $gmttimestamp = strtotime($vobject_task->due->value);
                
                // We need to figure out if this due date has a due time
                $fullDateTime = new DateTime();
                
                $timeZone = $fullDateTime->getTimeZone();
                $fullDateTime->setTimezone(new DateTimeZone('GMT'));
                $fullDateTime->setTimeStamp($gmttimestamp);

                $modDateTime = new DateTime();
                
                // dates coming from caldav will be set to midnight if they don't have a due time
                $modDateTime->setTimezone(new DateTimeZone('GMT'));
                $modDateTime->setTimeStamp($gmttimestamp);
                $modDateTime->setTime(00, 00, 00);
                
                $interval = $fullDateTime->diff($modDateTime);
                
                $dueDateHasTime = true;
                
                if( ($interval->s == 0) && ($interval->i == 0) && ($interval->h == 0) )
                    $dueDateHasTime = false;
                
                $this->setDueDateHasTime($dueDateHasTime);
                
                if($dueDateHasTime)
                    $dueDateValue = TDOUtil::dateFromGMT($gmttimestamp);
                else
                    $dueDateValue = TDOUtil::normalizedDateFromGMT($gmttimestamp);

                if(empty($dueDateValue))
                    $this->setDueDateHasTime(false);
                
                $this->setDueDate($dueDateValue);
//                $this->setDueDate(strtotime($vobject_task->due->value));
			}
			else
			{
                $this->setdueDate(0);
			}

			if (!empty($vobject_task->completed))
			{
				$this->setCompletionDate(strtotime($vobject_task->completed->value));
			}
			else
			{
				$this->setCompletionDate(0);
			}

			if (!empty($vobject_task->priority))
			{
				$this->setPriority($vobject_task->priority);
			}
		}


		private function newOrExistingCalDavString()
		{
			if ($this->caldavData() != NULL)
				return $this->caldavData();

			$nowDateStamp = time();
			$nowDateTimeString = date("Ymd\THis\Z", $nowDateStamp);
			$nowDateString = date("Ymd", $nowDateStamp);

			$blankTask =
			"BEGIN:VCALENDAR\r\n" .
			"VERSION:2.0\r\n" .
			"PRODID:-//Plunkboard.com//CalDAV Server//EN\r\n" .
			"CALSCALE:GREGORIAN\r\n" .
			"BEGIN:VTODO\r\n" .
			"CREATED:" . $nowDateTimeString . "\r\n" .
			"DTSTAMP:" . $nowDateTimeString . "\r\n" .
			"SEQUENCE:0\r\n";


			/*

			 taskid
			 listid

			 name (title)
			 note
			 duedate INT
			 completiondate INT (Use NULL to indicate it is not completed)
			 priority (INT)

			 timestamp (last modified by the user)
			 caldavuri VARCHAR(255)
			 caldavdata BLOB

			 */


			// UID
			if ($this->taskId() == NULL)
				$uid = TDOUtil::uuid();
			else
				$uid = $this->taskId();
			$blankTask .= "UID:" . $uid . "\r\n";

			// LAST-MODIFIED
			if ($this->timestamp() == 0)
				$lastmodified = $nowDateTimeString;
			else
				$lastmodified = date("Ymd\THis\Z", $this->timestamp());
			$blankTask .= "LAST-MODIFIED:" . $lastmodified . "\r\n";

			// name
			if ($this->name() != NULL)
				$blankTask .= "SUMMARY:" . $this->name() . "\r\n";

			// DESCRIPTION
			if ($this->note() != NULL)
				$blankTask .= "DESCRIPTION:" . $this->note() . "\r\n";

			// DUE
			if ($this->dueDate() > 0)
				$blankTask .= "DUE;VALUE=DATE:" . date("Ymd", $this->dueDate()) . "\r\n";

			// COMPLETED
			if ($this->completionDate() > 0)
				$blankTask .= "COMPLETED:" . date("Ymd\THis\Z", $this->completionDate()) . "\r\n";

			// PRIORITY
			if ($this->priority() == 0)
				$blankTask .= "PRIORITY:0\r\n";
			else
				$blankTask .= "PRIORITY:" . $this->priority() . "\r\n";

			$blankTask .=
			"END:VTODO\r\n" .
			"END:VCALENDAR";

			return $blankTask;
		}


		// This should be called anytime significant data is changed on the task
		private function incrementCalDavSequenceNumber(&$sabreObject)
		{
			$currentSequenceNumber = $sabreObject->vtodo[0]->sequence->value;
			$currentSequenceNumber++;
			$sabreObject->vtodo[0]->sequence = (string)$currentSequenceNumber;
		}


//		private function createBlankCalDAVString()
//		{
//			$nowDateStamp = time();
//			$nowDateString = date("Ymd\THis\Z", $nowDateStamp);
//
//			$uid = TDOUtil::uuid();
//
//			$blankTask =
//				"BEGIN:VCALENDAR\r\n" .
//				"VERSION:2.0\r\n" .
//				"PRODID:-//Plunkboard.com//CalDAV Server//EN\r\n" .
//				"CALSCALE:GREGORIAN\r\n" .
//				"BEGIN:VTODO\r\n" .
//				"CREATED:" . $nowDateString . "\r\n" .
//				"UID:" . $uid . "\r\n" .
//				"DTSTAMP:" . $nowDateString . "\r\n" .
//				"SEQUENCE:0\r\n" .
//				"END:VTODO\r\n" .
//				"END:VCALENDAR";
//
//			return $blankTask;
//		}

        
        private function explodeCalDavData(&$calDavData) 
        {
            // Normalizing newlines
            $data = str_replace(array("\r","\n\n"), array("\n","\n"), $calDavData);
            
            $lines = explode("\n", $data);
            
            // Unfolding lines
            $lines2 = array();
            foreach($lines as $line)
            {
                // Skipping empty lines
                if (!$line) continue;
                
                if ($line[0] == " " || $line[0] == "\t")
                {
                    $lines2[count($lines2)-1].=substr($line,1);
                }
                else
                {
                    $lines2[] = $line;
                }
            }
            
            unset($lines);
            
            reset($lines2);

            return $lines2;
        }        
        
        
        
        //Returns the uncompleted child count
        public function getSubtaskHash($taskid, $link)
        {
            if(!$link)
                return false;

            $hashString = "";
            
			// first read the subtasks outside of the sql transaction
            $subTasks = TDOTask::getAllNondeletedSubtasksForTask($taskid, $link);
            foreach($subTasks as $task)
            {
                $subTaskHash = TDOTask::hashForTask($task, $link);
                if(!empty($subTaskHash))
                    $hashString .= $subTaskHash;
            }
            
            if(strlen($hashString) > 0)
                return hash('md5', $hashString);
            else
                return $hashString;
        }
        
        
        public function getTagHash($taskid, $link)
        {
            $tags = TDOTag::getTagsForTask($taskid, $link);
            
            $tagNameArray = array();
            
            foreach($tags as $tag)
            {
                $tagNameArray[] = $tag->getName();
            }

            asort($tagNameArray);

            $hashString = "";
            foreach($tagNameArray as $tagName)
            {
                $hashString .= ",".$tagName;
            }

            if(strlen($hashString) > 0)
                return hash('md5', $hashString);
            else
                return $hashString;
        }
        
        
        
        //Returns the uncompleted child count
        public function hashForTask($task, $link)
        {
            $hashString = "";
            
            $value = $task->name();
            if(!empty($value))
                $hashString .= hash('md5', $value);
            
            $value = $task->note();
            if(!empty($value))
                $hashString .= hash('md5', $value);
            
            if($task->compStartDate() == 0)
                $hashString .=hash('md5', strval($task->compStartDate()));
            	
			if($task->compDueDate() == 0)
                $hashString .= hash('md5', strval($task->compDueDate()));

			if ($task->compPriority() != 0)
                $hashString .= hash('md5', strval($task->compPriority()));

            $hashString .= hash('md5', strval($task->deleted()));
            $hashString .= hash('md5', strval($task->compStarredVal()));
            
			if($task->taskType() != 0)
                $hashString .= hash('md5', strval($task->taskType()));

            $value = $task->assignedUserId();
            if(!empty($value))
                $hashString .= hash('md5', $value);

            if ($task->recurrenceType() != 0)
                $hashString .= hash('md5', strval($task->recurrenceType()));
            
            $value = $task->locationAlert();
            if(!empty($value))
                $hashString .= hash('md5', $value);

            if($task->isChecklist())
            {
                $hashString .= TDOTaskito::getTaskitosHash($task->taskId(), $link);
            }

            // if there are comments, generate a guid and hash it, there 
            // is no way this should match... make sure to not get deleted comments!
            if(TDOComment::getCommentCountForItem($task->taskId(), false, $link) > 0)
            {
                $hashString .= hash('md5', TDOUtil::uuid());
            }
            
            $tagHash = TDOTask::getTagHash($task->taskId(), $link);
            if(!empty($tagHash))
                $hashString .= $tagHash;
            
            $trueHashString = hash('md5', $hashString);

            return $trueHashString;
        }
        
        public function updateValuesFromTaskName($userId)
        {
            if($this->taskId() == NULL)
                return false;
        
            if($this->name() == NULL)
                return false;
            
            $userSettings = TDOUserSettings::getUserSettingsForUserid($userId);
            if(empty($userSettings))
                return false;
            
            $changesMade = array();
            
            if($userSettings->skipTaskPriorityParsing() == false && $this->updatePriorityFromTaskName())
                $changesMade[] = 'priority';
            
            if($userSettings->skipTaskContextParsing() == false && $this->updateContextFromTaskName($userId))
                $changesMade[] = 'context';
           
            if($this->parentId() == NULL && $userSettings->skipTaskListParsing() == false && $this->updateListFromTaskName($userId))
                $changesMade[] = 'list';
            
            if($userSettings->skipTaskTagParsing() == false && $this->updateTagsFromTaskName($userId))
                $changesMade[] = 'tag';
            
            if($userSettings->skipTaskDateParsing() == false && $this->updateDateFromTaskName())
                $changesMade[] = 'duedate';
            
            if($userSettings->skipTaskStartDateParsing() == false && $this->updateStartDateFromTaskName())
                $changesMade[] = 'startdate';
            
            if($userSettings->skipTaskChecklistParsing() == false &&$this->updateChecklistFromTaskName())
                $changesMade[] = 'checklist';
           
            //Only try to parse project items if this is not a checklist or a subtask
            if($this->parentId() == NULL && $this->isChecklist() == false)
            {
                if($userSettings->skipTaskProjectParsing() == false &&$this->updateProjectFromTaskName())
                    $changesMade[] = 'project';
            }
            
            if(!empty($changesMade))
            {
                if($this->updateObject() == false)
                {
                    error_log("updateValuesFromTaskName failed to update task object");
                    return false;
                }
            }
            
            return $changesMade;
        }
        
        //Returns true if a priority was parsed from the name, false
        //otherwise
        private function updatePriorityFromTaskName()
        {
            $taskName = $this->name();
            $matches = array();
            
            //This regular expression will match a token that starts with
            //a "!" followed by more exclamation points or letters
            preg_match_all('/(^|\s)!([a-zA-Z]|!)*(?=($|\s))/', $taskName, $matches, PREG_OFFSET_CAPTURE);
            if(!empty($matches))
            {
                $priorityExpressions = array(0 => array("!N", "!None"), 9 => array("!Low", "!L", "!"), 5 => array("!Medium", "!Med", "!M", "!!"), 1 => array("!High", "!H", "!Hi", "!!!"));
                foreach($matches[0] as $match)
                {
                    $expression = $match[0];
                    $matched = false;
                    foreach($priorityExpressions as $key=>$possibilities)
                    {
                        foreach($possibilities as $value)
                        {
                            if(strcasecmp(trim($expression), $value) == 0)
                            {
                                //We found a valid priority! Set the task's priority
                                $this->setPriority($key);
                                
                                //Remove the priority expression from the task name, unless that will make the name empty
                                $offset = $match[1];
                                $newTaskName = trim(substr_replace($taskName, "", $offset, strlen($expression)));
                                if(!empty($newTaskName))
                                {
                                    $this->setName($newTaskName);
                                }
                                return true;
                            }
                        }
                    }
                }
            }
            
            return false;
        }
        
        private function updateContextFromTaskName($userId)
        {
            $taskName = $this->name();
            //if string starts with task name and string has contexts line
            if (preg_match('/^[^\@].+/', $taskName) && preg_match_all('/\s\@(\w+)/', $taskName, $contexts)) {
                if (sizeof($contexts) == 2 && sizeof($contexts[1])) {
                    $contextName = $contexts[1][0];
                    $contextExist = FALSE;
                    $userContexts = TDOContext::getContextsForUser($userId);
                    if ($userContexts && sizeof($userContexts)) {
                        foreach ($userContexts as $context) {
                            if ($context->getName() === $contextName) {
                                $contextExist = TRUE;
                                if (TDOContext::assignTaskToContext($this->taskId(), $context->getContextid(), $userId) == false) {
                                    error_log("updateValuesFromTaskName failed to update task context");
                                    return false;
                                } else {
                                    //Found a matching context! Set the task's context.
                                    $this->setContextId($context->getContextid());
                                }
                                break;
                            }
                        }
                    }

                    if ($contextExist === FALSE) {
                        $TDOContext = new TDOContext();
                        $TDOContext->setName($contextName);
                        $TDOContext->setUserid($userId);
                        if ($TDOContext->addContext()) {
                            if (TDOContext::assignTaskToContext($this->taskId(), $TDOContext->getContextid(), $userId) == false) {
                                error_log("updateValuesFromTaskName failed to update task context");
                                return false;
                            } else {
                                //Found a matching context! Set the task's context.
                                $this->setContextId($TDOContext->getContextid());
                            }
                        }
                    }
                    $taskName = preg_replace('/(@' . $contextName . '\s*)/', '', $taskName);
                    $this->setName(trim($taskName));
                }
            }
            return true;
        }
        
        private function updateListFromTaskName($userId)
        {
            $taskName = $this->name();
            
            $lists = NULL;
            $position = 0;
            
            
            //This regex matches any type of dash (em, en, or figure)
            $matches = array();
            preg_match_all('/(–|-|—)/u', $taskName, $matches, PREG_OFFSET_CAPTURE);
            
            if(!empty($matches))
            {
                foreach($matches[0] as $match)
                {
                    $position = $match[1];
                    $charLength = strlen($match[0]);
                    
                    //Make sure the '-' is at the beginning of the string or preceded by whitespace
                    if($position == 0 || TDOUtil::stringIsWhitespace(substr($taskName, $position - 1, 1)))
                    {
                        //For Siri integration - Siri will let you add a caret by saying "caret", but it
                        //will insert a space before the next character. So, if the next character is a space,
                        //skip over it
                        $skippedWhitespace = false;
                        if(strlen($taskName) > $position + $charLength && TDOUtil::stringIsWhitespace(substr($taskName, $position + $charLength, 1)))
                        {
                            $position += $charLength;
                            $charLength = 1;
                            $skippedWhitespace = true;
                        }                
                    
                        //See if the string following the '-' matches any of the user's existing lists
                        if(empty($lists))
                            $lists = TDOList::getListsForUser($userId);
                        if(!empty($lists))
                        {
                            foreach($lists as $list)
                            {
                                $listName = trim($list->name());
                                if(!empty($listName))
                                {
                                    //First check if the string following the '-' is even long enough to match the list name
                                    $endPosition = $position + strlen($listName) + ($charLength - 1);
                                    if(strlen($taskName) > $endPosition)
                                    {
                                        //Pull out the string following the '-'
                                        $compString = substr($taskName, $position + $charLength, strlen($listName));
                                        if(!empty($compString))
                                        {
                                            //Compare the string following the '-' to the context name
                                            if(strcasecmp($compString, $listName) == 0)
                                            {
                                                //Make sure the string is either the end of the task name or followed by whitespace
                                                if(strlen($taskName) == $endPosition + 1 || TDOUtil::stringIsWhitespace(substr($taskName, $endPosition + 1, 1)))
                                                {
                                                    //Found a matching list! Set the task's list.
                                                    $this->setListId($list->listId());
                                                    
                                                    //Remove the list expression from the task name, unless that will make the name empty
                                                    $deleteLength = strlen($listName) + strlen($match[0]);
                                                    
                                                    if($skippedWhitespace)
                                                    {
                                                        $position -= strlen($match[0]);
                                                        $deleteLength++;
                                                    }
                                                    
                                                    //If there is a space after the list name, trim the space as well so we don't get 2 spaces btw words
                                                    if(strlen($taskName) > $endPosition + 1)
                                                        $deleteLength++;
                                                    
                                                    $newTaskName = trim(substr_replace($taskName, "", $position, $deleteLength));
                                                    if(!empty($newTaskName))
                                                    {
                                                        $this->setName($newTaskName);
                                                    }
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            return false;
        }
        
        private function updateTagsFromTaskName($userId)
        {
            $taskName = $this->name();
        
            /**** Find the tags in the task name (there can be multiple) ****/
            $tags = NULL;
            $position = 0;
            $tagParsed = false;
            while($position < strlen($taskName) && ($position = strpos($taskName, "#", $position)) !== false)
            {
                //Make sure the # sign is at the beginning of the string or preceded by whitespace
                if($position == 0 || TDOUtil::stringIsWhitespace(substr($taskName, $position - 1, 1)))
                {
                    $matchedExistingTag = false;
                    
                    //See if the string following the # matches any of the user's existing tags
                    if(empty($tags))
                        $tags = TDOTag::getTagsForUser($userId);
                    if(!empty($tags))
                    {
                        foreach($tags as $tag)
                        {
                            $tagName = trim($tag->getName());
                            if(!empty($tagName))
                            {
                                //First check if the string following the # is even long enough to match the tag name
                                $endPosition = $position + strlen($tagName);
                                if(strlen($taskName) > $endPosition)
                                {
                                    //Pull out the string following the #
                                    $compString = substr($taskName, $position + 1, strlen($tagName));
                                    if(!empty($compString))
                                    {
                                        //Compare the string following the # to the context name
                                        if(strcasecmp($compString, $tagName) == 0)
                                        {
                                            //Make sure the string is either the end of the task name or followed by whitespace
                                            if(strlen($taskName) == $endPosition + 1 || TDOUtil::stringIsWhitespace(substr($taskName, $endPosition + 1, 1)))
                                            {
                                                $matchedExistingTag = true;
                                                if(TDOTag::addTagToTask($tag->getTagid(), $this->taskId()) == false)
                                                {
                                                    error_log("updateValuesFromTaskName failed to update task tag");
                                                }
                                                else
                                                {
                                                    $tagParsed = true;
                                                    
                                                    //Remove the tag expression from the task name, unless that will make the name empty
                                                    
                                                     //If there is a space after the context name, trim the space as well so we don't get 2 spaces btw words
                                                    $whitespaceTrimoffset = 0;
                                                    if(strlen($taskName) > $endPosition + 1)
                                                        $whitespaceTrimoffset = 1;
                                                    
                                                    $newTaskName = trim(substr_replace($taskName, "", $position, strlen($tagName) + 1 + $whitespaceTrimoffset));
                                                    if(!empty($newTaskName))
                                                    {
                                                        $position--;
                                                        $taskName = $newTaskName;
                                                        $this->setName($taskName);
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    //If the tag doesn't match their existing tags, add a new tag with the first word
                    //after the # symbol, unless that word begins with a number
                    if(!$matchedExistingTag && strlen($taskName) > $position + 1)
                    {
                        $matches = array();
                        preg_match('/^[^\s0-9][^\s]*(?=($|\s))/', substr($taskName, $position + 1), $matches);
                        
                        if(!empty($matches))
                        {
                            $match = $matches[0];
                            if(strlen($match) > 0)
                            {
                                if(TDOTag::addTagNameToTask($match, $this->taskId()) == false)
                                {
                                    error_log("updateValuesFromTaskName failed to update task tag");
                                }
                                else
                                {
                                    $tagParsed = true;
                                    
                                    //Remove the tag expression from the task name, unless that will make the name empty
                                    
                                    //If there is a space after the context name, trim the space as well so we don't get 2 spaces btw words
                                    $whitespaceTrimoffset = 0;
                                    if(strlen($taskName) > $position + strlen($match) + 1)
                                        $whitespaceTrimoffset = 1;
                                    
                                    $newTaskName = trim(substr_replace($taskName, "", $position, strlen($match) + 1 + $whitespaceTrimoffset));
                                    if(!empty($newTaskName))
                                    {
                                        $position--;
                                        $taskName = $newTaskName;
                                        $this->setName($taskName);
                                    }
                                }
                            }
                        }
                    }
                }
                $position++;
            }
            return $tagParsed;
        }
        
        private function updateDateFromTaskName()
        {
            $taskName = $this->name();
            $matches = array();
            
            //This regular expression will match a parenthesized expression
            preg_match_all('/(\s)*\([^\)]+\)(\s)*/', $taskName, $matches, PREG_OFFSET_CAPTURE);
            if(!empty($matches))
            {
                foreach($matches[0] as $match)
                {
                    $matched = false;
                    
                    $expression = trim($match[0]);
                    //Remove the parentheses from the expression
                    $expression = trim(substr($expression, 1, strlen($expression) - 2));
                    
                    if(!empty($expression) && strlen($expression) > 1)
                    {
                        //First see if the user is trying to specify no due date
                        if(strcasecmp($expression, "no date") == 0 || strcasecmp($expression, "no due date") == 0 || strcasecmp($expression, "none") == 0)
                        {
                            $this->setDueDateHasTime(false);
                            $this->setDueDate(0);
                            $matched = true;
                        }
                        else
                        {
                        
                            //This regular expression removes the words 'on', 'at', 'in', and @, because they are not
                            //handled by strtotime
                            $expression = preg_replace('/(^|\s)(on|at|in|@)(?=($|\s))/i', " ", $expression);
                            
                            $date = strtotime($expression);
                            if($date !== false)
                            {
                                //Found a date! Set the date on the task.
                                
                                //Try to determine if a time is specified in the string. If so,
                                //set duedatehastime to true
                                if(    stripos($expression, "pm") !== false || stripos($expression,"am") !== false || stripos($expression,"a.m.") !== false || stripos($expression,"p.m.") !== false
                                    || stripos($expression,"o'clock") !== false || stripos($expression,":") !== false || stripos($expression,"oclock") !== false )
                                {
                                    $this->setDueDateHasTime(true);
                                    $this->setDueDate($date);
                                }
                                else
                                {
                                    $this->setDueDateHasTime(false);
                                    $this->setDueDate(TDOUtil::normalizeDateToNoonGMT($date));
                                }
                                $matched = true;
                            }
                        }
                        
                        if($matched)
                        {
                            //Remove the parenthesized expression from the task name, unless that will make the name empty
                            $newTaskName = trim(substr_replace($taskName, " ", $match[1], strlen($match[0])));
                            if(!empty($newTaskName))
                            {
                                $this->setName($newTaskName);
                            }
                            
                            return true;
                        }
                    }
                }
            }
            
            return false;
        }
        
        private function updateStartDateFromTaskName()
        {
            $taskName = $this->name();
            $matches = array();
            
            //This regular expression will match a bracketed expression: []
            preg_match_all('/(\s)*\[[^\]]+\](\s)*/', $taskName, $matches, PREG_OFFSET_CAPTURE);
            if(!empty($matches))
            {
                foreach($matches[0] as $match)
                {
                    $matched = false;
                    
                    $expression = trim($match[0]);
                    //Remove the parentheses from the expression
                    $expression = trim(substr($expression, 1, strlen($expression) - 2));
                    
                    if(!empty($expression) && strlen($expression) > 1)
                    {
                        //First see if the user is trying to specify no start date
                        if(strcasecmp($expression, "no date") == 0 || strcasecmp($expression, "none") == 0)
                        {
                            $this->setStartDate(0);
                            $matched = true;
                        }
                        else
                        {
                            $date = strtotime($expression);
                            if($date !== false)
                            {
                                //Found a date! Set the start date on the task.
                                $this->setStartDate(TDOUtil::normalizeDateToNoonGMT($date));
                                $matched = true;
                            }
                        }
                        
                        if($matched)
                        {
                            //Remove the bracketed expression from the task name, unless that will make the name empty
                            $newTaskName = trim(substr_replace($taskName, " ", $match[1], strlen($match[0])));
                            if(!empty($newTaskName))
                            {
                                $this->setName($newTaskName);
                            }
                            
                            return true;
                        }
                    }
                }
            }
            
            return false;
        }
        
        private function updateChecklistFromTaskName()
        {
            //A user can turn a task into a checklist by adding a colon
            //followed by a comma-separated list of items
            
            $taskName = trim($this->name());
            
            $colonPosition = strpos($taskName, ":");
            if($colonPosition !== false && $colonPosition != 0 && $colonPosition + 1 < strlen($taskName))
            {
                $potentialList = substr($taskName, $colonPosition + 1);
                
                $checklistItems = explode(",", $potentialList);
                
                if(!empty($checklistItems) && count($checklistItems) > 1)
                {
                    //We found checklist items, so convert this to a checklist and add the new items
                    if($this->updateTaskType(TaskType::Checklist))
                    {
                        $sortOrder = 0;
                        foreach($checklistItems as $checklistItem)
                        {
                            $checklistItem = trim($checklistItem);
                            if(!empty($checklistItem))
                            {
                                $taskito = new TDOTaskito();
                                $taskito->setName(TDOUtil::mb_ucfirst($checklistItem));
                                $taskito->setParentId($this->taskId());
                                $taskito->setSortOrder($sortOrder);
                                
                                if($taskito->addObject() == false)
                                    error_log("updateChecklistFromTaskName failed to add taskito to database: $checklistItem");
                                else
                                    $sortOrder++;
                            }
                        }
                        
                        //Remove the checklist substring from the task name
                        $taskName = trim(substr_replace($taskName, "", $colonPosition, strlen($potentialList) + 1));
                        $this->setName($taskName);
                        return true;
                        
                    }
                }
            }
            
            return false;
        }
        
        private function updateProjectFromTaskName()
        {
            //A user can turn a task into a project by adding a semicolon
            //followed by a comma-separated list of items
            
            $taskName = trim($this->name());
            
            $colonPosition = strpos($taskName, ";");
            if($colonPosition !== false && $colonPosition != 0 && $colonPosition + 1 < strlen($taskName))
            {
                $potentialList = substr($taskName, $colonPosition + 1);
                
                $projectItems = explode(",", $potentialList);
                
                if(!empty($projectItems) && count($projectItems) > 1)
                {
                    //We found checklist items, so convert this to a checklist and add the new items
                    if($this->updateTaskType(TaskType::Project))
                    {
                        $sortOrder = 0;
                        foreach($projectItems as $projectItem)
                        {
                            $projectItem = trim($projectItem);
                            if(!empty($projectItem))
                            {
                                $task = new TDOTask();
                                $task->setListId($this->listId());
                                $task->setName(TDOUtil::mb_ucfirst($projectItem));
                                $task->setParentId($this->taskId());
                                $task->setSortOrder($sortOrder);
                                
                                if($task->addObject() == false)
                                    error_log("updateProjectFromTaskName failed to add task to database: $projectItem");
                                else
                                    $sortOrder++;
                            }
                        }
                        
                        //If we ever implement something where the tasks we're adding here can be assigned due dates, priorities,
                        //etc., we will need the following call. For now we can omit it. 
//                       TDOTask::fixupChildPropertiesForTask($this, false);
                        
                        //Remove the checklist substring from the task name
                        $taskName = trim(substr_replace($taskName, "", $colonPosition, strlen($potentialList) + 1));
                        $this->setName($taskName);
                        return true;
                        
                    }
                }
            }
            
            return false;
        }
        
        private function updateTaskParsingCalDavData($calDavData)
        {
//        BEGIN:VCALENDAR\r\n
//        CALSCALE:GREGORIAN\r\n
//        PRODID:-//Apple Inc.//iOS 6.0//EN\r\n
//        VERSION:2.0\r\n
//        BEGIN:VTIMEZONE\r\n
//        TZID:America/Denver\r\n
//        BEGIN:DAYLIGHT\r\n
//        DTSTART:20070311T020000\r\n
//        RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU\r\n
//        TZNAME:MDT\r\n
//        TZOFFSETFROM:-0700\r\n
//        TZOFFSETTO:-0600\r\n
//        END:DAYLIGHT\r\n
//        BEGIN:STANDARD\r\n
//        DTSTART:20071104T020000\r\n
//        RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU\r\n
//        TZNAME:MST\r\n
//        TZOFFSETFROM:-0600\r\n
//        TZOFFSETTO:-0700\r\n
//        END:STANDARD\r\n
//        END:VTIMEZONE\r\n
//        BEGIN:VTODO\r\n
//        CREATED:20120815T185658Z\r\n
//        DTSTAMP:20120815T185704Z\r\n
//            DTSTART;TZID=America/Denver:20120815T131158\r\n
//            DUE;TZID=America/Denver:20120815T131158\r\n
//            LAST-MODIFIED:20120815T185658Z\r\n
//        SEQUENCE:0\r\n
//        STATUS:NEEDS-ACTION\r\n
//        SUMMARY:Go get some lunch\r\n
//        UID:2FA600E6-6398-43F1-BCAA-8F9DE4F28B72\r\n
//            X-APPLE-SORT-ORDER:366749818\r\n
//        BEGIN:VALARM\r\n
//        ACTION:DISPLAY\r\n
//        DESCRIPTION:Reminder\r\n
//            TRIGGER;VALUE=DATE-TIME:20120815T191158Z\r\n
//        UID:CD6B8F8E-E6AA-432A-B739-AD504653FA19\r\n
//            X-WR-ALARMUID:CD6B8F8E-E6AA-432A-B739-AD504653FA19\r\n
//        END:VALARM\r\n
//        END:VTODO\r\n
//        END:VCALENDAR\r\n        
        
            
//          BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Apple Inc.//Mac OS X 10.8//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VTODO\r\nSTATUS:COMPLETED\r\nCREATED:20120815T211825Z\r\nUID:B8D2C1C7-E2C9-4307-A78E-132EB03F2F73\r\nSUMMARY:trying this again\r\nCOMPLETED:20120815T211829Z\r\nDTSTAMP:20120815T211825Z\r\nPERCENT-COMPLETE:100\r\nSEQUENCE:0\r\nEND:VTODO\r\nEND:VCALENDAR\r\n            
            
            
            if(empty($calDavData))
            {
                return false;
            }
            
            //error_log($calDavData);
            
            $lines = $this->explodeCalDavData($calDavData);
            if(empty($lines))
                return false;

            $hasProximityAlert = false;
            $proximityIsDepart = false;
            $isTaskValue = false;
            $isValarm = false;
            $hasdueDate = false;

            foreach($lines as $line)
            {
                //error_log($line);
                
                if(stripos($line, "BEGIN:VTODO") === 0)
                    $isTaskValue = true;
                
                if(stripos($line, "BEGIN:VALARM") === 0)
                {
                    $isTaskValue = false;
                    $isValarm = true;
                }
                
                // if the task has a UID then set it
                if( ($isTaskValue == true) && (stripos($line, "UID:") === 0) )
                {
                    $uidValues = explode(":", $line);
                    
                    $uid = end($uidValues);
                    
                    if( (!empty($uid)) && (strlen($uid) == 36) )
                    {
                        $this->setTaskId($uid);

                        // it's possible there are incorrect notifications for this task, remove them
                        // we have to do it here so the new notifications can be created later
                        TDOTaskNotification::deleteAllTaskNotificationsForTask($uid);            
                    }
                    
                }
                
                if( ($isTaskValue == true) && (stripos($line, "SUMMARY:") === 0) )
                {
                    $colPos = stripos($line, ":");
                    
                    $name = substr($line, $colPos+1);
                    
                    $name = stripcslashes($name);

                    if(!empty($name))
                    {
                        $this->setName($name);
                    }
                }
                
                if( ($isTaskValue == true)  && (stripos($line, "DESCRIPTION:") === 0) )
                {
                    $noteValues = explode(":", $line);
                    
                    $note = end($noteValues);
                    
                    if(!empty($note))
                    {
                        $this->setNote($note);
                    }
                }
                
                if( ($isTaskValue == true) && (stripos($line, "DUE;") === 0) )
                {
                    $dueValues = explode(":", $line);
                    
                    $timeZone = reset($dueValues);
                    $timeZone = substr($timeZone, 9);
                    $dueString = end($dueValues);
                    
                    if(!empty($dueString))
                    {
                        // CRG - Make sure to set the timezone before parsing out the date
                        // this is going to be wrong without a valid time zone
                        if(!empty($timeZone))
                            date_default_timezone_set($timeZone);

                        $gmttimestamp = strtotime($dueString);

                        $dueDateHasTime = true;
                        
                        $this->setDueDateHasTime(true);
                        
                        $dueDateValue = TDOUtil::dateFromGMT($gmttimestamp, $timeZone);
                        
                        $this->setDueDate($dueDateValue);
                        $hasdueDate = true;
                    }
                }
                

                if( ($isTaskValue == true) && (stripos($line, "COMPLETED:") === 0) )
                {
                    $completedValues = explode(":", $line);
                    
                    $timeZone = reset($completedValues);
                    $timeZone = substr($timeZone, 9);
                    $completionString = end($completedValues);
                    
                    if(!empty($completionString))
                    {
                        if(!empty($timeZone))
                            date_default_timezone_set($timeZone);

                        $this->setCompletionDate(strtotime($completionString));
                    }
                    else
                    {
                        $this->setCompletionDate(0);
                    }
                }

                if( ($isTaskValue == false)  && (stripos($line, "X-APPLE-PROXIMITY:") === 0) )
                {
                    //error_log("Setting proximity alert to yes");
                    $hasProximityAlert = true;
                    
                    $proxTypeValues = explode(":", $line);
                    
                    $type = end($proxTypeValues);
                    
                    if($type == "DEPART")
                    {
                        $proximityIsDepart = true;
                    }
                }
                
                if( ($isTaskValue == false) && ($isValarm == true) && ($hasProximityAlert == true) && (stripos($line, "X-APPLE-STRUCTURED-LOCATION;") === 0) )
                {
                    // X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-APPLE-RADIUS=0;X-TITLE=1036 W Center St\\nOrem Utah 84058\\nUnited States:geo:40.297032,-111.720998
                    $locationValues = explode(";", $line);
                    
                    foreach($locationValues as $locValue)
                    {
                        if(stripos($locValue, "X-TITLE=") === 0)
                        {
                            $locValue = substr($locValue, 8);
                            $geoPos = stripos($locValue, ":geo:");
                            
                            if($geoPos)
                                $realLocation = substr($locValue, 0, strlen($locValue) - (strlen($locValue) - $geoPos));
                            else
                                $realLocation = $locValue;
                            
                            $realLocation = str_replace("\\n", " ", $realLocation);

                            if($proximityIsDepart == true)
                            {
                                $this->setLocationAlert("<::" . $realLocation);
                            }
                            else
                            {
                                $this->setLocationAlert(">::" . $realLocation);
                            }
                        }
                    }
                }
                

                if( ($hasdueDate == true) && ($isValarm == true) && (stripos($line, "TRIGGER;") === 0) )
                {
                    $locationValues = explode(":", $line);
                    
                    $type = reset($locationValues);
                    
                    if($type == "TRIGGER;VALUE=DATE-TIME")
                    {
                        $triggerDateString = end($locationValues);
                        $taskid = $this->taskId();
                        
                        if(!empty($triggerDateString) && !empty($taskid) )
                        {
                            $newNotification = new TDOTaskNotification();
                            $newNotification->setTaskId($taskid);
                            
                            $newNotification->setSoundName("bells");
                            $newNotification->setTriggerOffset(0);
                            $newNotification->setTriggerDate(strtotime($triggerDateString));
                       		$newNotification->addTaskNotification();
                            
                            //Bug 7171 - Since the Siri task has not been added to the DB when updateTaskParsingCalDavData is called, addTaskNotification will not
                            //update the notification timestamp because it doesn't know which list to update. Update the timestamp here instead.
                            if($this->listId() != NULL)
                            {
                                TDOList::updateNotificationTimestampForList($this->listId(), time());
                            }
                        }
                    }
                }
                
                
//                UID:ACDF7609-2878-4984-8410-3D392090C861
//                TRIGGER;VALUE=DATE-TIME:20120815T220000Z
//                [Wed Aug 15 15:39:19 2012] [error] [client ::1] DESCRIPTION:Event reminder
//                [Wed Aug 15 15:39:19 2012] [error] [client ::1] ACTION:DISPLAY
                
            }
        }
	}

