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
 * User bounces check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_emailutils\check;
use \tool_emailutils\helper;
use core\check\check;
use core\check\result;

/**
 * User bounces check.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bounces extends check {

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/tool/emailutils/bounces.php'),
            get_string('reportbounces', 'tool_emailutils'));
    }

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result() : result {
        global $DB, $CFG, $OUTPUT;

        $details = '';

        [$handlebounces, $minbounces, $bounceratio] = helper::get_bounce_config();
        if (empty($handlebounces)) {
            $status = result::OK;
            $summary = "Moodle is not configured to handle bounces.";
            $details = $summary;
        } else {
            $sql = "SELECT up.userid, up.value AS bouncecount, up2.value AS sendcount
                      FROM {user_preferences} up
                 LEFT JOIN {user_preferences} up2 ON up2.name = 'email_send_count' AND up.userid = up2.userid
                     WHERE up.name = 'email_bounce_count' AND CAST(up.value AS INTEGER) > :threshold";
            // Start with a threshold of 1 as that was a historical default for manually created users.
            $bounces = $DB->get_records_sql($sql, ['threshold' => 1]);
            $userswithbounces = count($bounces);

            // Split bounces into 3 groups based on whether they meet bounce threshold criteria.
            $overthreshold = 0;
            $overbouncereq = 0;
            $underbouncereq = 0;
            foreach ($bounces as $bounce) {
                $bouncereq = $bounce->bouncecount >= $minbounces;
                $ratioreq = !empty($bounce->sendcount) && ($bounce->bouncecount / $bounce->sendcount >= $bounceratio);
                if ($bouncereq && $ratioreq) {
                    $overthreshold++;
                } else if (!$ratioreq) {
                    $overbouncereq++;
                } else {
                    $underbouncereq++;
                }
            }

            $messages = [];
            if (!$userswithbounces) {
                $status = result::OK;
                $summary = "No users have had emails rejected.";
            } else if (!$overthreshold) {
                $status = result::OK;
                $summary = "No users are over the Moodle bounce threshold, but $userswithbounces have had emails rejected";
            } else {
                $status = result::WARNING;
                $summary = "Found $overthreshold users over the Moodle bounce threshold";
                $messages[] = "$overthreshold user(s) have at least $minbounces email rejections with a bounce ratio over $bounceratio";
            }

            if ($overbouncereq) {
                $messages[] = "$overbouncereq user(s) have at least $minbounces email rejections with a bounce ratio under $bounceratio";
            }

            if ($underbouncereq) {
                $allowedbounces = $minbounces - 1;
                $messages[] = "$underbouncereq user(s) have between 1 and $allowedbounces email rejections";
            }

            // Render config used for calculating threshold.
            $details = $OUTPUT->render_from_template('tool_emailutils/bounce_config', [
                'handlebounces' => $handlebounces,
                'minbounces' => $minbounces,
                'bounceratio' => $bounceratio,
                'breakdown' => true,
                'messages' => $messages,
            ]);
        }

        return new result($status, $summary, $details);
    }

}
