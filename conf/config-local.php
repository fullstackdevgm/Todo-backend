<?php

define('TDO_SERVER_TYPE', 'local');

// Domains used in Todo Cloud

define('TDO_DOMAIN_USER_ACCOUNTS',      'tdo_user_accounts');
define('TDO_DOMAIN_USER_SESSIONS',      'tdo_user_sessions');
define('TDO_DOMAIN_LISTS',              'tdo_lists');
define('TDO_DOMAIN_EVENTS',             'tdo_events');
define('TDO_DOMAIN_TASKS',              'tdo_tasks');
define('TDO_DOMAIN_INVITATIONS',		'tdo_invitations');

define('EMAIL_FROM_NAME', 'Todo Cloud');
define('EMAIL_FROM_ADDR', 'no-reply@todo-cloud.com');
define('EMAIL_SITE_NAME', 'Todo Cloud');

define('INCOMING_MAIL_ADDR', 'newtask.todo-cloud.com');

define('SITE_BASE_URL', '127.0.0.1:8989');
define('SITE_PROTOCOL', 'http://');

define('CACERTS_PATH', '/etc/ssl/certs/');

define('TERMS_OF_SERVICE_URL', '/terms/');
define('PRIVACY_POLICY_URL', '/privacy/');

//define('SUPPORT_SITE_BASE_URL', 'localhost/~calvin/auth');
define('SUPPORT_SITE_BASE_URL', 'auth.appigo.com');
define('SUPPORT_EMAIL_FROM_NAME', 'Appigo Support');
define('SUPPORT_EMAIL_FROM_ADDR', 'no-reply@appigo.com');
define('SUPPORT_EMAIL_SITE_NAME', 'Appigo Support');
define('SUPPORT_SITE_FULL_URL', 'http://support.appigo.com/');

// This is blank so local machines will work with each user's name, don't change it
define ('SITE_BASE_S3_URL', '');
define ('SITE_BASE_S3_URL_EMAIL', '');
define ('S3_IMAGE_UPLOAD_BUCKET', 'dev.todopro.com');
define ('S3_BASE_USER_IMAGE_URL', 'https://s3.amazonaws.com/dev.todopro.com/user-images/profile-images/');
define ('S3_BASE_USER_IMAGE_URL_LARGE', 'https://s3.amazonaws.com/dev.todopro.com/user-images/profile-images-large/');
define ('S3_BASE_TMP_USER_IMAGE_URL', 'https://s3.amazonaws.com/dev.todopro.com/user-images/profile-images-tmp/');
define ('S3_BASE_TMP_USER_IMAGE_URL_LARGE', 'https://s3.amazonaws.com/dev.todopro.com/user-images/profile-images-tmp-large/');

define('FB_REDIRECT_URL', 'https://apps.facebook.com/todopilot/');
define('FB_PLANO_APP_ID', '292669120832371');
define ('FB_PILOT_APP_ID', '251947294917033');

if(isset($_SERVER['SERVER_NAME']))
    $app_url = 'https://'.$_SERVER['SERVER_NAME'];
//$app_url = 'http://localhost/~nicole/plunkboard';
//$app_url = 'http://localhost/~calvin/pb';

define('AMAZON_AWS_KEY', '12AC2HZ9DWM2B9YRE7R2');
define('AMAZON_AWS_SECRET', '+CrKPqnI1kcWC7wzOoQiLX2w+JCLFFJo0dXY4x88');

define('TDO_JWT_SECRET', 'local-7VuQxXqZruwN4eArXjq$x){7T]#NF$6bB*i9k43%2xP;');

// Stripe Constants
////////////////////////////////////////////////////////////////////////////
//                                                                        //
//     **      **    **    ******   **   **  ******  **   **   ******     //
//     **  **  **  **  **  **   **  **** **    **    **** **  **          //
//     **  **  **  ******  ******   ** ****    **    ** ****  **  ***     //
//       **  **    **  **  **   **  **   **  ******  **   **   ******     //
//                                                                        //
////////////////////////////////////////////////////////////////////////////
// THESE ARE FOR TESTING ONLY.  COMMENT THESE OUT BEFORE LAUNCHING!!!!
define('APPIGO_STRIPE_SECRET_KEY', 'DUtytkg84Q5C0KhnhDgxuyqwa5NyVO64');
define('APPIGO_STRIPE_PUBLIC_KEY', 'pk_PAFUEjoj7cCw2Bb7w5ZP3i4QpHjWI');

// THE FOLLOWING ARE FOR PRODUCTION
//define('APPIGO_STRIPE_SECRET_KEY', 'wKzScDg8KY4LL0ScXrG1LmGtYeRe6xvZ');
//define('APPIGO_STRIPE_PUBLIC_KEY', 'pk_d9smE03J80d7tKfbPoA8QrTV3RKls');

// don't use ? > php end to avoid emitting unwanted spaces
