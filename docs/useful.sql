/* List table sizes */
SELECT table_name AS "Table", 
round(((data_length + index_length) / 1024 / 1024), 2) "Size in MB" 
FROM information_schema.TABLES 
WHERE table_schema = "tdo_db"
ORDER BY (data_length + index_length) desc;


/* select list & settings by username */
select u.username, l.name, s.* from 
tdo_lists l 
inner join tdo_list_memberships m on m.listid = l.listid
inner join tdo_user_accounts u on u.userid = m.userid
inner join tdo_list_settings s on s.listid = l.listid
where u.username = 'dave.welch@mcubedlabs.com';


/* Select google play subscriptions by email  */
select u.username, from_unixtime(gp.purchase_timestamp), gp.* from 
tdo_googleplay_payment_history gp
inner join tdo_user_accounts u on u.userid = gp.userid
where u.username = 'cobus.swanepoel@bluewin.ch';

/* View subscriptions by email */
select u.userid, u.username, from_unixtime(s.expiration_date) as expires, s.*
from tdo_subscriptions s
inner join tdo_user_accounts u on u.userid = s.userid
where u.username = 'cobus.swanepoel@bluewin.ch';



-- find user details and subcription details
select u.*, ' -- ' as 'SubcriptionDetails', s.* from tdo_user_accounts u
	inner join tdo_subscriptions s on s.userid = u.userid
	where u.username = 'dave.welch@mcubedlabs.com';

-- Look up user detail for a team (TODO: add a where clause)
select u.* from 
	tdo_team_members m
	inner join tdo_team_accounts a on a.teamid = m.teamid
	inner join tdo_user_accounts u on u.userid = m.userid;




/* Update a subscription expiration date */
/*
update tdo_subscriptions 
set expiration_date = UNIX_TIMESTAMP('2014-09-09 09:00AM')
where subscriptionid='4471d838-3f1c-b0a8-ead6-000058aa3c04';
*/





select u.username, from_unixtime(gp.purchase_timestamp), gp.* from 
tdo_googleplay_payment_history gp
inner join tdo_user_accounts u on u.userid = gp.userid
where u.username = 'cobus.swanepoel@bluewin.ch';



select u.userid, u.username, from_unixtime(s.expiration_date) as expires, s.*
from tdo_subscriptions s
inner join tdo_user_accounts u on u.userid = s.userid
where u.username = 'cobus.swanepoel@bluewin.ch';
SELECT UNIX_TIMESTAMP(STR_TO_DATE('Aug 13 2015 06:35PM', '%M %d %Y %h:%i%p')); 
/* 1439490900 */
select * from tdo_subscriptions s where s.userid = 'dffe4e0c-5168-4604-8650-383935c9c906';
update tdo_subscriptions 
set expiration_timestamp = UNIX_TIMESTAMP(STR_TO_DATE('Aug 13 2015 06:35PM', '%M %d %Y %h:%i%p'))
where userid = 'dffe4e0c-5168-4604-8650-383935c9c906';






-- How to resolve customers recurring billing issue (due to outage or something)
select * from tdo_user_accounts where userid = '625b8aca-c16b-af49-aaea-00002785bd0b';
select from_unixtime(expiration_date) as expiration, s.* from tdo_subscriptions s where userid='625b8aca-c16b-af49-aaea-00002785bd0b';

-- find renew history by user ID
select h.*, ' -- ' as 'UserDetails', u.*, ' -- ' as 'SubcriptionDetails', s.* from tdo_autorenew_history h 
	inner join tdo_subscriptions s on s.subscriptionid = h.subscriptionid
	inner join tdo_user_accounts u on u.userid = s.userid
	where u.username = 'dave.welch@mcubedlabs.com';


/* WARNING: Delete all change log entries older than Aug 1, 2013
/* 
delete from tdo_change_log where mod_date < unix_timestamp('2013-08-01 00:00:01');
*/ 
/* WARNING: Delete all completed_tasks older than Aug 1, 2013
/* 
delete from tdo_completed_tasks where completiondate < unix_timestamp('2013-08-01 00:00:01');
*/ 
/* WARNING: Delete all tdo_context_assignments older than Aug 1, 2013
/* 
delete from tdo_context_assignments where context_assignment_timestamp < unix_timestamp('2013-08-01 00:00:01');
*/ 
