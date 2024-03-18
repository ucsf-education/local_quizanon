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
     * Enable quizanon plugin.
     *
     * @Given Quizanon plugin is enabled
     */
    public function quizanonpluginisenabled() {
        set_config('enablequizanon', 1, 'local_quizanon');
    }

    /**
     * Disable quizanon plugin.
     *
     * @Given Quizanon plugin is disabled
     */
    public function quizanonpluginisdisabled() {
        set_config('enablequizanon', 0, 'local_quizanon');
    }
}
