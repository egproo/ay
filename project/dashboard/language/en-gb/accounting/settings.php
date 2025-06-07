<?php
// Heading
$_['heading_title']                      = 'Accounting Settings';

// Text
$_['text_success']                       = 'Success: You have modified accounting settings!';
$_['text_edit']                          = 'Edit Accounting Settings';
$_['text_enabled']                       = 'Enabled';
$_['text_disabled']                      = 'Disabled';
$_['text_select']                        = '--- Please Select ---';
$_['text_accounting_info']               = 'Accounting settings allow you to configure how inventory movements affect your accounting system. When enabled, each inventory movement will create corresponding journal entries.';
$_['text_inventory_mapping_info']        = 'Configure which accounts should be used for different types of inventory transactions. For each transaction type, you need to specify an inventory account (usually an asset) and a contra account.';
$_['text_purchase']                      = 'Purchase';
$_['text_sale']                          = 'Sale';
$_['text_adjustment_increase']           = 'Adjustment Increase';
$_['text_adjustment_decrease']           = 'Adjustment Decrease';
$_['text_transfer_in']                   = 'Transfer In';
$_['text_transfer_out']                  = 'Transfer Out';
$_['text_initial']                       = 'Initial Stock';
$_['text_return_in']                     = 'Return In';
$_['text_return_out']                    = 'Return Out';
$_['text_scrap']                         = 'Scrap';
$_['text_production']                    = 'Production';
$_['text_consumption']                   = 'Consumption';
$_['text_cost_adjustment']               = 'Cost Adjustment';
$_['text_purchase_description']          = 'When inventory is purchased, debit inventory account and credit accounts payable or cash.';
$_['text_sale_description']              = 'When inventory is sold, debit cost of goods sold and credit inventory account.';
$_['text_adjustment_increase_description'] = 'When inventory is increased through adjustment, debit inventory account and credit inventory adjustment account.';
$_['text_adjustment_decrease_description'] = 'When inventory is decreased through adjustment, debit inventory adjustment account and credit inventory account.';

// Entry
$_['entry_status']                       = 'Status';
$_['entry_inventory_account']            = 'Inventory Account';
$_['entry_contra_account']               = 'Contra Account';
$_['entry_description']                  = 'Description';

// Tab
$_['tab_general']                        = 'General';
$_['tab_inventory']                      = 'Inventory Mapping';

// Column
$_['column_transaction_type']            = 'Transaction Type';
$_['column_inventory_account']           = 'Inventory Account';
$_['column_contra_account']              = 'Contra Account';
$_['column_description']                 = 'Description';

// Error
$_['error_permission']                   = 'Warning: You do not have permission to modify accounting settings!';
