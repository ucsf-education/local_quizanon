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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/quizanon/report/grading/gradingsettings_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/grading/report.php');
require_once($CFG->dirroot . '/local/quizanon/lib.php');

/**
 * Quiz report to help teachers manually grade questions that need it.
 *
 * This report basically provides two screens:
 * - List question that might need manual grading (or optionally all questions).
 * - Provide an efficient UI to grade all attempts at a particular question.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizanon_grading_report extends quiz_grading_report {

    /**
     * Display the report.
     *
     * @param stdClass $quiz the quiz object.
     * @param stdClass $cm the course_module object.
     * @param stdClass $course the course object.
     */
    public function display($quiz, $cm, $course) {

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;

        // Get the URL options.
        $slot = optional_param('slot', null, PARAM_INT);
        $questionid = optional_param('qid', null, PARAM_INT);
        $grade = optional_param('grade', null, PARAM_ALPHA);

        $includeauto = optional_param('includeauto', false, PARAM_BOOL);
        if (!in_array($grade, ['all', 'needsgrading', 'autograded', 'manuallygraded'])) {
            $grade = null;
        }
        $pagesize = optional_param('pagesize',
                get_user_preferences('quiz_grading_pagesize', self::DEFAULT_PAGE_SIZE),
                PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $order = optional_param('order',
                get_user_preferences('quiz_grading_order', self::DEFAULT_ORDER),
                PARAM_ALPHAEXT);

        // Assemble the options required to reload this page.
        $optparams = ['includeauto', 'page'];
        foreach ($optparams as $param) {
            if ($$param) {
                $this->viewoptions[$param] = $$param;
            }
        }
        if (!data_submitted() && !preg_match(self::REGEX_POSITIVE_INT, $pagesize)) {
            // We only validate if the user accesses the page via a cleaned-up GET URL here.
            throw new moodle_exception('invalidpagesize');
        }
        if ($pagesize != self::DEFAULT_PAGE_SIZE) {
            $this->viewoptions['pagesize'] = $pagesize;
        }
        if ($order != self::DEFAULT_ORDER) {
            $this->viewoptions['order'] = $order;
        }

        // Check permissions.
        $this->context = context_module::instance($this->cm->id);
        require_capability('mod/quiz:grade', $this->context);
        $shownames = false;
        // Whether the current user can see custom user fields.
        $showcustomfields = false;
        $userfieldsapi = \core_user\fields::for_identity($this->context)->with_name();
        $customfields = [];
        foreach ($userfieldsapi->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $field) {
            $customfields[] = $field;
        }
        // Validate order.
        $orderoptions = array_merge(['random', 'date', 'studentfirstname', 'studentlastname'], $customfields);
        if (!in_array($order, $orderoptions)) {
            $order = self::DEFAULT_ORDER;
        } else if (!$shownames && ($order == 'studentfirstname' || $order == 'studentlastname')) {
            $order = self::DEFAULT_ORDER;
        } else if (!$showcustomfields && in_array($order, $customfields)) {
            $order = self::DEFAULT_ORDER;
        }
        if ($order == 'random') {
            $page = 0;
        }

        // Get the list of questions in this quiz.
        $this->questions = quiz_report_get_significant_questions($quiz);
        if ($slot && !array_key_exists($slot, $this->questions)) {
            throw new moodle_exception('unknownquestion', 'quiz_grading');
        }

        // Process any submitted data.
        if ($data = data_submitted() && confirm_sesskey() && $this->validate_submitted_marks()) {
            // Changes done to handle attempts being missed from grading due to redirecting to new page.
            $attemptsgraded = $this->process_submitted_data();

            $nextpagenumber = $page + 1;
            // If attempts need grading and one or more have now been graded, then page number should remain the same.
            if ($grade == 'needsgrading' && $attemptsgraded) {
                $nextpagenumber = $page;
            }

            redirect($this->grade_question_url($slot, $questionid, $grade, $nextpagenumber));
        }

        // Get the group, and the list of significant users.
        $this->currentgroup = $this->get_current_group($cm, $course, $this->context);
        if ($this->currentgroup == self::NO_GROUPS_ALLOWED) {
            $this->userssql = [];
        } else {
            $this->userssql = get_enrolled_sql($this->context,
                    ['mod/quiz:reviewmyattempts', 'mod/quiz:attempt'], $this->currentgroup);
        }

        $hasquestions = quiz_has_questions($this->quiz->id);
        if (!$hasquestions) {
            $this->print_header_and_tabs($cm, $course, $quiz, 'grading');
            echo $this->renderer->render_quiz_no_question_notification($quiz, $cm, $this->context);
            return true;
        }

        if (!$slot) {
            $this->display_index($includeauto);
            return true;
        }

        // Display the grading UI for one question.

        // Make sure there is something to do.
        $counts = null;
        $statecounts = $this->get_question_state_summary([$slot]);
        foreach ($statecounts as $record) {
            if ($record->questionid == $questionid) {
                $counts = $record;
                break;
            }
        }

        // If not, redirect back to the list.
        if (!$counts || $counts->$grade == 0) {
            redirect($this->list_questions_url(), get_string('alldoneredirecting', 'quiz_grading'));
        }

        $this->display_grading_interface($slot, $questionid, $grade,
                $pagesize, $page, $shownames, $showcustomfields, $order, $counts);
        return true;
    }

    /**
     * Display the UI for grading attempts at one question.
     *
     * @param int $slot identifies which question to grade.
     * @param int $questionid identifies which question to grade.
     * @param string $grade type of attempts to grade.
     * @param int $pagesize number of questions to show per page.
     * @param int $page current page number.
     * @param bool $shownames whether student names should be shown.
     * @param bool $showcustomfields whether custom field values should be shown.
     * @param string $order preferred order of attempts.
     * @param stdClass $counts object that stores the number of each type of attempt.
     */
    protected function display_grading_interface($slot, $questionid, $grade,
            $pagesize, $page, $shownames, $showcustomfields, $order, $counts) {

        if ($pagesize * $page >= $counts->$grade) {
            $page = 0;
        }

        // Prepare the options form.
        $hidden = [
            'id' => $this->cm->id,
            'mode' => 'grading',
            'slot' => $slot,
            'qid' => $questionid,
            'page' => $page,
        ];
        if (array_key_exists('includeauto', $this->viewoptions)) {
            $hidden['includeauto'] = $this->viewoptions['includeauto'];
        }
        $mform = new quizanon_grading_settings_form($hidden, $counts, $shownames, $showcustomfields, $this->context);

        // Tell the form the current settings.
        $settings = new stdClass();
        $settings->grade = $grade;
        $settings->pagesize = $pagesize;
        $settings->order = $order;
        $mform->set_data($settings);

        if ($mform->is_submitted()) {
            if ($mform->is_validated()) {
                // If the form was submitted and validated, save the user preferences, and
                // redirect to a cleaned-up GET URL.
                set_user_preference('quiz_grading_pagesize', $pagesize);
                set_user_preference('quiz_grading_order', $order);
                redirect($this->grade_question_url($slot, $questionid, $grade, $page));
            } else {
                // Set the pagesize back to the previous value, so the report page can continue the render
                // and the form can show the validation.
                $pagesize = get_user_preferences('quiz_grading_pagesize', self::DEFAULT_PAGE_SIZE);
            }
        }

        list($qubaids, $count) = $this->get_usage_ids_where_question_in_state(
                $grade, $slot, $questionid, $order, $page, $pagesize);
        $attempts = $this->load_attempts_by_usage_ids($qubaids);

        // Question info.
        $questioninfo = new stdClass();
        $questioninfo->number = $this->questions[$slot]->number;
        $questioninfo->questionname = format_string($counts->name);

        // Paging info.
        $paginginfo = new stdClass();
        $paginginfo->from = $page * $pagesize + 1;
        $paginginfo->to = min(($page + 1) * $pagesize, $count);
        $paginginfo->of = $count;
        $qubaidlist = implode(',', $qubaids);

        $this->print_header_and_tabs($this->cm, $this->course, $this->quiz, 'grading');

        $gradequestioncontent = '';
        foreach ($qubaids as $qubaid) {
            $attempt = $attempts[$qubaid];
            $quba = question_engine::load_questions_usage_by_activity($qubaid);
            $displayoptions = quiz_get_review_options($this->quiz, $attempt, $this->context);
            $displayoptions->generalfeedback = question_display_options::HIDDEN;
            $displayoptions->history = question_display_options::HIDDEN;
            $displayoptions->manualcomment = question_display_options::EDITABLE;

            $gradequestioncontent .= $this->renderer->render_grade_question(
                    $quba,
                    $slot,
                    $displayoptions,
                    $this->questions[$slot]->number,
                    $this->get_question_heading($attempt, $shownames, $showcustomfields)
            );
        }

        $pagingbar = new stdClass();
        $pagingbar->count = $count;
        $pagingbar->page = $page;
        $pagingbar->pagesize = $pagesize;
        $pagingbar->pagesize = $pagesize;
        $pagingbar->order = $order;
        $pagingbar->pagingurl = $this->grade_question_url($slot, $questionid, $grade, false);

        $hiddeninputs = [
                'qubaids' => $qubaidlist,
                'slots' => $slot,
                'sesskey' => sesskey()
        ];

        echo $this->renderer->render_grading_interface(
                $questioninfo,
                $this->list_questions_url(),
                $mform,
                $paginginfo,
                $pagingbar,
                $this->grade_question_url($slot, $questionid, $grade, $page),
                $hiddeninputs,
                $gradequestioncontent
        );
    }

    /**
     * Get the base URL for this report.
     * @return moodle_url the URL.
     */
    protected function base_url() {
        return new moodle_url('/local/quizanon/report.php',
                ['id' => $this->context->instanceid, 'mode' => 'grading']);
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @param stdClass $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('quiz_grading');
        $this->printanon_header_and_tabs($cm, $course, $quiz, $reportmode);
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @param stdClass $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function printanon_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
        global $PAGE, $OUTPUT, $CFG;

        // Print the page header.
        $PAGE->set_title($quiz->name);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        $context = context_module::instance($cm->id);
        if (!$PAGE->has_secondary_navigation()) {
            echo $OUTPUT->heading(format_string($quiz->name, true, ['context' => $context]));
        }
        $context = $PAGE->context;
        $cmid = $PAGE->cm->id;
        if (has_any_capability(['mod/quiz:viewreports', 'mod/quiz:grade'], $context)) {
            require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
            $reportlist = quiz_report_list($context);
            $sesskey = sesskey();
            $baseurl = new \moodle_url('/course/jumpto.php');
            $baseurl->params(['sesskey' => $sesskey]);
            $options = [];
            foreach ($reportlist as $report) {
                $anonreportexists = is_readable($CFG->dirroot . '/local/quizanon/report/' . $report . '/report.php');
                if ($anonreportexists) {
                    $url = new \moodle_url('/local/quizanon/report.php', ['id' => $cmid, 'mode' => $report]);
                } else {
                    $url = new \moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => $report]);
                }
                $options[$url->out_as_local_url(false)] = get_string($report, 'quiz_'.$report);
            }
            $selected = new \moodle_url('/local/quizanon/report.php', ['id' => $cmid, 'mode' => $reportmode]);
            $select = new single_select($baseurl, 'jump', $options, $selected->out_as_local_url(false), null, 'quiz-report-select');
            $select->method = 'post';
            $select->class = 'mb-3 mt-1';
            echo $OUTPUT->render($select);
        }
        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            echo plagiarism_update_status($course, $cm);
        }
    }

    /**
     * Get question heading.
     *
     * @param stdClass $attempt An instance of quiz_attempt.
     * @param bool $shownames True to show the student first/lastnames.
     * @param bool $showcustomfields Whether custom field values should be shown.
     * @return string The string text for the question heading.
     */
    protected function get_question_heading(stdClass $attempt, bool $shownames, bool $showcustomfields): string {
        global $DB;
        $a = new stdClass();
        $a->attempt = $attempt->attempt;
        $a->fullname = local_anonquiz_generate_usercode($attempt->userid, $this->quiz->id);

        $customfields = [];
        foreach ($this->extrauserfields as $field) {
            if (strval($attempt->{$field}) !== '') {
                $customfields[] = s($attempt->{$field});
            }
        }

        $a->customfields = implode(', ', $customfields);

        if ($showcustomfields) {
            return get_string('gradingattemptwithcustomfields', 'quiz_grading', $a);
        } else {
            return get_string('gradingattempt', 'quiz_grading', $a);
        }
    }
}
