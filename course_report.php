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

use core\session\manager;
use report_cart\local\form\course_report_filter_form;
use report_cart\local\object\course_report_search;
use report_cart\local\table\course_report_table;

require_once('../../config.php');

// Global Moodle objects and libraries.
global $PAGE, $OUTPUT;

// Ensure the user is logged in and has the required capability to view the report.
require_login();
$context = context_system::instance();
require_capability('report/cart:view', $context);

$logformat = optional_param('download', '', PARAM_ALPHA);

// Define the page URL and metadata for the report.
$url = new moodle_url('/report/cart/course_report.php');
$title = get_string('course_report', 'report_cart');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report'); // Use the "report" layout for consistent styling.
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->set_url($url);

// Handle filter reset before processing form data.
if (optional_param('clearfilters', '', PARAM_RAW) !== '') {
    redirect($url);
}

// Initialize the filter form and search helper.
$mform = new course_report_filter_form();
$search = new course_report_search();

if ($data = $mform->get_data()) {
    $search->load_from_form($data);
} else {
    $search->load_from_params();
}

$filterparams = $search->get_filter_params();
$mform->set_data($filterparams);

// Configure the report table.
$table = new course_report_table('payments-report');
$table->set_sql(
    $search->get_sql_fields(),
    $search->get_sql_from(),
    $search->get_sql_where(),
    $search->get_sql_params(),
);
$table->define_baseurl($PAGE->url->out(false, $filterparams));

if ($logformat !== '') {
    manager::write_close();
    $table->download($logformat);
    exit;
}

// Render the page.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$mform->display();

$stats = $search->get_stats();
echo html_writer::start_div('alert alert-info');
echo html_writer::div(
    get_string('total_amount_full', 'report_sadad', [
        'amount' => number_format($stats->total_amount),
        'currency' => '',
    ]),
);
echo html_writer::div(
    get_string('total_count_full', 'report_sadad', [
        'count' => number_format($stats->total_orders),
    ]),
);
echo html_writer::end_div();

$table->out(25, false);

echo $OUTPUT->footer();