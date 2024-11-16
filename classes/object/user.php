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

/**
 * Represents a user object that extends the base user object in enrol_cart.
 *
 * This class provides additional methods for populating user data
 * from a cart report.
 */
class user extends \enrol_cart\object\user {
    /**
     * Populates a user object using data from a cart report.
     *
     * This method extracts user-related fields from the given cart report
     * and creates a new user object.
     *
     * @param cart $cartreport The cart report object containing user data.
     * @return self The populated user object.
     */
    public static function populate_from_cart_report(cart $cartreport): self {
        $user = [
            'id' => $cartreport->user_id,
            'username' => $cartreport->username,
            'first_name' => $cartreport->first_name,
            'last_name' => $cartreport->last_name,
        ];

        return static::populate_one($user);
    }
}
