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

use core\dataformat;
use enrol_cart\local\formatter\currency_formatter;
use enrol_cart\local\helper\cart_helper;
use enrol_cart\local\object\base_object;
use enrol_cart\local\object\cart_status_interface;
use html_writer;
use moodle_recordset;
use moodle_url;

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
     * Search filter for payment gateway.
     *
     * @var string|null
     */
    private ?string $gateway = null;

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
     * Pagination limit (number of records per page).
     *
     * @var int
     */
    private int $perpage = 30;

    /**
     * Sorting column name.
     *
     * @var string|null
     */
    private ?string $sort = null;

    /**
     * Sorting direction.
     *
     * @var string|null
     */
    private ?string $dir = null;

    /**
     * Initializes the sorting properties.
     *
     * @return void
     */
    public function init(): void {
        $this->sort = $this->get_sort();
        $this->dir = $this->get_dir();
    }

    /**
     * Load data into the search filters from an input object.
     *
     * @param object|null $data Input data containing search criteria.
     * @return void
     */
    public function load_data(?object $data): void {
        $fields = ['id', 'user', 'couponcode', 'status', 'gateway', 'from', 'to'];
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
        $this->params = [];

        $this->add_condition($where, 'c.id = :id', 'id', $this->id);
        $this->add_condition($where, 'c.user_id = :user', 'user', $this->user);
        $this->add_condition($where, 'c.status = :status', 'status', $this->status);
        $this->add_condition($where, 'c.coupon_code = :coupon_code', 'coupon_code', $this->couponcode);
        $this->add_condition($where, 'p.gateway = :gateway', 'gateway', $this->gateway);

        if ($this->from || $this->to) {
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
     * @param bool $paginated Whether to apply LIMIT and OFFSET for pagination.
     * @return string The complete SQL query.
     */
    private function build_query(bool $paginated = true): string {
        $select = 'c.*, u.username, u.email, u.firstname as first_name, u.lastname as last_name, p.gateway';
        $where = $this->build_where();

        $sql = "SELECT {$select} FROM {enrol_cart} c
                INNER JOIN {user} u ON c.user_id = u.id
                LEFT JOIN {payments} p ON p.component = 'enrol_cart' AND p.itemid = c.id";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        $sort = $this->get_sort();
        $dir = $this->get_dir();
        if ($sort && $dir) {
            $sql .= " ORDER BY {$sort} {$dir}";
        }

        if ($paginated) {
            $limit = $this->get_perpage();
            $offset = $this->get_offset();
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return $sql;
    }

    /**
     * Build the SQL query for counting the total number of matching carts.
     *
     * @return string The complete SQL query for counting.
     */
    private function build_count_query(): string {
        $where = $this->build_where();

        $sql = "SELECT COUNT(c.id) FROM {enrol_cart} c
                LEFT JOIN {payments} p ON p.component = 'enrol_cart' AND p.itemid = c.id";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        return $sql;
    }

    /**
     * Builds an SQL query to calculate the total payable amount grouped by currency.
     *
     * This function constructs an SQL query that calculates the sum of the `payable`
     * field for all matching rows in the `enrol_cart` table. The results are grouped
     * by currency to provide a breakdown of total payable amounts per currency.
     *
     * @return string The generated SQL query for summing the payable amounts.
     */
    private function build_sum_query(): string {
        $where = $this->build_where();

        $sql = "SELECT SUM(c.payable) as payable, c.currency FROM {enrol_cart} c
                LEFT JOIN {payments} p ON p.component = 'enrol_cart' AND p.itemid = c.id";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        $sql .= ' GROUP BY c.currency';

        return $sql;
    }

    /**
     * Retrieve all carts that match the search criteria.
     *
     * @return cart[] An array of cart objects that match the search criteria.
     */
    public function get_all(): array {
        global $DB;
        $rows = $DB->get_records_sql($this->build_query(), $this->params);
        return cart::populate($rows);
    }

    /**
     * Retrieve a recordset of all carts matching the search criteria without pagination.
     *
     * @return moodle_recordset
     */
    public function get_recordset(): moodle_recordset {
        global $DB;
        return $DB->get_recordset_sql($this->build_query(false), $this->params);
    }

    /**
     * Export all carts matching the current search criteria in the requested dataformat.
     *
     * @param string $dataformat Export format name (e.g., csv, excel, ods, json).
     * @return void
     */
    public function export(string $dataformat): void {
        $columns = [
            'id' => get_string('order_id', 'enrol_cart'),
            'username' => get_string('username'),
            'user' => get_string('user', 'enrol_cart'),
            'coupon_code' => get_string('coupon_code', 'enrol_cart'),
            'discount' => get_string('discount', 'enrol_cart'),
            'payable' => get_string('payable', 'enrol_cart'),
            'currency' => get_string('currency', 'report_cart'),
            'gateway' => get_string('gateway', 'report_cart'),
            'status' => get_string('cart_status', 'enrol_cart'),
            'checkout_at' => get_string('checkout_at', 'report_cart'),
        ];

        $filename = clean_filename(
            get_string('pluginname', 'report_cart') . '_' .
            userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false)
        );

        $recordset = $this->get_recordset();
        dataformat::download_data(
            $filename,
            $dataformat,
            $columns,
            $recordset,
            function($record) {
                $cart = cart::populate_one($record);
                return (object) [
                    'id' => $cart->id,
                    'username' => $cart->username,
                    'user' => $cart->user->full_name,
                    'coupon_code' => $cart->coupon_code ?: '--',
                    'discount' => $cart->get_final_discount_amount() ?? 0,
                    'payable' => (float) ($cart->payable ?? 0),
                    'currency' => $cart->get_final_currency(),
                    'gateway' => $cart->gateway ?: '--',
                    'status' => $cart->get_status_name(),
                    'checkout_at' => $cart->is_delivered
                        ? userdate($cart->checkout_at, '%Y-%m-%d %H:%M', 99, false, false)
                        : '--',
                ];
            },
        );
        $recordset->close();
    }

    /**
     * Count all records matching the search criteria.
     *
     * @return int The total number of records matching the search conditions.
     */
    public function count_all(): int {
        global $DB;
        return $DB->count_records_sql($this->build_count_query(), $this->params);
    }

    /**
     * Retrieves the total payable amounts grouped by currency.
     *
     * This function executes the SQL query generated by `build_sum_query` to calculate
     * the total payable amounts for all matching rows, grouped by currency. It also
     * formats the payable amounts for display.
     *
     * @return array An array of objects, where each object contains:
     *  - `payable`: The total payable amount.
     *  - `currency`: The currency of the payable amount.
     *  - `payable_formatted`: The formatted representation of the payable amount.
     */
    public function get_total_payable(): array {
        global $DB;
        $records = $DB->get_records_sql($this->build_sum_query(), $this->params);
        $out = [];
        foreach ($records as $record) {
            $currency = $record->currency ?: cart_helper::get_config('payment_currency');
            $payable = $record->payable;

            if (isset($out[$currency])) {
                $payable += $out[$currency]->payable;
            }

            $out[$currency] = (object) [
                'payable' => $payable,
                'currency' => $currency,
                'payable_formatted' => currency_formatter::get_cost_as_formatted((float) $payable, $currency),
            ];
        }

        return $out;
    }

    /**
     * Get the offset for the pagination based on the current page and records per page.
     *
     * @return int The offset value for pagination.
     */
    public function get_offset(): int {
        return $this->get_perpage() * $this->get_page();
    }

    /**
     * Get the current page number from the URL parameter 'page'.
     *
     * @return int The current page number, defaulting to 0 if not provided.
     */
    public function get_page(): int {
        return optional_param('page', 0, PARAM_INT);
    }

    /**
     * Get the number of records to display per page.
     *
     * @return int The number of records per page.
     */
    public function get_perpage(): int {
        return $this->perpage;
    }

    /**
     * Retrieves the current sort field, ensuring it is a valid option.
     *
     * @return string|null The sort field (e.g., 'id', 'coupon_code'), or null if not set.
     */
    public function get_sort(): ?string {
        if ($this->sort === null) {
            $allowedsortfields = ['id', 'coupon_code', 'payable', 'gateway', 'status', 'checkout_at'];
            $sort = optional_param('sort', 'checkout_at', PARAM_ALPHANUMEXT);
            if (in_array($sort, $allowedsortfields)) {
                $this->sort = $sort;
            }
        }
        return $this->sort;
    }

    /**
     * Retrieves the current sort direction (ascending or descending).
     *
     * @return string The sort direction ('ASC' or 'DESC').
     */
    public function get_dir(): string {
        if ($this->dir === null) {
            $dir = optional_param('dir', 'DESC', PARAM_ALPHANUMEXT);
            $this->dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        }
        return $this->dir;
    }

    /**
     * Generates the sort icon based on the current sort direction.
     *
     * @return string The HTML for the sort icon.
     */
    private function get_sort_icon(): string {
        global $OUTPUT;
        $dir = $this->get_dir();
        $icon = $dir === 'DESC' ? 'sort_desc' : 'sort_asc';
        return $OUTPUT->pix_icon('t/' . $icon, get_string(strtolower($dir), 'core'), 'core', ['class' => 'iconsort']);
    }

    /**
     * Generates the column header with sorting links and icons for a table.
     *
     * @param string $key The field to sort by.
     * @param string $label The display label for the column header.
     * @return string The HTML for the column header, including the sort link and icon.
     */
    public function get_table_column_head(string $key, string $label): string {
        $sort = $this->get_sort();
        $dir = $this->get_dir();
        $icon = $this->get_sort_icon();

        $params = $this->get_url_params();
        $params['sort'] = $key;
        $params['dir'] = $dir === 'ASC' ? 'DESC' : 'ASC';

        $link = html_writer::tag('a', $label, [
            'href' => new moodle_url('/report/cart/index.php', $params),
        ]);

        return $link . ($sort === $key ? $icon : '');
    }

    /**
     * Retrieves the url parameters for the cart search.
     *
     * This function collects the values of the search filters (e.g., cart ID, user, coupon code, etc.)
     * and prepares them for inclusion in the URL query string.
     *
     * @return array An associative array of url parameters where the keys are the filter fields
     *               and the values are the corresponding filter values.
     */
    public function get_url_params(): array {
        $params = [];

        // List of fields to include in url.
        $fields = ['id', 'user', 'couponcode', 'status', 'gateway', 'from', 'to', 'sort', 'dir'];
        foreach ($fields as $field) {
            if (($field === 'from' || $field === 'to') && empty($this->$field)) {
                continue;
            }
            $params[$field] = (string) $this->$field;
        }

        return $params;
    }
}
