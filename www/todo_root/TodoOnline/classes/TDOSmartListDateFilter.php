<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListDateFilter extends TDOSmartListFilter
{
  protected $_type;
  protected $_relation;

  protected $_date;

  protected $_intervalRange;
  protected $_dateRange;
  protected $_period;
  protected $_periodValue;

  protected $_primaryKey;

  function __construct($dictionary, $primaryKey)
  {
    parent::__construct($dictionary);
    $this->_filterName = "DateFilter";

    if (!empty($primaryKey)) {
      $this->_primaryKey = $primaryKey;
    }

    $this->_type = SMART_LIST_DATE_TYPE_VALUE_NONE;

    if (!empty($dictionary)) {
      $innerD = $dictionary[$this->_primaryKey];
      if (!empty($innerD)) {
        $this->_type = $innerD[SMART_LIST_DATE_TYPE_KEY];

        if ($this->_type != SMART_LIST_DATE_TYPE_VALUE_ANY && $this->_type != SMART_LIST_DATE_TYPE_VALUE_NONE) {
          $this->_relation = $innerD[SMART_LIST_DATE_RELATION_KEY];
          if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_EXACT) {
            $dateString = $innerD[SMART_LIST_DATE_KEY];
            if (!empty($dateString)) {
              // This is an exact date
              $this->_date = date('U', strtotime($dateString));
            } else {
              // This is a date range
              $dateRangeD = $innerD[SMART_LIST_DATE_RANGE_KEY];
              if (!empty($dateRangeD)) {
                $startDateString = $dateRangeD[SMART_LIST_DATE_RANGE_START_KEY];
                $endDateString = $dateRangeD[SMART_LIST_DATE_RANGE_END_KEY];

                if (!empty($startDateString) && !empty($endDateString)) {
                  $this->_dateRange = array(
                    SMART_LIST_DATE_RANGE_START_KEY => date('U', strtotime($startDateString)),
                    SMART_LIST_DATE_RANGE_END_KEY => date('U', strtotime($endDateString))
                  );
                }
              }
            }
          } else if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_RELATIVE) {
            $this->_period = $innerD[SMART_LIST_DATE_PERIOD_KEY];
            if (!empty($this->_period)) {
              // Exact period
              $this->_periodValue = $innerD[SMART_LIST_DATE_PERIOD_VALUE_KEY];
            } else {
              // Period Interval
              $intervalRangeD = $innerD[SMART_LIST_DATE_INTERVAL_RANGE_START_KEY];
              $intervalRangeE = $innerD[SMART_LIST_DATE_INTERVAL_RANGE_END_KEY];

              if (!empty($intervalRangeD) && !empty($intervalRangeE)) {
                $periodStart = $intervalRangeD[SMART_LIST_DATE_INTERVAL_PERIOD_KEY];
                $periodEnd = $intervalRangeE[SMART_LIST_DATE_INTERVAL_PERIOD_KEY];

                $start = $intervalRangeD[SMART_LIST_DATE_RANGE_START_KEY];
                $end = $intervalRangeD[SMART_LIST_DATE_RANGE_END_KEY];

                if (!empty($start) && !empty($end)) {
                  $intervalRange = array(
                    SMART_LIST_DATE_INTERVAL_RANGE_START_KEY => $periodStart,
                    SMART_LIST_DATE_INTERVAL_RANGE_END_KEY => $periodEnd,
                    SMART_LIST_DATE_RANGE_START_KEY => $start,
                    SMART_LIST_DATE_RANGE_END_KEY => $end
                  );
                  $this->_intervalRange = $intervalRange;
                }
              }
            }
          }
        }
      }
    }
  }

  public function dictionaryRepresentation()
  {
    if (empty($this->_primaryKey)) {
      return NULL;
    }

    if (empty($this->_type)) {
      return NULL;
    }

    $innerD = array();
    $innerD[SMART_LIST_DATE_TYPE_KEY] = $this->_type;

    if ($this->_type != SMART_LIST_DATE_TYPE_VALUE_NONE && $this->_type != SMART_LIST_DATE_TYPE_VALUE_ANY) {
      if (empty($this->_relation)) {
        return NULL; // Invalid filter
      }

      $innerD[SMART_LIST_DATE_RELATION_KEY] = $this->_relation;

      if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_EXACT) {
        if (!empty($this->_date)) {
          $innerD[SMART_LIST_DATE_KEY] = date(DateTime::ISO8601, $this->_date);
        } else if (!empty($this->_dateRange)) {
          $innerD[SMART_LIST_DATE_RANGE_KEY] = array(
            SMART_LIST_DATE_RANGE_START_KEY => date(DateTime::ISO8601, $this->_dateRange[SMART_LIST_DATE_RANGE_START_KEY]),
            SMART_LIST_DATE_RANGE_END_KEY => date(DateTime::ISO8601, $this->_dateRange[SMART_LIST_DATE_RANGE_END_KEY])
          );
        } else {
          // Not enough information was set to be a valid filter
          return NULL;
        }
      } else if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_RELATIVE) {
        if (!empty($this->_intervalRange)) {
          $intervalRangeD = array(
            SMART_LIST_DATE_INTERVAL_PERIOD_KEY => $this->_intervalRange[SMART_LIST_DATE_INTERVAL_RANGE_START_KEY]
          );
          $intervalRangeE = array(
            SMART_LIST_DATE_INTERVAL_PERIOD_KEY => $this->_intervalRange[SMART_LIST_DATE_INTERVAL_RANGE_END_KEY]
          );

          $intervalRangeD[SMART_LIST_DATE_RANGE_START_KEY] = $this->_intervalRange[SMART_LIST_DATE_RANGE_START_KEY];
          $intervalRangeD[SMART_LIST_DATE_RANGE_END_KEY] = $this->_intervalRange[SMART_LIST_DATE_RANGE_END_KEY];
          $innerD[SMART_LIST_DATE_INTERVAL_RANGE_START_KEY] = $intervalRangeD;
          $innerD[SMART_LIST_DATE_INTERVAL_RANGE_END_KEY] = $intervalRangeE;
        } else {
          $innerD[SMART_LIST_DATE_PERIOD_KEY] = $this->_period;
          $innerD[SMART_LIST_DATE_PERIOD_VALUE_KEY] = $this->_periodValue;
        }
      } else {
        return NULL;
      }
    }

    $dictionary = array($this->_primaryKey => $innerD);
    return $dictionary;
  }

  public function buildSQLFilter($usingStartDates = false)
  {
    $sql = "";

    $zeroInterval = 0;
    $isDueDate = false;
    $isStartDate = false;
    $columnName = NULL;
    $distantPastInterval = 0;

    if ($this->_primaryKey == SMART_LIST_DUE_DATE_KEY) {
      $columnName = "duedate";
      $zeroInterval = PHP_INT_MAX; // ??? Not sure what to do here
      $isDueDate = true;
    } else if ($this->_primaryKey == SMART_LIST_START_DATE_KEY) {
      $columnName = "startdate";
      $zeroInterval = 0;
      $isStartDate = true;
    } else if ($this->_primaryKey == SMART_LIST_COMPLETED_DATE_KEY) {
      $columnName = "completiondate";
      $zeroInterval = 0;
    } else if ($this->_primaryKey == SMART_LIST_MODIFIED_DATE_KEY) {
      $columnName = "timestamp";
    }

    if (empty($columnName)) {
      return "";
    }

    $projectColumnName = "project_" . $columnName;

    $sql .= "(";
    switch ($this->_type) {
      case SMART_LIST_DATE_TYPE_VALUE_NONE: {
        if ($isDueDate || $isStartDate) {
          $sql .= "(";
          $sql .= "(task_type != 1 AND ($columnName IS NULL OR $columnName = $zeroInterval))";
          $sql .= " OR ";
          $sql .= "(task_type = 1 AND ($projectColumnName IS NULL OR $projectColumnName = $zeroInterval))";
          $sql .= ")";
        } else {
          $sql .= "(";
          $sql .= "$columnName IS NULL";
          if ($zeroInterval != 0) {
            $sql .= " OR $columnName = $zeroInterval";
          }
          $sql .= ")";
        }
        break;
      }
      case SMART_LIST_DATE_TYPE_VALUE_ANY: {
        if ($isDueDate || $isStartDate) {
          $sql .= "(";
          $sql .= "(task_type != 1 AND $columnName IS NOT NULL AND $columnName != $zeroInterval)";
          $sql .= " OR ";
          $sql .= "(task_type = 1 AND $projectColumnName IS NOT NULL AND $projectColumnName != $zeroInterval)";
          $sql .= ")";
        } else {
          $sql .= "(";
          $sql .= "$columnName IS NOT NULL";
          if ($zeroInterval != 0) {
            $sql .= " AND $columnName != $zeroInterval";
          }
          $sql .= ")";
        }
        break;
      }
      case SMART_LIST_DATE_TYPE_VALUE_AFTER: {
        $dateInterval = 0;
        if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_EXACT) {
          if (empty($this->_date)) {
            return "";
          }

          // This is relying on the session having the right time zone set
          $date = new DateTime();

          // $timezone = $dateTime->getTimeZone();
          //
          // if($timezone == NULL)
          //     $dateTime->setTimezone(new DateTimeZone('GMT'));

          $dateTime->setTimestamp($this->_date);
          $dateTime->setTime(0, 00, 00);
          $dateTime->sub(new DateInterval('P1S')); // One second before midnight

          $dateInterval = $dateTime->getTimestamp();
        } else if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_RELATIVE) {
          // Calculate a date from the specified period
          $intervalDate = $this->_dateForPeriod($this->_period, $this->_periodValue);
          $intervalDate->setTime(0, 00, 00);
          $intervalDate->sub(new DateInterval('P1S'));
          $dateInterval = $intervalDate->getTimestamp();
        }

        // This is needed in order to pay close attention to "Today" when
        // evaluating tasks with a due time set. If the date interval is not
        // today, ignore looking at the due time.

        $session = TDOSession::getInstance();
        $userID = $session->getUserId();
        $userTimezone = TDOUserSettings::getTimezoneForUser($userID);

        $nowInterval = time();
        $normalizedNow = TDOUtil::normalizedDateFromGMT($nowInterval, $userTimezone);
        $normalizedInterval = TDOUtil::normalizedDateFromGMT($dateInterval, $userTimezone);
        $isToday = $normalizedNow == $normalizedInterval;

        if ($isDueDate && $isToday) {
          if ($usingStartDates) {
            $sql .= "(";
            $sql .= "(task_type != 1 AND ((((due_date_has_time = 0) AND (duedate > $dateInterval) AND (duedate != $zeroInterval)) OR ((due_date_has_time = 1) AND (duedate > $nowInterval) AND (duedate != $zeroInterval))) OR (startdate IS NOT NULL AND startdate > $dateInterval)))";
            $sql .= " OR ";
            $sql .= "(task_type = 1 AND ((((project_duedate_has_time = 0) AND (project_duedate > $dateInterval) AND (project_duedate != $zeroInterval)) OR ((project_duedate_has_time = 1) AND (project_duedate > $nowInterval) AND (project_duedate != $zeroInterval))) OR (project_startdate IS NOT NULL AND project_startdate > $dateInterval)))";
            $sql .= ")";
          } else {
            $sql .= "(";
            $sql .= "(task_type != 1 AND (((due_date_has_time = 0) AND (duedate > $dateInterval) AND (duedate != $zeroInterval)) OR ((due_date_has_time = 1) AND (duedate > $nowInterval) AND (duedate != $zeroInterval))))";
            $sql .= " OR ";
  					$sql .= "(task_type = 1 AND (((project_duedate_has_time = 0) AND (project_duedate > $dateInterval) AND (project_duedate != $zeroInterval)) OR ((project_duedate_has_time = 0) AND (project_duedate > $nowInterval) AND (project_duedate != $zeroInterval))))";
            $sql .= ")";
          }
        } else {
          if ($isDueDate && $usingStartDates) {
            $sql .= "(";
            $sql .= "(task_type != 1 AND (((duedate > $dateInterval) AND (duedate != $zeroInterval)) OR (startdate > $dateInterval AND startdate IS NOT NULL)))";
            $sql .= " OR ";
  					$sql .= "(task_type = 1 AND (((project_duedate > $dateInterval) AND (project_duedate != $zeroInterval)) OR (project_startdate > $dateInterval AND project_startdate IS NOT NULL)))";
            $sql .= ")";
          } else {
            if ($isStartDate) {
              $sql .= "(";
              $sql .= "(task_type != 1 AND ";
            }
            $sql .= "(($columnName > $dateInterval) AND ($columnName != $zeroInterval))";

            if ($isStartDate) {
              $sql .= ")";

              $sql .= " OR ";
              $sql .= "(task_type=1 AND (($projectColumnName> $dateInterval) AND ($projectColumnName != $zeroInterval)))";
              $sql .= ")";
            }
          }
        }
        break;
      }
      case SMART_LIST_DATE_TYPE_VALUE_BEFORE: {
        $dateInterval = 0;
        if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_EXACT) {
          if (empty($this->_date)) {
            return "";
          }
          $date = new DateTime();
          $dateTime->setTimestamp($this->_date);
          $dateTime->setTime(0, 00, 00);
          $dateTime->sub(new DateInterval('P1S')); // One second before midnight

          $dateInterval = $dateTime->getTimestamp();
        } else if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_RELATIVE) {
          // Calculate a date from the specified period
          $intervalDate = $this->_dateForPeriod($this->_period, $this->_periodValue);
          $intervalDate->setTime(0, 00, 00);
          $dateInterval = $intervalDate->getTimestamp();
        }

        $session = TDOSession::getInstance();
        $userID = $session->getUserId();
        $userTimezone = TDOUserSettings::getTimezoneForUser($userID);

        $nowInterval = time();
        $normalizedNow = TDOUtil::normalizedDateFromGMT($nowInterval, $userTimezone);
        $normalizedInterval = TDOUtil::normalizedDateFromGMT($dateInterval, $userTimezone);
        $isToday = $normalizedNow == $normalizedInterval;

        if ($isDueDate && $isToday) {
  				if ($usingStartDates) {
            $sql .= "(";
  					$sql .= "(task_type!=1 AND ((((due_date_has_time = 0) AND (duedate < $dateInterval) AND (duedate != $zeroInterval)) OR ((due_date_has_time = 1) AND (duedate < $nowInterval) AND (duedate != $zeroInterval))) OR (startdate IS NOT NULL AND startdate < $dateInterval AND startdate != 0)))";
            $sql .= " OR ";
  					$sql .= "(task_type=1 AND ((((project_duedate_has_time = 0) AND (project_duedate < $dateInterval) AND (project_duedate != $zeroInterval)) OR ((project_duedate_has_time = 1) AND (project_duedate < $nowInterval) AND (project_duedate != $zeroInterval))) OR (project_startdate IS NOT NULL AND project_startdate < $dateInterval AND project_startdate != $distantPastInterval)))";
            $sql .= ")";
  				} else {
            $sql .= "(";
  					$sql .= "(task_type!=1 AND (((due_date_has_time = 0) AND (duedate < $dateInterval) AND (duedate != $zeroInterval)) OR ((due_date_has_time = 1) AND (duedate < $nowInterval) AND (duedate != $zeroInterval))))";
            $sql .= " OR ";
  					$sql .= "(task_type=1 AND (((project_duedate_has_time = 0) AND (project_duedate < $dateInterval) AND (project_duedate != $zeroInterval)) OR ((project_duedate_has_time = 1) AND (project_duedate < $nowInterval) AND (project_duedate != $zeroInterval))))";
            $sql .= ")";
  				}
  			} else {
  				if ($isDueDate && $usingStartDates) {
            $sql .= "(";
  					$sql .= "(task_type!=1 AND (((duedate < $dateInterval) AND (duedate != $zeroInterval)) OR (startdate IS NOT NULL AND startdate < $dateInterval AND startdate != $distantPastInterval)))";
            $sql .= " OR ";
            $sql .= "(task_type=1 AND (((project_duedate < $dateInterval) AND (project_duedate != $zeroInterval)) OR (project_startdate IS NOT NULL AND project_startdate < $dateInterval AND project_startdate != $distantPastInterval)))";
            $sql .= ")";
  				} else {
  					if ($isStartDate) {
              $sql .= "(";
              $sql .= "(task_type != 1 AND ";
  					}
  					$sql .= "(($columnName < $dateInterval) AND ($columnName != $zeroInterval))";
  					if ($isStartDate) {
              $sql .= ")";

              $sql .= " OR ";
  						$sql .= "(task_type=1 AND (($projectColumnName < $dateInterval) AND ($projectColumnName != $zeroInterval)))";
              $sql .= ")";
  					}
  				}
  			}

        break;
      }
      case SMART_LIST_DATE_TYPE_VALUE_IS:
      case SMART_LIST_DATE_TYPE_VALUE_NOT: {
        $startInterval = 0;
  			$endInterval = 0;

        $session = TDOSession::getInstance();
        $userID = $session->getUserId();
        $userTimezone = TDOUserSettings::getTimezoneForUser($userID);

  			if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_EXACT) {
  				if (empty($this->_date) && empty($this->_dateRange)) {
  					return "";
  				}

          if (!empty($this->_date)) {
  					// Be sure to capture the entire day
            $date = new DateTime();
            $dateTime->setTimestamp($this->_date);
            $dateTime->setTime(0, 00, 00);
            $startInterval = $dateTime->getTimestamp();

            $dateTime->setTime(23, 59, 59);
            $endInterval = $dateTime->getTimestamp();
  				} else {
            if (empty($this->_dateRange[SMART_LIST_DATE_RANGE_START_KEY]) || empty($this->_dateRange[SMART_LIST_DATE_RANGE_END_KEY])) {
              return "";
            }

            $date = new DateTime();
            $dateTime->setTimestamp($this->_dateRange[SMART_LIST_DATE_RANGE_START_KEY]);
            $dateTime->setTime(0, 0, 0);
            $startInterval = $dateTime->getTimestamp();

            $dateTime->setTimestamp($this->_dateRange[SMART_LIST_DATE_RANGE_END_KEY]);
            $dateTime->setTime(23, 59, 59);
            $endInterval = $dateTime->getTimestamp();
  				}
  			} else if ($this->_relation == SMART_LIST_DATE_RELATION_VALUE_RELATIVE) {
          if (!empty($this->_intervalRange)) {
            $startDate = $this->_dateForPeriod($this->_intervalRange[SMART_LIST_DATE_INTERVAL_RANGE_START_KEY], $this->_intervalRange[SMART_LIST_DATE_RANGE_START_KEY]);
            $startDate->setTime(0,0,0);
            $startInterval = $startDate->getTimestamp();

            $endDate = $this->_dateForPeriod($this->_intervalRange[SMART_LIST_DATE_INTERVAL_RANGE_END_KEY], $this->_intervalRange[SMART_LIST_DATE_RANGE_END_KEY]);
            $endDate->setTime(23,59,59);
            $endInterval = $endDate->getTimestamp();
  				} else {
            $intervalDate = $this->_dateForPeriod($this->_period, $this->_periodValue);
            $intervalDate->setTime(0, 00, 00);
            $startInterval = $intervalDate->getTimestamp();
            $intervalDate->setTime(23,59,59);
            $endInterval = $intervalDate->getTimestamp();
  				}
  			}

        if ($this->_type == SMART_LIST_DATE_TYPE_VALUE_IS) {
  				if ($isDueDate) {
            $sql .= "(";
  					$sql .= "(task_type != 1 AND ((duedate > $startInterval AND duedate < $endInterval) OR (startdate IS NOT NULL AND startdate < $endInterval AND startdate != $distantPastInterval)))";
            $sql .= " OR ";
  					$sql .= "(task_type = 1 AND ((project_duedate > $startInterval AND project_duedate < $endInterval) OR (project_startdate IS NOT NULL AND project_startdate < $endInterval AND project_startdate != $distantPastInterval)))";
            $sql .= ")";
  				} else {
  					if ($isStartDate) {
              $sql .= "(";
  						$sql .= "(task_type != 1 AND ";
  					}
  					$sql .= "$columnName > $startInterval AND $columnName < $endInterval";
  					if ($isStartDate) {
              $sql .= ")";

              $sql .= " OR ";
  						$sql .= "(task_type = 1 AND ($projectColumnName > $startInterval AND $projectColumnName < $endInterval))";
              $sql .= ")";
  					}
  				}
  			} else {
  				// NOT
  				if ($isDueDate || $isStartDate) {
            $sql .= "(";
  					$sql .= "(task_type != 1 AND ";
  				}
  				$sql .= "$columnName < $startInterval OR $columnName > $endInterval";
  				if ($isDueDate || $isStartDate) {
            $sql .= ")";

            $sql .= " OR ";
  					$sql .= "(task_type = 1 AND ($projectColumnName < $startInterval OR $projectColumnName > $endInterval))";
            $sql .= ")";
  				}
  			}

        break;
      }
    }

    $sql .= ")";
    return $sql;
  }

  private function _dateForPeriod($period, $value) {
    $intervalString = "";
    switch($period) {
      case SMART_LIST_DATE_INTERVAL_PERIOD_DAY:
      {
        $intervalString = "P" . $value . "D";
        break;
      }
      case SMART_LIST_DATE_INTERVAL_PERIOD_WEEK:
      {
        $intervalString = "P" . $value . "W";
        break;
      }
      case SMART_LIST_DATE_INTERVAL_PERIOD_MONTH:
      {
        $intervalString = "P" . $value . "M";
        break;
      }
      case SMART_LIST_DATE_INTERVAL_PERIOD_YEAR:
      {
        $intervalString = "P" . $value . "Y";
        break;
      }
    }

    $date = new DateTime();
    $date->add(new DateInterval($intervalString));
    return $date;
  }
}


?>
