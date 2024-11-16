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

namespace report_cart\object;

use enrol_cart\object\base_object;
use enrol_cart\object\cart_status_interface;

/**
 * Class cart_search
 *
 * Handles the construction and execution of a search query for shopping cart data.
 */
class cart_search extends base_object {
    /**
     * Parameters for the SQL query.
     *
     * @var array
     */
    private array $params = [];

    /**
     * Search filter for cart ID.
     *
     * @var string|null
     */
    private ?string $id = null;

    /**
     * Search filter for user ID.
     *
     * @var string|null
     */
    private ?string $user = null;

    /**
     * Search filter for coupon code.
     *
     * @var string|null
     */
    private ?string $couponcode = null;

    /**
     * Search filter for cart status.
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Search filter for start date of checkout.
     *
     * @var int|null
     */
    private ?int $from = null;

    /**
     * Search filter for end date of checkout.
     *
     * @var int|null
     */
    private ?int $to = null;

    /**
     * Load data into the search filters from an input object.
     *
     * @param object|null $data Input data containing search criteria.
     * @return void
     */
    public function load_data(?object $data): void {
        $fields = ['id', 'user', 'couponcode', 'status', 'from', 'to'];
        foreach ($fields as $field) {
            if (isset($data->$field) && $data->$field != '') {
                $this->$field = $data->$field;
            }
        }
    }

    /**
     * Build the WHERE clause for the SQL query.
     *
     * @return string The constructed WHERE clause.
     */
    private function build_where(): string {
        $where = [];

        $this->add_condition($where, 'c.id = :id', 'id', $this->id);
        $this->add_condition($where, 'c.user_id = :user', 'user', $this->user);
        $this->add_condition($where, 'c.status = :status', 'status', $this->status);
        $this->add_condition($where, 'c.coupon_code = :coupon_code', 'coupon_code', $this->couponcode);

        if ($this->from !== null || $this->to !== null) {
            $this->add_condition(
                $where,
                'c.status = :status_delivered',
                'status_delivered',
                cart_status_interface::STATUS_DELIVERED,
            );
        }

        $this->add_condition(
            $where,
            'c.checkout_at >= :checkout_time_from',
            'checkout_time_from',
            $this->from ? strtotime(date('Y-m-d', $this->from)) : null,
        );
        $this->add_condition(
            $where,
            'c.checkout_at <= :checkout_time_to',
            'checkout_time_to',
            $this->to ? strtotime(date('Y-m-d 23:59:59', $this->to)) : null,
        );

        return implode(' AND ', $where);
    }

    /**
     * Add a condition to the WHERE clause if the value is not null.
     *
     * @param array $where The list of conditions.
     * @param string $condition The SQL condition string.
     * @param string $param The parameter name.
     * @param mixed $value The parameter value to bind.
     * @return void
     */
    private function add_condition(array &$where, string $condition, string $param, $value): void {
        if ($value !== null) {
            $where[] = $condition;
            $this->params[$param] = $value;
        }
    }

    /**
     * Build the complete SQL query based on the applied filters.
     *
     * @return string The complete SQL query.
     */
    private function build_sql(): string {
        $select = 'c.*, u.username, u.email, u.firstname as first_name, u.lastname as last_name';
        $where = $this->build_where();

        return "SELECT {$select} FROM {enrol_cart} c INNER JOIN {user} u ON c.user_id = u.id" .
            (!empty($where) ? " WHERE {$where}" : '');
    }

    /**
     * Retrieve all carts that match the search criteria.
     *
     * @return cart[] An array of cart objects that match the search criteria.
     */
    public function get_all(): array {
        global $DB;
        $rows = $DB->get_records_sql($this->build_sql(), $this->params);
        return cart::populate($rows);
    }
}
