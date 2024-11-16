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

use moodleform;

require_once("$CFG->libdir/formslib.php");

/**
 * This class defines a filter form for the shopping cart report in Moodle.
 */
class cart_report_filter_form extends moodleform {
    /**
     * {@inheritdoc}
     */
    public function definition() {
        $form = $this->_form;

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
                    return $OUTPUT->render_from_template(
                        'core_search/form-user-selector-suggestion', $details);
                },
            ],
        );

        // Add action buttons (Apply).
        $this->add_action_buttons(true, get_string('apply', 'report_cart'));
    }
}
