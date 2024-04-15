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
 * This file defines the quizanon overview report class.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

 require_once($CFG->dirroot . '/mod/quiz/report/overview/report.php');
 require_once($CFG->dirroot . '/local/quizanon/report/overview/overview_form.php');
 require_once($CFG->dirroot . '/local/quizanon/report/overview/overview_table.php');
 require_once($CFG->dirroot . '/local/quizanon/report/overview/overview_options.php');

/**
 * Quiz report subclass for the overview (grades) report.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizanon_overview_report extends quiz_overview_report {

    /**
     * Display the report.
     *
     * @param stdClass $quiz the quiz settings.
     * @param stdClass $cm the course_module information.
     * @param stdClass $course the course settings.
     * @return bool success or failure.
     */
    public function display($quiz, $cm, $course) {
        global $DB, $PAGE;
        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'overview', 'quizanon_overview_settings_form', $quiz, $cm, $course);

        $options = new quizanon_overview_options('overview', $quiz, $cm, $course);

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
                ['context' => \context_course::instance($course->id)]);
        $table = new quizanon_overview_table($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
        $filename = quiz_report_download_filename(get_string('overviewfilename', 'quiz_overview'),
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
            // are accessible, is not a security porblem.
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
            // Construct the SQL.
            $table->setup_sql_queries($allowedjoins);

            if (!$table->is_downloading()) {
                // Output the regrade buttons.
                if (has_capability('mod/quiz:regrade', $this->context)) {
                    $regradesneeded = $this->count_question_attempts_needing_regrade(
                            $quiz, $groupstudentsjoins);
                    if ($currentgroup) {
                        $a = new \stdClass();
                        $a->groupname = format_string(groups_get_group_name($currentgroup), true, [
                            'context' => $this->context,
                        ]);
                        $a->coursestudents = get_string('participants');
                        $a->countregradeneeded = $regradesneeded;
                        $regradealldrydolabel =
                                get_string('regradealldrydogroup', 'quiz_overview', $a);
                        $regradealldrylabel =
                                get_string('regradealldrygroup', 'quiz_overview', $a);
                        $regradealllabel =
                                get_string('regradeallgroup', 'quiz_overview', $a);
                    } else {
                        $regradealldrydolabel =
                                get_string('regradealldrydo', 'quiz_overview', $regradesneeded);
                        $regradealldrylabel =
                                get_string('regradealldry', 'quiz_overview');
                        $regradealllabel =
                                get_string('regradeall', 'quiz_overview');
                    }
                    $displayurl = new \moodle_url($options->get_url(), ['sesskey' => sesskey()]);
                    echo '<div class="regradebuttons">';
                    echo '<form action="'.$displayurl->out_omit_querystring().'">';
                    echo '<div>';
                    echo \html_writer::input_hidden_params($displayurl);
                    echo '<input type="submit" class="btn btn-secondary" name="regradeall" value="'.$regradealllabel.'"/>';
                    echo '<input type="submit" class="btn btn-secondary ml-1" name="regradealldry" value="' .
                            $regradealldrylabel . '"/>';
                    if ($regradesneeded) {
                        echo '<input type="submit" class="btn btn-secondary ml-1" name="regradealldrydo" value="' .
                                $regradealldrydolabel . '"/>';
                    }
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';
                }
                // Print information on the grading method.
                if ($strattempthighlight = quiz_report_highlighting_grading_method(
                        $quiz, $this->qmsubselect, $options->onlygraded)) {
                    echo '<div class="quizattemptcounts mt-3">' . $strattempthighlight . '</div>';
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
            $this->add_time_columns($columns, $headers);

            $this->add_grade_columns($quiz, $options->usercanseegrades, $columns, $headers, false);

            if (!$table->is_downloading() && has_capability('mod/quiz:regrade', $this->context) &&
                    $this->has_regraded_questions($table->sql->from, $table->sql->where, $table->sql->params)) {
                $columns[] = 'regraded';
                $headers[] = get_string('regrade', 'quiz_overview');
            }

            if ($options->slotmarks) {
                foreach ($questions as $slot => $question) {
                    $columns[] = 'qsgrade' . $slot;
                    $header = get_string('qbrief', 'quiz', $question->number);
                    if (!$table->is_downloading()) {
                        $header .= '<br />';
                    } else {
                        $header .= ' ';
                    }
                    $header .= '/' . quiz_rescale_grade($question->maxmark, $quiz, 'question');
                    $headers[] = $header;
                }
            }

            $this->set_up_table_columns($table, $columns, $headers, $this->get_base_url(), $options, false);
            $table->set_attribute('class', 'generaltable generalbox grades');

            $table->out($options->pagesize, true);
        }

        if (!$table->is_downloading() && $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            list($bands, $bandwidth) = self::get_bands_count_and_width($quiz);
            $labels = self::get_bands_labels($bands, $bandwidth, $quiz);

            if ($currentgroup && $this->hasgroupstudents) {
                $sql = "SELECT qg.id
                          FROM {quiz_grades} qg
                          JOIN {user} u on u.id = qg.userid
                        {$groupstudentsjoins->joins}
                          WHERE qg.quiz = $quiz->id AND {$groupstudentsjoins->wheres}";
                if ($DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, $groupstudentsjoins);
                    $chart = self::get_chart($labels, $data);
                    $groupname = format_string(groups_get_group_name($currentgroup), true, [
                        'context' => $this->context,
                    ]);
                    $graphname = get_string('overviewreportgraphgroup', 'quiz_overview', $groupname);
                    // Numerical range data should display in LTR even for RTL languages.
                    echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
                }
            }

            if ($DB->record_exists('quiz_grades', ['quiz' => $quiz->id])) {
                $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());
                $chart = self::get_chart($labels, $data);
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                // Numerical range data should display in LTR even for RTL languages.
                echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
            }
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
    public function print_header_and_tabs($cm, $course, $quiz, $reportmode = 'overview') {
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
            $select = new single_select($baseurl, 'jump', $options, $reportmode, null, 'quiz-report-select');
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
     * Check necessary capabilities, and start the display of the regrade progress page.
     * @param object $quiz the quiz settings.
     * @param object $cm the cm object for the quiz.
     */
    protected function start_regrade($quiz, $cm) {
        require_capability('mod/quiz:regrade', $this->context);
        $course = get_course($cm->course);
        $this->print_header_and_tabs($cm, $course, $quiz, $this->mode);
    }
}
