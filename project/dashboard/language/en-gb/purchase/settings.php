<?php
// Heading
$_['heading_title']          = 'Purchase Settings';

// Text
$_['text_success']           = 'Success: You have successfully modified purchase settings!';
$_['text_reset_success']     = 'Settings have been reset to default values successfully!';
$_['text_import_success']    = 'Settings imported successfully!';
$_['text_edit']              = 'Edit Purchase Settings';
$_['text_enabled']           = 'Enabled';
$_['text_disabled']          = 'Disabled';

// Tab
$_['tab_general']            = 'General Settings';
$_['tab_numbering']          = 'Numbering Settings';
$_['tab_notifications']      = 'Notification Settings';
$_['tab_inventory']          = 'Inventory Settings';
$_['tab_integration']        = 'Integration Settings';
$_['tab_approval']           = 'Approval Settings';
$_['tab_reports']            = 'Report Settings';

// Entry - General Settings
$_['entry_auto_approve_limit'] = 'Auto Approval Limit';
$_['entry_require_approval'] = 'Require Approval';
$_['entry_default_payment_terms'] = 'Default Payment Terms';
$_['entry_default_currency'] = 'Default Currency';

// Entry - Numbering Settings
$_['entry_order_prefix']     = 'Purchase Order Prefix';
$_['entry_order_start_number'] = 'Purchase Order Start Number';
$_['entry_requisition_prefix'] = 'Purchase Requisition Prefix';
$_['entry_quotation_prefix'] = 'Quotation Prefix';

// Entry - Notification Settings
$_['entry_email_notifications'] = 'Email Notifications';
$_['entry_notification_emails'] = 'Notification Email Addresses';
$_['entry_low_stock_notification'] = 'Low Stock Notification';

// Entry - Inventory Settings
$_['entry_auto_update_inventory'] = 'Auto Update Inventory';
$_['entry_inventory_method'] = 'Inventory Valuation Method';
$_['entry_reorder_level_days'] = 'Reorder Level Days';

// Entry - Integration Settings
$_['entry_accounting_integration'] = 'Accounting Integration';
$_['entry_expense_account'] = 'Expense Account';
$_['entry_payable_account'] = 'Accounts Payable';

// Entry - Approval Settings
$_['entry_approval_workflow'] = 'Approval Workflow';
$_['entry_approval_levels'] = 'Approval Levels';

// Entry - Report Settings
$_['entry_default_report_period'] = 'Default Report Period';
$_['entry_report_auto_email'] = 'Auto Email Reports';

// Button
$_['button_save']            = 'Save';
$_['button_cancel']          = 'Cancel';
$_['button_reset']           = 'Reset';
$_['button_export']          = 'Export Settings';
$_['button_import']          = 'Import Settings';

// Payment Terms
$_['text_net_30']            = 'Net 30 Days';
$_['text_net_60']            = 'Net 60 Days';
$_['text_net_90']            = 'Net 90 Days';
$_['text_cod']               = 'Cash on Delivery';
$_['text_prepaid']           = 'Prepaid';

// Inventory Methods
$_['text_fifo']              = 'First In, First Out (FIFO)';
$_['text_lifo']              = 'Last In, First Out (LIFO)';
$_['text_weighted_average']  = 'Weighted Average';

// Approval Workflows
$_['text_no_approval']       = 'No Approval';
$_['text_single_approval']   = 'Single Approval';
$_['text_multi_approval']    = 'Multi-Level Approval';

// Report Periods
$_['text_daily']             = 'Daily';
$_['text_weekly']            = 'Weekly';
$_['text_monthly']           = 'Monthly';
$_['text_quarterly']         = 'Quarterly';
$_['text_yearly']            = 'Yearly';

// Help
$_['help_auto_approve_limit'] = 'Maximum amount that can be automatically approved without human intervention';
$_['help_require_approval']  = 'Do all purchase orders require approval before execution?';
$_['help_order_prefix']      = 'Prefix used in purchase order numbering (e.g., PO)';
$_['help_order_start_number'] = 'Starting number for purchase order numbering';
$_['help_notification_emails'] = 'Email addresses that will receive notifications (comma separated)';
$_['help_low_stock_notification'] = 'Send notification when inventory level is low';
$_['help_auto_update_inventory'] = 'Automatically update inventory quantities when goods are received';
$_['help_inventory_method']  = 'Inventory valuation method used for cost calculation';
$_['help_reorder_level_days'] = 'Number of days used in calculating reorder level';
$_['help_accounting_integration'] = 'Enable integration with accounting system';
$_['help_approval_workflow'] = 'Type of approval workflow required';
$_['help_approval_levels']   = 'Number of approval levels required';
$_['help_default_report_period'] = 'Default period for displaying reports';
$_['help_report_auto_email'] = 'Automatically email reports';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify purchase settings!';
$_['error_auto_approve_limit'] = 'Auto approval limit must be a valid number!';
$_['error_start_number']     = 'Start number must be a valid number!';
$_['error_reorder_level_days'] = 'Reorder level days must be a valid number!';
$_['error_invalid_file']     = 'Invalid file!';
$_['error_upload']           = 'File upload error!';

// Success Messages
$_['success_settings_saved'] = 'Settings saved successfully!';
$_['success_settings_reset'] = 'Settings reset successfully!';
$_['success_settings_exported'] = 'Settings exported successfully!';
$_['success_settings_imported'] = 'Settings imported successfully!';

// Info Messages
$_['info_general_settings']  = 'General settings for the purchase system';
$_['info_numbering_settings'] = 'Document numbering settings';
$_['info_notification_settings'] = 'Notification and alert settings';
$_['info_inventory_settings'] = 'Inventory management settings';
$_['info_integration_settings'] = 'Integration settings with other systems';
$_['info_approval_settings'] = 'Approval workflow settings';
$_['info_report_settings']   = 'Report and analytics settings';

// Validation Messages
$_['validation_auto_approve_limit'] = 'Auto approval limit must be a positive number!';
$_['validation_start_number'] = 'Start number must be a positive number!';
$_['validation_reorder_days'] = 'Reorder days must be a positive number!';
$_['validation_email_format'] = 'Invalid email format!';

// Warning Messages
$_['warning_reset_settings'] = 'Are you sure you want to reset all settings to default values?';
$_['warning_import_settings'] = 'Current settings will be replaced. Do you want to continue?';

// Configuration Groups
$_['config_group_general']   = 'General Settings';
$_['config_group_numbering'] = 'Numbering Settings';
$_['config_group_notifications'] = 'Notification Settings';
$_['config_group_inventory'] = 'Inventory Settings';
$_['config_group_integration'] = 'Integration Settings';
$_['config_group_approval']  = 'Approval Settings';
$_['config_group_reports']   = 'Report Settings';

// Advanced Settings
$_['entry_advanced_settings'] = 'Advanced Settings';
$_['entry_debug_mode']       = 'Debug Mode';
$_['entry_log_level']        = 'Log Level';
$_['entry_cache_enabled']    = 'Enable Cache';
$_['entry_api_enabled']      = 'Enable API';
$_['entry_webhook_url']      = 'Webhook URL';

// Security Settings
$_['entry_security_settings'] = 'Security Settings';
$_['entry_require_2fa']      = 'Require Two-Factor Authentication';
$_['entry_session_timeout']  = 'Session Timeout (minutes)';
$_['entry_max_login_attempts'] = 'Maximum Login Attempts';
$_['entry_password_policy']  = 'Password Policy';

// Backup Settings
$_['entry_backup_settings']  = 'Backup Settings';
$_['entry_auto_backup']      = 'Automatic Backup';
$_['entry_backup_frequency'] = 'Backup Frequency';
$_['entry_backup_retention'] = 'Backup Retention (days)';

// Performance Settings
$_['entry_performance_settings'] = 'Performance Settings';
$_['entry_page_size']        = 'Page Size';
$_['entry_query_timeout']    = 'Query Timeout (seconds)';
$_['entry_memory_limit']     = 'Memory Limit (MB)';

// Email Settings
$_['entry_email_settings']   = 'Email Settings';
$_['entry_smtp_host']        = 'SMTP Host';
$_['entry_smtp_port']        = 'SMTP Port';
$_['entry_smtp_username']    = 'SMTP Username';
$_['entry_smtp_password']    = 'SMTP Password';
$_['entry_smtp_encryption']  = 'SMTP Encryption';

// File Upload Settings
$_['entry_upload_settings']  = 'File Upload Settings';
$_['entry_max_file_size']    = 'Maximum File Size (MB)';
$_['entry_allowed_extensions'] = 'Allowed Extensions';
$_['entry_upload_path']      = 'Upload Path';

// Localization Settings
$_['entry_localization']     = 'Localization Settings';
$_['entry_default_language'] = 'Default Language';
$_['entry_default_timezone'] = 'Default Timezone';
$_['entry_date_format']      = 'Date Format';
$_['entry_time_format']      = 'Time Format';
$_['entry_number_format']    = 'Number Format';

// System Information
$_['text_system_info']       = 'System Information';
$_['text_version']           = 'Version';
$_['text_database_version']  = 'Database Version';
$_['text_php_version']       = 'PHP Version';
$_['text_server_info']       = 'Server Information';
$_['text_last_backup']       = 'Last Backup';

// Import/Export
$_['text_export_settings']   = 'Export Settings';
$_['text_import_settings']   = 'Import Settings';
$_['text_select_file']       = 'Select File';
$_['text_export_format']     = 'Export Format';
$_['text_json_format']       = 'JSON';
$_['text_xml_format']        = 'XML';
$_['text_csv_format']        = 'CSV';

// Maintenance
$_['text_maintenance']       = 'Maintenance';
$_['text_clear_cache']       = 'Clear Cache';
$_['text_rebuild_index']     = 'Rebuild Index';
$_['text_optimize_database'] = 'Optimize Database';
$_['text_check_updates']     = 'Check Updates';

// Status
$_['text_status_active']     = 'Active';
$_['text_status_inactive']   = 'Inactive';
$_['text_status_pending']    = 'Pending';
$_['text_status_error']      = 'Error';

// Actions
$_['action_save']            = 'Save';
$_['action_reset']           = 'Reset';
$_['action_export']          = 'Export';
$_['action_import']          = 'Import';
$_['action_test']            = 'Test';
$_['action_backup']          = 'Backup';
$_['action_restore']         = 'Restore';
