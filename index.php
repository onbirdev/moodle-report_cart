<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Shopping Cart Report Plugin for Moodle
 *
 * @package     report_cart
 * @author      MohammadReza PourMohammad <onbirdev@gmail.com>
 * @copyright   2024 MohammadReza PourMohammad
 * @link        https://onbir.dev
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_cart\form\cart_search_form;
use report_cart\object\cart_search;

require_once('../../config.php');

// Global Moodle objects and libraries.
global $CFG, $PAGE, $OUTPUT, $DB;

// Ensure the user is logged in and has the required capability to view the report.
require_login();
$context = context_system::instance();
require_capability('report/cart:view', $context);

// Define the page URL and metadata for the report.
$url = new moodle_url('/report/cart/index.php');
$title = get_string('pluginname', 'report_cart');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report'); // Use the "report" layout for consistent styling.
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->set_url($url);

// Initialize the filter form for the report.
$form = new cart_search_form($url, [], 'get');

// Handle form cancellation.
if ($form->is_cancelled()) {
    redirect($url);
    exit();
}

// Process filter data and fetch the relevant cart reports.
$search = new cart_search();
$search->load_data($form->get_data());
$carts = $search->get_all();
$total = $search->count_all();
$offset = $search->get_offset();
$begin = $offset + 1;
$end = $begin + count($carts) - 1;

// Create and populate the report table.
$table = new html_table();
$table->head = [
    '#',
    $search->get_table_column_head('id', get_string('order_id', 'enrol_cart')),
    get_string('user', 'enrol_cart'),
    $search->get_table_column_head('coupon_code', get_string('coupon_code', 'enrol_cart')),
    get_string('discount', 'enrol_cart'),
    $search->get_table_column_head('payable', get_string('payable', 'enrol_cart')),
    $search->get_table_column_head('status', get_string('cart_status', 'enrol_cart')),
    $search->get_table_column_head('checkout_at', get_string('checkout_at', 'report_cart')),
];
$table->attributes = ['class' => 'generaltable'];
$table->data = [];
$i = $offset;

// Add a row to the table indicating no results were found, spanning all columns.
if (empty($carts)) {
    $cell = new html_table_cell(get_string('no_results_found', 'report_cart'));
    $cell->colspan = 8;
    $cell->attributes['class'] = 'text-center';
    $table->data[] = new html_table_row([$cell]);
}

// Populate the table rows with cart data.
foreach ($carts as $cart) {
    $i++;

    $row = [
        $i,
        html_writer::tag('a', $cart->id, [
            'href' => $cart->get_view_url(),
        ]),
        html_writer::tag('a', $cart->user->full_name, ['href' => $cart->user->profile_url]),
        $cart->coupon_code ?: '--',
        $cart->get_final_discount_amount()
            ? html_writer::tag('span', $cart->get_final_discount_amount_formatted(), [
                'class' => 'currency',
            ])
            : '--',
        html_writer::tag('span', $cart->get_payable_formatted(), ['class' => 'currency']),
        $cart->get_status_name_formatted_html(),
        $cart->is_delivered ? userdate($cart->checkout_at) : '--',
    ];

    $table->data[] = new html_table_row($row);
}

// Render the page.
echo $OUTPUT->header();
$form->display();
if (!empty($carts)) {
    echo get_string('pagination_info', 'report_cart', [
        'begin' => number_format($begin),
        'end' => number_format($end),
        'total' => number_format($total),
    ]);
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar(
    $total,
    $search->get_page(),
    $search->get_perpage(),
    new moodle_url('/report/cart/index.php', $search->get_url_params()),
);
echo $OUTPUT->footer();
