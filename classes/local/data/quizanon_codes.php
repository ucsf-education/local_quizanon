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
 * Quiz anon codes Persistent Class.
 *
 * This class defines the persistent entity for the 'local_quizanon_usercodes' table in Moodle.
 * It encapsulates the logic for interacting with the table's data.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizanon\local\data;

/**
 * Quiz anon codes Persistent Entity Class.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @property   int $quizid
 * @property   int $userid
 * @property   string $code
 */
class quizanon_codes extends base {
    /** Table name for the persistent. */
    const TABLE = 'local_quizanon_usercodes';

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
            'userid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'code' => [
                'type' => PARAM_ALPHANUM,
                'null' => NULL_NOT_ALLOWED,
            ],
        );
    }
}
