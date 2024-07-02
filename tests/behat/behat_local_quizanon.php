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
 * Steps definitions related to local_quizanon.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../question/tests/behat/behat_question_base.php');

/**
 * Steps definitions related to mod_quiz.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_quizanon extends behat_question_base {

    /**
     * Enable quizanon on instance.
     *
     * @Given /^Quizanon plugin is enabled for quiz "(?P<quiz_name_string>(?:[^"]|\\")*)"$/
     * @param string $quizname
     */
    public function quizanonpluginisenable($quizname) {
        global $DB;
        $quiz = $DB->get_record('quiz', array('name' => $quizname), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        $roles = [];
        $quizanon = new local_quizanon\local\data\quizanon();
        $quizanon->set_many([
            'quizid' => $cm->id,
            'enable' => 1,
            'roles' => json_encode($roles),
        ]);
        $quizanon->save();
    }

    /**
     * Exclude role from quizanon on instance.
     *
     * @Given /^"(?P<role>(?:[^"]|\\")*)" is excluded from quizanon for quiz "(?P<quiz_name_string>(?:[^"]|\\")*)"$/
     * @param string $role
     * @param string $quizname
     */
    public function roleisexcludedfromquizanon($role, $quizname) {
        global $DB;
        $quiz = $DB->get_record('quiz', array('name' => $quizname), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        $roles = [];
        $getroleid = $DB->get_record('role', ['shortname' => $role]);
        $roles[] = $getroleid->id;
        $quizanon = new local_quizanon\local\data\quizanon();
        $quizanon->set_many([
            'quizid' => $cm->id,
            'enable' => 1,
            'roles' => json_encode($roles),
        ]);
        $quizanon->save();
    }

    /**
     * Disable quizanon on instance.
     *
     * @Given /^Quizanon plugin is disabled for quiz "(?P<quiz_name_string>(?:[^"]|\\")*)"$/
     * @param string $quizname
     */
    public function quizanonpluginisdisabled($quizname) {
        global $DB;
        $quiz = $DB->get_record('quiz', array('name' => $quizname), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        if (!$quiz) {

            if ($DB->record_exists('local_quizanon', ['quizid' => $cm->id])) {
                $DB->delete_records('local_quizanon', ['quizid' => $cm->id]);
            }
        }
    }
}
