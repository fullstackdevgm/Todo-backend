<?php

include_once('TodoOnline/base_sdk.php');

class TDOSmartListFilterGroup
{
  protected $_filterGroupDictionary;
  protected $_filtersA;

  public function __construct($dictionary)
  {
    $this->_filterGroupDictionary = $dictionary;
    $this->_filtersA = array();

    foreach($dictionary as $key => $value) {
      $filterD = array($key => $value);
      $smartListFilter = TDOSmartListFilter::smartListFilterForDictionary($filterD);
      if (!empty($smartListFilter)) {
        $this->_filtersA[] = $smartListFilter;
      }
    }
  }

  public function filters()
  {
    return $this->_filtersA;
  }

  public function canAddFilterWithClassName($filterClassName)
  {
    foreach($this->_filtersA as $filter) {
      $className = get_class($filter);
      if ($className == $filterClassName) {
        // This filter already exists, so it cannot be added
        return false;
      }
    }

    return true;
  }

  public function addFilter($filter)
  {
    if (empty($filter)) {
      return false;
    }

    // Only add the filter if that kind of filter isn't already present
    $canAddFilter = $this->canAddFilterWithClassName(get_class($filter));
    if (!$canAddFilter) {
      error_log("TDOSmartListFilterGroup::addFilter() Cannot add this type of filter (" . get_class($filter) . "). This filter type probably already exists in the filter group.");
      return false;
    }

    $this->_filtersA[] = $filter;
    $this->_rebuildDictionary();
    return true;
  }

  public function updateFilter($filter)
  {
    $filterIndex = $this->_indexOfSmartListFilter($filter);
    if ($filterIndex < 0) {
      error_log("TDOSmartListFilterGroup::addFilter() Cannot update the specified filter because it was not found: " . get_class($filter));
      return false;
    }

    // Replace the existing filter with the new one
    $this->_filtersA[$filterIndex] = $filter;
    $this->_rebuildDictionary();
    return true;
  }

  public function removeFilterAtIndex($filterIndex)
  {
    if ($filterIndex < 0 || ($filterIndex > count($this->_filtersA) - 1)) {
      return false; // Invalid index
    }

    unset($this->_filtersA[$filterIndex]);
    // Re-index the filters
    $this->_filtersA = array_values($this->_filtersA);
    $this->_rebuildDictionary();
    return true;
  }

  public function dictionaryRepresentation()
  {
    return $this->_filterGroupDictionary;
  }

  private function _rebuildDictionary()
  {
    $newGroupD = array();
    foreach($this->_filtersA as $filter) {
      $filterD = $filter->dictionaryRepresentation();
      if (!empty($filterD)) {
        $newGroupD[] = $filterD;
      }
    }
    $this->_filterGroupDictionary = $newGroupD;
  }

  private function _indexOfSmartListFilter($filter)
  {
    $filterClassName = get_class($filter);
    $idx = 0;
    foreach($this->_filtersA as $aFilter) {
      $className = get_class($aFilter);
      if ($className == $filterClassName) {
        return $idx;
      }
      $idx++;
    }

    return -1; // not found
  }

}

?>
