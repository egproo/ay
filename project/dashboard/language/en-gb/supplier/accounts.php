<?php
// Heading
$_['heading_title']          = 'Supplier Accounts';

// Text
$_['text_success']           = 'Success: You have successfully modified supplier accounts!';
$_['text_list']              = 'Supplier Accounts List';
$_['text_account_details']   = 'Account Details';
$_['text_transaction_success'] = 'Transaction added successfully!';
$_['text_payment_success']   = 'Payment added successfully!';
$_['text_credit_limit_updated'] = 'Credit limit updated successfully!';
$_['text_status_updated']    = 'Account status updated successfully!';
$_['text_aging_report']      = 'Aging Report';
$_['text_statement']         = 'Account Statement';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';

// Column
$_['column_supplier']        = 'Supplier';
$_['column_account_number']  = 'Account Number';
$_['column_current_balance'] = 'Current Balance';
$_['column_credit_limit']    = 'Credit Limit';
$_['column_payment_terms']   = 'Payment Terms';
$_['column_account_status']  = 'Account Status';
$_['column_last_transaction'] = 'Last Transaction';
$_['column_action']          = 'Action';
$_['column_transaction_date'] = 'Transaction Date';
$_['column_transaction_type'] = 'Transaction Type';
$_['column_amount']          = 'Amount';
$_['column_reference']       = 'Reference';
$_['column_description']     = 'Description';
$_['column_user']            = 'User';
$_['column_current_30']      = 'Current (0-30 days)';
$_['column_days_31_60']      = '31-60 days';
$_['column_days_61_90']      = '61-90 days';
$_['column_over_90']         = 'Over 90 days';

// Entry
$_['entry_supplier']         = 'Supplier';
$_['entry_supplier_name']    = 'Supplier Name';
$_['entry_account_status']   = 'Account Status';
$_['entry_balance_min']      = 'Minimum Balance';
$_['entry_balance_max']      = 'Maximum Balance';
$_['entry_transaction_type'] = 'Transaction Type';
$_['entry_amount']           = 'Amount';
$_['entry_transaction_date'] = 'Transaction Date';
$_['entry_reference']        = 'Reference';
$_['entry_description']      = 'Description';
$_['entry_payment_amount']   = 'Payment Amount';
$_['entry_payment_method']   = 'Payment Method';
$_['entry_payment_date']     = 'Payment Date';
$_['entry_reference_number'] = 'Reference Number';
$_['entry_notes']            = 'Notes';
$_['entry_credit_limit']     = 'Credit Limit';
$_['entry_date_start']       = 'Date Start';
$_['entry_date_end']         = 'Date End';

// Button
$_['button_filter']          = 'Filter';
$_['button_view_account']    = 'View Account';
$_['button_add_transaction'] = 'Add Transaction';
$_['button_add_payment']     = 'Add Payment';
$_['button_export']          = 'Export';
$_['button_aging_report']    = 'Aging Report';
$_['button_statement']       = 'Statement';
$_['button_update_credit']   = 'Update Credit';
$_['button_toggle_status']   = 'Toggle Status';
$_['button_print']           = 'Print';

// Tab
$_['tab_account_info']       = 'Account Info';
$_['tab_transactions']       = 'Transactions';
$_['tab_payments']           = 'Payments';
$_['tab_summary']            = 'Summary';

// Transaction Types
$_['transaction_purchase']   = 'Purchase';
$_['transaction_invoice']    = 'Invoice';
$_['transaction_payment']    = 'Payment';
$_['transaction_credit']     = 'Credit';
$_['transaction_debit']      = 'Debit';
$_['transaction_adjustment'] = 'Adjustment';

// Account Status
$_['status_active']          = 'Active';
$_['status_suspended']       = 'Suspended';
$_['status_closed']          = 'Closed';

// Payment Terms
$_['terms_net_30']           = 'Net 30 days';
$_['terms_net_60']           = 'Net 60 days';
$_['terms_net_90']           = 'Net 90 days';
$_['terms_cod']              = 'Cash on Delivery';
$_['terms_prepaid']          = 'Prepaid';

// Error
$_['error_permission']       = 'Warning: You do not have permission to access supplier accounts!';
$_['error_supplier']         = 'Supplier required!';
$_['error_transaction_type'] = 'Transaction type required!';
$_['error_amount']           = 'Amount must be greater than zero!';
$_['error_transaction_date'] = 'Transaction date required!';
$_['error_payment_amount']   = 'Payment amount must be greater than zero!';
$_['error_payment_method']   = 'Payment method required!';
$_['error_payment_date']     = 'Payment date required!';
$_['error_credit_limit']     = 'Credit limit must be greater than or equal to zero!';
$_['error_missing_data']     = 'Missing data!';
$_['error_update_status']    = 'Error updating account status!';

// Help
$_['help_current_balance']   = 'Current supplier balance (positive = owe to supplier, negative = supplier owes)';
$_['help_credit_limit']      = 'Maximum credit limit allowed for the supplier';
$_['help_payment_terms']     = 'Agreed payment terms with the supplier';
$_['help_account_status']    = 'Account status (active, suspended, closed)';

// Success
$_['success_transaction_added'] = 'Transaction added successfully!';
$_['success_payment_added']  = 'Payment added successfully!';
$_['success_credit_updated'] = 'Credit limit updated successfully!';
$_['success_status_updated'] = 'Account status updated successfully!';
$_['success_export']         = 'Data exported successfully!';

// Info
$_['info_account_help']      = 'Use this screen to manage supplier accounts and track financial transactions';
$_['info_balance_positive']  = 'Positive balance means the company owes the supplier';
$_['info_balance_negative']  = 'Negative balance means the supplier owes the company';
$_['info_aging_help']        = 'Aging report shows distribution of outstanding amounts by time period';

// Statistics
$_['text_total_accounts']    = 'Total Accounts';
$_['text_active_accounts']   = 'Active Accounts';
$_['text_total_balance']     = 'Total Balance';
$_['text_positive_balance']  = 'Positive Balances';
$_['text_negative_balance']  = 'Negative Balances';
$_['text_account_summary']   = 'Account Summary';

// Dashboard Widget
$_['widget_title']           = 'Supplier Accounts';
$_['widget_total_balance']   = 'Total Balance';
$_['widget_overdue']         = 'Overdue';
$_['widget_current']         = 'Current';
$_['widget_view_all']        = 'View All';

// Report
$_['report_aging_title']     = 'Supplier Aging Report';
$_['report_statement_title'] = 'Supplier Account Statement';
$_['report_summary_title']   = 'Supplier Accounts Summary';
$_['report_transactions']    = 'Supplier Transactions Report';

// Email Templates
$_['email_statement_subject'] = 'Account Statement - %s';
$_['email_statement_body']   = 'Attached is your account statement for the period from %s to %s';
$_['email_overdue_subject']  = 'Overdue Payment Reminder';
$_['email_overdue_body']     = 'You have overdue amounts totaling %s past due date';

// Export
$_['export_filename']        = 'supplier_accounts_%s.csv';
$_['export_aging_filename']  = 'aging_report_%s.csv';
$_['export_statement_filename'] = 'statement_%s_%s.csv';

// Notifications
$_['notification_payment_received'] = 'Payment received from supplier %s amount %s';
$_['notification_credit_limit_exceeded'] = 'Warning: Supplier %s exceeded credit limit';
$_['notification_account_suspended'] = 'Supplier account %s has been suspended';

// Validation
$_['validation_supplier_required'] = 'Supplier must be selected!';
$_['validation_amount_positive'] = 'Amount must be greater than zero!';
$_['validation_date_required'] = 'Date is required!';
$_['validation_credit_limit_valid'] = 'Credit limit must be a valid number!';

// Filters
$_['filter_all_suppliers']   = 'All Suppliers';
$_['filter_all_statuses']    = 'All Statuses';
$_['filter_positive_balance'] = 'Positive Balance';
$_['filter_negative_balance'] = 'Negative Balance';
$_['filter_zero_balance']    = 'Zero Balance';

// Actions
$_['action_view_details']    = 'View Details';
$_['action_add_transaction'] = 'Add Transaction';
$_['action_add_payment']     = 'Add Payment';
$_['action_view_statement']  = 'View Statement';
$_['action_suspend_account'] = 'Suspend Account';
$_['action_activate_account'] = 'Activate Account';

// Bulk Actions
$_['bulk_export']            = 'Export Selected';
$_['bulk_suspend']           = 'Suspend Selected';
$_['bulk_activate']          = 'Activate Selected';
$_['bulk_action_success']    = 'Action performed on %d accounts successfully!';

// Search
$_['search_placeholder']     = 'Search supplier accounts...';
$_['search_results']         = 'Search results for: %s';
$_['search_no_results']      = 'No results found for: %s';

// Modal
$_['modal_add_transaction']  = 'Add New Transaction';
$_['modal_add_payment']      = 'Add New Payment';
$_['modal_update_credit']    = 'Update Credit Limit';
$_['modal_confirm_suspend']  = 'Confirm Account Suspension';
$_['modal_confirm_activate'] = 'Confirm Account Activation';

// API
$_['api_success']            = 'Operation completed successfully';
$_['api_error']              = 'Error performing operation';
$_['api_invalid_data']       = 'Invalid data provided';
$_['api_not_found']          = 'Account not found';
$_['api_permission_denied']  = 'You do not have permission for this operation';
