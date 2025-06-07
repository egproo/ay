<?php
// File: admin/language/en-gb/migration/migration.php
// Language: English - Great Britain (en-gb)
// Description: Language file for System Migration section

// --- System Migration Section ---
$_['text_migration']                = 'System Migration';
$_['text_odoo_migration']           = 'Migrate from Odoo';
$_['text_woocommerce_migration']    = 'Migrate from WooCommerce';
$_['text_shopify_migration']        = 'Migrate from Shopify';
$_['text_excel_migration']          = 'Import from Excel Files';
$_['text_migration_review']         = 'Review & Approve Imported Data';

// --- Success and Error Messages ---
$_['text_success']                  = 'Data imported successfully!';
$_['text_error']                    = 'Error occurred during data import!';

// --- Buttons ---
$_['button_import']                 = 'Import';
$_['button_review']                 = 'Review';
$_['button_approve']                = 'Approve';
$_['button_reject']                 = 'Reject';
$_['button_rollback']               = 'Rollback';

// --- Headings ---
$_['heading_title']                 = 'System Migration';
$_['heading_import']                = 'Import Data';
$_['heading_review']                = 'Review Data';
$_['heading_mapping']               = 'Field Mapping';
$_['heading_history']               = 'Import History';

// --- Alerts ---
$_['alert_backup']                  = 'Make sure to create a backup before proceeding!';
$_['alert_required_fields']         = 'Please ensure all required fields are filled';
$_['alert_review_needed']           = 'Data must be reviewed before final approval';

// --- Fields ---
$_['entry_source']                  = 'Source System:';
$_['entry_file']                    = 'Data File:';
$_['entry_encoding']                = 'File Encoding:';
$_['entry_delimiter']               = 'Field Delimiter:';
$_['entry_mapping']                 = 'Field Mapping:';
$_['entry_skip_rows']               = 'Skip Rows:';
$_['entry_batch_size']              = 'Batch Size:';

// --- Columns ---
$_['column_source']                 = 'Source';
$_['column_destination']            = 'Destination';
$_['column_status']                 = 'Status';
$_['column_date']                   = 'Date';
$_['column_user']                   = 'User';
$_['column_records']                = 'Records Count';
$_['column_action']                 = 'Action';

// --- Statuses ---
$_['status_pending']                = 'Pending Review';
$_['status_approved']               = 'Approved';
$_['status_rejected']               = 'Rejected';
$_['status_completed']              = 'Completed';
$_['status_failed']                 = 'Failed';

// --- Errors ---
$_['error_permission']              = 'Warning: You do not have permission to modify system migrations!';
$_['error_file']                    = 'File could not be found!';
$_['error_encoding']                = 'Invalid file encoding!';
$_['error_mapping']                 = 'Field mapping must be specified!';
$_['error_required']                = 'This field is required!';
$_['error_credentials']             = 'Invalid credentials!';
$_['error_connection']              = 'Failed to connect to source system!';
$_['error_invalid_source']          = 'Invalid source system!';
$_['error_processing']              = 'Error occurred while processing data!';
$_['error_validation']              = 'Data validation failed: %s';
$_['error_sync']                    = 'Data synchronization failed: %s';