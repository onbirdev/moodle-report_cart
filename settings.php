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
 * Shopping Cart Report Plugin for Moodle.
 *
 * @package     report_cart
 * @author      MohammadReza PourMohammad <onbirdev@gmail.com>
 * @copyright   2024 MohammadReza PourMohammad
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link        https://onbir.dev
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add(
    'reports',
    new admin_externalpage(
        'report_cart',
        get_string('pluginname', 'report_cart'),
        new moodle_url('/report/cart/index.php'),
        'report/cart:view',
    ),
);

$settings = null;
