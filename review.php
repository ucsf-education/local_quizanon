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
 * Soft fork of mod_quiz review.php to anonymize the user.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\output\renderer;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/quizanon/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');

$attemptid = required_param('attempt', PARAM_INT);
$page      = optional_param('page', 0, PARAM_INT);
$showall   = optional_param('showall', null, PARAM_BOOL);
$cmid      = optional_param('cmid', null, PARAM_INT);

$url = new moodle_url('/local/quizanon/review.php', ['attempt' => $attemptid]);
if ($page !== 0) {
    $url->param('page', $page);
} else if ($showall) {
    $url->param('showall', $showall);
}
$PAGE->set_url($url);
$PAGE->set_secondary_active_tab("modulepage");

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj->preload_all_attempt_step_users();
$page = $attemptobj->force_page_number_into_range($page);

// Now we can validate the params better, re-genrate the page URL.
if ($showall === null) {
    $showall = $page == 0 && $attemptobj->get_default_show_all('review');
}
$PAGE->set_url($attemptobj->review_url(null, $page, $showall));

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->check_review_capability();

// Create an object to manage all the other (non-roles) access rules.
$accessmanager = $attemptobj->get_access_manager(time());
$accessmanager->setup_attempt_page($PAGE);

$options = $attemptobj->get_display_options(true);
$oldquestionlink = $options->questionreviewlink;
if (!empty($oldquestionlink)) {
    $questionparams = $oldquestionlink->params();
    $newquestionlink = new moodle_url('/local/quizanon/reviewquestion.php', $questionparams);
    $options->questionreviewlink = $newquestionlink;
}

$oldcommentlink = $options->manualcommentlink;
if (!empty($oldcommentlink)) {
    $commentparams = $oldcommentlink->params();
    $newcommentlink = new moodle_url('/local/quizanon/comment.php', $commentparams);
    $options->manualcommentlink = $newcommentlink;
}

// Check permissions - warning there is similar code in reviewquestion.php and
// quiz_attempt::check_file_access. If you change on, change them all.
if ($attemptobj->is_own_attempt()) {
    if (!$attemptobj->is_finished()) {
        redirect($attemptobj->attempt_url(null, $page));

    } else if (!$options->attempt) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('mod_quiz'),
                $attemptobj->cannot_review_message());
    }

} else if (!$attemptobj->is_review_allowed()) {
    throw new moodle_exception('noreviewattempt', 'quiz', $attemptobj->view_url());
}

// Load the questions and states needed by this page.
if ($showall) {
    $questionids = $attemptobj->get_slots();
} else {
    $questionids = $attemptobj->get_slots($page);
}

// Save the flag states, if they are being changed.
if ($options->flags == question_display_options::EDITABLE && optional_param('savingflags', false,
        PARAM_BOOL)) {
    require_sesskey();
    $attemptobj->save_question_flags();
    redirect($attemptobj->review_url(null, $page, $showall));
}

// Work out appropriate title and whether blocks should be shown.
if ($attemptobj->is_own_preview()) {
    navigation_node::override_active_url($attemptobj->start_attempt_url());

} else {
    if (empty($attemptobj->get_quiz()->showblocks) && !$attemptobj->is_preview_user()) {
        $PAGE->blocks->show_only_fake_blocks();
    }
}

// Set up the page header.
$headtags = $attemptobj->get_html_head_contributions($page, $showall);
$PAGE->set_title($attemptobj->review_page_title($page, $showall));
$PAGE->set_heading($attemptobj->get_course()->fullname);
$PAGE->activityheader->disable();

// Summary table start.

// Work out some time-related things.
$attempt = $attemptobj->get_attempt();
$quiz = $attemptobj->get_quiz();
$overtime = 0;

if ($attempt->state == mod_quiz\quiz_attempt::FINISHED) {
    if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
        if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
            $overtime = $timetaken - $quiz->timelimit;
            $overtime = format_time($overtime);
        }
        $timetaken = format_time($timetaken);
    } else {
        $timetaken = "-";
    }
} else {
    $timetaken = get_string('unfinished', 'quiz');
}

// Prepare summary information about the whole attempt.
$summarydata = [];
if (!$attemptobj->get_quiz()->showuserpicture && $attemptobj->get_userid() != $USER->id) {
    // If showuserpicture is true, the picture is shown elsewhere, so don't repeat it.
    $student = $DB->get_record('user', ['id' => $attemptobj->get_userid()]);
    $userpicture = new user_picture($student);
    $userpicture->courseid = $attemptobj->get_courseid();
    $summarydata['user'] = [
        'title'   => 'User code',
        'content' => local_anonquiz_get_usercode($attemptobj->get_userid(), $quiz->id),
    ];
}

if ($attemptobj->has_capability('mod/quiz:viewreports')) {
    $attemptlist = $attemptobj->links_to_other_attempts($attemptobj->review_url(null, $page,
            $showall));
    if ($attemptlist) {
        $summarydata['attemptlist'] = [
            'title'   => get_string('attempts', 'quiz'),
            'content' => $attemptlist,
        ];
    }
}

// Timing information.
$summarydata['startedon'] = [
    'title'   => get_string('startedon', 'quiz'),
    'content' => userdate($attempt->timestart),
];

$summarydata['state'] = [
    'title'   => get_string('attemptstate', 'quiz'),
    'content' => mod_quiz\quiz_attempt::state_name($attempt->state),
];

if ($attempt->state == mod_quiz\quiz_attempt::FINISHED) {
    $summarydata['completedon'] = [
        'title'   => get_string('completedon', 'quiz'),
        'content' => userdate($attempt->timefinish),
    ];
    $summarydata['timetaken'] = [
        'title'   => get_string('attemptduration', 'quiz'),
        'content' => $timetaken,
    ];
}

if (!empty($overtime)) {
    $summarydata['overdue'] = [
        'title'   => get_string('overdue', 'quiz'),
        'content' => $overtime,
    ];
}

// Show marks (if the user is allowed to see marks at the moment).
$grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
if ($options->marks >= question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {

    if ($attempt->state != mod_quiz\quiz_attempt::FINISHED) {
        // Cannot display grade.
        $empty = true;
    } else if (is_null($grade)) {
        $summarydata['grade'] = [
            'title'   => get_string('gradenoun'),
            'content' => quiz_format_grade($quiz, $grade),
        ];

    } else {
        // Show raw marks only if they are different from the grade (like on the view page).
        if ($quiz->grade != $quiz->sumgrades) {
            $a = new stdClass();
            $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
            $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
            $summarydata['marks'] = [
                'title'   => get_string('marks', 'quiz'),
                'content' => get_string('outofshort', 'quiz', $a),
            ];
        }

        // Now the scaled grade.
        $a = new stdClass();
        $a->grade = html_writer::tag('b', quiz_format_grade($quiz, $grade));
        $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
        if ($quiz->grade != 100) {
            // Show the percentage using the configured number of decimal places,
            // but without trailing zeroes.
            $a->percent = html_writer::tag('b', format_float(
                    $attempt->sumgrades * 100 / $quiz->sumgrades,
                    $quiz->decimalpoints, true, true));
            $formattedgrade = get_string('outofpercent', 'quiz', $a);
        } else {
            $formattedgrade = get_string('outof', 'quiz', $a);
        }
        $summarydata['grade'] = [
            'title'   => get_string('gradenoun'),
            'content' => $formattedgrade,
        ];
    }
}

// Any additional summary data from the behaviour.
$summarydata = array_merge($summarydata, $attemptobj->get_additional_summary_data($options));

// Feedback if there is any, and the user is allowed to see it now.
$feedback = $attemptobj->get_overall_feedback($grade);
if ($options->overallfeedback && $feedback) {
    $summarydata['feedback'] = [
        'title'   => get_string('feedback', 'quiz'),
        'content' => $feedback,
    ];
}

// Summary table end.

if ($showall) {
    $slots = $attemptobj->get_slots();
    $lastpage = true;
} else {
    $slots = $attemptobj->get_slots($page);
    $lastpage = $attemptobj->is_last_page($page);
}

/** @var renderer $output */
$output = $PAGE->get_renderer('mod_quiz');

// Arrange for the navigation to be displayed.
$navbc = $attemptobj->get_navigation_panel($output, 'mod_quiz\output\navigation_panel_review', $page, $showall);
$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($navbc, reset($regions));

/**
 * @var mod_quiz\output\attempt_summary_information 
 * This is a new object for summary information about the attempt that will replace the old array.
 */
$attemptsummarydata = mod_quiz\output\attempt_summary_information::create_from_legacy_array($summarydata);

echo $output->review_page($attemptobj, $slots, $page, $showall, $lastpage, $options, $attemptsummarydata);

// Trigger an event for this review.
$attemptobj->fire_attempt_reviewed_event();
