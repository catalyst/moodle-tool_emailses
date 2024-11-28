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
 * Page to bulk reset the email bounce count for users.
 *
 * @package     tool_emailutils
 * @author      Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('userbulk');
require_capability('moodle/user:update', context_system::instance());

$confirm = optional_param('confirm', 0, PARAM_BOOL);

$return = new moodle_url('/admin/user/user_bulk.php');

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetbounces', 'tool_emailutils'));

if ($confirm && confirm_sesskey()) {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $rs = $DB->get_recordset_select('user', "id $in", $params, '', 'id, ' . \tool_emailutils\helper::get_username_fields());
    foreach ($rs as $user) {
        \tool_emailutils\helper::reset_bounce_count($user);
    }
    $rs->close();
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo $OUTPUT->notification(get_string('bouncesreset', 'tool_emailutils'), 'notifysuccess');

    $continue = new single_button(new moodle_url($return), get_string('continue'), 'post');
    echo $OUTPUT->render($continue);
    echo $OUTPUT->box_end();
} else {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    $formcontinue = new single_button(new moodle_url('reset_bounces.php', ['confirm' => 1]), get_string('yes'));
    $formcancel = new single_button(new moodle_url('/admin/user/user_bulk.php'), get_string('no'), 'get');
    echo $OUTPUT->confirm(get_string('bouncecheckfull', 'tool_emailutils', $usernames), $formcontinue, $formcancel);
}
echo $OUTPUT->footer();
