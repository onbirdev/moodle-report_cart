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

namespace report_cart\local\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use enrol_cart\local\object\cart_status_interface;
use html_writer;
use moodle_url;
use report_cart\local\object\cart;
use table_sql;

/**
 * SQL table for displaying shopping cart report entries broken down by course.
 */
class course_report_table extends table_sql {
    /** @var string Field name containing the user ID. */
    public $useridfield = 'user_id';

    /**
     * Constructor.
     *
     * @param string $uniqueid Unique ID for this table instance.
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $columns = ['username', 'fullname', 'shortname', 'amount', 'currency', 'gateway', 'timecreated', 'status'];
        $headers = [
            get_string('username'),
            get_string('firstname'),
            get_string('course'),
            get_string('amount', 'report_cart'),
            get_string('currency', 'report_cart'),
            get_string('gateway', 'report_cart'),
            get_string('purchasedate', 'report_cart'),
            get_string('status'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(false);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);
    }

    /**
     * Format the course shortname column.
     *
     * @param object $row Database row object.
     * @return string
     */
    public function col_shortname($row) {
        if ($this->is_downloading()) {
            return $row->shortname;
        }
        return format_string($row->shortname);
    }

    /**
     * Format the user full name column with profile link.
     *
     * @param object $row Database row object.
     * @return string
     */
    public function col_fullname($row) {
        $fullname = $row->firstname . ' ' . $row->lastname;
        if ($this->is_downloading()) {
            return $fullname;
        }
        $userid = $row->{$this->useridfield};
        $profileurl = new moodle_url('/user/profile.php', ['id' => $userid]);
        return html_writer::link($profileurl, $fullname);
    }

    /**
     * Format the payable amount column.
     *
     * @param object $row Database row object.
     * @return string
     */
    public function col_amount($row) {
        if ($this->is_downloading()) {
            return $row->amount;
        }
        return number_format((float) ($row->amount ?? 0));
    }

    /**
     * Format the checkout timestamp column.
     *
     * @param object $row Database row object.
     * @return string
     */
    public function col_timecreated($row) {
        return $row->timecreated ? userdate($row->timecreated, '%Y-%m-%d %H:%M', 99, false, false) : '';
    }

    /**
     * Format the cart status column.
     *
     * @param object $row Database row object.
     * @return string
     */
    public function col_status($row) {
        $statusoptions = cart::get_status_options();
        $statustext = $statusoptions[$row->status] ?? $row->status;
        if ($this->is_downloading()) {
            return $statustext;
        }

        $class = 'badge badge-secondary';
        switch ($row->status) {
            case cart_status_interface::STATUS_DELIVERED:
                $class = 'badge badge-success';
                break;
            case cart_status_interface::STATUS_CHECKOUT:
                $class = 'badge badge-warning';
                break;
        }
        return html_writer::span($statustext, $class);
    }

    /**
     * Download the table data in the specified format.
     *
     * @param string $logformat Download format (e.g., csv, excel).
     * @return void
     */
    public function download($logformat) {
        $filename = get_string('course_report', 'report_cart') . '_' .
            userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        $this->is_downloading($logformat, $filename);
        $this->out(0, false);
    }
}
