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

use report_cart\form\cart_report_filter_form;

require_once('../../config.php');

global $CFG, $PAGE, $OUTPUT, $DB;

require_login();

$context = context_system::instance();
require_capability('report/cart:view', $context);

$url = new moodle_url('/report/cart/index.php');
$title = get_string('pluginname', 'report_cart');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagetype('admin-' . $PAGE->pagetype);
$PAGE->set_url($url);

$form = new cart_report_filter_form($url, [], 'get');

if ($form->is_cancelled()) {
    redirect($url);
    exit();
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
