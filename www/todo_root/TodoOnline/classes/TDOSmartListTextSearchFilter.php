<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListTextSearchFilter extends TDOSmartListFilter
{
  protected $_comparator;
  protected $_searchTerms;
  protected $_primaryKey;

  function __construct($dictionary, $primaryKey)
  {
    parent::__construct($dictionary);
    $this->_filterName = "TextSearchFilter";

    $this->_comparator = SMART_LIST_COMPARATOR_AND;
    $this->_searchTerms = array();
    $this->_primaryKey = NULL;

    if (!empty($primaryKey)) {
      $this->_primaryKey = $primaryKey;
    }


    if (!empty($dictionary)) {
      $innerD = $dictionary[$this->_primaryKey];
      if (!empty($innerD)) {
        $comparator = $innerD[SMART_LIST_COMPARATOR_KEY];
        if (empty($comparator) || $comparator == SMART_LIST_COMPARATOR_AND) {
          $this->_comparator = SMART_LIST_COMPARATOR_AND;
        } else {
          $this->_comparator = SMART_LIST_COMPARATOR_OR;
        }

        $searchTerms = $innerD[SMART_LIST_SEARCH_TERMS_KEY];
        if (!empty($searchTerms)) {
          foreach($searchTerms as $searchTerm) {
            $this->addSearchTerm($searchTerm);
          }
        }
      }
    }
  }

  public function searchTerms()
  {
    return $this->_searchTerms;
  }

  public function addSearchTerm($searchTerm) {
    if (empty($searchTerm)) {
      return false;
    }

    // Prevent more than the max allowed search terms
    if (count($this->_searchTerms) >= SMART_LIST_MAX_SEARCH_TERMS_PER_FILTER) {
      return false;
    }

    // $containsNumber = $searchTerm[SMART_LIST_CONTAINS_KEY];
    $text = $searchTerm[SMART_LIST_TEXT_KEY];
    // if (!empty($containsNumber) && !empty($text)) {
    if (!empty($text)) {
      $text = trim($text);
      // Make sure that this search term doesn't already exist
      foreach($this->_searchTerms as $existingSearchTerm) {
        $existingText = $existingSearchTerm[SMART_LIST_TEXT_KEY];
        if (strtolower($existingText) == strtolower($text)) {
          // This search term already exists
          return false;
        }
      }

      $this->_searchTerms[] = $searchTerm;
    }
  }

  public function removeSearchTermAtIndex($index)
  {
    if ($index < 0 || $index > count($this->_searchTerms) - 1) {
      // Invalid index
      return false;
    }

    unset($this->_searchTerms[$index]);
    $this->_searchTerms = array_values($this->_searchTerms);
  }

  public function dictionaryRepresentation()
  {
    if (empty($this->_primaryKey)) {
      // Cannot build a dictionary representation if the primary key is nil
      return nil;
    }

    if (count($this->_searchTerms) == 0) {
      // Not really a valid filter if there are no search terms
      return nil;
    }

    $innerD = array(
      SMART_LIST_COMPARATOR_KEY => $this->_comparator,
      SMART_LIST_SEARCH_TERMS_KEY => $this->_searchTerms
    );

    $dictionary = array(
      $this->_primaryKey => $innerD
    );

    return $dictionary;
  }

  public function buildSQLFilter()
  {
    if (empty($this->_primaryKey)) {
      return "";
    }

    if (count($this->_searchTerms) == 0) {
      return "";
    }

    $searchColumn = NULL;
    if ($this->_primaryKey == SMART_LIST_NAME_KEY) {
      $searchColumn = "name";
    } else if ($this->_primaryKey == SMART_LIST_NOTE_KEY) {
      $searchColumn = "note";
    }

    if (empty($searchColumn)) {
      return "";
    }

    $sql = "(";

    foreach($this->_searchTerms as $searchTerm) {
      $text = $searchTerm[SMART_LIST_TEXT_KEY];
      if (empty($text)) {
        continue;
      }
      if (strlen($sql) > 1) { // index of 1 to account for the initial '('
        if ($this->_comparator == SMART_LIST_COMPARATOR_OR) {
          $sql .= " OR ";
        } else {
          $sql .= " AND ";
        }
      }

      $sql .= $searchColumn . " LIKE '%%" . $text . "%%'";
    }

    $sql .= ")";
    return $sql;
  }
}


?>
