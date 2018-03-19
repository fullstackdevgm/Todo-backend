<?php


/*
	IMPORTANT: When adding a new button to create a new table, be sure to add the necessary changes in the following places:
			   - drawCreateTableButtons(), this is where your button is drawn
			   - createDatabase(), add the function that creates your table so that the table is created when the database is created
*/
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/classes/TDOTableManager.php');
include_once('TodoOnline/DBConstants.php');


function drawDeleteDBButton()
{
	echo "<form action=\"manageDB.php\" method=\"POST\">";
	echo "<input type=\"hidden\" name=\"deletedb\" value=\"yes\">";
	echo '<input style="background:rgb(184,37,43);color:white;" type="submit" value="Delete database">';
	echo "</form>";
}
    
function drawCreateAllTablesButton()
{
    echo "<form action=\"manageDB.php\" method=\"POST\">";
    echo "<input type=\"hidden\" name=\"createAllTables\" value=\"yes\">";
    echo '<input style="background:rgb(184,37,43);color:white;" type="submit" value="Create All Tables and Indexes">';
    echo "</form>";
}
    

function drawCreateTableButtons()
{
	print '	<form action="manageDB.php" method="POST">
                <input type="hidden" name="alltables" value="true">
                <input type="submit" value="Create All Tables and Indexes">
            </form>
	
            <form action="manageDB.php" method="POST">
				<input type="hidden" name="usertable" value="true">
				<input type="submit" value="Create User Table">
			</form>
			
            <form action="manageDB.php" method="POST">
                <input type="hidden" name="usermigrationtable" value="true">
                <input type="submit" value="Create User Migration Table">
            </form>
    
            <form action="manageDB.php" method="POST">
                <input type="hidden" name="systemnotificationtable" value="true">
                <input type="submit" value="Create System Notification Table">
            </form>
	
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="referralstable" value="true">
				<input type="submit" value="Create Referrals Table">
			</form>
	
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="referralunsubscriberstable" value="true">
				<input type="submit" value="Create Referral Unsubscribers Table">
			</form>
	
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="referralcredithistorytable" value="true">
				<input type="submit" value="Create Referral Credit History Table">
			</form>
			
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="sessiontable" value="true">
				<input type="submit" value="Create Session Table">
			</form>
			
			
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="listtable" value="true">
				<input type="submit" value="Create List Table">
			</form>
				
			<form action="manageDB.php" method="POST">
			    <input type="hidden" name="invitationtable" value="true">
			    <input type="submit" value="Create Invitation Table">
			</form>	
			
			<form action="manageDB.php" method="POST">
			    <input type="hidden" name="resetpwdtable" value="true">
			    <input type="submit" value="Create Reset Password Table">
			</form>

			<form action="manageDB.php" method="POST">
			    <input type="hidden" name="emailverificationtable" value="true">
			    <input type="submit" value="Create Email Verification Table">
			</form>
            
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="eventtable" value="true" />
				<input type="submit" value="Create Event Table" />
			</form>
	
	
			<form action="manageDB.php" method="POST">
				<input type="hidden" name="tasktable" value="true" />
				<input type="submit" value="Create Task Table" />
			</form>

            <form action="manageDB.php" method="POST">
                <input type="hidden" name="taskitotable" value="true" />
                <input type="submit" value="Create Taskito Table" />
            </form>

			<form action="manageDB.php" method="POST">
				<input type="hidden" name="changelogtable" value="true" />
				<input type="submit" value="Create Changelog Table" />
			</form>
			
			<form action="manageDB.php" method="POST">
			    <input type="hidden" name="commenttable" value="true" >
			    <input type="submit" value="Create Comment Table">
			</form>
            <form action="manageDB.php" method="POST">
			    <input type="hidden" name="notificationtable" value="true" >
			    <input type="submit" value="Create Email Notification Table">
			</form>
            
            <form action="manageDB.php" method="POST">          
			    <input type="hidden" name="listsettingstable" value="true" >
			    <input type="submit" value="Create List Settings Table">            
            </form>

			<form action="manageDB.php" method="POST">          
				<input type="hidden" name="contexttable" value="true" >
				<input type="submit" value="Create Context Table">            
			</form>
            
            <form action="manageDB.php" method="POST">          
                <input type="hidden" name="tagtable" value="true" >
                <input type="submit" value="Create Tag Table">            
            </form>
            
            <form action="manageDB.php" method="POST">          
                <input type="hidden" name="tasknotificationtable" value="true" >
                <input type="submit" value="Create Task Notification Table">            
            </form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="promocodestable" value="true" >
	<input type="submit" value="Create Promo Codes Table">
	</form>
    
    <form action="manageDB.php" method="POST">
    <input type="hidden" name="giftcodestable" value="true">
    <input type="submit" value="Create Gift Codes Table">
	</form>
    
    <form action="manageDB.php" method="POST">
    <input type="hidden" name="stripegiftpaymenthistorytable" value="true">
    <input type="submit" value="Create Stripe Gift Payment History Table">
    </form>
    
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="subscriptionstable" value="true" >
	<input type="submit" value="Create Subscriptions Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="subscriptioninvitationstable" value="true" >
	<input type="submit" value="Create Subscription Invitations Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="userpaymentsystemtable" value="true" >
	<input type="submit" value="Create User Payment System Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="stripeuserinfotable" value="true" >
	<input type="submit" value="Create Stripe User Info Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="stripepaymenthistorytable" value="true" >
	<input type="submit" value="Create Stripe Payment History Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="iappaymenthistorytable" value="true" >
	<input type="submit" value="Create IAP Payment History Table">
	</form>

	<form action="manageDB.php" method="POST">
	<input type="hidden" name="iapautorenewreceipttable" value="true" >
	<input type="submit" value="Create IAP Autorenew Receipt Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="googleplaypaymenthistorytable" value="true" >
	<input type="submit" value="Create Google Play Payments History Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="googleplayautorenewtokenstable" value="true" >
	<input type="submit" value="Create Google Play Autorenew Tokens Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="systemsettingstable" value="true" >
	<input type="submit" value="Create System Settings Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="teamaccountstable" value="true" >
	<input type="submit" value="Create Team Accounts Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="teamadminstable" value="true" >
	<input type="submit" value="Create Team Admins Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="teammemberstable" value="true" >
	<input type="submit" value="Create Team Members Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="teaminvitationstable" value="true" >
	<input type="submit" value="Create Team Invitations Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="salestaxtable" value="true" >
	<input type="submit" value="Create Sales Tax Table">
	</form>
	
	<form action="manageDB.php" method="POST">
	<input type="hidden" name="teamsubscriptioncreditstable" value="true" >
	<input type="submit" value="Create Team Subscription Credits Table">
	</form>

    <form action="manageDB.php" method="POST">
        <input type="hidden" name="alltableindexes" value="true">
        <input type="submit" value="Create All Table Indexes">
    </form>';
            
}

function createAllTables()
{
    createListTable();
    createListSettingsTable();
    createUserTable();
    createUserMigrationTable();
    createSessionTable();
    createInvitationTable();
    createPasswordResetTable();
    createEmailVerificationTable();
    createEventTable();
	createTaskTable();
    createTaskitoTable();
    createChangelogTable();
    createCommentTable();
    createEmailNotificationTable();
	createContextTable();
    createTagTable();
    createTaskNotificationTable();
	createPromoCodesTable();
	createPromoCodeHistoryTable();
    createGiftCodesTable();
    createStripeGiftPaymentHistoryTable();
	createSubscriptionsTable();
	createUserPaymentSystemTable();
	createStripeUserInfoTable();
	createStripePaymentHistoryTable();
	createIAPPaymentHistoryTable();
    createIAPAutorenewReceiptTable();
	createAutorenewHistoryTable();
	createUserAccountLog();
    createUserDeviceTable();
	createBouncedEmailsTable();
	createUserMaintenanceTable();
    createSystemNotificationTable();
	createReferralsTable();
	createReferralUnsubscribersTable();
	createReferralCreditHistoryTable();
	createGooglePlayPaymentHistoryTable();
    createGooglePlayAutorenewTokensTable();
	createSystemSettingsTable();
	
	createTeamAccountsTable();
	createTeamAdminsTable();
	createTeamMembersTable();
	createTeamInvitationsTable();
	createSalesTaxTable();
	createTeamSubscriptionCreditsTable();
	
    createAllTableIndexes();
}
    
//create database
function createDB()
{
    if(TDOTableManager::createDatabase() == false)
        echo "Unable to create database<br/>";
    
    createAllTables();
}

//create tables
function createUserTable()
{
	if(TDOTableManager::createUserTable() == false)
        echo "Unable to create user table<br/>";
}
function createUserMigrationTable()
{
    if(TDOTableManager::createUserMigrationTable() == false)
        echo "Unable to create user migration table<br/>";
}
function createSessionTable()
{
	if(TDOTableManager::createSessionTable() == false)
		echo "Unable to create session table<br/>";
}
function createListTable()
{
	if(TDOTableManager::createListTable() == false)
		echo "Unable to create list table<br>";
}
function createInvitationTable()
{
	if(TDOTableManager::createInvitationTable() == false)
		echo "Unable to create invitation table<br>";
}
function createPasswordResetTable()
{
    if(TDOTableManager::createPasswordResetTable() == false)
        echo "Unable to create reset password table<br>";
}
function createEmailVerificationTable()
{
    if(TDOTableManager::createEmailVerificationTable() == false)
        echo "Unable to create email verification table<br>";
}

function createEventTable()
{
	if (TDOTableManager::createEventTable() == false)
		echo "Unable to create event table<br/>";
}
function createTaskTable()
{
	if (TDOTableManager::createTaskTable() == false)
		echo "Unable to create task table<br/>";
}

function createTaskitoTable()
{
    if (TDOTableManager::createTaskitoTable() == false)
        echo "Unable to create taskito table<br/>";
}

function createChangelogTable()
{
	if (TDOTableManager::createChangeLogTable() == false)
		echo "Unable to create changelog table<br/>";
}
function createCommentTable()
{
    if(TDOTableManager::createCommentTable() == false)
        echo "Unable to create comment table<br>";
}
function createEmailNotificationTable()
{
    if(TDOTableManager::createEmailNotificationTable() == false)
        echo "Unalbe to create notification table<br>";
}
function createListSettingsTable()
{
    if(TDOTableManager::createListSettingsTable() == false)
        echo "Unable to create list settings table<br>";
}
function createContextTable()
{
	if(TDOTableManager::createContextTable() == false)
		echo "Unable to create context settings table<br>";
}
function createTagTable()
{
    if(TDOTableManager::createTagTable() == false)
        echo "Unable to create tags table<br>";
}
function createTaskNotificationTable()
{
    if(TDOTableManager::createTaskNotificationTable() == false)
        echo "Unable to create task notifications table<br>";
}
function createPromoCodesTable()
{
	if (TDOTableManager::createPromoCodesTable() == false)
		echo "Unable to create the promo codes table<br/>";
}
function createPromoCodeHistoryTable()
{
	if (TDOTableManager::createPromoCodeHistoryTable() == false)
		echo "Unable to create the promo code history table<br/>";
}
function createGiftCodesTable()
{
    if(TDOTableManager::createGiftCodesTable() == false)
        echo "Unable to create the gift codes table<br/>";
}
function createStripeGiftPaymentHistoryTable()
{
    if(TDOTableManager::createStripeGiftPaymentHistoryTable() == false)
        echo "Unable to create the stripe payment history table<br/>";
}

function createSubscriptionsTable()
{
	if (TDOTableManager::createSubscriptionsTable() == false)
		echo "Unable to create the subscriptions table<br/>";
}
//function createSubscriptionMembersTable()
//{
//	if (TDOTableManager::createSubscriptionMembersTable() == false)
//		echo "Unable to create the subscription members table<br/>";
//}
function createSubscriptionInvitationsTable()
{
	if (TDOTableManager::createSubscriptionInvitationsTable() == false)
		echo "Unable to create the subscription invitations table<br/>";
}
function createUserPaymentSystemTable()
{
	if (TDOTableManager::createUserPaymentSystemTable() == false)
		echo "Unable to create the user payment system table<br/>";
}
function createStripeUserInfoTable()
{
	if (TDOTableManager::createStripeUserInfoTable() == false)
		echo "Unable to create the Stripe user info table<br/>";
}
function createStripePaymentHistoryTable()
{
	if (TDOTableManager::createStripePaymentHistoryTable() == false)
		echo "Unable to create the Stripe payment history table<br/>";
}
function createIAPPaymentHistoryTable()
{
	if (TDOTableManager::createIAPPaymentHistoryTable() == false)
		echo "Unable to create the IAP payment history table<br/>";
}
function createIAPAutorenewReceiptTable()
{
    if(TDOTableManager::createIAPAutorenewReceiptTable() == false)
        echo "Unable to create the IAP autorenew receipt table<br/>";
}
function createAutorenewHistoryTable()
{
	if (TDOTableManager::createAutorenewHistoryTable() == false)
		echo "Unable to create the Autorenew History table<br/>";
}
function createUserAccountLog()
{
	if (TDOTableManager::createUserAccountLog() == false)
		echo "Unable to create the User Account Log table<br/>";
}
function createUserDeviceTable()
{
	if (TDOTableManager::createUserDeviceTable() == false)
		echo "Unable to create the User Device Table<br/>";
}
function createBouncedEmailsTable()
{
	if (TDOTableManager::createBouncedEmailsTable() == false)
		echo "Unable to create the Bounced Emails Table<br/>";
}
function createUserMaintenanceTable()
{
	if (TDOTableManager::createUserMaintenanceTable() == false)
		echo "Unable to create the User Maintenance Table<br/>";
}
function createSystemNotificationTable()
{
    if(TDOTableManager::createSystemNotificationTable() == false)
        echo "Unable to create the User Maintenance Table<br/>";
}
function createReferralsTable()
{
	if (TDOTableManager::createReferralsTable() == false)
		echo "Unable to create the Referrals Table<br/>";
}
function createReferralUnsubscribersTable()
{
	if (TDOTableManager::createReferralUnsubscribersTable() == false)
		echo "Unable to create the Referral Unsubscribers Table<br/>";
}
function createReferralCreditHistoryTable()
{
	if (TDOTableManager::createReferralCreditHistoryTable() == false)
		echo "Unable to create the Referral Credit History Table<br/>";
}
    
function createAllTableIndexes()
{
    if(TDOTableManager::createAllTableIndexes() == false)
        echo "Unable to create Task indexes<br/>";
}
	
function createGooglePlayPaymentHistoryTable()
{
	if (TDOTableManager::createGooglePlayPaymentHistoryTable() == false)
		echo "Unable to create the Google Play Payment History table<br/>";
}
	
function createGooglePlayAutorenewTokensTable()
{
	if (TDOTableManager::createGooglePlayAutorenewTokensTable() == false)
		echo "Unable to create the Google Play Autorenew Tokens table<br/>";
}
	
function createSystemSettingsTable()
{
	if (TDOTableManager::createSystemSettingsTable() == false)
		echo "Unable to create the System Settings table<br/>";
}
	
	
function createTeamAccountsTable()
{
	if (TDOTableManager::createTeamAccountsTable() == false)
		echo "Unable to create the Team Accounts table<br/>";
}
	
function createTeamAdminsTable()
{
	if (TDOTableManager::createTeamAdminsTable() == false)
		echo "Unable to create the Team Admins table<br/>";
}
	
function createTeamMembersTable()
{
	if (TDOTableManager::createTeamMembersTable() == false)
		echo "Unable to create the Team Members table<br/>";
}
	
function createTeamInvitationsTable()
{
	if (TDOTableManager::createTeamInvitationsTable() == false)
		echo "Unable to create the Team Invitations table<br/>";
}
	
function createSalesTaxTable()
{
	if (TDOTableManager::createSalesTaxTable() == false)
		echo "Unable to create the Sales Tax table<br/>";
}
	
function createTeamSubscriptionCreditsTable()
{
	if (TDOTableManager::createTeamSubscriptionCreditsTable() == false)
		echo "Unable to create the Team Subscription Credits Table<br/>";
}
	
	
    
//delete table
function deleteTable($table)
{
    if(TDOTableManager::deleteTable($table) == false)
        echo "Unable to delete table $table<br/>";
}
//delete index
function deleteIndex($index, $table)
{
    if(TDOTableManager::deleteIndex($index, $table) == false)
        echo "Unable to delete index $index<br/>";
}
    
//delete DB
if(isset($_POST["deletedb"]))
{
    if(TDOTableManager::deleteDatabase() == false)
        echo "Unable to delete database<br/>";
}
    
if(isset($_POST["createAllTables"]))
{
    createAllTables();
}

//handle post request
if(isset($_POST["createdb"]))
	createDB();
	
if(isset($_POST["usertable"]))
    createUserTable();

if(isset($_POST["usermigrationtable"]))
    createUserMigrationTable();
    
if(isset($_POST["systemnotificationtable"]))
    createSystemNotificationTable();
	
if(isset($_POST["referralstable"]))
	createReferralsTable();
	
if(isset($_POST["referralunsubscriberstable"]))
	createReferralUnsubscribersTable();
	
if(isset($_POST["referralcredithistorytable"]))
	createReferralCreditHistoryTable();
    
if(isset($_POST["sessiontable"]))
	createSessionTable();
	
if(isset($_POST["listtable"]))
	createListTable();

if(isset($_POST["invitationtable"]))
	createInvitationTable();
    
if(isset($_POST["resetpwdtable"]))
    createPasswordResetTable();

if(isset($_POST["emailverificationtable"]))
    createEmailVerificationTable();

if(isset($_POST["eventtable"]))
	createEventTable();
	
if(isset($_POST["tasktable"]))
	createTaskTable();

if(isset($_POST["taskitotable"]))
	createTaskitoTable();
    
if(isset($_POST["changelogtable"]))
	createChangelogTable();
	
if(isset($_POST["commenttable"]))
	createCommentTable();

if(isset($_POST["notificationtable"]))
    createEmailNotificationTable();

if(isset($_POST["listsettingstable"]))
    createListSettingsTable();
   
if(isset($_POST["contexttable"]))
    createContextTable();
    
if(isset($_POST["tagtable"]))
    createTagTable();

if(isset($_POST["tasknotificationtable"]))
    createTaskNotificationTable();
	
if (isset($_POST["promocodestable"]))
	createPromoCodesTable();

if (isset($_POST["promocodestable"]))
	createPromoCodeHistoryTable();
	
if(isset($_POST["giftcodestable"]))
    createGiftCodesTable();
    
if(isset($_POST["stripegiftpaymenthistorytable"]))
    createStripeGiftPaymentHistoryTable();
    
if (isset($_POST["subscriptionstable"]))
	createSubscriptionsTable();
	
//if (isset($_POST["subscriptionmemberstable"]))
//	createSubscriptionMembersTable();
	
if (isset($_POST["subscriptioninvitationstable"]))
	createSubscriptionInvitationsTable();
	
if (isset($_POST["userpaymentsystemtable"]))
	createUserPaymentSystemTable();
	
if (isset($_POST["stripeuserinfotable"]))
	createStripeUserInfoTable();
	
if (isset($_POST["stripepaymenthistorytable"]))
	createStripePaymentHistoryTable();
	
if (isset($_POST["iappaymenthistorytable"]))
	createIAPPaymentHistoryTable();

if(isset($_POST["iapautorenewreceipttable"]))
    createIAPAutorenewReceiptTable();
	
if(isset($_POST["googleplaypaymenthistorytable"]))
	createGooglePlayPaymentHistoryTable();

if(isset($_POST["googleplayautorenewtokenstable"]))
	createGooglePlayAutorenewTokensTable();

if(isset($_POST["systemsettingstable"]))
	createSystemSettingsTable();
	
	
if(isset($_POST["teamaccountstable"]))
	createTeamAccountsTable();
	
if(isset($_POST["teamadminstable"]))
	createTeamAdminsTable();

if(isset($_POST["teammemberstable"]))
	createTeamMembersTable();

if(isset($_POST["teaminvitationstable"]))
	createTeamInvitationsTable();

if(isset($_POST["salestaxtable"]))
	createSalesTaxTable();
	
if(isset($_POST["teamsubscriptioncreditstable"]))
	createTeamSubscriptionCreditsTable();

	
if(isset($_POST["alltableindexes"]))
    createAllTableIndexes();
    
    
if(isset($_POST["delete"]))
    deleteTable($_POST['delete']);

    
if(isset($_POST["deleteindex"]))
{
    if(isset($_POST['indextablename']))
    {
        deleteIndex($_POST['deleteindex'], $_POST['indextablename']);
    }
}


//draw html
	print '<a href="." >< Todo Cloud</a><br/>';
	
	
$link = TDOUtil::getDBLink(false);
if (!$link) 
{
    die('Could not connect: ' . mysql_error());
}

$sql = "SHOW TABLES FROM ".DB_NAME;
$result = mysql_query($sql, $link);


	
if ($result) 
{
	print '<div id="buttons">';
	print '<fieldset ><legend><b>Actions</b></legend>';
	drawDeleteDBButton();
    drawCreateAllTablesButton();
	drawCreateTableButtons();
	print '</fieldset>';
	print '</div>';
	
		
	print '<div id="tables">';
	print '<fieldset>';
	print '<legend>Existing tables</legend>';	
	echo "<table cellpadding=\"10\">";
	while ($row = mysql_fetch_row($result)) 
	{
		echo "<tr>";
		echo "<td>{$row[0]}</td>";
		echo "<form action=\"manageDB.php\" method=\"POST\">";
		echo "<input type=\"hidden\" name=\"delete\" value=\"{$row[0]}\">";
		echo '<td><input type="submit" value="Delete"></td>';
		echo "</form>";
		echo "</tr>";
        
        $indexsql = "SHOW INDEXES FROM ".$row[0]. " FROM ".DB_NAME;

        $indexResult = mysql_query($indexsql, $link);
        
        if($indexResult)
        {
            while ($indexrow = mysql_fetch_row($indexResult))
            {
                if($indexrow[2] != "PRIMARY")
                {
                    echo "<tr>";
                    echo "<td style='color:brown;'>{$indexrow[2]}</td>";
                    echo "<form action=\"manageDB.php\" method=\"POST\">";
                    echo "<input type=\"hidden\" name=\"deleteindex\" value=\"{$indexrow[2]}\">";
                    echo "<input type=\"hidden\" name=\"indextablename\" value=\"{$row[0]}\">";
                    echo '<td><input type="submit" value="Delete"></td>';
                    echo "</form>";
                    echo "</tr>";
                }
            }
            mysql_free_result($indexResult);
        }
        else
            error_log("error getting index " . mysql_error());
	}
	echo "</table>";
		
	mysql_free_result($result);
    
    
    
    
    
}
else
{
    print '<fieldset>';
    print '<legend>You don\'t have a database :(</legend>';
    echo "<form action=\"manageDB.php\" method=\"POST\">";
    echo "<input type=\"hidden\" name=\"createdb\" value=\"true\">";
    echo "<input type=\"submit\" value=\"Create database\">";
    echo "</form>";	
    print '</fieldset>';
}
	
	print '</legend>';	
	print '</div>';
	
	TDOUtil::closeDBLink($link);

?>

<style>
	div {
		padding:10px;
		margin: 10px;
	}
	fieldset {
		width:300px;
	}
	#buttons {
		float:left;
	}
	
	#tables {
		float:left;
	}
	
	a:link {text-decoration: none;color:black;}
	a:active {text-decoration: none;color:black;}
	a:visited {text-decoration: none;color:black;}
	a:hover {text-decoration: underline;color:maroon;}
	
</style>