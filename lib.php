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
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation with the local plugin's items.
 *
 * @param navigation_node $navigation The navigation node to extend.
 */
function local_quizanon_extend_navigation_course(navigation_node $navigation) {
    global $CFG, $PAGE, $COURSE, $DB;
    $pagename = $PAGE->pagetype;
    $urlparams = $PAGE->url->params();
    $quizanonenabled = get_config('local_quizanon', 'enablequizanon');
    if (!$quizanonenabled) {
        if ($pagename == 'local-quizanon-report') {
            $moodleurl = new moodle_url('/mod/quiz/report.php', $urlparams);
            redirect($moodleurl);
        }
        if ($pagename == 'local-quizanon-review') {
            $moodleurl = new moodle_url('/mod/quiz/review.php', $urlparams);
            redirect($moodleurl);
        }
        return;
    }

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

/**
 * Generate a usercode to anonymize the user.
 *
 * @param int $userid
 * @param int $quizid
 * @return string
 */
function local_anonquiz_generate_usercode($userid, $quizid) {
    $hash = sha1($userid . $quizid);
    $extractletters = preg_replace('/[^a-zA-Z]/', '', $hash);
    $extractnumbers = preg_replace('/[^0-9]/', '', $hash);
    $usercode = substr($extractletters, 0, 3) . substr($extractnumbers, 0, 3);
    $codeupper = strtoupper($usercode);
    return $codeupper;
}
