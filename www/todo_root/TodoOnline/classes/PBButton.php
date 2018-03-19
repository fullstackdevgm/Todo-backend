<?php
/*
	Description: PBButton.php 
	Purpose: Used to help manage buttons in the UI
*/

define('SINGLE_ACTION_BUTTON',	'single_a_button');
define('MULTI_ACTION_BUTTON', 'multi_a_button');
define('BUTTON_IMG', 'button_img');
define('NO_LABEL', 'no label');
define('BUTTON_FLYOUT', 'button_flyout');
define('BUTTON_FLYOUT_TOGGLE','button_flyout_toggle');
define('CHILD_BUTTON_CONTAINER','child_button_container');
define('BUTTON_OPTION', 'button_option');
define('CHILD_BUTTON_DISABLED','child_button_disabled');
define('CHILD_BUTTON_CONTAINER_HR' ,'child_button_container_hr');


class PBButton
{
	private $_label;		//text describing button
	private $_url;			//
	private $_imageUrl;		//url for the image to the left of the label
	private $_onClick;		//javascript method to be executed onClick. 
							//IMPORTANT: If you provide an onClick method, the href will be set to "#" 							//           Set onClick variable like this: $button->setonClick("alert('hello world')")
	private $_container;	//the type of container the buttons will be drawn in (header, content, control, etc)
	private $_children; 	//array of children buttons if the button offers more than a single action
	private $_isSelected;	//places a checkmark to the left of the button's label
	private $_isDisabled;	
	private $_isDivisor;		//adds a divisor between children options
	private $_id;


	public function drawButton()
	{
        $html = $this->htmlForButton();
        print $html;
	}
	
    public function htmlForButton()
    {

		$label = $this->_label;
		$url = $this->_url;
		$imageUrl = $this->_imageUrl;
		$onClick = $this->_onClick;
		$container= $this->_container;
		$children = $this->_children;
		$id = $this->_id;
		
		if(!isset($id))
			$id = '';
		
		$html = '<div class="button_container">';
		
		//opening <a> tag
			//class attribute
			if(isset($children) && count($children)> 0)
				$html .= '<a id="'.$id.'" class="'.BUTTON_FLYOUT_TOGGLE.' '.SINGLE_ACTION_BUTTON.'"';
			else
				$html .= '<a id="'.$id.'" class="'.SINGLE_ACTION_BUTTON.'"';
			
			//if(isset($container))
				//print ' '.$container;
				
			//onClick attribute
			if(isset($onClick))
				$html .= ' onclick="'.$onClick.'" href="javascript:void(0)"';
			else
			{
				//href attribute
				if(isset($url))
					$html .= ' href="'.$url.'"';
				else
					$html .= ' href="javascript:void(0)"';
			}
				
			
			$html .= ' >'; //closing opening <a> tag
		
		//button img
		if(isset($imageUrl))
			$html .= '<li class="'.BUTTON_IMG.'"></li>';	
		
		//button label
		if(isset($label))
			$html .= $label;
		else
			$html .= NO_LABEL;
				
		$html .= '</a>';
		
		
		//children flyout
		if(isset($children) && count($children)>0)
		{
			$html .= '<div class="'.BUTTON_FLYOUT.'">';
			
			foreach($children as $child_button)
			{
				$child_label = $child_button->_label;
				$child_url = $child_button->_url;
				$child_imageUrl = $child_button->_imageUrl;
				$child_onClick = $child_button->_onClick;
				$child_isDisabled = $child_button->_isDisabled;
				$child_isSelected = $child_button->_isSelected;
				$child_isDivisor = $child_button->_isDivisor;
				$child_id = $child_button->_id;
				
				if(isset($child_isDisabled) || isset($child_isDivisor))
					$disabled = CHILD_BUTTON_DISABLED;
				else
					$disabled ='';	
					
				$html .= '<div class="'.CHILD_BUTTON_CONTAINER.' '.$disabled.'">';
				
				if(isset($child_isDivisor))
				{
					$html .= '<hr class="'.CHILD_BUTTON_CONTAINER_HR.'" />';
				}
				else
				{
					//opening <a> tag
					//class attribute
					$html .= '<a class="'.BUTTON_OPTION.'"';
					
					//onclick attribute
					if(isset($child_onClick))
						$html .= ' onclick="'.$child_onClick.'" href="javascript:void(0)"';
					else
					{
						//href attribute
						if(isset($child_url))
							$html .= ' href="'.$child_url.'"';
						else
							$html .= ' href="javascript:void(0)"';
					}
							
					$html .= '>'; //closing opening <a> tag
					
					//button image for selected state
					if(isset($child_isSelected))
						$html .= '<li class="'.BUTTON_IMG.'" style="margin-right:6px;"></li>';
					else
						$html .= '<li class="'.BUTTON_IMG.'" style="margin-right:6px;background:none;"></li>';
					//button label
					if(isset($child_label))
						$html .= $child_label;
					else
						$html .= NO_LABEL;
						
					$html .= '</a>';		
				}
				
					
				$html .= '</div>';
			
			}
			
			$html .= '</div>';
		
		}

		$html .= '</div>';        
        return $html;
    }

	public function getLabel()
	{
		return $this->_label;
	}
	public function setLabel($val)
	{
		$this->_label = addslashes($val);
	}
	
	public function getUrl()
	{
		return $this->_url;
	}
	public function setUrl($val)
	{
		$this->_url = addslashes($val);
	}
	
	public function getImageUrl()
	{
		return $this->_url;
	}
	public function setImageUrl($val)
	{
		$this->_imageUrl = addslashes($val);
	}
	
	public function getonClick()
	{
		return $this->_onClick;
	}
	public function setonClick($val)
	{
		$this->_onClick = ($val);
	}
	
	public function getContainer()
	{
		return $this->_container;
	}
	public function setContainer($val)
	{
		$this->_container = addslashes($val);
	}

	public function getChildren()
	{
		return $this->_children;
	}
	public function setChildren($val)
	{
		$this->_children = $val;
	}
	
	public function getIsSelected()
	{
		return $this->_isSelected;
	}
	public function setIsSelected($val)
	{
		$this->_isSelected = $val;
	}
	
	public function getIsDisabled()
	{
		return $this->_isDisabled;
	}
	public function setIsDisabled($val)
	{
		$this->_isDisabled = $val;
	}
	
	public function getIsDivisor()
	{
	
		print $this->_label." is a divisor";
		return $this->_isDivisor;
	}
	public function setIsDivisor($val)
	{
		$this->_isDivisor = $val;
	}
	
	public function getId()
	{
		return $this->_id;
	}
	public function setId($val)
	{
		$this->_id = $val;
	}
}

?>