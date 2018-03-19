<?php
function our_error_log($msg){
  $bt = debug_backtrace();
  $caller = array_shift($bt);
  $line = $caller['line'];
  $file = $caller['file'];
  $msg = "[$file:$line]  " . $msg;
  error_log($msg);
}


//      ckSession
//      This will track the login session of a user

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/config.php');
include_once('TodoOnline/statics.php');
include_once('TodoOnline/version.php');
include_once('TodoOnline/classes/jwt_helper.php');
include_once('TodoOnline/classes/TDOAuthJWT.php');
include_once('TodoOnline/classes/TDORESTAdapter.php');
include_once('TodoOnline/classes/TDOInternalization.php');
include_once('TodoOnline/classes/TDODBObject.php');
include_once('TodoOnline/classes/TDOSession.php');
include_once('TodoOnline/classes/TDOUser.php');
include_once('TodoOnline/classes/TDOSmartList.php');
include_once('TodoOnline/classes/TDOList.php');
include_once('TodoOnline/classes/TDOTask.php');
include_once('TodoOnline/classes/TDOTaskito.php');
include_once('TodoOnline/classes/TDOContext.php');
include_once('TodoOnline/classes/TDOTag.php');
include_once('TodoOnline/classes/TDOMailer.php');
include_once('TodoOnline/classes/TDOInvitation.php');
include_once('TodoOnline/classes/TDOUtil.php');
include_once('TodoOnline/classes/TDOFBUtil.php');
include_once('TodoOnline/classes/PBButton.php');
include_once('TodoOnline/classes/TDOChangeLog.php');
include_once('TodoOnline/classes/TDOComment.php');
include_once('TodoOnline/classes/TDOListSettings.php');
include_once('TodoOnline/classes/TDOUserSettings.php');
include_once('TodoOnline/classes/TDOTaskNotification.php');
include_once('TodoOnline/classes/TDOSubscription.php');

include_once('TodoOnline/classes/TDOSmartListFilterGroup.php');
include_once('TodoOnline/classes/TDOSmartListFilter.php');
include_once('TodoOnline/classes/TDOSmartList.php');
include_once('TodoOnline/classes/TDOSmartListDateFilter.php');
include_once('TodoOnline/classes/TDOSmartListTextSearchFilter.php');

include_once('TodoOnline/classes/TDOSmartListCompletedDateFilter.php');
include_once('TodoOnline/classes/TDOSmartListCompletedTasksFilter.php');
include_once('TodoOnline/classes/TDOSmartListDueDateFilter.php');
include_once('TodoOnline/classes/TDOSmartListLocationFilter.php');
include_once('TodoOnline/classes/TDOSmartListModifiedDateFilter.php');
include_once('TodoOnline/classes/TDOSmartListNameFilter.php');
include_once('TodoOnline/classes/TDOSmartListNoteFilter.php');
include_once('TodoOnline/classes/TDOSmartListPriorityFilter.php');
include_once('TodoOnline/classes/TDOSmartListRecurrenceFilter.php');
include_once('TodoOnline/classes/TDOSmartListStarredFilter.php');
include_once('TodoOnline/classes/TDOSmartListStartDateFilter.php');
include_once('TodoOnline/classes/TDOSmartListTagFilter.php');
include_once('TodoOnline/classes/TDOSmartListTaskActionFilter.php');
include_once('TodoOnline/classes/TDOSmartListTaskTypeFilter.php');
include_once('TodoOnline/classes/TDOSmartListUserAssignmentFilter.php');

include_once('TodoOnline/classes/TDOTeamAccount.php');
include_once('TodoOnline/classes/TDOTeamSlackIntegration.php');
include_once('TodoOnline/classes/TDOReferral.php');
include_once('TodoOnline/classes/TDOLegacy.php');
include_once('TodoOnline/classes/TDOPasswordReset.php');
include_once('TodoOnline/classes/TDOPromoCode.php');
include_once('TodoOnline/classes/TDOEmailVerification.php');
include_once('TodoOnline/classes/TDODevice.php');
//include_once('TodoOnline/classes/DateStringParser.inc.php');
include_once('TodoOnline/classes/TDOUserMaintenance.php');
include_once('TodoOnline/classes/TDOSystemNotification.php');
include_once('TodoOnline/classes/TDOGiftCode.php');
include_once('TodoOnline/classes/TDOStripeGiftPayment.php');
include_once('TodoOnline/classes/TDOInAppPurchase.php');
include_once('TodoOnline/classes/AppigoEmailVerification.php');
include_once('TodoOnline/classes/AppigoPasswordReset.php');
include_once('TodoOnline/classes/AppigoUser.php');
include_once('TodoOnline/classes/AppigoEmailListUser.php');
include_once('TodoOnline/helpers/translation.php');
