<?php
// Heading
$_['heading_title']        = 'BOX NOW Delivery';
$_['heading_title_report'] = 'BOX NOW Delivery Report';
$_['heading_help']         = 'To use the BOX NOW shipping module you need to register <a target="_blank" href="https://boxnow.bg/e-shops">here</a>, then fill in the credentials you receive below.';

// Text
$_['text_home']      = 'Dashboard';
$_['text_extension']  = 'Extensions';
$_['text_success']   = 'Success: You have modified the BOX NOW shipping module!';
$_['text_edit']      = 'Edit BOX NOW Shipping Module';

$_['tab_settings'] = 'General Settings';
$_['tab_pricing']  = 'Pricing';

// Entry - settings
$_['entry_api_url']             = 'API URL';
$_['entry_client_id']           = 'Client ID';
$_['entry_client_secret']       = 'Client Secret';
$_['entry_warehouse_number']    = 'Warehouse number(s)';
$_['entry_partner_id']          = 'Partner ID';
$_['entry_partner_name']        = 'Sender name';
$_['entry_partner_email']       = 'Sender email';
$_['entry_partner_phone']       = 'Sender phone';
$_['entry_partner_phone_hint']  = '+359';
$_['entry_geo_zone']            = 'Geo Zone';
$_['entry_status']              = 'Status';
$_['entry_sort_order']          = 'Sort Order';

// Entry - pricing
$_['entry_banned_weight']      = 'Disallow if weight is over:';
$_['entry_unit_of_measure']    = 'Unit of measure';
$_['entry_kilogram']           = 'Kilogram';
$_['entry_gram']                = 'Gram';
$_['entry_cart_weight']         = 'Weight to use when the cart has none';
$_['entry_free_shipping']      = 'Order total above which shipping is free';
$_['entry_tax_class']          = 'Tax Class';

$_['entry_categories']           = 'Select Categories';
$_['entry_categories_available']  = 'Available categories';
$_['entry_categories_selected']  = 'Categories that hide the method';
$_['entry_categories_search']    = 'Search...';
$_['help_categories']            = 'If a cart product belongs to one of the selected categories, the BOX NOW shipping method will be hidden.';

$_['entry_payment_modules'] = 'Allowed for the following payment methods';
$_['help_payment_modules']  = 'Some payment methods (e.g. Cash on Delivery) may not be combinable with the BOX NOW shipping module.';

$_['entry_weight_from']  = 'Weight - From';
$_['entry_weight_to']    = 'Weight - To (inclusive)';
$_['entry_fixed_price']  = 'Fixed price';

$_['button_save']              = 'Save';
$_['button_back']              = 'Back';
$_['button_remove']            = 'Remove';
$_['button_weight_value_add']  = 'Add weight range';
$_['button_view']              = 'View';

$_['text_all_zones'] = 'All Zones';
$_['text_enabled']   = 'Enabled';
$_['text_disabled']  = 'Disabled';
$_['text_none']      = ' --- None --- ';
$_['text_missing']   = 'Missing';
$_['text_no_results'] = 'No BOX NOW orders found!';
$_['text_pagination'] = 'Showing %d to %d of %d (%d Pages)';

// Report
$_['column_order_id']        = 'Order ID';
$_['column_customer']        = 'Customer';
$_['column_order_status']    = 'Order Status';
$_['column_total']           = 'Total';
$_['column_total_products']  = 'Qty';
$_['column_date_added']      = 'Date Added';
$_['column_vouchers']        = 'Waybills';
$_['column_info']            = 'Info / Download label';
$_['column_locker_id']       = 'Locker ID';
$_['column_box_now_status']  = 'Status';

$_['text_voucher_success']        = 'Created';
$_['text_voucher_pending']        = 'Pending';
$_['text_voucher_cancelled']      = 'Cancelled';
$_['text_voucher_notfound']       = 'Not found';
$_['text_voucher_send']           = 'Create waybill';
$_['text_voucher_cancel']         = 'Cancel waybill';
$_['text_voucher_cancel_confirm'] = 'Are you sure you want to cancel this waybill?';
$_['text_voucher_status_success'] = 'The waybill was created successfully';
$_['text_voucher_cancel_success'] = 'The waybill was cancelled successfully';
$_['text_voucher_cancel_error']   = 'There was an error cancelling the waybill';

$_['status_message_error'] = 'The waybill could not be created (Error code: %s). %s';

$_['entry_customer_telephone']  = 'Customer telephone';
$_['entry_select_warehouse']    = 'Warehouse ID';
$_['entry_compartment_size']    = 'Compartment size';
$_['entry_compartment_small']   = 'Small (60x45x8 cm)';
$_['entry_compartment_medium']  = 'Medium (60x45x17 cm)';
$_['entry_compartment_large']   = 'Large (60x45x36 cm)';

// Error
$_['error_order_locker']    = 'Order #%s has no locker selected. Pick a locker from the map or enter the locker ID and try again.';
$_['error_order_telephone'] ='Order #%s has no valid customer telephone. Enter it in the "Customer telephone" field of the row and try again.';
$_['error_permission'] ='Warning: You do not have permission to modify the BOX NOW shipping module!';
