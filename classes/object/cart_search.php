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

class cart_search extends base_object {
    private ?int $user = null;

    public function load_data($array) {

    }

    public function build_query(): string {
        $sql = "SELECT c.*, u.username, u.email, u.firstname as first_name, u.lastname as last_name 
FROM {enrol_cart} c 
    INNER JOIN {user} u ON c.user_id = u.id";

        return $sql;
    }

    public function get_params(): array {
        return [];
    }

    /**
     * @return cart[] The array of cart report objects.
     */
    public function get_all() {
        global $DB;

        $rows = $DB->get_records_sql($this->build_query(), $this->get_params());

        return cart::populate($rows);
    }
}