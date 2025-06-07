<?php
// Heading
$_['heading_title']          = 'Purchase Planning';

// Text
$_['text_success']           = 'Success: You have successfully modified purchase planning!';
$_['text_list']              = 'Purchase Plans List';
$_['text_add']               = 'Add Plan';
$_['text_edit']              = 'Edit Plan';
$_['text_view_plan']         = 'View Plan';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';
$_['text_planning_report']   = 'Purchase Planning Report';

// Column
$_['column_plan_name']       = 'Plan Name';
$_['column_plan_period']     = 'Plan Period';
$_['column_start_date']      = 'Start Date';
$_['column_end_date']        = 'End Date';
$_['column_total_budget']    = 'Total Budget';
$_['column_used_budget']     = 'Used Budget';
$_['column_remaining_budget'] = 'Remaining Budget';
$_['column_status']          = 'Status';
$_['column_progress']        = 'Progress';
$_['column_created_by']      = 'Created By';
$_['column_action']          = 'Action';

// Entry
$_['entry_plan_name']        = 'Plan Name';
$_['entry_plan_description'] = 'Plan Description';
$_['entry_plan_period']      = 'Plan Period';
$_['entry_start_date']       = 'Start Date';
$_['entry_end_date']         = 'End Date';
$_['entry_total_budget']     = 'Total Budget';
$_['entry_status']           = 'Status';
$_['entry_notes']            = 'Notes';
$_['entry_product']          = 'Product';
$_['entry_category']         = 'Category';
$_['entry_quantity']         = 'Quantity';
$_['entry_estimated_price']  = 'Estimated Price';
$_['entry_priority']         = 'Priority';
$_['entry_item_notes']       = 'Item Notes';

// Button
$_['button_filter']          = 'Filter';
$_['button_add_item']        = 'Add Item';
$_['button_remove_item']     = 'Remove Item';
$_['button_view_progress']   = 'View Progress';
$_['button_export']          = 'Export';
$_['button_print']           = 'Print';
$_['button_view_report']     = 'View Report';

// Tab
$_['tab_general']            = 'General';
$_['tab_items']              = 'Items';
$_['tab_budget']             = 'Budget';
$_['tab_progress']           = 'Progress';
$_['tab_analytics']          = 'Analytics';

// Status
$_['text_status_draft']      = 'Draft';
$_['text_status_active']     = 'Active';
$_['text_status_completed']  = 'Completed';
$_['text_status_cancelled']  = 'Cancelled';

// Period
$_['text_period_monthly']    = 'Monthly';
$_['text_period_quarterly']  = 'Quarterly';
$_['text_period_yearly']     = 'Yearly';
$_['text_period_custom']     = 'Custom';

// Priority
$_['text_priority_high']     = 'High';
$_['text_priority_medium']   = 'Medium';
$_['text_priority_low']      = 'Low';

// Error
$_['error_permission']       = 'Warning: You do not have permission to access purchase planning!';
$_['error_plan_name']        = 'Plan name must be between 3 and 255 characters!';
$_['error_start_date']       = 'Start date required!';
$_['error_end_date']         = 'End date required!';
$_['error_end_date_before_start'] = 'End date must be after start date!';
$_['error_total_budget']     = 'Total budget must be greater than zero!';

// Help
$_['help_plan_name']         = 'Enter a descriptive name for the plan';
$_['help_plan_period']       = 'Select plan period (monthly, quarterly, yearly, or custom)';
$_['help_total_budget']      = 'Enter the total budget allocated for this plan';
$_['help_plan_items']        = 'Add products and quantities to be purchased in this plan';
$_['help_priority']          = 'Set priority for each item (high, medium, low)';

// Success
$_['success_plan_added']     = 'Plan added successfully!';
$_['success_plan_updated']   = 'Plan updated successfully!';
$_['success_plan_deleted']   = 'Plan deleted successfully!';
$_['success_export']         = 'Data exported successfully!';

// Info
$_['info_planning_help']     = 'Use this screen to create and manage purchase plans';
$_['info_budget_tracking']   = 'Budget usage is tracked automatically from purchase orders';
$_['info_progress_monitoring'] = 'Plan execution progress can be monitored through reports';

// Statistics
$_['text_total_plans']       = 'Total Plans';
$_['text_active_plans']      = 'Active Plans';
$_['text_completed_plans']   = 'Completed Plans';
$_['text_total_budget']      = 'Total Budget';
$_['text_used_budget']       = 'Used Budget';
$_['text_remaining_budget']  = 'Remaining Budget';

// Dashboard Widget
$_['widget_title']           = 'Purchase Planning';
$_['widget_active_plans']    = 'Active Plans';
$_['widget_budget_usage']    = 'Budget Usage';
$_['widget_overdue_plans']   = 'Overdue Plans';
$_['widget_view_all']        = 'View All';

// Report
$_['report_planning_summary'] = 'Purchase Planning Summary';
$_['report_budget_analysis'] = 'Budget Analysis';
$_['report_performance_metrics'] = 'Performance Metrics';
$_['report_by_period']       = 'Report by Period';
$_['report_by_category']     = 'Report by Category';

// Analytics
$_['analytics_by_category']  = 'Analysis by Category';
$_['analytics_by_priority']  = 'Analysis by Priority';
$_['analytics_top_products'] = 'Top Products';
$_['analytics_budget_utilization'] = 'Budget Utilization';

// Progress
$_['progress_planned_items'] = 'Planned Items';
$_['progress_purchased_items'] = 'Purchased Items';
$_['progress_items_percentage'] = 'Items Percentage';
$_['progress_quantity_percentage'] = 'Quantity Percentage';
$_['progress_budget_percentage'] = 'Budget Percentage';

// Email Templates
$_['email_plan_created_subject'] = 'New Purchase Plan - %s';
$_['email_plan_created_body'] = 'New purchase plan created: %s for period from %s to %s';
$_['email_plan_completed_subject'] = 'Purchase Plan Completed - %s';
$_['email_plan_completed_body'] = 'Purchase plan: %s has been completed successfully';

// Export
$_['export_filename']        = 'purchase_plans_%s.csv';
$_['export_headers']         = array(
    'Plan Name',
    'Plan Period',
    'Start Date',
    'End Date',
    'Total Budget',
    'Used Budget',
    'Status',
    'Progress'
);

// Notifications
$_['notification_plan_created'] = 'New purchase plan created: %s';
$_['notification_plan_activated'] = 'Purchase plan activated: %s';
$_['notification_plan_completed'] = 'Purchase plan completed: %s';
$_['notification_budget_exceeded'] = 'Warning: Budget exceeded for plan %s';

// Validation
$_['validation_plan_name_required'] = 'Plan name is required!';
$_['validation_dates_required'] = 'Start and end dates are required!';
$_['validation_budget_positive'] = 'Budget must be greater than zero!';
$_['validation_items_required'] = 'At least one item must be added!';

// Filters
$_['filter_all_periods']     = 'All Periods';
$_['filter_all_statuses']    = 'All Statuses';
$_['filter_active_only']     = 'Active Only';
$_['filter_completed_only']  = 'Completed Only';

// Actions
$_['action_view_details']    = 'View Details';
$_['action_activate']        = 'Activate';
$_['action_complete']        = 'Complete';
$_['action_cancel']          = 'Cancel';
$_['action_duplicate']       = 'Duplicate';

// Bulk Actions
$_['bulk_activate']          = 'Activate Selected';
$_['bulk_complete']          = 'Complete Selected';
$_['bulk_cancel']            = 'Cancel Selected';
$_['bulk_export']            = 'Export Selected';
$_['bulk_action_success']    = 'Action performed on %d plans successfully!';

// Search
$_['search_placeholder']     = 'Search purchase plans...';
$_['search_results']         = 'Search results for: %s';
$_['search_no_results']      = 'No results found for: %s';

// Modal
$_['modal_add_item']         = 'Add New Item';
$_['modal_edit_item']        = 'Edit Item';
$_['modal_confirm_delete']   = 'Confirm Delete';
$_['modal_activate_plan']    = 'Activate Plan';
$_['modal_complete_plan']    = 'Complete Plan';

// Workflow
$_['workflow_draft']         = 'Draft';
$_['workflow_active']        = 'Active';
$_['workflow_completed']     = 'Completed';
$_['workflow_cancelled']     = 'Cancelled';

// API
$_['api_success']            = 'Operation completed successfully';
$_['api_error']              = 'Error performing operation';
$_['api_invalid_data']       = 'Invalid data provided';
$_['api_not_found']          = 'Plan not found';
$_['api_permission_denied']  = 'You do not have permission for this operation';

// Audit Trail
$_['audit_plan_created']     = 'Plan created';
$_['audit_plan_updated']     = 'Plan updated';
$_['audit_plan_activated']   = 'Plan activated';
$_['audit_plan_completed']   = 'Plan completed';
$_['audit_plan_cancelled']   = 'Plan cancelled';
$_['audit_plan_deleted']     = 'Plan deleted';

// Integration
$_['integration_purchase_orders'] = 'Integration with Purchase Orders';
$_['integration_inventory'] = 'Integration with Inventory';
$_['integration_accounting'] = 'Integration with Accounting';

// Approval Workflow
$_['approval_required']      = 'Approval Required';
$_['approval_pending']       = 'Pending Approval';
$_['approval_approved']      = 'Approved';
$_['approval_rejected']      = 'Rejected';

// Budget Management
$_['budget_allocated']       = 'Allocated';
$_['budget_committed']       = 'Committed';
$_['budget_spent']           = 'Spent';
$_['budget_available']       = 'Available';

// Performance Indicators
$_['kpi_completion_rate']    = 'Completion Rate';
$_['kpi_budget_utilization'] = 'Budget Utilization';
$_['kpi_on_time_delivery']   = 'On-Time Delivery';
$_['kpi_cost_variance']      = 'Cost Variance';

// Forecasting
$_['forecast_demand']        = 'Demand Forecast';
$_['forecast_budget']        = 'Budget Forecast';
$_['forecast_timeline']      = 'Timeline Forecast';

// Risk Management
$_['risk_budget_overrun']    = 'Budget Overrun';
$_['risk_schedule_delay']    = 'Schedule Delay';
$_['risk_supplier_issues']   = 'Supplier Issues';
$_['risk_quality_concerns']  = 'Quality Concerns';
