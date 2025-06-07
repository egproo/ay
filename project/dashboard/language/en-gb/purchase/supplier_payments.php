<?php
// Heading
$_['heading_title']          = 'Supplier Payments';

// Text
$_['text_success']           = 'Success: You have successfully modified supplier payments!';
$_['text_list']              = 'Supplier Payments List';
$_['text_add']               = 'Add Payment';
$_['text_edit']              = 'Edit Payment';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';
$_['text_payment_approved']  = 'Payment approved successfully!';
$_['text_payment_cancelled'] = 'Payment cancelled successfully!';
$_['text_payment_report']    = 'Supplier Payments Report';

// Column
$_['column_payment_number']  = 'Payment Number';
$_['column_supplier']        = 'Supplier';
$_['column_payment_amount']  = 'Payment Amount';
$_['column_payment_method']  = 'Payment Method';
$_['column_payment_date']    = 'Payment Date';
$_['column_reference_number'] = 'Reference Number';
$_['column_status']          = 'Status';
$_['column_created_by']      = 'Created By';
$_['column_action']          = 'Action';

// Entry
$_['entry_supplier']         = 'Supplier';
$_['entry_payment_amount']   = 'Payment Amount';
$_['entry_payment_method']   = 'Payment Method';
$_['entry_payment_date']     = 'Payment Date';
$_['entry_reference_number'] = 'Reference Number';
$_['entry_bank_account']     = 'Bank Account';
$_['entry_check_number']     = 'Check Number';
$_['entry_check_date']       = 'Check Date';
$_['entry_notes']            = 'Notes';
$_['entry_status']           = 'Status';
$_['entry_date_start']       = 'Date Start';
$_['entry_date_end']         = 'Date End';
$_['entry_cancellation_reason'] = 'Cancellation Reason';

// Button
$_['button_filter']          = 'Filter';
$_['button_approve']         = 'Approve';
$_['button_cancel_payment']  = 'Cancel Payment';
$_['button_export']          = 'Export';
$_['button_print']           = 'Print';
$_['button_view_report']     = 'View Report';

// Tab
$_['tab_general']            = 'General';
$_['tab_payment_details']    = 'Payment Details';
$_['tab_bank_details']       = 'Bank Details';
$_['tab_notes']              = 'Notes';

// Status
$_['text_status_pending']    = 'Pending';
$_['text_status_approved']   = 'Approved';
$_['text_status_paid']       = 'Paid';
$_['text_status_cancelled']  = 'Cancelled';
$_['text_status_returned']   = 'Returned';

// Payment Methods
$_['text_method_cash']       = 'Cash';
$_['text_method_bank_transfer'] = 'Bank Transfer';
$_['text_method_check']      = 'Check';
$_['text_method_credit_card'] = 'Credit Card';
$_['text_method_money_order'] = 'Money Order';

// Error
$_['error_permission']       = 'Warning: You do not have permission to access supplier payments!';
$_['error_supplier']         = 'Supplier required!';
$_['error_payment_amount']   = 'Payment amount must be greater than zero!';
$_['error_payment_method']   = 'Payment method required!';
$_['error_payment_date']     = 'Payment date required!';
$_['error_payment_id']       = 'Payment ID required!';
$_['error_approve_payment']  = 'Error approving payment!';
$_['error_cancel_payment']   = 'Error cancelling payment!';

// Help
$_['help_payment_amount']    = 'Enter the payment amount to be paid to the supplier';
$_['help_reference_number']  = 'Reference number for the payment (check number, transfer number, etc.)';
$_['help_bank_account']      = 'Bank account used for payment (for bank transfers)';
$_['help_check_details']     = 'Check details (check number and date)';
$_['help_status']            = 'Payment status: Pending (needs approval), Approved (ready for payment), Paid (payment completed)';

// Success
$_['success_payment_added']  = 'Payment added successfully!';
$_['success_payment_updated'] = 'Payment updated successfully!';
$_['success_payment_deleted'] = 'Payment deleted successfully!';
$_['success_payment_approved'] = 'Payment approved successfully!';
$_['success_payment_cancelled'] = 'Payment cancelled successfully!';
$_['success_export']         = 'Data exported successfully!';

// Info
$_['info_payment_help']      = 'Use this screen to manage supplier payments and track payments';
$_['info_approval_required'] = 'Payments require approval before execution';
$_['info_payment_tracking']  = 'All payments can be tracked through reports';

// Statistics
$_['text_total_payments']    = 'Total Payments';
$_['text_pending_payments']  = 'Pending Payments';
$_['text_approved_payments'] = 'Approved Payments';
$_['text_paid_payments']     = 'Paid Payments';
$_['text_total_amount']      = 'Total Amount';
$_['text_monthly_payments']  = 'Monthly Payments';
$_['text_monthly_amount']    = 'Monthly Amount';

// Dashboard Widget
$_['widget_title']           = 'Supplier Payments';
$_['widget_pending']         = 'Pending';
$_['widget_approved']        = 'Approved';
$_['widget_paid']            = 'Paid';
$_['widget_view_all']        = 'View All';

// Report
$_['report_payment_summary'] = 'Supplier Payments Summary';
$_['report_by_supplier']     = 'Report by Supplier';
$_['report_by_method']       = 'Report by Payment Method';
$_['report_by_period']       = 'Report by Period';

// Email Templates
$_['email_payment_subject']  = 'Payment Notification - %s';
$_['email_payment_body']     = 'New payment %s created for amount %s to supplier %s';
$_['email_approval_subject'] = 'Payment Approval Request - %s';
$_['email_approval_body']    = 'Payment %s for amount %s requires approval';

// Export
$_['export_filename']        = 'supplier_payments_%s.csv';
$_['export_headers']         = array(
    'Payment Number',
    'Supplier',
    'Payment Amount',
    'Payment Method',
    'Payment Date',
    'Reference Number',
    'Status',
    'Notes'
);

// Notifications
$_['notification_payment_created'] = 'New payment %s created';
$_['notification_payment_approved'] = 'Payment %s approved';
$_['notification_payment_cancelled'] = 'Payment %s cancelled';
$_['notification_approval_required'] = 'Payment %s requires approval';

// Validation
$_['validation_supplier_required'] = 'Supplier must be selected!';
$_['validation_amount_positive'] = 'Payment amount must be greater than zero!';
$_['validation_date_required'] = 'Payment date is required!';
$_['validation_method_required'] = 'Payment method is required!';
$_['validation_reference_required'] = 'Reference number is required for this payment method!';
$_['validation_check_details_required'] = 'Check details are required!';

// Filters
$_['filter_all_suppliers']   = 'All Suppliers';
$_['filter_all_methods']     = 'All Payment Methods';
$_['filter_all_statuses']    = 'All Statuses';
$_['filter_pending_only']    = 'Pending Only';
$_['filter_approved_only']   = 'Approved Only';
$_['filter_paid_only']       = 'Paid Only';

// Actions
$_['action_view_details']    = 'View Details';
$_['action_approve']         = 'Approve';
$_['action_cancel']          = 'Cancel';
$_['action_print_voucher']   = 'Print Voucher';
$_['action_view_receipt']    = 'View Receipt';

// Bulk Actions
$_['bulk_approve']           = 'Approve Selected';
$_['bulk_cancel']            = 'Cancel Selected';
$_['bulk_export']            = 'Export Selected';
$_['bulk_action_success']    = 'Action performed on %d payments successfully!';

// Search
$_['search_placeholder']     = 'Search payments...';
$_['search_results']         = 'Search results for: %s';
$_['search_no_results']      = 'No results found for: %s';

// Modal
$_['modal_approve_payment']  = 'Approve Payment';
$_['modal_cancel_payment']   = 'Cancel Payment';
$_['modal_confirm_approve']  = 'Are you sure you want to approve this payment?';
$_['modal_confirm_cancel']   = 'Are you sure you want to cancel this payment?';
$_['modal_cancellation_reason'] = 'Cancellation Reason';

// Workflow
$_['workflow_created']       = 'Payment Created';
$_['workflow_pending']       = 'Pending Approval';
$_['workflow_approved']      = 'Approved';
$_['workflow_paid']          = 'Paid';
$_['workflow_cancelled']     = 'Cancelled';

// API
$_['api_success']            = 'Operation completed successfully';
$_['api_error']              = 'Error performing operation';
$_['api_invalid_data']       = 'Invalid data provided';
$_['api_not_found']          = 'Payment not found';
$_['api_permission_denied']  = 'You do not have permission for this operation';
$_['api_cannot_modify']      = 'Cannot modify this payment';

// Audit Trail
$_['audit_payment_created']  = 'Payment created';
$_['audit_payment_updated']  = 'Payment updated';
$_['audit_payment_approved'] = 'Payment approved';
$_['audit_payment_cancelled'] = 'Payment cancelled';
$_['audit_payment_deleted']  = 'Payment deleted';

// Integration
$_['integration_accounting'] = 'Accounting entry recorded';
$_['integration_bank']       = 'Transfer sent to bank';
$_['integration_notification'] = 'Notification sent';

// Approval Workflow
$_['approval_level_1']       = 'Level 1 Approval';
$_['approval_level_2']       = 'Level 2 Approval';
$_['approval_final']         = 'Final Approval';
$_['approval_rejected']      = 'Rejected';

// Bank Integration
$_['bank_transfer_pending']  = 'Bank Transfer Pending';
$_['bank_transfer_sent']     = 'Transfer Sent';
$_['bank_transfer_confirmed'] = 'Transfer Confirmed';
$_['bank_transfer_failed']   = 'Transfer Failed';

// Reconciliation
$_['reconciliation_matched'] = 'Matched';
$_['reconciliation_unmatched'] = 'Unmatched';
$_['reconciliation_pending'] = 'Pending Reconciliation';
