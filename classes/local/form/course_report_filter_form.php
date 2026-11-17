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

namespace report_cart\local\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use enrol_cart\local\object\cart_status_interface;
use moodleform;
use report_cart\local\object\cart;

/**
 * Filter form for the shopping cart report by course.
 */
class course_report_filter_form extends moodleform {
    /**
     * Define form elements for filtering the course report.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'filters', get_string('filters', 'report_cart'));

        // User selector with AJAX search.
        $mform->addElement(
            'autocomplete',
            'userid',
            get_string('user'),
            [],
            [
                'multiple' => false,
                'ajax' => 'core_search/form-search-user-selector',
                'valuehtmlcallback' => function($value) {
                    if (empty($value)) {
                        return false;
                    }
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

        // Course selector.
        $mform->addElement('course', 'courseid', get_string('courses'));

        // Cart status selector.
        $statusoptions = ['' => get_string('all', 'report_cart')];
        $statusoptions += cart::get_status_options();
        $mform->addElement('select', 'status', get_string('cart_status', 'enrol_cart'), $statusoptions);
        $mform->setDefault('status', cart_status_interface::STATUS_DELIVERED);

        // Payment gateway selector.
        $gateways = $DB->get_records_sql("
            SELECT DISTINCT gateway
            FROM {payments}
            WHERE component = 'enrol_cart' AND gateway IS NOT NULL
            ORDER BY gateway
        ");

        $gatewayoptions = ['' => get_string('all')];
        foreach ($gateways as $gateway) {
            $gatewayoptions[$gateway->gateway] = ucfirst($gateway->gateway);
        }
        $mform->addElement('select', 'gateway', get_string('gateway', 'report_cart'), $gatewayoptions);

        // Date range selectors.
        $mform->addElement('date_selector', 'datefrom', get_string('checkout_time_from', 'report_cart'), [
            'optional' => true,
            'step' => 1,
        ]);
        $mform->addElement('date_selector', 'dateto', get_string('checkout_time_to', 'report_cart'), [
            'optional' => true,
            'step' => 1,
        ]);

        // Action buttons.
        $buttonarray = [
            $mform->createElement('submit', 'submitbutton', get_string('filter')),
            $mform->createElement('submit', 'clearfilters', get_string('reset')),
        ];
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Validate filter form inputs.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['datefrom']) && !empty($data['dateto']) && $data['datefrom'] > $data['dateto']) {
            $errors['dateto'] = get_string('error_daterange', 'report_cart');
        }

        return $errors;
    }
}
