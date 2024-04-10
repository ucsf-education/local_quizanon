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
 * Quiz anon Persistent Class.
 *
 * This class defines the persistent entity for the 'local_quizanon' table in Moodle.
 * It encapsulates the logic for interacting with the table's data.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizanon\local\data;

/**
 * Quiz anon Persistent Entity Class.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @property   int $quizid
 * @property   bool $enable
 * @property   string $roles
 */
class quizanon extends base {
    /** Table name for the persistent. */
    const TABLE = 'local_quizanon';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return array(
            'quizid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'enable' => [
                'type' => PARAM_BOOL,
                'null' => NULL_NOT_ALLOWED,
            ],
            'roles' => [
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED,
            ]   
        );
    }
}
