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
 * Hook callbacks for the Quiz Anonymization plugin
 *
 * @package    local_quizanon
 * @copyright  2024 UCSF Education IT
 * @author     Leon U. Bailey <leon.bailey@ucsf.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizanon;

use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Hook callback implementations for the Quiz Anonymization plugin
 *
 * @package    local_quizanon
 * @copyright  2024 UCSF Education IT
 * @author     Leon U. Bailey <leon.bailey@ucsf.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Hook callback for before standard top of body HTML generation.
     * Handles quiz anonymization redirects based on user roles and page types.
     * Verify if the user has the role to access the quiz report or redirect to the local plugin.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html(before_standard_top_of_body_html_generation $hook): void {
        local_quizanon_before_standard_top_of_body_html();
    }
}
