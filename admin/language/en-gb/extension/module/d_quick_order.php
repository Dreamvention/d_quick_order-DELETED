<?php
/*
 *  location: admin/language
 */

//heading
$_['heading_title']                             = '<span style="color:#449DD0; font-weight:bold">Quick Order</span><span style="font-size:12px; color:#999"> by <a href="http://www.opencart.com/index.php?route=extension/extension&filter_username=Dreamvention" style="font-size:1em; color:#999" target="_blank">Dreamvention</a></span>';
$_['heading_title_main']                        = 'Quick Order';
$_['text_edit']                                 = 'Edit Quick Order';
$_['text_module']                               = 'Modules';
$_['text_undefined']                            = 'Undefined';

// main tabs
$_['tab_heading_orders']                        = 'Orders';
$_['tab_heading_orders_icon']                   = '<span class="fa fa-shopping-basket"></span>';
$_['tab_heading_setting']                       = 'Setting';
$_['tab_heading_setting_icon']                  = '<span class="fa fa-cog"></span>';
$_['tab_heading_instruction']                   = 'Instructions';
$_['tab_heading_instruction_icon']              = '<span class="fa fa-info-circle"></span>';

// Ajax responce
$_['ajax_success_delete']                       = 'Successfully deleted';
$_['ajax_success_uninstall']                    = 'Successfully uninstall module';
$_['ajax_error_delete']                         = 'Sorry! Something went wrong. If this repeats, contact the support please.';
$_['ajax_error_delete_empty_orderId']           = "Sorry! Empty order ID.";
$_['ajax_error_delete_order_status']            = "Sorry! You can't create order with this order ID.";

// tabs in settings tab
$_['tab_basic_setting_enable']                  = 'Enable Module';
$_['tab_basic_setting_selector']                = 'Choose selector';


$_['tab_basic_setting']                         = 'Basic Settings';
$_['tab_button_setting']                        = 'Button Settings';
$_['tab_modal_window_setting']                  = 'Modal Window Setting';
$_['tab_modal_fields_setting']                  = 'Modal Fields Setting';

$_['tab_modal_button_name']                     = 'Text order button';
$_['tab_modal_button_style_color']              = 'Button color';
$_['tab_modal_button_style_border']             = 'Button border color';
$_['tab_modal_button_style_bgColor']            = 'Button background color';

$_['tab_modal_button_style_hover_color']        = 'Button hover color';
$_['tab_modal_button_style_hover_border']       = 'Button hover border color';
$_['tab_modal_button_style_hover_bgColor']      = 'Button hover background color';

$_['tab_modal_modal_setting_title']             = 'Text title modal';
$_['tab_modal_modal_setting_description']       = 'Text description modal';
$_['tab_modal_modal_setting_button_submit']     = 'Text modal button confirm';

$_['tab_modal_fields_setting_name']             = 'Enable name field in modal window';
$_['tab_modal_fields_setting_name_require']     = 'Require name';
$_['tab_modal_fields_setting_email']            = 'Enable modal field in modal window';
$_['tab_modal_fields_setting_email_require']    = 'Require Email';
$_['tab_modal_fields_setting_comment']          = 'Enable comment field in modal window';
$_['tab_modal_fields_setting_comment_require']  = 'Require comment';
$_['tab_modal_fields_setting_phone_format']     = 'Phone validation format';


$_['column_order_id_number']                    = '#';
$_['column_order_id']                           = 'Order ID';
$_['column_product']                            = 'Product';
$_['column_product_name']                       = 'Product Name';
$_['column_product_description']                = "Product Description";
$_['column_product_qty']                        = "Product Qty";
$_['column_product_total']                      = "Total";
$_['column_product_status']                     = "Status";
$_['column_products_images']                    = "Products";
$_['column_product_price']                      = "Product Price";
$_['column_customer_name']                      = "Name";
$_['column_customer_name_input']                = "Enter name";
$_['column_customer_email']                     = "Email";
$_['column_customer_email_input']               = "Enter Email";
$_['column_customer_phone']                     = "Phone";
$_['column_customer_phone_input']               = "Enter phone";
$_['column_customer_comment']                   = "Comment";
$_['column_date_added']                         = "Date Added";
$_['column_sort_status']                        = "Status";
$_['column_action']                             = "Actions";

// filters
$_['filter_customer_name']                      = 'Enter name';
$_['filter_customer_email']                     = 'Enter email';
$_['filter_product_phone']                      = 'Enter phone';
$_['filter_order_status']                       = 'Status';


// Settings
$_['entry_design_custom_style']                 = 'Set Custom Class';

$_['text_install_twig_support']                 = 'Install Twig';
$_['text_install_event_support']                = 'Install Events';
$_['help_twig_support']                         = '<h4>Activate Twig support</h4><p>The Quick Order Module runs on twig that allows you to edit your twig files vertually with Twig manager from your opencart Admin. Click install Twig.</p>';
$_['help_event_support']                        = '<h4>Activate Event support</h4><p>The Quick Order Module uses the latest Opencart Events mechanism instead of Vqmod/Ocmod. Old version of Opencart do not support events but you can enable them here. Click install Events. </p>';
$_['help_layout']                               = '<h4>What is layout?</h4><p>A layout is a simple way of defining how your posts should be presented on the page. You can set 1 row with 1 column: you will see only one post under another. Dull, wouldn\'t you say?. Lest spice it up. Try setting 1 row - 1 columns, 2 row - 2 columns, 3 row - 3 columns. Make your blog standout.</p>';
$_['help_home_category']                        = '<h4>What is Home category?</h4><p>It is best prectice to pick one category which will be your Home category. When going to the root of your blog, this category will be displayed. Every other category should be a child to it. This way you can edit the description and title and other things a category lets you do.</p>';

$_['help_range_type']                           = '(Optional, leave empty if not needed)';
$_['help_incremental_yes']                      = '(Update and / or add data)';
$_['help_incremental_no']                       = '(Remove all the old data before importing)';
$_['help_review_social_login']                  = '<h4>Social login</h4><p>If you have social login installed, you can allow visitors to write comments in posts by registrting through their social network account. You can <a href="http://www.opencart.com/index.php?route=extension/extension/info&extension_id=16711" target="_blank">upgrade social login here</a></p>';
$_['help_style_short_description_display']      = '<h4>How it works?</h4><p>If you want to use any style for your post short description, turn on this switch. Be careful, this option disables the settings of "Set short description length" and will include html tags in short description.</p>';

//button
$_['button_save_and_stay']                      = 'Save and stay';
$_['button_enabled_ssl']                        = 'Specify URL';
$_['button_create_order']                       = 'Create order';
$_['button_delete_order']                       = 'Delete order';
$_['button_uninstall']                          = 'Uninstall';

//success
$_['success_modifed']                           = 'Success: You have modified module Quick Order Module!';
$_['success_twig_compatible']                   = 'Success: Twig support enabled. Please go to Quick Order Module!';
$_['success_enabled_ssl']                       = 'Success: You have modified the SSL link. CLick save to save the data to your settings!';
//error
$_['error_permission']                          = 'Warning: You do not have permission to modify this module!';

//update
$_['entry_update']                              = 'Your version is %s';
$_['button_update']                             = 'Check update';
$_['success_no_update']                         = 'Super! You have the latest version.';
$_['warning_new_update']                        = 'Wow! There is a new version available for download.';
$_['error_update']                              = 'Sorry! Something went wrong. If this repeats, contact the support please.';
$_['error_failed']                              = 'Oops! We could not connect to the server. Please try again later.';

//support
$_['text_support']                              = 'Support';
$_['entry_support']                             = 'Support<br/><small>Create a ticket. If you find a bug, even in a free version, please let us know.</small>';
$_['button_support']                            = 'Open ticket';

//instruction
$_['tab_instruction']                           = 'Instructions';
$_['text_instruction']                          = 'to be added...';
$_['text_powered_by']							= 'Tested with <a href="https://shopunity.net/extension/blog-module" target="_blank">Shopunity.net</a> <br> Find more amazing extensions at <a href="https://dreamvention.ee/" target="_blank">Dreamvention.ee</a>';

$_['text_not_found'] = '<div class="jumbotron">
          <h1>Please install Shopunity</h1>
          <p>Before you can use this module you will need to install Shopunity. Simply download the archive for your version of opencart and install it view Extension Installer or unzip the archive and upload all the files into your root folder from the UPLOAD folder.</p>
          <p><a class="btn btn-primary btn-lg" href="https://shopunity.net/download" target="_blank">Download</a></p>
        </div>';
//welcome
$_['text_welcome_title']            = 'Quick Order';
$_['text_welcome_description']      = 'This quick order form, very easy to use, allows your customer to save time while creating their order. They will be able to add all the products they want within the same page. Easy to customize modal window. Increase sales now!';

$_['text_welcome_visual_editor']    = 'Powerfull SEO';
$_['text_welcome_building_blocks']  = 'Manage Author <br> permissions';
$_['text_welcome_mobile_ready']     = 'Choose different <br> layouts and themes';
$_['text_welcome_increase_sales']   = 'Add Dozens <br> of modules';

$_['button_setup']                  = 'Setup';
$_['success_setup']                 = 'Success: You have now setup Quick Order Module! You should now see the blog link in the frontend category menu';
$_['text_pro']                      = '<a href="https://dream.page.link/tc4X" target="_blank">For SEO Urls and Quick Order Modules get the Quick Order Module Pro</a>';