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
 * Local quizanon test class.
 *
 * @package    local_quizanon
 * @copyright  Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizanon;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the local_quizanon plugin.
 * @package    local_quizanon
 * @copyright  Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_quizanon_test extends \advanced_testcase {

    /**
     * Test setup
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test template for local_quizanon plugin.
     */
    public function test_template() {
    }
}
