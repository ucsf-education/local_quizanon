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
 * Soft fork of mod/quiz/comment.php to anonymize the user's name.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/local/quizanon/lib.php');

$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT); // The question number in the attempt.
$cmid = optional_param('cmid', null, PARAM_INT);

$PAGE->set_url('/local/quizanon/comment.php', ['attempt' => $attemptid, 'slot' => $slot]);

$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj->preload_all_attempt_step_users();
$student = $DB->get_record('user', ['id' => $attemptobj->get_userid()]);

// Can only grade finished attempts.
if (!$attemptobj->is_finished()) {
    throw new \moodle_exception('attemptclosed', 'quiz');
}

// Check login and permissions.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->require_capability('mod/quiz:grade');
$usercode = local_anonquiz_get_usercode($attemptobj->get_userid(), $attemptobj->get_quizid());
// Print the page header.
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('manualgradequestion', 'quiz', [
        'question' => format_string($attemptobj->get_question_name($slot)),
        'quiz' => format_string($attemptobj->get_quiz_name()), 'user' => $usercode]));
$PAGE->set_heading($attemptobj->get_course()->fullname);
$output = $PAGE->get_renderer('mod_quiz');
echo $output->header();

// Prepare summary information about this question attempt.
$summarydata = [];

$summarydata['user'] = [
    'title'   => get_string('usercode', 'local_quizanon'),
    'content' => $usercode
];

// Quiz name.
$summarydata['quizname'] = [
    'title'   => get_string('modulename', 'quiz'),
    'content' => format_string($attemptobj->get_quiz_name()),
];

// Question name.
$summarydata['questionname'] = [
    'title'   => get_string('question', 'quiz'),
    'content' => $attemptobj->get_question_name($slot),
];

// Process any data that was submitted.
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('submit', false, PARAM_BOOL) &&
        question_engine::is_manual_grade_in_range($attemptobj->get_uniqueid(), $slot)) {
        $transaction = $DB->start_delegated_transaction();
        $attemptobj->process_submitted_actions(time());
        $transaction->allow_commit();

        // Log this action.
        $params = [
            'objectid' => $attemptobj->get_question_attempt($slot)->get_question_id(),
            'courseid' => $attemptobj->get_courseid(),
            'context' => $attemptobj->get_quizobj()->get_context(),
            'other' => [
                'quizid' => $attemptobj->get_quizid(),
                'attemptid' => $attemptobj->get_attemptid(),
                'slot' => $slot
            ]
        ];
        $event = \mod_quiz\event\question_manually_graded::create($params);
        $event->trigger();

        echo $output->notification(get_string('changessaved'), 'notifysuccess');
        close_window(2, true);
        die;
    }
}

// Print quiz information.
echo $output->review_summary_table($summarydata, 0);

// Print the comment form.
echo '<form method="post" class="mform" id="manualgradingform" action="' .
        $CFG->wwwroot . '/local/quizanon/comment.php">';
echo $attemptobj->render_question_for_commenting($slot);
?>
<div>
    <input type="hidden" name="attempt" value="<?php echo $attemptobj->get_attemptid(); ?>" />
    <input type="hidden" name="slot" value="<?php echo $slot; ?>" />
    <input type="hidden" name="slots" value="<?php echo $slot; ?>" />
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
</div>
<fieldset class="hidden">
    <div>
        <div class="fitem fitem_actionbuttons fitem_fsubmit mt-3">
            <fieldset class="felement fsubmit">
                <input id="id_submitbutton" type="submit" name="submit" class="btn btn-primary" value="<?php
                        print_string('save', 'quiz'); ?>"/>
            </fieldset>
        </div>
    </div>
</fieldset>
<?php
echo '</form>';
$PAGE->requires->js_init_call('M.mod_quiz.init_comment_popup', null, false, quiz_get_js_module());

// End of the page.
echo $output->footer();
