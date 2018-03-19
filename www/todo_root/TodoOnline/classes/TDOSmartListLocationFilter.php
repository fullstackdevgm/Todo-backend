<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListLocationFilter extends TDOSmartListFilter
{
  protected $_hasLocation;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "LocationFilter";

    $this->_hasLocation = false;

    if (!empty($dictionary)) {
      $value = $dictionary[SMART_LIST_HAS_LOCATION_KEY];
      if (!empty($value) && $value > 0) {
        $this->_hasLocation = true;
      }
    }
  }

  public function dictionaryRepresentation()
  {
    $dictionary = array(SMART_LIST_HAS_LOCATION_KEY => $this->_hasLocation);
    return $dictionary;
  }

  public function buildSQLFilter()
  {
    $sql = "";
    if ($this->_hasLocation) {
      $sql .= "(LENGTH(location_alert) > 0)";
    } else {
      $sql .= "((location_alert IS NULL) OR (LENGTH(location_alert) = 0))";
    }
    return $sql;
  }
}


?>
