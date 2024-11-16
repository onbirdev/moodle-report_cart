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

use enrol_cart\formatter\currency_formatter;
use enrol_cart\helper\cart_helper;
use enrol_cart\object\base_model;
use enrol_cart\object\cart_status_trait;
use moodle_url;

/**
 * Represents a shopping cart report.
 *
 * This class provides methods for accessing and manipulating cart-related data,
 * including user details, pricing, discounts, and status.
 *
 * @property int $id The unique identifier for the cart.
 * @property int $user_id The ID of the user associated with the cart.
 * @property int $status The status of the cart (e.g., active, checkout, delivered).
 * @property string|null $currency The currency code for the cart (e.g., USD, EUR).
 * @property float|null $price The total price of items in the cart.
 * @property float|null $payable The total payable amount after discounts, taxes, etc.
 * @property int|null $coupon_id The ID of the applied coupon, if any.
 * @property string|null $coupon_code The code of the applied coupon, if any.
 * @property int|null $coupon_usage_id The ID of the coupon usage record, if any.
 * @property array|null $data Additional custom data related to the cart.
 * @property int|null $checkout_at The timestamp when the cart was checked out.
 * @property int $created_at The timestamp when the cart was created.
 * @property int $created_by The ID of the user who created the cart.
 * @property int|null $updated_at The timestamp when the cart was last updated.
 * @property int|null $updated_by The ID of the user who last updated the cart.
 * @property string $username The username of the associated user.
 * @property string $first_name The first name of the associated user.
 * @property string $last_name The last name of the associated user.
 * @property user $user The user object associated with the cart.
 */
class cart extends base_model {
    /** @var user|null The user object associated with the cart. */
    private ?user $_user = null;

    use cart_status_trait;

    /**
     * Returns the list of attributes for the cart report.
     *
     * @return string[] The attributes of the cart report.
     */
    public function attributes(): array {
        return [
            'id',
            'user_id',
            'status',
            'currency',
            'price',
            'payable',
            'coupon_id',
            'coupon_code',
            'coupon_usage_id',
            'coupon_discount_amount',
            'data',
            'checkout_at',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
            'username',
            'email',
            'first_name',
            'last_name',
        ];
    }

    /**
     * Retrieves the cart's currency.
     *
     * @return string The currency code of the cart.
     */
    public function get_final_currency(): string {
        return $this->currency ?: (string) cart_helper::get_config('payment_currency');
    }

    /**
     * Calculates the total discount applied to the cart.
     *
     * @return float|null The total discount amount, or null if not applicable.
     */
    public function get_final_discount_amount(): ?float {
        return $this->price - $this->payable;
    }

    /**
     * Formats the total discount amount as a human-readable string.
     *
     * @return string|null The formatted discount amount, or null if not applicable.
     */
    public function get_final_discount_amount_formatted(): ?string {
        if ($this->get_final_discount_amount() !== null) {
            return currency_formatter::get_cost_as_formatted(
                (float) $this->get_final_discount_amount(),
                $this->get_final_currency(),
            );
        }

        return null;
    }

    /**
     * Formats the total price of the cart as a human-readable string.
     *
     * @return string The formatted price.
     */
    public function get_price_formatted(): string {
        if ($this->price > 0) {
            return currency_formatter::get_cost_as_formatted((float) $this->price, $this->get_final_currency());
        }

        return get_string('free', 'enrol_cart');
    }

    /**
     * Formats the total payable amount as a human-readable string.
     *
     * @return string The formatted payable amount.
     */
    public function get_payable_formatted(): string {
        if ($this->payable > 0) {
            return currency_formatter::get_cost_as_formatted((float) $this->payable, $this->get_final_currency());
        }

        return get_string('free', 'enrol_cart');
    }

    /**
     * Retrieves the user object associated with this cart report.
     *
     * @return user The user associated with the cart.
     */
    public function get_user(): user {
        if ($this->_user === null) {
            $this->_user = user::populate_from_cart_report($this);
        }
        return $this->_user;
    }

    /**
     * Retrieves the URL for viewing the cart associated with this report.
     *
     * @return moodle_url The URL for viewing the cart.
     */
    public function get_view_url(): moodle_url {
        return cart_helper::get_cart_view_url($this->id);
    }
}
