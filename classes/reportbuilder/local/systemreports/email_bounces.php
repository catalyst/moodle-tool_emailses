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

namespace tool_emailutils\reportbuilder\local\systemreports;

use context_system;
use tool_emailutils\reportbuilder\local\entities\email_bounce;
use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;

/**
 * Email bounces report class implementation.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class email_bounces extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new email_bounce();
        $entitymainalias = $entitymain->get_table_alias('user_preferences');

        $this->set_main_table('user_preferences', $entitymainalias);
        $this->add_entity($entitymain);
        $this->add_base_condition_simple("{$entitymainalias}.name", 'email_bounce_count');

        // We can join the "user" entity to our "main" entity using standard SQL JOIN.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("LEFT JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = {$entitymainalias}.userid")
        );

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Set if report can be downloaded.
        $this->set_downloadable(true, get_string('reportbounces', 'tool_emailutils'));
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $columns = [
            'user:fullnamewithlink',
            'user:username',
            'user:email',
            'email_bounce:bounces',
            'email_bounce:send',
            'email_bounce:ratio',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('email_bounce:bounces', SORT_DESC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'email_bounce:bounces',
            'email_bounce:send',
            'user:fullname',
            'user:username',
            'user:email',
        ];

        $this->add_filters_from_entities($filters);
    }
}
