<?php

include_once('TodoOnline/base_sdk.php');

/**
 *
 */
class TDOSmartListTagFilter extends TDOSmartListFilter
{
  protected $_comparator;
  protected $_tags;
  protected $_tagsString;

  function __construct($dictionary)
  {
    parent::__construct($dictionary);
    $this->_filterName = "TagFilter";

    $this->_comparator = NULL;
    $this->_tags = NULL;
    $this->_tagsString = NULL;

    if (!empty($dictionary)) {
      $innerD = $dictionary[SMART_LIST_TAGS_KEY];
      if (!empty($innerD)) {
        $this->_comparator = $innerD[SMART_LIST_COMPARATOR_KEY];

        $this->_tags = $innerD[SMART_LIST_TAGS_KEY];
      }
    }
  }

  public function dictionaryRepresentation()
  {
    if ($this->_tags == NULL || count($this->_tags) == 0) {
      return NULL;
    }

    $innerD = array(
      SMART_LIST_TAGS_KEY => $this->_tags,
      SMART_LIST_COMPARATOR_KEY => $this->_comparator
    );

    $dictionary = array(
      SMART_LIST_TAGS_KEY => $innerD
    );

    return $dictionary;
  }

  public function buildSQLFilter()
  {
    // Punting on the tags filter for now because tags are tracked in a separate table and
    // to pull this off, we'd have to make a VERY complex query with a few table joins.
    return "taskid IS NOT NULL";
  }

  public function tagsString()
  {
    if (empty($this->_tags) || count($this->_tags) == 0) {
      return "";
    }

    $firstTag = $this->_tags[0];
    if ($firstTag == _("Any Tag")) {
      return _("any");
    } else if ($firstTag == _("No Tag")) {
      return _("none");
    }

    $tagsString = implode(", ", $this->_tags);
    return $tagsString;
  }
}


?>
