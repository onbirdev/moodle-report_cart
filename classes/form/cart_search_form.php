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

namespace report_cart\form;

defined('MOODLE_INTERNAL') || die();

use enrol_cart\object\cart_status_interface;
use moodleform;
use report_cart\object\cart;

require_once("$CFG->libdir/formslib.php");

/**
 * Class cart_search_form
 *
 * Defines a filter form for searching and filtering shopping cart reports in Moodle.
 */
class cart_search_form extends moodleform {
    /**
     * Define the elements of the filter form.
     *
     * This method adds input fields, selectors, and action buttons to the form
     * to allow users to filter shopping cart reports based on various criteria,
     * such as order ID, user, coupon code, cart status, and date range.
     *
     * @return void
     */
    public function definition() {
        $form = $this->_form;

        // Add a text field for filtering by order ID.
        $form->addElement('text', 'id', get_string('order_id', 'enrol_cart'));
        $form->setType('id', PARAM_ALPHANUM);

        // Add a user selector field with AJAX search functionality.
        $form->addElement(
            'autocomplete',
            'user',
            get_string('user'),
            [],
            [
                'multiple' => false,
                'ajax' => 'core_search/form-search-user-selector',
                'valuehtmlcallback' => function($value) {
                    global $DB, $OUTPUT;
                    $user = $DB->get_record('user', ['id' => (int) $value], '*', IGNORE_MISSING);
                    if (!$user || !user_can_view_profile($user)) {
                        return false;
                    }
                    $details = user_get_user_details($user);
                    return $OUTPUT->render_from_template('core_search/form-user-selector-suggestion', $details);
                },
            ],
        );

        // Add a text field for filtering by coupon code.
        $form->addElement('text', 'coupon_code', get_string('coupon_code', 'enrol_cart'));
        $form->setType('coupon_code', PARAM_TEXT);

        // Add a dropdown selector for filtering by cart status.
        $options = ['' => get_string('all', 'report_cart')];
        $options += cart::get_status_options();
        $form->addElement('select', 'status', get_string('cart_status', 'enrol_cart'), $options);
        $form->setDefault('status', cart_status_interface::STATUS_DELIVERED);

        // Add date selectors for filtering by checkout time range.
        $form->addElement('date_selector', 'from', get_string('checkout_time_from', 'report_cart'), [
            'optional' => true,
        ]);

        $form->addElement('date_selector', 'to', get_string('checkout_time_to', 'report_cart'), [
            'optional' => true,
        ]);

        // Add action buttons for applying filters.
        $this->add_action_buttons(true, get_string('apply', 'report_cart'));
    }

    /**
     * Modify form elements after the definition is complete.
     *
     * This method removes session-specific fields (e.g., `sesskey`) from the form
     * to prevent them from interfering with filter functionality.
     *
     * @return void
     */
    public function after_definition() {
        parent::after_definition();

        // Remove the `sesskey` element if it exists.
        if (isset($this->_form->_elementIndex['sesskey'])) {
            $_GET['sesskey'] = sesskey();
            unset($this->_form->_elements[$this->_form->_elementIndex['sesskey']]);
        }

        // Remove the QuickForm identifier field if it exists.
        if (isset($this->_form->_elementIndex['_qf__' . $this->_formname])) {
            $_GET['_qf__' . $this->_formname] = 1;
            unset($this->_form->_elements[$this->_form->_elementIndex['_qf__' . $this->_formname]]);
        }
    }
}
