<?php
// Heading
$_['heading_title']          = 'Supplier Evaluation';

// Text
$_['text_success']           = 'Success: You have successfully modified supplier evaluations!';
$_['text_list']              = 'Supplier Evaluation List';
$_['text_add']               = 'Add Evaluation';
$_['text_edit']              = 'Edit Evaluation';
$_['text_confirm']           = 'Are you sure?';
$_['text_loading']           = 'Loading...';
$_['text_no_results']        = 'No results!';
$_['text_report']            = 'Evaluation Report';
$_['text_evaluation_details'] = 'Evaluation Details';
$_['text_evaluation_history'] = 'Evaluation History';
$_['text_comparison']        = 'Supplier Comparison';

// Column
$_['column_supplier']        = 'Supplier';
$_['column_evaluator']       = 'Evaluator';
$_['column_evaluation_date'] = 'Evaluation Date';
$_['column_quality_score']   = 'Quality Score';
$_['column_delivery_score']  = 'Delivery Score';
$_['column_price_score']     = 'Price Score';
$_['column_service_score']   = 'Service Score';
$_['column_overall_score']   = 'Overall Score';
$_['column_comments']        = 'Comments';
$_['column_action']          = 'Action';

// Entry
$_['entry_supplier']         = 'Supplier';
$_['entry_evaluation_date']  = 'Evaluation Date';
$_['entry_quality_score']    = 'Quality Score (1-5)';
$_['entry_delivery_score']   = 'Delivery Score (1-5)';
$_['entry_price_score']      = 'Price Score (1-5)';
$_['entry_service_score']    = 'Service Score (1-5)';
$_['entry_overall_score']    = 'Overall Score';
$_['entry_comments']         = 'Comments';
$_['entry_evaluator']        = 'Evaluator';
$_['entry_date_start']       = 'Date Start';
$_['entry_date_end']         = 'Date End';

// Button
$_['button_filter']          = 'Filter';
$_['button_view_report']     = 'View Report';
$_['button_compare']         = 'Compare';
$_['button_export']          = 'Export';
$_['button_print']           = 'Print';

// Tab
$_['tab_general']            = 'General';
$_['tab_scores']             = 'Scores';
$_['tab_comments']           = 'Comments';
$_['tab_history']            = 'History';

// Error
$_['error_permission']       = 'Warning: You do not have permission to access supplier evaluations!';
$_['error_supplier']         = 'Supplier required!';
$_['error_evaluation_date']  = 'Evaluation date required!';
$_['error_quality_score']    = 'Quality score must be between 0 and 5!';
$_['error_delivery_score']   = 'Delivery score must be between 0 and 5!';
$_['error_price_score']      = 'Price score must be between 0 and 5!';
$_['error_service_score']    = 'Service score must be between 0 and 5!';

// Help
$_['help_quality_score']     = 'Rate the quality of products and services provided by the supplier from 1 to 5';
$_['help_delivery_score']    = 'Rate the supplier\'s adherence to delivery schedules from 1 to 5';
$_['help_price_score']       = 'Rate the competitiveness of supplier prices from 1 to 5';
$_['help_service_score']     = 'Rate the level of customer service and technical support from 1 to 5';
$_['help_overall_score']     = 'Overall score is automatically calculated as the average of the four scores';

// Success
$_['success_add']            = 'Evaluation added successfully!';
$_['success_edit']           = 'Evaluation updated successfully!';
$_['success_delete']         = 'Evaluation deleted successfully!';
$_['success_export']         = 'Evaluations exported successfully!';

// Info
$_['info_evaluation_help']   = 'Use this screen to evaluate supplier performance in various aspects';
$_['info_score_range']       = 'Scores range from 1 (very poor) to 5 (excellent)';
$_['info_automatic_calculation'] = 'Overall score is calculated automatically';

// Statistics
$_['text_total_evaluations'] = 'Total Evaluations';
$_['text_average_score']     = 'Average Score';
$_['text_top_suppliers']     = 'Top Suppliers';
$_['text_recent_evaluations'] = 'Recent Evaluations';
$_['text_evaluation_trends'] = 'Evaluation Trends';

// Score Labels
$_['score_excellent']        = 'Excellent (4.5-5.0)';
$_['score_good']             = 'Good (3.5-4.4)';
$_['score_average']          = 'Average (2.5-3.4)';
$_['score_poor']             = 'Poor (1.0-2.4)';

// Report
$_['report_supplier_performance'] = 'Supplier Performance Report';
$_['report_evaluation_summary'] = 'Evaluation Summary';
$_['report_comparison']      = 'Supplier Comparison Report';
$_['report_trends']          = 'Evaluation Trends Report';

// Dashboard Widget
$_['widget_title']           = 'Supplier Evaluations';
$_['widget_recent']          = 'Recent Evaluations';
$_['widget_top_rated']       = 'Top Rated';
$_['widget_needs_evaluation'] = 'Needs Evaluation';

// Notifications
$_['notification_new_evaluation'] = 'New evaluation for supplier %s';
$_['notification_low_score'] = 'Warning: Supplier %s received a low score';
$_['notification_excellent_score'] = 'Supplier %s received an excellent score';

// Email Templates
$_['email_evaluation_subject'] = 'New Evaluation - %s';
$_['email_evaluation_body'] = 'A new evaluation has been added for supplier %s with overall score %s';

// Export
$_['export_filename']        = 'supplier_evaluations_%s.csv';
$_['export_headers']         = array(
    'Evaluation ID',
    'Supplier',
    'Evaluator',
    'Evaluation Date',
    'Quality Score',
    'Delivery Score',
    'Price Score',
    'Service Score',
    'Overall Score',
    'Comments'
);

// Validation
$_['validation_supplier_required'] = 'Supplier must be selected!';
$_['validation_date_required'] = 'Evaluation date is required!';
$_['validation_scores_required'] = 'All scores are required!';
$_['validation_score_range'] = 'Score must be between 1 and 5!';

// Filters
$_['filter_all_suppliers']   = 'All Suppliers';
$_['filter_all_evaluators']  = 'All Evaluators';
$_['filter_score_range']     = 'Score Range';
$_['filter_date_range']      = 'Date Range';

// Comparison
$_['comparison_title']       = 'Supplier Comparison';
$_['comparison_select']      = 'Select suppliers to compare';
$_['comparison_criteria']    = 'Comparison Criteria';
$_['comparison_results']     = 'Comparison Results';

// Trends
$_['trends_monthly']         = 'Monthly Trend';
$_['trends_quarterly']       = 'Quarterly Trend';
$_['trends_yearly']          = 'Yearly Trend';
$_['trends_improvement']     = 'Improvement';
$_['trends_decline']         = 'Decline';
$_['trends_stable']          = 'Stable';

// Actions
$_['action_evaluate']        = 'Evaluate';
$_['action_re_evaluate']     = 'Re-evaluate';
$_['action_view_history']    = 'View History';
$_['action_compare']         = 'Compare';
$_['action_generate_report'] = 'Generate Report';

// Bulk Actions
$_['bulk_delete']            = 'Delete Selected';
$_['bulk_export']            = 'Export Selected';
$_['bulk_approve']           = 'Approve Selected';
$_['bulk_action_success']    = 'Action performed on %d evaluations successfully!';

// Search
$_['search_placeholder']     = 'Search evaluations...';
$_['search_results']         = 'Search results for: %s';
$_['search_no_results']      = 'No results found for: %s';

// API
$_['api_success']            = 'Operation completed successfully';
$_['api_error']              = 'Error performing operation';
$_['api_invalid_data']       = 'Invalid data provided';
$_['api_not_found']          = 'Evaluation not found';
$_['api_permission_denied']  = 'You do not have permission for this operation';
