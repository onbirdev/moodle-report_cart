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

namespace report_cart\local\object;

defined('MOODLE_INTERNAL') || die();

use enrol_cart\local\object\cart_status_interface;

/**
 * Handles filter parameter parsing, SQL query construction, and summary statistics
 * for the shopping cart course report.
 */
class course_report_search {
    /** @var array Filter parameters collected from form or URL. */
    private array $filterparams = [];

    /** @var string Constructed SQL WHERE clause. */
    private string $where = '';

    /** @var array Bound SQL parameters. */
    private array $params = [];

    /** @var bool Whether SQL conditions have been built. */
    private bool $built = false;

    /**
     * Load filter parameters from submitted form data.
     *
     * @param object $data Form submission data.
     * @return void
     */
    public function load_from_form(object $data): void {
        $this->filterparams = [];

        if (!empty($data->userid)) {
            $this->filterparams['userid'] = (int) $data->userid;
        }
        if (!empty($data->courseid)) {
            $this->filterparams['courseid'] = (int) $data->courseid;
        }
        if (isset($data->status)) {
            $this->filterparams['status'] = (string) $data->status;
        } else {
            $this->filterparams['status'] = cart_status_interface::STATUS_DELIVERED;
        }
        if (!empty($data->gateway)) {
            $this->filterparams['gateway'] = (string) $data->gateway;
        }
        if (!empty($data->datefrom)) {
            $this->filterparams['datefrom'] = (int) $data->datefrom;
        }
        if (!empty($data->dateto)) {
            $this->filterparams['dateto'] = (int) $data->dateto;
        }

        $this->built = false;
    }

    /**
     * Load filter parameters from URL query parameters.
     *
     * @return void
     */
    public function load_from_params(): void {
        $this->filterparams = [
            'userid' => optional_param('userid', null, PARAM_INT),
            'courseid' => optional_param('courseid', null, PARAM_INT),
            'status' => optional_param('status', cart_status_interface::STATUS_DELIVERED, PARAM_ALPHANUMEXT),
            'gateway' => optional_param('gateway', null, PARAM_ALPHA),
            'datefrom' => optional_param('datefrom', null, PARAM_INT),
            'dateto' => optional_param('dateto', null, PARAM_INT),
        ];

        $this->built = false;
    }

    /**
     * Return the current filter parameters (suitable for form defaults and pagination URLs).
     *
     * @return array
     */
    public function get_filter_params(): array {
        return $this->filterparams;
    }

    /**
     * Return the SELECT fields list for the report table.
     *
     * @return string
     */
    public function get_sql_fields(): string {
        return '
            eci.id,
            ec.id as order_id,
            ec.user_id,
            u.username,
            u.firstname,
            u.lastname,
            c.shortname,
            eci.payable as amount,
            p.currency as currency,
            p.gateway,
            ec.checkout_at as timecreated,
            ec.status
        ';
    }

    /**
     * Return the FROM/JOIN clause for the report query.
     *
     * @return string
     */
    public function get_sql_from(): string {
        return "
            {enrol_cart} ec
            JOIN {user} u ON u.id = ec.user_id
            JOIN {enrol_cart_items} eci ON eci.cart_id = ec.id
            JOIN {enrol} e ON e.id = eci.instance_id
            JOIN {course} c ON c.id = e.courseid
            LEFT JOIN {payments} p ON p.component = 'enrol_cart' AND p.itemid = ec.id
        ";
    }

    /**
     * Return the constructed SQL WHERE clause.
     *
     * @return string
     */
    public function get_sql_where(): string {
        $this->build_conditions();
        return $this->where;
    }

    /**
     * Return the bound parameters for the SQL WHERE clause.
     *
     * @return array
     */
    public function get_sql_params(): array {
        $this->build_conditions();
        return $this->params;
    }

    /**
     * Fetch aggregate order count and total amount matching current filters.
     *
     * @return object Object containing `total_orders` and `total_amount`.
     */
    public function get_stats(): object {
        global $DB;

        $statsql = "
            SELECT
                COUNT(*) as total_orders,
                SUM(eci.payable) as total_amount
            FROM {$this->get_sql_from()}
            WHERE {$this->get_sql_where()}
        ";

        $stats = $DB->get_record_sql($statsql, $this->get_sql_params());

        return (object) [
            'total_orders' => (int) ($stats->total_orders ?? 0),
            'total_amount' => (float) ($stats->total_amount ?? 0),
        ];
    }

    /**
     * Build the SQL WHERE clause and parameter bindings from active filters.
     *
     * @return void
     */
    private function build_conditions(): void {
        if ($this->built) {
            return;
        }

        $wheres = ["p.component = 'enrol_cart'"];
        $params = [];

        if (!empty($this->filterparams['userid'])) {
            $wheres[] = 'ec.user_id = :userid';
            $params['userid'] = $this->filterparams['userid'];
        }

        if (!empty($this->filterparams['courseid'])) {
            $wheres[] = 'c.id = :courseid';
            $params['courseid'] = $this->filterparams['courseid'];
        }

        if (!empty($this->filterparams['status'])) {
            $wheres[] = 'ec.status = :status';
            $params['status'] = $this->filterparams['status'];
        }

        if (!empty($this->filterparams['gateway'])) {
            $wheres[] = 'p.gateway = :gateway';
            $params['gateway'] = $this->filterparams['gateway'];
        }

        if (!empty($this->filterparams['datefrom'])) {
            $wheres[] = 'p.timecreated >= :datefrom';
            $params['datefrom'] = strtotime(date('Y-m-d', $this->filterparams['datefrom']));
        }

        if (!empty($this->filterparams['dateto'])) {
            $wheres[] = 'p.timecreated <= :dateto';
            $params['dateto'] = strtotime(date('Y-m-d 23:59:59', $this->filterparams['dateto']));
        }

        $this->where = implode(' AND ', $wheres);
        $this->params = $params;
        $this->built = true;
    }
}
