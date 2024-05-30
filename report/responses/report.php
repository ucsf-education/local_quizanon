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
 * This file defines the quiz responses report class.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/responses/report.php');
require_once($CFG->dirroot . '/local/quizanon/report/responses/responses_form.php');
require_once($CFG->dirroot . '/local/quizanon/report/responses/first_or_all_responses_table.php');
require_once($CFG->dirroot . '/local/quizanon/report/responses/last_responses_table.php');
require_once($CFG->dirroot . '/local/quizanon/report/responses/responses_options.php');

/**
 * Quiz report subclass for the responses report.
 *
 * This report lists some combination of
 *  * what question each student saw (this makes sense if random questions were used).
 *  * the response they gave,
 *  * and what the right answer is.
 *
 * Like the overview report, there are options for showing students with/without
 * attempts, and for deleting selected attempts.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizanon_responses_report extends quiz_responses_report {

    /**
     * This function displays the quiz responses report.
     *
     * @param stdClass $quiz the quiz settings.
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @return bool true if the report was displayed, false if not.
     */
    public function display($quiz, $cm, $course) {
        global $OUTPUT, $DB;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'responses', 'quizanon_responses_settings_form', $quiz, $cm, $course);
        $options = new quizanon_responses_options('responses', $quiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);

        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                ['context' => context_course::instance($course->id)]);
        if ($options->whichtries === question_attempt::LAST_TRY) {
            $tableclassname = 'quizanon_last_responses_table';
        } else {
            $tableclassname = 'quizanon_first_or_all_responses_table';
        }
        $table = new $tableclassname($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
        $filename = quiz_report_download_filename(get_string('responsesfilename', 'quiz_responses'),
                $courseshortname, $quiz->name);
        $table->is_downloading($options->download, $filename,
                $courseshortname . ' ' . format_string($quiz->name, true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }
        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security problem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = quiz_has_questions($quiz->id);

        // Start output.
        if (!$table->is_downloading()) {
            // Only print headers if not asked to download data.
            $this->print_standard_header_and_messages($cm, $course, $quiz,
                    $options, $currentgroup, $hasquestions, $hasstudents);

            // Print the display options.
            $this->form->display();
        }

        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);
        if ($hasquestions && ($hasstudents || $options->attempts == self::ALL_WITH)) {

            $table->setup_sql_queries($allowedjoins);

            if (!$table->is_downloading()) {
                // Print information on the grading method.
                if ($strattempthighlight = quiz_report_highlighting_grading_method(
                        $quiz, $this->qmsubselect, $options->onlygraded)) {
                    echo '<div class="quizattemptcounts">' . $strattempthighlight . '</div>';
                }
            }

            // Define table columns.
            $columns = [];
            $headers = [];

            if (!$table->is_downloading() && $options->checkboxcolumn) {
                $columnname = 'checkbox';
                $columns[] = $columnname;
                $headers[] = $table->checkbox_col_header($columnname);
            }

            $columns[] = 'usercode';
            $headers[] = get_string('usercode', 'local_quizanon');
            $this->add_state_column($columns, $headers);

            if ($table->is_downloading()) {
                $this->add_time_columns($columns, $headers);
            }

            $this->add_grade_columns($quiz, $options->usercanseegrades, $columns, $headers);

            foreach ($questions as $id => $question) {
                if ($options->showqtext) {
                    $columns[] = 'question' . $id;
                    $headers[] = get_string('questionx', 'question', $question->number);
                }
                if ($options->showresponses) {
                    $columns[] = 'response' . $id;
                    $headers[] = get_string('responsex', 'quiz_responses', $question->number);
                }
                if ($options->showright) {
                    $columns[] = 'right' . $id;
                    $headers[] = get_string('rightanswerx', 'quiz_responses', $question->number);
                }
            }

            $table->define_columns($columns);
            $table->define_headers($headers);
            $table->sortable(true, 'uniqueid');

            // Set up the table.
            $table->define_baseurl($options->get_url());

            $this->configure_user_columns($table);

            $table->no_sorting('feedbacktext');
            $table->column_class('sumgrades', 'bold');

            $table->set_attribute('id', 'responses');

            $table->collapsible(true);
            $table->sql->from .= " LEFT JOIN {local_quizanon_usercodes} qan ON qan.userid = u.id AND qan.quizid = :quizid2";
            $table->sql->params['quizid2'] = $quiz->id;
            $table->sql->fields .= ', qan.code as usercode';
            $table->out($options->pagesize, true);
        }
        return true;
    }

    /**
     * Get the base URL for this report.
     * @return moodle_url the URL.
     */
    protected function get_base_url() {
        return new moodle_url('/local/quizanon/report.php',
                ['id' => $this->context->instanceid, 'mode' => $this->mode]);
    }

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @param stdClass $quiz the quiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'responses') {
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
}
