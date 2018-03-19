<?php
    
    /****** Include a select menu for choosing the recurrence type *******/
    
    $taskHTML .= '<div class="select_wrapper">';
    $taskHTML .= '<select id="recurrence_select_'.$taskid.'" onclick="taskRecurrenceTypeSelected(\''.$taskid.'\', this.value)">';
    
    //Go through the normal task recurrence types
    $selectedRecurrenceType = $task->getRecurrenceType();
    $orderedRecurrenceTypes = array(TaskRecurrenceType::None,TaskRecurrenceType::Daily,TaskRecurrenceType::Weekly,TaskRecurrenceType::Biweekly,TaskRecurrenceType::Monthly,TaskRecurrenceType::Quarterly,TaskRecurrenceType::Semiannually,TaskRecurrenceType::Yearly);
    foreach($orderedRecurrenceTypes as $recurrenceType)
    {
        if($selectedRecurrenceType == $recurrenceType || $selectedRecurrenceType == $recurrenceType + 100)
            $selectedString = "selected=true";
        else
            $selectedString = "";
        $taskHTML .= "<option value=$recurrenceType $selectedString>".TDOTask::localizedStringForTaskRecurrenceType($recurrenceType)."</option>";
    }
    
    //Go through the advanced task recurrence types
    $selectedAdvancedRecurrenceString = $task->getAdvancedRecurrenceString();
    $selectedAdvancedType = TDOTask::advancedRecurrenceTypeForString($selectedAdvancedRecurrenceString);
    $orderedAdvancedTypes = array(AdvancedRecurrenceType::EveryXDaysWeeksMonths, AdvancedRecurrenceType::TheXOfEachMonth, AdvancedRecurrenceType::EveryMonTueEtc);
    
    foreach($orderedAdvancedTypes as $advancedType)
    {
        if(($selectedRecurrenceType == TaskRecurrenceType::Advanced || $selectedRecurrenceType == TaskRecurrenceType::Advanced + 100) &&  $selectedAdvancedType == $advancedType)
            $selectedString = "selected=true";
        else
            $selectedString = "";
            
        $taskHTML .= "<option value=50:".$advancedType." $selectedString>".TDOTask::localizedGenericStringForAdvancedRecurrenceType($advancedType)."</option>";
    }
    
    
    if($task->getParentId() != NULL && strlen($task->getParentId()) > 0)
    {
        if(($selectedRecurrenceType == TaskRecurrenceType::WithParent || $selectedRecurrenceType == TaskRecurrenceType::WithParent + 100))
            $selectedString = "selected=true";
        else
            $selectedString = "";
        $taskHTML .= '<option '.$selectedString.' value=9>' . _('Repeat With Parent Task') . '</option>';
    }
    

    $taskHTML .= '</span>';
    
    $taskHTML .= '</select>';
    $taskHTML .= '</div>'; //end select_wrapper

    /***** Include additional content for choosing advanced recurrence types ******/
    
    $advancedStringComponents = preg_split('/\s+/', $selectedAdvancedRecurrenceString);
    
    foreach($orderedAdvancedTypes as $advancedType)
    {
        $isSelectedType = false;
        $styleString = 'style="display:none;"';
        if($selectedAdvancedType == $advancedType || $selectedAdvancedType == $advancedType + 100)
        {
            $isSelectedType = true;
            $styleString = 'style="display:block;"';
        }
        $taskHTML .= '<div '.$styleString.' id="advanced_recurrence_section_'.$taskid.'_'.$advancedType.'">';
        
        if ($advancedType == AdvancedRecurrenceType::EveryMonTueEtc)
        	$height = ' style="height:42px;"';
        else
        	$height = '';
        		
        $taskHTML .= '<span class="label_title"><div'.$height.' id="advanced_recurrence_title_'.$taskid.'_'.$advancedType.'">';
        if($isSelectedType)
            $taskHTML .= TDOTask::localizedStringForAdvancedRecurrenceStringOfType($selectedAdvancedRecurrenceString, $advancedType);
        else
            $taskHTML .= TDOTask::defaultLocalizedStringForAdvancedRecurrenceType($advancedType);
        
        $taskHTML .= '</div></span>'; //end advanced_recurrence_title
         
        
        switch($advancedType)
        {
            case AdvancedRecurrenceType::EveryXDaysWeeksMonths:
            {
                $interval = 1;
                if($isSelectedType)
                {
                    $interval = $advancedStringComponents[1];
                }
                
                $taskHTML .= '<input type="text" id="every_x_days_int_'.$taskid.'" value="'.$interval.'" size="2" onkeyup="formatXDaysRecurrenceInterval(this, \''.$taskid.'\');">';
                $taskHTML .= '<div class="medium_select"><select id="every_x_days_select_'.$taskid.'" onchange="everyXDaysRecurrenceChanged(\''.$taskid.'\')">';
                
                //TODO: localize these
                $localizedEveryXOptions = array("Days"=>_("Days"), "Weeks"=>_("Weeks"), "Months"=>_("Months"), "Years"=>_("Years"));
                foreach($localizedEveryXOptions as $option=>$localizedOption)
                {
                    $selectedString = "";
                    if($isSelectedType)
                    {
                        $dayMonthYearVal = $advancedStringComponents[2];
                        if(strcasecmp($dayMonthYearVal, $option) == 0 || strcasecmp($dayMonthYearVal, substr($option, 0, strlen($option) - 1)) == 0 )
                        {
                            $selectedString = "selected=true";
                        }
                    }
                    else
                    {
                        if($option == "Days")
                            $selectedString = "selected=true";
                    }
                    $taskHTML .= '<option value='.$option.' '.$selectedString.'>'.$localizedOption.'</option>';
                }
                
                $taskHTML .= '</select></div>';
                break;
            }
            case AdvancedRecurrenceType::EveryMonTueEtc:
            {
                //TODO: localize these
                $localizedDayOptions = array(
                    "Monday,Mon,Weekday,Every Day" => _("Monday"),
                    "Tuesday,Tue,Tues,Weekday,Every Day" => _("Tuesday"),
                    "Wednesday,Wed,Wendsday,Weekday,Every Day" => _("Wednesday"),
                    "Thursday,Thu,Thurs,Weekday,Every Day" => _("Thursday"),
                    "Friday,Fri,Fryday,Weekday,Every Day" => _("Friday"),
                    "Saturday,Sat,Weekend,Every Day" => _("Saturday"),
                    "Sunday,Sun,Weekend,Every Day" => _("Sunday")
                );
                $dayIndex = 0;
                foreach($localizedDayOptions as $option=>$localizedOption)
                {
                    $selectedString = "";
                    if($isSelectedType)
                    {
                        $compStrings = explode(",", $option);
                        foreach($compStrings as $compString)
                        {
                            if(stripos($selectedAdvancedRecurrenceString, $compString) !== false)
                            {
                                
                                $selectedString = "checked=true";
                                break;
                            }
                        }
                    }
                    
                    $taskHTML .= '<span class="label"><input type="checkbox" id="day_of_week_box_'.$dayIndex.'_'.$taskid.'" onclick="everyEtcRecurrenceChanged(\''.$taskid.'\')" name="every_etc_checkbox_'.$taskid.'" value='.$option.' '.$selectedString.'><label for="day_of_week_box_'.$dayIndex.'_'.$taskid.'">'.$localizedOption.'</label></option></span>';
                    $dayIndex++;
                }

                break;
            }
            case AdvancedRecurrenceType::TheXOfEachMonth:
            {
                $taskHTML .= '<div class="small_select"><select id="the_x_of_each_month_week_select_'.$taskid.'" onchange="theXOfEachMonthRecurrenceChanged(\''.$taskid.'\')">';
                //TODO: localize these
                $localizedWeekOptions = array(
                    "1st,first" => _("1st"),
                    "2nd,second" => _("2nd"),
                    "3rd,third" => _("3rd"),
                    "4th,fourth" => _("4th"),
                    "5th,fifth" => _("5th"),
                    "last,final" => _("last")
                );
                foreach($localizedWeekOptions as $option=>$localizedOption)
                {
                    $selectedString = "";
                    if($isSelectedType)
                    {
                        $weekString = $advancedStringComponents[1];
                        $compStrings = explode(",", $option);
                        foreach($compStrings as $compString)
                        {
                            if(strcasecmp($weekString, $compString) == 0)
                            {
                                $selectedString = "selected=true";
                                break;
                            }
                        }
                    }
                    else
                    {
                        if($option == "1st,first")
                            $selectedString = "selected=true";
                    }
                    $taskHTML .= '<option value='.$option.' '.$selectedString.'>'.$localizedOption.'</option>';
                }
                
                $taskHTML .= '</select></div>'; //end the_x_of_each_month_week_select
                
                $taskHTML .= '<div class="medium_select"><select id="the_x_of_each_month_day_select_'.$taskid.'" onchange="theXOfEachMonthRecurrenceChanged(\''.$taskid.'\')">';
                //TODO: localize these
                $localizedDayOptions = array("Monday,Mon"=>"Monday", "Tuesday,Tue,Tues"=>"Tuesday", "Wednesday,Wed"=>"Wednesday", "Thursday,Thu,Thur,Thurs"=>"Thursday", "Friday,Fri"=>"Friday", "Saturday,Sat"=>"Saturday", "Sunday,Sun"=>"Sunday");
                foreach($localizedDayOptions as $option=>$localizedOption)
                {
                    $selectedString = "";
                    if($isSelectedType)
                    {   
                        $dayString = $advancedStringComponents[2];
                        $compStrings = explode(",", $option);
                        foreach($compStrings as $compString)
                        {
                            if(strcasecmp($dayString, $compString) == 0)
                            {
                                $selectedString = "selected=true";
                                break;
                            }
                        }
                    }
                    else
                    {
                        if($option == "Monday,Mon")
                            $selectedString = "selected=true";
                    }
                    $taskHTML .= '<option value='.$option.' '.$selectedString.'>'.$localizedOption.'</option>';
                }
                
                $taskHTML .= '</select></div>'; //end the_x_of_each_month_day_select 
                break;
            }
        }

        
        $taskHTML .= '</div>'; //end advanced_recurrence_section
    }

    /***** Include radio buttons for choosing 'from completion date' or 'from due date' ******/
    
    $repeatFromCompletion = ($selectedRecurrenceType >= 100);

$taskHTML .= '<span class="label_title">' . _('Repeat On:') . '</span>';
    if($repeatFromCompletion)
        $selectedString = "";
    else
        $selectedString = "checked=true";
    $taskHTML .= '	<span class="label">
    					<input type="radio" id="repeat_from_completion_false_'.$taskid.'" name="repeat_from_completion_'.$taskid.'" value="false" '.$selectedString.'/><label for="repeat_from_completion_false_'.$taskid.'">' . _('Due Date') . '</label>
    				</span>';
    
    if(!$repeatFromCompletion)
        $selectedString = "";
    else
        $selectedString = "checked=true";
    $taskHTML .= '	<span class="label">
    					<input type="radio" id="repeat_from_completion_true_'.$taskid.'" name="repeat_from_completion_'.$taskid.'" value="true" '.$selectedString.'/><label for="repeat_from_completion_true_'.$taskid.'">' . _('Completion Date') . '</label>
    				</span>';



    
?>

