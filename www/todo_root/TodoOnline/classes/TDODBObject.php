<?php
	// TDODBObject
	//
	// Created by Calvin Gaisford on 6/27/2012.
	// Copyright (C) 2012 Plunkboard, Inc. All rights reserved.
	
	// include files
	include_once('TodoOnline/base_sdk.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	include_once('Sabre/VObject/includes.php');
	
	class TDODBObject
	{
        /*
         identifier - should always map to the underlying object's identifier
         name
         timestamp
         deleted
        */
		
        protected $_publicPropertyArray;
        protected $_privatePropertyArray;
        
		public function __construct()
		{
			$this->set_to_default();
		}
		
		public function set_to_default()
		{
            $this->_publicPropertyArray = array();
            $this->_privatePropertyArray = array();
            
            $this->setTimestamp(0);
		}
        

        // ------------------------
        // property Methods
        // ------------------------
        
		public function identifier()
		{
            return NULL;
		}
		public function setIdentifier($val)
		{
        }
        
		public function name()
		{
			if(isset($this->_publicPropertyArray['name']) && empty($this->_publicPropertyArray['name']) && $this->_publicPropertyArray['name'] != '0') {
                return NULL;
			}
			
			if (isset($this->_publicPropertyArray['name'])) {
				return $this->_publicPropertyArray['name'];
			}
			else {
				return NULL;
			}
		}
		public function setName($val)
		{
            if($val != '0' && empty($val))
                unset($this->_publicPropertyArray['name']);
            else
                $this->_publicPropertyArray['name'] = TDOUtil::ensureUTF8($val);
		}
        
		public function timestamp()
		{
            if(empty($this->_publicPropertyArray['timestamp']))
                return 0;
            else
                return $this->_publicPropertyArray['timestamp'];
		}
		public function setTimestamp($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['timestamp']);
            else
                $this->_publicPropertyArray['timestamp'] = $val;
		}

		public function deleted()
		{
            if(empty($this->_publicPropertyArray['deleted']))
                return false;
            else
                return $this->_publicPropertyArray['deleted'];
		}
		public function setDeleted($val)
		{
            if(empty($val))
                unset($this->_publicPropertyArray['deleted']);
            else
                $this->_publicPropertyArray['deleted'] = $val;
		}
        

		public function getPropertiesArray()
		{
            return $this->_publicPropertyArray;
		}
        

	}
	
