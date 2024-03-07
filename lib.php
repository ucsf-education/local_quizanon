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
 * local_anonquiz redirect to quiz report with anonymous user.
 *
 * @package    local_anonquiz
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_quizanon_extend_navigation_course(navigation_node $navigation) {
    global $CFG, $PAGE, $COURSE, $DB;
    $pagename = $PAGE->pagetype;
    $urlparams = $PAGE->url->params();

    $mode = !empty($urlparams['mode']) ? $urlparams['mode'] : '';
    $anonreportexists = is_readable($CFG->dirroot . '/local/quizanon/report/' . $mode . '/report.php');

    if ($pagename == 'mod-quiz-report' && $anonreportexists) {
        $moodleurl = new moodle_url('/local/quizanon/report.php', $urlparams);
        redirect($moodleurl);
    }
    if ($pagename == 'mod-quiz-review'  && $anonreportexists) {
        $moodleurl = new moodle_url('/local/quizanon/review.php', $urlparams);
        redirect($moodleurl);
    }
}
