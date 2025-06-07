<?php
// Heading
$_['heading_title']          = 'Purchase Order Tracking';

// Text
$_['text_success']           = 'Success: You have successfully modified purchase order tracking!';
$_['text_list']              = 'Purchase Order Tracking List';
$_['text_add']               = 'Add Tracking';
$_['text_edit']              = 'Edit Tracking';
$_['text_view']              = 'View Tracking';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';
$_['text_tracking_history']  = 'Tracking History';
$_['text_order_details']     = 'Order Details';
$_['text_current_status']    = 'Current Status';
$_['text_delivery_info']     = 'Delivery Information';
$_['text_overdue']           = 'Overdue';
$_['text_on_time']           = 'On Time';
$_['text_upcoming']          = 'Upcoming';
$_['text_delivered']         = 'Delivered';

// Status Text
$_['text_status_created']              = 'Created';
$_['text_status_sent_to_vendor']       = 'Sent to Vendor';
$_['text_status_confirmed_by_vendor']  = 'Confirmed by Vendor';
$_['text_status_partially_received']  = 'Partially Received';
$_['text_status_fully_received']      = 'Fully Received';
$_['text_status_cancelled']           = 'Cancelled';
$_['text_status_closed']              = 'Closed';
$_['text_status_delivery_date_updated'] = 'Delivery Date Updated';
$_['text_status_delivery_completed']   = 'Delivery Completed';

// Column
$_['column_po_number']         = 'PO Number';
$_['column_supplier']          = 'Supplier';
$_['column_order_date']        = 'Order Date';
$_['column_expected_delivery'] = 'Expected Delivery';
$_['column_actual_delivery']   = 'Actual Delivery';
$_['column_status']            = 'Order Status';
$_['column_current_status']    = 'Tracking Status';
$_['column_total']             = 'Total';
$_['column_action']            = 'Action';
$_['column_tracking_date']     = 'Tracking Date';
$_['column_status_change']     = 'Status Change';
$_['column_notes']             = 'Notes';
$_['column_created_by']        = 'Created By';
$_['column_days_overdue']      = 'Days Overdue';
$_['column_days_until']        = 'Days Until';

// Entry
$_['entry_po_number']          = 'PO Number';
$_['entry_supplier']           = 'Supplier';
$_['entry_status']             = 'Status';
$_['entry_date_start']         = 'Date Start';
$_['entry_date_end']           = 'Date End';
$_['entry_status_change']      = 'Status Change';
$_['entry_expected_delivery']  = 'Expected Delivery';
$_['entry_actual_delivery']    = 'Actual Delivery';
$_['entry_notes']              = 'Notes';
$_['entry_tracking_date']      = 'Tracking Date';

// Button
$_['button_filter']            = 'Filter';
$_['button_view']              = 'View';
$_['button_update']            = 'Update';
$_['button_add_tracking']      = 'Add Tracking';
$_['button_update_status']     = 'Update Status';
$_['button_view_history']      = 'View History';
$_['button_print']             = 'Print';
$_['button_export']            = 'Export';
$_['button_refresh']           = 'Refresh';

// Tab
$_['tab_general']              = 'General';
$_['tab_tracking']             = 'Tracking';
$_['tab_history']              = 'History';
$_['tab_delivery']             = 'Delivery';
$_['tab_statistics']           = 'Statistics';

// Error
$_['error_permission']         = 'Warning: You do not have permission to access purchase order tracking!';
$_['error_not_found']          = 'Error: Purchase order not found!';
$_['error_po_id']              = 'Error: Purchase order ID required!';
$_['error_status']             = 'Error: Tracking status required!';
$_['error_date']               = 'Error: Invalid date!';
$_['error_delivery_date']      = 'Error: Delivery date must be after order date!';

// Help
$_['help_po_number']           = 'Search by purchase order number';
$_['help_supplier']            = 'Select supplier to filter';
$_['help_status']              = 'Select tracking status to filter';
$_['help_date_range']          = 'Set date range for search';
$_['help_expected_delivery']   = 'Expected date for goods delivery';
$_['help_actual_delivery']     = 'Actual date when goods were delivered';
$_['help_tracking_notes']      = 'Add notes about tracking status';

// Success
$_['success_tracking_added']   = 'Tracking record added successfully!';
$_['success_tracking_updated'] = 'Tracking record updated successfully!';
$_['success_status_updated']   = 'Tracking status updated successfully!';
$_['success_delivery_updated'] = 'Delivery information updated successfully!';

// Warning
$_['warning_overdue']          = 'Warning: This order is overdue!';
$_['warning_upcoming']         = 'Notice: Delivery date is approaching!';
$_['warning_no_tracking']      = 'No tracking records for this order';

// Info
$_['info_tracking_help']       = 'Use this screen to track purchase order status and monitor delivery schedules';
$_['info_status_flow']         = 'Status Flow: Created → Sent to Vendor → Confirmed → Partially/Fully Received → Closed';
$_['info_overdue_orders']      = 'Overdue orders are highlighted in red';
$_['info_upcoming_deliveries'] = 'Upcoming deliveries are highlighted in yellow';

// Statistics
$_['text_total_orders']        = 'Total Orders';
$_['text_pending_orders']      = 'Pending Orders';
$_['text_overdue_orders']      = 'Overdue Orders';
$_['text_delivered_orders']    = 'Delivered Orders';
$_['text_avg_delivery_time']   = 'Average Delivery Time';
$_['text_on_time_delivery']    = 'On-Time Delivery';
$_['text_late_delivery']       = 'Late Delivery';

// Dashboard Widgets
$_['widget_overdue_orders']    = 'Overdue Purchase Orders';
$_['widget_upcoming_deliveries'] = 'Upcoming Deliveries';
$_['widget_tracking_summary']  = 'Tracking Summary';

// Email Templates
$_['email_overdue_subject']    = 'Alert: Overdue Purchase Order - %s';
$_['email_overdue_message']    = 'Purchase Order %s is overdue by %d day(s).';
$_['email_upcoming_subject']   = 'Reminder: Upcoming Delivery - %s';
$_['email_upcoming_message']   = 'Purchase Order %s is expected to be delivered in %d day(s).';

// Reports
$_['report_tracking_summary']  = 'Tracking Summary Report';
$_['report_overdue_orders']    = 'Overdue Orders Report';
$_['report_delivery_performance'] = 'Delivery Performance Report';
$_['report_supplier_performance'] = 'Supplier Performance Report';

// Export
$_['export_tracking_data']     = 'Export Tracking Data';
$_['export_overdue_list']      = 'Export Overdue Orders List';
$_['export_delivery_report']   = 'Export Delivery Report';
