<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.05.2019
 * Time: 18:41
 */

$_['d_quick_order_setting'] = array(
    'config' => 'd_quick_order',

    'selector' => '#button-cart',
    'text_button' => array('1' => 'Quick Order'),
    'button_style' => array(
        'color' => 'white',
        'border' => 'black',
        'background_color' => '#faa732',

        'hover_color' => 'white',
        'hover_border' => 'black',
        'hover_background_color' => '#9a5d5d',
    ),

    'modal_heading_title' => array('1' => 'Quick Order'),
    'modal_description' => array('1' => 'Quick Order description'),
    'text_modal_button' => array('1' => 'Quick Order Now'),

    'modal_field' => array(
        'name' => true,
        'name_required' => true,
        'email' => true,
        'email_required' => true,
        'comment' => true,
        'comment_required' => true,

        'phone_format' => '+ 9(999)999 - 99 - 99',
    ),
);

$_['d_quick_order_statuses'] = array(
    '1' => array('order_status_id' => '0', 'name' => 'Processing'),
    '2' => array('order_status_id' => '1', 'name' => 'Processed'),
    '3' => array('order_status_id' => '2', 'name' => 'Canceled'),
);


