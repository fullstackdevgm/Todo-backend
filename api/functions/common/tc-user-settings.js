'use strict'

const TCObject = require('./tc-object')

class TCUserSettings extends TCObject {
    tableName() {
        return 'tdo_user_settings'
    }

    identifierName() {
        return 'userid'
    }

    columnNames() {
        return super.columnNames().concat(['timezone', 'user_inbox', 'tag_filter_with_and', 'task_sort_order', 'start_date_filter', 'focus_show_undue_tasks', 'focus_show_starred_tasks', 'focus_show_completed_date', 'focus_hide_task_date', 'focus_hide_task_priority', 'focus_list_filter_string', 'focus_show_subtasks', 'focus_ignore_start_dates', 'task_creation_email', 'referral_code', 'all_list_hide_dashboard', 'starred_list_hide_dashboard', 'focus_list_hide_dashboard', 'all_list_filter_string', 'default_duedate', 'show_overdue_section', 'skip_task_date_parsing', 'skip_task_priority_parsing', 'skip_task_list_parsing', 'skip_task_context_parsing', 'skip_task_tag_parsing', 'skip_task_checklist_parsing', 'skip_task_project_parsing', 'skip_task_startdate_parsing', 'new_feature_flags', 'email_notification_defaults', 'enable_google_analytics_tracking', 'default_list'])
    }

    requiredColumnNamesForAdding() {
        return super.requiredColumnNamesForAdding().concat(['user_inbox', 'task_creation_email'])
    }
}

module.exports = TCUserSettings