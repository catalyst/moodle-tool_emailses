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
 * Helper class for tool_emailutils.
 *
 * @package    tool_emailutils
 * @copyright  Catalyst IT 2024
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_emailutils;

use tool_emailutils\event\bounce_count_reset;

/**
 * Helper class for tool_emailutils.
 *
 * @package    tool_emailutils
 * @copyright  Catalyst IT 2024
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** Default bounce ratio from over_bounce_threshold() */
    const DEFAULT_BOUNCE_RATIO = 0.2;

    /** Default minimum bounces from over_bounce_threshold() */
    const DEFAULT_MIN_BOUNCES = 10;


    /**
     * Gets the username fields for the fullname function. Contains legacy support for 3.9.
     * @param string $tablealias
     * @return string username fields, without a leading comma
     */
    public static function get_username_fields(string $tablealias = ''): string {
        // Use user field api, added in 3.11.
        if (method_exists('\core_user\fields', 'for_name')) {
            $userfields = \core_user\fields::for_name();
            return $userfields->get_sql($tablealias, false, '', '', false)->selects;
        } else {
            // Legacy support for 3.9.
            return get_all_user_name_fields(true, $tablealias);
        }
    }

    /**
     *
     * Gets the min bounces, otherwise return the default.
     * @return int
     */
    public static function get_min_bounces(): int {
        global $CFG;
        // The core check for using the default uses empty().
        return empty($CFG->minbounces) ? self::DEFAULT_MIN_BOUNCES : $CFG->minbounces;
    }

    /**
     * Gets the bounce rate config, otherwise return the default.
     * @return float
     */
    public static function get_bounce_ratio(): float {
        global $CFG;
        // The core check for using the default uses empty().
        return empty($CFG->bounceratio) ? self::DEFAULT_BOUNCE_RATIO : $CFG->bounceratio;
    }

    /**
     * Is a positive bounce ratio being used?
     * @return bool use bounce ratio?
     */
    public static function use_bounce_ratio(): bool {
        if (self::get_bounce_ratio() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Does the config imply the use of consecutive bounce count?
     *
     * Consecutive bounce counts should be used when possible as it's best practice.
     * This requires a non-positive bounce ratio to ignore send count and pass threshold checks.
     * Delivery notifications may also need to be set up to accurately track successful deliveries.
     *
     * @return bool use consecutive bounce count?
     */
    public static function use_consecutive_bounces(): bool {
        return !self::use_bounce_ratio();
    }


    /**
     * Resets the bounce count of a user and fires off an event.
     * @param \stdClass $resetuser user who will have their bounce count reset
     * @return void
     */
    public static function reset_bounce_count(\stdClass $resetuser): void {
        global $USER;

        // If bounce ratio is being used, the send count also needs to be reset.
        $resetsendcount = self::use_bounce_ratio();

        $bouncecount = get_user_preferences('email_bounce_count', null, $resetuser);
        $sendcount = get_user_preferences('email_send_count', null, $resetuser);
        if (!isset($bouncecount) && (!$resetsendcount || !isset($sendcount))) {
            return;
        }

        // Swap to set_bounce_count($resetuser, true) once MDL-73798 is integrated.
        unset_user_preference('email_bounce_count', $resetuser);
        if ($resetsendcount) {
            unset_user_preference('email_send_count', $resetuser);
        }

        $event = bounce_count_reset::create([
            'userid' => $USER->id,
            'relateduserid' => $resetuser->id,
            'context'  => \context_system::instance(),
        ]);
        $event->trigger();
    }

    /**
     * Gets the bounce config, or the defaults.
     * @return array [handlebounces, minbounces, bounceratio]
     */
    public static function get_bounce_config(): array {
        global $CFG;
        return [
            $CFG->handlebounces,
            self::get_min_bounces(),
            self::get_bounce_ratio(),
        ];
    }
}
