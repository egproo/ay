<?php
// Heading
$_['heading_title']                      = 'Accounting Periods';

// Text
$_['text_success']                       = 'Success: You have modified accounting periods!';
$_['text_list']                          = 'Accounting Periods List';
$_['text_add']                           = 'Add Accounting Period';
$_['text_edit']                          = 'Edit Accounting Period';
$_['text_close']                         = 'Close Accounting Period';
$_['text_open']                          = 'Open';
$_['text_closed']                        = 'Closed';
$_['text_locked']                        = 'Locked';
$_['text_close_success']                 = 'Success: Accounting period has been closed!';
$_['text_reopen_success']                = 'Success: Accounting period has been reopened!';
$_['text_lock_success']                  = 'Success: Accounting period has been locked!';
$_['text_close_info']                    = 'Closing an accounting period will prevent further transactions from being posted to this period. This action can be reversed by reopening the period, unless it has been locked.';
$_['text_confirm']                       = 'Are you sure?';
$_['text_confirm_reopen']                = 'Are you sure you want to reopen this period? This will delete any closing entries that were created.';
$_['text_confirm_lock']                  = 'Are you sure you want to lock this period? This action cannot be undone!';
$_['text_yes']                           = 'Yes';
$_['text_no']                            = 'No';
$_['text_no_results']                    = 'No results found';

// Column
$_['column_name']                        = 'Name';
$_['column_description']                 = 'Description';
$_['column_start_date']                  = 'Start Date';
$_['column_end_date']                    = 'End Date';
$_['column_status']                      = 'Status';
$_['column_action']                      = 'Action';

// Entry
$_['entry_name']                         = 'Name';
$_['entry_description']                  = 'Description';
$_['entry_start_date']                   = 'Start Date';
$_['entry_end_date']                     = 'End Date';
$_['entry_status']                       = 'Status';
$_['entry_date_range']                   = 'Date Range';
$_['entry_closing_notes']                = 'Closing Notes';
$_['entry_create_closing_entries']       = 'Create Closing Entries';

// Help
$_['help_create_closing_entries']        = 'If enabled, the system will automatically create journal entries to close revenue and expense accounts and transfer the net income/loss to retained earnings.';

// Button
$_['button_close']                       = 'Close Period';
$_['button_reopen']                      = 'Reopen Period';
$_['button_lock']                        = 'Lock Period';

// Error
$_['error_permission']                   = 'Warning: You do not have permission to modify accounting periods!';
$_['error_name']                         = 'Period name must be between 3 and 128 characters!';
$_['error_start_date']                   = 'Start date is required!';
$_['error_end_date']                     = 'End date is required!';
$_['error_date_range']                   = 'End date must be after start date!';
$_['error_delete']                       = 'Warning: This period cannot be deleted because it has journal entries or is closed/locked!';
$_['error_close']                        = 'Warning: Failed to close the accounting period!';
$_['error_reopen']                       = 'Warning: Failed to reopen the accounting period! Locked periods cannot be reopened.';
$_['error_lock']                         = 'Warning: Failed to lock the accounting period! Only closed periods can be locked.';
