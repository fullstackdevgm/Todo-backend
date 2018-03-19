// function define(name, value) {
//     Object.defineProperty(exports, name, {
//         value: value,
//         enumerable: true
//     })
// }

// The following domains are domains that the
// system automatically sends out verification
// emails when a person's premium account is
// nearing expiration. The email asks the person
// to verify their email address once per year and
// as long as they do, the system will extend their
// premium account another year. We implemented this
// years ago to support giving Apple employees a
// free Todo Cloud account. A few of the domains are
// also from the founders/owners of Appigo. We should
// eventually move this into our environment parameters,
// but for now, this will work.
//
// NOTE: Domains listed here must be lower case.
exports.VIPDomains = [
    "apple.com", // For Apple employees
    "filemaker.com", // Also for Apple employees
    "musipal.com", // Boyd Timothy's personal domain name
    "gaisford.com", // Calvin Gaisford's personal domain name
    "karren.com" // Tom Karren's personal domain name
]

// The number of premium account months a VIP is given when
// they verify their email address.
exports.VIPExtensionIntervalInMonths = 12

exports.maxFirstNameLength = 60
exports.maxLastNameLength = 60
exports.maxPasswordLength = 64
exports.minPasswordLength = 6
exports.maxPagedTasks = 100
exports.maxUsernameLength = 100
exports.maxCreateBulkTasks = 20

exports.defaultEmailFromAddress = 'Todo Cloud <no-reply@todo-cloud.com>'
exports.defaultEmailSubject = 'Message from Todo Cloud'
exports.defaultPagedTasks = 20
exports.defaultTimeZone = 'America/Denver'

exports.supportedLocales = ["en-US", "de-DE", "es-MX", "fr-FR", "it-IT", "ja-JP", "pt-BR", "ru-RU", "zh-CN", "zh-TW"]

exports.passwordResetTimeoutInSeconds = 604800 // 7 days

exports.profileImageSizeLimitInBytes = 131072 // 128KB Limit

exports.SystemUserID = {
    MeUser: "ME",
    AllUser: "4BD35E04-8885-4546-8AC3-A42CCDCCEALL",
    UnassignedUser: "142F63FA-F450-4F0E-A5E4-E0UNASSIGNED"
}

exports.httpMethods = {
    GET    : 'GET',
    POST   : 'POST',
    PUT    : 'PUT',
    DELETE : 'DELETE'
}

exports.ChangeLogItemType = {
    List: 1,
    User: 2,
    Event: 3,
    Comment: 4,
    Note: 5,
    Invitation: 6,
    Task: 7,
    Context: 8,
    Notification: 9,
    Taskito: 10
}

exports.AccountChangeLogItemType = {
    Password: 1,
    Username: 2,
    Name: 3,
    ExpirationDate: 4,
    PurchaseReceipt: 5,
    DowngradeToFreeAccount: 6,
    MailPasswordReset: 7,
    PasswordReset: 8,
    ClearBounceEmail: 9,
    EnableRemigrate: 10,
    VIPFreePremiumAccount: 11,
    ConvertSubscriptionToGiftCode: 12,
    JoinTeam: 13,
    LeaveTeam: 14,
    EmailOptOut: 15,
    Impersonation: 16
}

exports.ChangeLogType = {
    Add: 1,
    Modify: 2,
    Delete: 3,
    Restore: 4
}

exports.ChangeLogLocation = {
    API: 1,
    CalDav: 2,
    Sync: 3,
    Migration: 4,
    Email: 5
}

exports.SystemSettingName = {
    SubscriptionTrialDurationInSeconds: 'SubscriptionTrialDurationInSeconds',
    SubscriptionMonthlyDurationInSeconds: 'SubscriptionMonthlyDurationInSeconds',
    SubscriptionYearlyDurationInSeconds: 'SubscriptionYearlyDurationInSeconds',
    MonthlyPricePerUser: "SYSTEM_SETTING_MONTHLY_PRICE_PER_USER",
    YearlyPricePerUser: "SYSTEM_SETTING_YEARLY_PRICE_PER_USER"
}

exports.SystemSettingDefault = {
    TrialDurationInSeconds: 86400 * 14,
    MonthlyDurationInSeconds: 86400 * 31,
    YearlyDurationInSeconds: 86400 * 365,
    PremiumMonthlyPriceInUSD: 1.99,
    PremiumYearlyPriceInUSD: 19.99
}

exports.SubscriptionType = {
    Unknown:    0,
    Month:      1,
    Year:       2
}

exports.SubscriptionTypeString = {
    Month:      "month",
    Year:       "year"
}

exports.SubscriptionLevel = {
    Expired:    0,
    Unknown:    1,
    Trial:      2,
    Promo:      3,
    Paid:       4,
    Migrated:   5,
    Pro:        6,
    Gift:       7
}

exports.PaymentSystemType = {
    Unknown:                0,
    Stripe:                 1,
    AppleIAP:               2,
    PayPal:                 3,  // Never used
    AppleIAPAutorenew:      4,
    GooglePlayAutorenew:    5,
    Team:                   6,  // Used to communicate with clients about team subscriptions
    Whitelisted:            7   // Used to communicate with clients about subscriptions
}

exports.ListMembershipType = {
    Viewer:     0,
    Member:     1,
    Owner:      2
}

exports.UserAccountAction = {
    PasswordChanged:        1,
    UsernameChanged:        2,
    NameChanged:            3,
    ExpirationDate:         4,
    PurchaseReceipt:        5,
    DowngradeToFree:        6,
    MailPasswordReset:      7,
    PasswordReset:          8,
    ClearBounceEmail:       9,
    EnableReMigrate:        10,
    VIPFreeSubscription:    11,
    ConvertSubToGiftCode:   12,
    JoinTeam:               13,
    LeaveTeam:              14,
    EmailOptOut:            15,
    Impersonation:          16
}

exports.TaskAdvancedRecurrenceType = {
    EveryXDaysWeeksMonths:  0,
    TheXOfEachMonth:        1,
    EveryMonTueEtc:         2,
    Unknown:                3
}

exports.TaskLocationAlertType = {
    None:       0,
    Arriving:   1,
    Leaving:    2
}

exports.TaskPriority = {
    High:   1,
    Medium: 5,
    Low:    9,
    None:   0
}

exports.TaskRecurrenceType = {
    None:           0,
    Weekly:         1,
    Monthly:        2,
    Yearly:         3,
    Daily:          4,
    Biweekly:       5,
    Bimonthly:      6,
    Semiannually:   7,
    Quarterly:      8,
    WithParent:     9,
    Advanced:       50
}

exports.TaskType = {
    Normal:         0,
    Project:        1,
    CallContact:    2,
    SMSContact:     3,
    EmailContact:   4,
    VisitLocation:  5,
    URL:            6,
    Checklist:      7,
    Custom:         8, // imported via third party app (AppigoPasteboard)
    Internal:       9
}

exports.SortType = {
    DatePriorityAlpha:  0,
    PriorityDateAlpha:  1,
    Alphabetical:       2,
    Manual:             3
}

exports.SmartListColor = {
    Blue: "33, 150, 243",
    Gray: "158, 158, 158",
    Orange: "238, 108, 0",
    Yellow: "255, 238, 88"
}

exports.SmartListJSONFilter = {
    Everything: "{\"completedTasks\":{\"type\":\"all\",\"period\":\"1month\"},\"showListForTasks\":true}",
    Important: "{\"completedTasks\":{\"type\":\"all\",\"period\":\"1day\"},\"filterGroups\":[{\"starred\":true}],\"showListForTasks\":true}",
    Focus: "{\"completedTasks\":{\"type\":\"all\",\"period\":\"3days\"},\"filterGroups\":[{\"dueDate\":{\"type\":\"before\",\"relation\":\"relative\",\"period\":\"day\",\"value\":3}}],\"showListForTasks\":true}",
    Someday: "{\"completedTasks\":{\"type\":\"all\",\"period\":\"1day\"},\"filterGroups\":[{\"dueDate\":{\"type\":\"none\"}}],\"showListForTasks\":true}"
}

exports.SmartListCompletedTasksPeriod = {
    None:           "none",
    OneDay:         "1day",
    TwoDays:        "2days",
    ThreeDays:      "3days",
    OneWeek:        "1week",
    TwoWeeks:       "2weeks"
}

exports.SmartListCompletedTasksFilterType = {
    All:            "all",
    Active:         "active",
    Completed:      "completed"
}

exports.SmartListFilterType = {
    Action:         "actionType",
    Assignment:     "assignment",
    CompletedDate:  "completedDate",
    DueDate:        "dueDate",
    Location:       "hasLocation",
    ModifiedDate:   "modifiedDate",
    Name:           "name",
    Note:           "note",
    Priority:       "priority",
    Recurrence:     "hasRecurrence",
    Starred:        "starred",
    StartDate:      "startDate",
    Tags:           "tags",
    TaskType:       "taskType"
}

exports.SmartListComparatorType = {
    And:            "and",
    Or:             "or"
}

exports.SmartListDateFilterType = {
    None:           "none",
    Any:            "any",
    Is:             "is",
    Not:            "not",
    After:          "after",
    Before:         "before"
}

exports.SmartListDateRelationType = {
    Exact:          "exact",
    Relative:       "relative"
}

exports.SmartListTaskTypeFilterType = {
    Normal    : "normal",
    Project   : "project",
    Checklist : "checklist"
}

exports.SmartListDatePeriod = {
    Day:            "day",
    Week:           "week",
    Month:          "month",
    Year:           "year"
}

exports.SmartListActionFilterType = {
    None:           "none",
    Contact:        "contact",
    Location:       "location",
    Url:            "url"
}

exports.SmartListPriorityFilterType = {
    None:           "none",
    Low:            "low",
    Medium:         "med",
    High:           "high"
}

exports.TaskFlag = {
    HasDueTime:             0x0001,
    HasNote:                0x0002,
    HasdsDueTime:           0x0004, // Deprecated in 6.0.5. We no longer use the ds_due_date
    HaspsDuetime:           0x0008, // Deprecated in 6.0.5. We no longer use the ps_due_date
    ReadOnly:               0x0010,
    ProjectDueDateHasTime:  0x0020
}

exports.mandrillAPIKey = '702V--AmZNTrGMNsJLZlXw'

///
/// Constants for tasks
///

exports.TasksTable = {
    Normal: "tdo_tasks",
    Completed: "tdo_completed_tasks",
    Deleted: "tdo_deleted_tasks"
}

exports.TasksTableNames = [
    exports.TasksTable.Normal,
    exports.TasksTable.Completed,
    exports.TasksTable.Deleted
]

///
/// Constants used for local-only operations
///

exports.ServerInboxId               = "9F6338F5-94C7-4B04-8E24-8F829UNFILED"
exports.LocalInboxId                = "INBOX"
exports.LocalEverythingSmartListId  = "EVERYTHING"
exports.LocalFocusSmartListId       = "FOCUS"
exports.LocalImportantSmartListId   = "IMPORTANT"
exports.LocalSomedaySmartListId     = "SOMEDAY"


///
/// Constants uses for the sync client
///
exports.SyncMaxSupportedProtocolVersion = 1.3

// The server returns subscription expiration information to us in the number of
// seconds until the expiration date.  Our UI and processes will inform the user
// of upcoming expirations proactively.  This fudge factor is basically going to
// remove 60 seconds off of what the server says which should account for any
// network latency issues.  I've put this here so it will be easy to adjust if
// we need to in the future.
exports.SyncSubscriptionExpirationFudgeFactor       = 60 // 60 seconds

// Only allow 100 tasks per type at a time so that we don't bog down the
// network or the server.
exports.SyncServiceBulkSyncPageTaskCount            = 100

// Some sync error codes
exports.SyncErrorCodeParentTaskNotProject           = 4715
exports.SyncErrorCodeParentTaskNotFound             = 4740


exports.CurrentSyncType = {
    None:           "None",
    Normal:         "Normal",
    Full:           "Full",
    Reset:          "Reset"
}

exports.AdminLevel = {
    None:           0,
    Basic:          47,
    Root:           100
}


exports.SettingCurrentJWTKey                        = "JWT"
exports.SettingSessionTokenKey                      = "SessionToken"
exports.SettingTodoCloudPendingInvitationsCountKey  = "TodoCloudPendingInvitations"
exports.SettingEmailValidatedKey                    = "EmailValidated"
exports.SettingSystemNotificationIdKey              = "SystemNotificationId"

exports.SettingSystemNotificationMessageKey         = "SystemNotificationMessage"
exports.SettingSystemNotificationTimestampKey       = "SystemNotificationTimestamp"
exports.SettingSystemNotificationUrlKey             = "SystemNotificationUrl"

exports.SettingLastResetDataTimeStampKey            = "LastResetDataTimeStamp"

exports.SettingListHashKey                          = "ListHash"
exports.SettingSmartListHashKey                     = "SmartListHash"
exports.SettingUserHashKey                          = "UserHash"
exports.SettingAllTaskTimeStampsKey                 = "AllTaskTimeStamps"
exports.SettingsAllTaskitoTimeStamps                = "AllTaskitoTimeStamps"
exports.SettingsAllNotificationTimeStamps           = "AllNotificationTimeStamps"
exports.SettingCompletedTaskSyncResetKey            = "CompletedTaskSyncReset"
exports.SettingListMembershipHashes                 = "ListMembershipHashes"
