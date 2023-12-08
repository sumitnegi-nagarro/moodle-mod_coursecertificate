<?php
// This file is part of the mod_coursecertificate plugin for Moodle - http://moodle.org/
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
 * Class mod_coursecertificate_observer
 *
 * @package     mod_coursecertificate
 * @copyright   2023 Sumit Negi <sumit.negi@nagarro.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_coursecertificate\observer;

use tool_certificate\external\issues;

defined('MOODLE_INTERNAL') || die;

/**
 * Class mod_coursecertificate_observer
 *
 * @package     mod_coursecertificate
 * @copyright   2023 Sumit Negi <sumit.negi@nagarro.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class revoke_certificate {

    /**
     * Triggered via local_recompletion\event\completion_reset.
     *
     * @param \local_recompletion\event\completion_reset $event
     * @return bool
     */
    public static function on_completion_reset(\local_recompletion\event\completion_reset $event) {
        global $DB;

        $courseid = $event->courseid;
        $userid = $event->relateduserid;
        $certificates = $DB->get_records('tool_certificate_issues', ['courseid' => $courseid, 'userid' => $userid]);
        foreach ($certificates as $certificate) {
            issues::revoke_issue($certificate->id);
        }

        return true;
    }
}
