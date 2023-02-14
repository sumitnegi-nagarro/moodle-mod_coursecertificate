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
 * Unit test for the task.
 *
 * @package     mod_coursecertificate
 * @category    test
 * @copyright   2023 Sumit Negi <sumit.negi@nagarro.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Unit test for the task.
 *
 * @package     mod_coursecertificate
 * @category    test
 * @copyright   2023 Sumit Negi <sumit.negi@nagarro.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_coursecertificate_course_completion_reset_test_testcase extends advanced_testcase {

    /**
     * Set up
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Get certificate generator
     *
     * @return tool_certificate_generator
     */
    protected function get_certificate_generator(): tool_certificate_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_certificate');
    }

    /**
     * Test issue_certificates_task with automaticsend setting enabled.
     */
    public function test_revoke_on_course_completion_reset() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/recompletion/locallib.php');
        // Create a course.
        $course = $this->getDataGenerator()->create_course(
            array('numsections' => 1, 'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true)
        );
        $certificate1 = $this->get_certificate_generator()->create_template((object)['name' => 'Certificate 1']);
        $expirydate = strtotime('+5 day');
        $mod = $this->getDataGenerator()->create_module(
            'coursecertificate',
            [
                'course' => $course->id,
                'template' => $certificate1->get_id(),
                'expires' => $expirydate,
                'completionview' => COMPLETION_VIEW_REQUIRED
            ]
        );
        //core_completion_external::mark_course_self_completed($course->id);
        $user1 = $this->getDataGenerator()->create_and_enrol($course);
        $ccompletion = new completion_completion(array('course' => $course->id, 'userid' => $user1->id));
        $ccompletion->mark_complete();
        $user2 = $this->getDataGenerator()->create_and_enrol($course);
        $ccompletion = new completion_completion(array('course' => $course->id, 'userid' => $user2->id));
        $ccompletion->mark_complete();
        $user3 = $this->getDataGenerator()->create_and_enrol($course);
        $ccompletion = new completion_completion(array('course' => $course->id, 'userid' => $user3->id));
        $ccompletion->mark_complete();
        $mod->automaticsend = 1;
        $DB->update_record('coursecertificate', $mod);
        // Issue certificates.
        $task = new mod_coursecertificate\task\issue_certificates_task();
        ob_start();
        $task->execute();
        ob_end_clean();

        // Check if certificates are issued.
        $issues = $DB->get_records('tool_certificate_issues', [
            'templateid' => $certificate1->get_id(),
            'courseid' => $course->id
        ]);
        $this->assertCount(3, $issues);
        // Reset course completion.
        $task = new \local_recompletion\task\check_recompletion();
        ob_start();
        $config = (object)[
            'recompletionemailenable' => true,
            'deletegradedata' => true,
            'archivecompletiondata' => true,
            'recompletionemailbody' => 'Test recompletion email body',
            'recompletionemailsubject' => 'Test recompletion email subject',
        ];
        $task->reset_user($user1->id, $course, $config);
        $task->reset_user($user2->id, $course, $config);
        $task->reset_user($user3->id, $course, $config);
        ob_end_clean();

        // Check if certificates are revoked.
        $issues = $DB->get_records('tool_certificate_issues', [
            'templateid' => $certificate1->get_id(),
            'courseid' => $course->id
        ]);
        $this->assertCount(0, $issues);
    }
}
