<?php
// Heading
$_['heading_title']     = 'Supplier Groups';

// Text
$_['text_success']      = 'Success: You have modified supplier groups!';
$_['text_list']         = 'Supplier Group List';
$_['text_add']          = 'Add Supplier Group';
$_['text_edit']         = 'Edit Supplier Group';
$_['text_default']      = 'Default';
$_['text_enabled']      = 'Enabled';
$_['text_disabled']     = 'Disabled';
$_['text_yes']          = 'Yes';
$_['text_no']           = 'No';
$_['text_select']       = 'Select';
$_['text_confirm']      = 'Are you sure?';
$_['text_loading']      = 'Loading...';
$_['text_no_results']   = 'No results!';

// Column
$_['column_name']       = 'Group Name';
$_['column_description'] = 'Description';
$_['column_approval']   = 'Requires Approval';
$_['column_sort_order'] = 'Sort Order';
$_['column_action']     = 'Action';
$_['column_supplier_count'] = 'Supplier Count';

// Entry
$_['entry_name']        = 'Group Name';
$_['entry_description'] = 'Description';
$_['entry_approval']    = 'Approve New Suppliers';
$_['entry_sort_order']  = 'Sort Order';

// Tab
$_['tab_general']       = 'General';
$_['tab_data']          = 'Data';

// Button
$_['button_filter']     = 'Filter';
$_['button_copy']       = 'Copy';
$_['button_export']     = 'Export';
$_['button_import']     = 'Import';
$_['button_set_default'] = 'Set as Default';
$_['button_move_suppliers'] = 'Move Suppliers';
$_['button_toggle_approval'] = 'Toggle Approval';

// Help
$_['help_approval']     = 'If enabled, new suppliers in this group will require admin approval before their accounts are activated.';
$_['help_sort_order']   = 'Order in which groups appear in dropdown lists.';
$_['help_name']         = 'Group name as it will appear to suppliers and administrators.';
$_['help_description']  = 'Detailed description of the supplier group and its characteristics.';

// Error
$_['error_permission']  = 'Warning: You do not have permission to access supplier groups!';
$_['error_name']        = 'Group Name must be between 3 and 32 characters!';
$_['error_default']     = 'Warning: This supplier group cannot be deleted as it is currently assigned as the default supplier group!';
$_['error_supplier']    = 'Warning: This supplier group cannot be deleted as it is currently assigned to %s suppliers!';
$_['error_exists']      = 'Warning: Group name already exists!';

// Success
$_['success_add']       = 'Supplier group added successfully!';
$_['success_edit']      = 'Supplier group updated successfully!';
$_['success_delete']    = 'Supplier group deleted successfully!';
$_['success_copy']      = 'Supplier group copied successfully!';
$_['success_export']    = 'Supplier groups exported successfully!';
$_['success_default']   = 'Default group set successfully!';
$_['success_move']      = 'Suppliers moved successfully!';
$_['success_toggle']    = 'Approval status toggled successfully!';

// Info
$_['info_total_groups'] = 'Total Groups';
$_['info_approval_required'] = 'Require Approval';
$_['info_no_approval'] = 'No Approval Required';
$_['info_default_group'] = 'Default Group';
$_['info_group_help']   = 'Use supplier groups to categorize suppliers and apply different rules to them';

// Statistics
$_['text_statistics']   = 'Group Statistics';
$_['text_group_distribution'] = 'Supplier Distribution by Groups';
$_['text_approval_stats'] = 'Approval Statistics';

// Modal
$_['modal_copy_title']  = 'Copy Supplier Group';
$_['modal_copy_text']   = 'Do you want to copy this group?';
$_['modal_move_title']  = 'Move Suppliers';
$_['modal_move_text']   = 'Select the new group to move suppliers to:';
$_['modal_delete_title'] = 'Delete Supplier Group';
$_['modal_delete_text'] = 'Are you sure you want to delete this group? All suppliers will be moved to the default group.';

// Validation
$_['validation_name_required'] = 'Group name is required!';
$_['validation_name_length'] = 'Group name must be between 3 and 32 characters!';
$_['validation_name_unique'] = 'Group name already exists!';
$_['validation_sort_order_numeric'] = 'Sort order must be a number!';

// Notifications
$_['notification_new_supplier_title'] = 'New Supplier Pending Approval';
$_['notification_new_supplier_message'] = 'New supplier %s joined group %s and needs approval';
$_['notification_supplier_approved_title'] = 'Supplier Approved';
$_['notification_supplier_approved_message'] = 'Supplier %s in group %s has been approved';

// Export
$_['export_filename']   = 'supplier_groups_%s.csv';
$_['export_headers']    = array(
    'Group ID',
    'Group Name',
    'Description',
    'Requires Approval',
    'Sort Order',
    'Supplier Count',
    'Date Created'
);

// Import
$_['import_title']      = 'Import Supplier Groups';
$_['import_help']       = 'Upload a CSV file containing supplier group data';
$_['import_sample']     = 'Download Sample File';
$_['import_success']    = 'Successfully imported %d supplier groups!';
$_['import_error']      = 'Error importing file: %s';

// Bulk Actions
$_['bulk_delete']       = 'Delete Selected';
$_['bulk_enable_approval'] = 'Enable Approval for Selected';
$_['bulk_disable_approval'] = 'Disable Approval for Selected';
$_['bulk_export']       = 'Export Selected';
$_['bulk_action_success'] = 'Action performed on %d groups successfully!';

// Search
$_['search_placeholder'] = 'Search supplier groups...';
$_['search_results']    = 'Search results for: %s';
$_['search_no_results'] = 'No results found for: %s';

// Filters
$_['filter_approval']   = 'Filter by Approval';
$_['filter_all']        = 'All';
$_['filter_approval_required'] = 'Requires Approval';
$_['filter_no_approval'] = 'No Approval Required';
$_['filter_sort_order'] = 'Filter by Sort Order';
$_['filter_name']       = 'Filter by Name';

// Permissions
$_['permission_view']   = 'View Supplier Groups';
$_['permission_add']    = 'Add Supplier Group';
$_['permission_edit']   = 'Edit Supplier Group';
$_['permission_delete'] = 'Delete Supplier Group';
$_['permission_export'] = 'Export Supplier Groups';
$_['permission_import'] = 'Import Supplier Groups';

// Activity Log
$_['activity_add']      = 'Added new supplier group: %s';
$_['activity_edit']     = 'Edited supplier group: %s';
$_['activity_delete']   = 'Deleted supplier group: %s';
$_['activity_copy']     = 'Copied supplier group: %s';
$_['activity_set_default'] = 'Set default supplier group: %s';
$_['activity_move_suppliers'] = 'Moved %d suppliers from group %s to %s';

// Dashboard Widget
$_['widget_title']      = 'Supplier Groups';
$_['widget_total']      = 'Total Groups';
$_['widget_active']     = 'Active Groups';
$_['widget_pending']    = 'Need Approval';
$_['widget_view_all']   = 'View All';

// Quick Actions
$_['quick_add']         = 'Quick Add';
$_['quick_edit']        = 'Quick Edit';
$_['quick_duplicate']   = 'Duplicate';
$_['quick_activate']    = 'Activate';
$_['quick_deactivate']  = 'Deactivate';

// Advanced Features
$_['advanced_settings'] = 'Advanced Settings';
$_['auto_approval']     = 'Auto Approval';
$_['approval_workflow'] = 'Approval Workflow';
$_['group_permissions'] = 'Group Permissions';
$_['custom_fields']     = 'Custom Fields';

// Integration
$_['integration_accounting'] = 'Accounting Integration';
$_['integration_crm']   = 'CRM Integration';
$_['integration_inventory'] = 'Inventory Integration';

// Reports
$_['report_group_performance'] = 'Group Performance Report';
$_['report_supplier_distribution'] = 'Supplier Distribution Report';
$_['report_approval_stats'] = 'Approval Statistics Report';

// Email Templates
$_['email_new_supplier_subject'] = 'New Supplier Pending Approval';
$_['email_new_supplier_body'] = 'New supplier %s has joined group %s and needs approval.';
$_['email_approval_subject'] = 'Your Account Has Been Approved';
$_['email_approval_body'] = 'Your account in group %s has been approved. You can now log in to the system.';

// API
$_['api_success']       = 'Operation completed successfully';
$_['api_error']         = 'Error performing operation';
$_['api_invalid_data']  = 'Invalid data provided';
$_['api_not_found']     = 'Group not found';
$_['api_permission_denied'] = 'You do not have permission for this operation';
