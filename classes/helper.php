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
        return $CFG->minbounce ?? self::DEFAULT_MIN_BOUNCES;
    }

    /**
     * Gets the bounce rate config, otherwise return the default.
     * @return float
     */
    public static function get_bounce_ratio(): float {
        global $CFG;
        return $CFG->bounceratio ?? self::DEFAULT_BOUNCE_RATIO;
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
