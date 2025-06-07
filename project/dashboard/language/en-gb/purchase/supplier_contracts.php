<?php
// Heading
$_['heading_title']          = 'Supplier Contracts';

// Text
$_['text_success']           = 'Success: You have successfully modified supplier contracts!';
$_['text_list']              = 'Supplier Contracts List';
$_['text_add']               = 'Add Contract';
$_['text_edit']              = 'Edit Contract';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';
$_['text_contract_details']  = 'Contract Details';
$_['text_contract_history']  = 'Contract History';
$_['text_contract_items']    = 'Contract Items';
$_['text_contract_renewed']  = 'Contract renewed successfully!';
$_['text_contract_terminated'] = 'Contract terminated successfully!';

// Contract Types
$_['text_contract_type_general']     = 'General Contract';
$_['text_contract_type_framework']   = 'Framework Contract';
$_['text_contract_type_exclusive']   = 'Exclusive Contract';
$_['text_contract_type_service']     = 'Service Contract';
$_['text_contract_type_maintenance'] = 'Maintenance Contract';

// Status
$_['text_status_draft']              = 'Draft';
$_['text_status_pending_approval']   = 'Pending Approval';
$_['text_status_active']             = 'Active';
$_['text_status_suspended']          = 'Suspended';
$_['text_status_expired']            = 'Expired';
$_['text_status_terminated']         = 'Terminated';

// Column
$_['column_contract_number'] = 'Contract Number';
$_['column_supplier']        = 'Supplier';
$_['column_contract_type']   = 'Contract Type';
$_['column_contract_date']   = 'Contract Date';
$_['column_start_date']      = 'Start Date';
$_['column_end_date']        = 'End Date';
$_['column_contract_value']  = 'Contract Value';
$_['column_status']          = 'Status';
$_['column_action']          = 'Action';
$_['column_history_date']    = 'Date';
$_['column_history_action']  = 'Action';
$_['column_history_notes']   = 'Notes';
$_['column_history_user']    = 'User';

// Entry
$_['entry_contract_number']  = 'Contract Number';
$_['entry_supplier']         = 'Supplier';
$_['entry_contract_type']    = 'Contract Type';
$_['entry_contract_date']    = 'Contract Date';
$_['entry_start_date']       = 'Start Date';
$_['entry_end_date']         = 'End Date';
$_['entry_contract_value']   = 'Contract Value';
$_['entry_currency']         = 'Currency';
$_['entry_payment_terms']    = 'Payment Terms';
$_['entry_delivery_terms']   = 'Delivery Terms';
$_['entry_terms_conditions'] = 'Terms & Conditions';
$_['entry_notes']            = 'Notes';
$_['entry_status']           = 'Status';
$_['entry_date_start']       = 'Date Start';
$_['entry_date_end']         = 'Date End';
$_['entry_new_end_date']     = 'New End Date';
$_['entry_renewal_notes']    = 'Renewal Notes';
$_['entry_termination_reason'] = 'Termination Reason';
$_['entry_termination_notes'] = 'Termination Notes';

// Button
$_['button_filter']          = 'Filter';
$_['button_renew']           = 'Renew';
$_['button_terminate']       = 'Terminate';
$_['button_view_history']    = 'View History';
$_['button_add_item']        = 'Add Item';
$_['button_export']          = 'Export';
$_['button_print']           = 'Print';

// Tab
$_['tab_general']            = 'General';
$_['tab_terms']              = 'Terms';
$_['tab_items']              = 'Items';
$_['tab_history']            = 'History';
$_['tab_documents']          = 'Documents';

// Error
$_['error_permission']       = 'Warning: You do not have permission to access supplier contracts!';
$_['error_contract_number']  = 'Contract Number must be between 1 and 64 characters!';
$_['error_supplier']         = 'Supplier required!';
$_['error_start_date']       = 'Start Date required!';
$_['error_end_date']         = 'End Date required!';
$_['error_end_date_before_start'] = 'End Date must be after Start Date!';
$_['error_contract_id']      = 'Contract ID required!';
$_['error_new_end_date']     = 'New End Date required!';
$_['error_new_end_date_invalid'] = 'New End Date must be after current End Date!';
$_['error_contract_not_found'] = 'Contract not found!';
$_['error_contract_already_terminated'] = 'Contract already terminated!';

// Help
$_['help_contract_number']   = 'Unique contract identifier';
$_['help_contract_type']     = 'Type of contract based on business nature';
$_['help_contract_value']    = 'Total contract value';
$_['help_payment_terms']     = 'Agreed payment terms';
$_['help_delivery_terms']    = 'Delivery and shipping terms';
$_['help_terms_conditions']  = 'Detailed contract terms and conditions';

// Success
$_['success_contract_added'] = 'Contract added successfully!';
$_['success_contract_updated'] = 'Contract updated successfully!';
$_['success_contract_deleted'] = 'Contract deleted successfully!';
$_['success_contract_renewed'] = 'Contract renewed successfully!';
$_['success_contract_terminated'] = 'Contract terminated successfully!';

// Warning
$_['warning_contract_expiring'] = 'Warning: This contract is expiring soon!';
$_['warning_contract_expired'] = 'Warning: This contract has expired!';
$_['warning_no_items'] = 'No items in this contract';

// Info
$_['info_contract_help'] = 'Use this screen to manage supplier contracts and monitor expiry dates';
$_['info_renewal_help'] = 'You can renew the contract by setting a new end date';
$_['info_termination_help'] = 'Terminating the contract will make it inactive and unusable';

// Statistics
$_['text_total_contracts']   = 'Total Contracts';
$_['text_active_contracts']  = 'Active Contracts';
$_['text_expired_contracts'] = 'Expired Contracts';
$_['text_expiring_contracts'] = 'Expiring Soon';
$_['text_total_value']       = 'Total Value';

// Dashboard Widgets
$_['widget_expiring_contracts'] = 'Expiring Contracts';
$_['widget_expired_contracts'] = 'Expired Contracts';
$_['widget_contract_summary'] = 'Contract Summary';

// Email Templates
$_['email_expiring_subject'] = 'Alert: Supplier Contract Expiring Soon - %s';
$_['email_expiring_message'] = 'Supplier contract %s will expire in %d day(s).';
$_['email_expired_subject'] = 'Warning: Supplier Contract Expired - %s';
$_['email_expired_message'] = 'Supplier contract %s expired %d day(s) ago.';

// Reports
$_['report_contract_summary'] = 'Contract Summary Report';
$_['report_expiring_contracts'] = 'Expiring Contracts Report';
$_['report_contract_performance'] = 'Contract Performance Report';
$_['report_supplier_contracts'] = 'Supplier Contracts Report';

// Export
$_['export_contract_data'] = 'Export Contract Data';
$_['export_expiring_list'] = 'Export Expiring Contracts List';
$_['export_contract_report'] = 'Export Contract Report';

// Actions
$_['action_created'] = 'Created';
$_['action_modified'] = 'Modified';
$_['action_renewed'] = 'Renewed';
$_['action_terminated'] = 'Terminated';
$_['action_suspended'] = 'Suspended';
$_['action_activated'] = 'Activated';

// Validation
$_['validation_contract_number_exists'] = 'Contract number already exists!';
$_['validation_supplier_required'] = 'Supplier must be selected!';
$_['validation_dates_required'] = 'Start and end dates are required!';
$_['validation_value_positive'] = 'Contract value must be greater than zero!';

// Notifications
$_['notification_contract_expiring_title'] = 'Supplier Contract Expiring Soon';
$_['notification_contract_expiring_message'] = 'Supplier %s contract %s will expire on %s';
$_['notification_contract_expired_title'] = 'Supplier Contract Expired';
$_['notification_contract_expired_message'] = 'Supplier %s contract %s expired on %s';
$_['notification_contract_renewed_title'] = 'Supplier Contract Renewed';
$_['notification_contract_renewed_message'] = 'Supplier %s contract %s renewed until %s';

// Contract Items
$_['text_item_product'] = 'Product';
$_['text_item_quantity'] = 'Quantity';
$_['text_item_unit_price'] = 'Unit Price';
$_['text_item_total'] = 'Total';
$_['entry_item_product'] = 'Product';
$_['entry_item_quantity'] = 'Quantity';
$_['entry_item_unit_price'] = 'Unit Price';
$_['entry_item_notes'] = 'Item Notes';

// Contract Documents
$_['text_document_name'] = 'Document Name';
$_['text_document_type'] = 'Document Type';
$_['text_document_date'] = 'Document Date';
$_['entry_document_name'] = 'Document Name';
$_['entry_document_type'] = 'Document Type';
$_['entry_document_file'] = 'Document File';
$_['button_upload_document'] = 'Upload Document';
$_['button_download_document'] = 'Download Document';

// Contract Renewal
$_['text_renewal_history'] = 'Renewal History';
$_['text_original_end_date'] = 'Original End Date';
$_['text_renewed_end_date'] = 'Renewed End Date';
$_['text_renewal_period'] = 'Renewal Period';
$_['text_auto_renewal'] = 'Auto Renewal';
$_['entry_auto_renewal'] = 'Auto Renewal';
$_['entry_renewal_period'] = 'Renewal Period (Months)';
$_['entry_renewal_notice_days'] = 'Renewal Notice Days';

// Contract Performance
$_['text_performance_rating'] = 'Performance Rating';
$_['text_delivery_performance'] = 'Delivery Performance';
$_['text_quality_performance'] = 'Quality Performance';
$_['text_cost_performance'] = 'Cost Performance';
$_['entry_performance_rating'] = 'Performance Rating';
$_['entry_performance_notes'] = 'Performance Notes';
