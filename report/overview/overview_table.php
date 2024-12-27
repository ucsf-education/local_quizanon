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

require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_table.php');
require_once($CFG->dirroot . '/local/quizanon/lib.php');

/**
 * This is a table subclass for displaying the quiz grades report.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizanon_overview_table extends quiz_overview_table {

    /**
     * Generate the display of the grade column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_sumgrades($attempt) {
        if ($attempt->state != 'finished') {
            return '-';
        }

        $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz);
        if ($this->is_downloading()) {
            return $grade;
        }

        if (isset($this->regradedqs[$attempt->usageid])) {
            $newsumgrade = 0;
            $oldsumgrade = 0;
            foreach ($this->questions as $question) {
                if (isset($this->regradedqs[$attempt->usageid][$question->slot])) {
                    $newsumgrade += $this->regradedqs[$attempt->usageid][$question->slot]->newfraction * $question->maxmark;
                    $oldsumgrade += $this->regradedqs[$attempt->usageid][$question->slot]->oldfraction * $question->maxmark;
                } else {
                    $newsumgrade += $this->lateststeps[$attempt->usageid][$question->slot]->fraction * $question->maxmark;
                    $oldsumgrade += $this->lateststeps[$attempt->usageid][$question->slot]->fraction * $question->maxmark;
                }
            }
            $newsumgrade = quiz_rescale_grade($newsumgrade, $this->quiz);
            $oldsumgrade = quiz_rescale_grade($oldsumgrade, $this->quiz);
            $grade = html_writer::tag('del', $oldsumgrade) . '/' .
                    html_writer::empty_tag('br') . $newsumgrade;
        }
        return html_writer::link(new moodle_url('/local/quizanon/review.php',
                ['attempt' => $attempt->attempt]), $grade,
                ['title' => get_string('reviewattempt', 'quiz')]);
    }

    /**
     * Make a link to review an individual question in a popup window.
     *
     * @param string $data HTML fragment. The text to make into the link.
     * @param stdClass $attempt data for the row of the table being output.
     * @param int $slot the number used to identify this question within this usage.
     */
    public function make_review_link($data, $attempt, $slot) {
        global $OUTPUT, $CFG;

        $flag = '';
        if ($this->is_flagged($attempt->usageid, $slot)) {
            $flag = $OUTPUT->pix_icon('i/flagged', get_string('flagged', 'question'),
                    'moodle', ['class' => 'questionflag']);
        }

        $feedbackimg = '';
        $state = $this->slot_state($attempt, $slot);
        if ($state && $state->is_finished() && $state != question_state::$needsgrading) {
            $feedbackimg = $this->icon_for_fraction($this->slot_fraction($attempt, $slot));
        }

        $output = html_writer::tag('span', $feedbackimg . html_writer::tag('span',
                $data, ['class' => $state->get_state_class(true)]) . $flag, ['class' => 'que']);

        $reviewparams = ['attempt' => $attempt->attempt, 'slot' => $slot];
        if (isset($attempt->try)) {
            $reviewparams['step'] = $this->step_no_for_try($attempt->usageid, $slot, $attempt->try);
        }
        $url = new moodle_url('/local/quizanon/reviewquestion.php', $reviewparams);
        $output = $OUTPUT->action_link($url, $output,
                new popup_action('click', $url, 'reviewquestion',
                        ['height' => 450, 'width' => 650]),
                ['title' => get_string('reviewresponse', 'quiz')]);

        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir . '/plagiarismlib.php');
            $output .= plagiarism_get_links([
                'context' => $this->context->id,
                'component' => 'qtype_'.$this->questions[$slot]->qtype,
                'cmid' => $this->context->instanceid,
                'area' => $attempt->usageid,
                'itemid' => $slot,
                'userid' => $attempt->userid]);
        }
        return $output;
    }

    /**
     * Generate unique code for the user and the quiz.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_usercode($attempt) {
        return local_anonquiz_get_usercode($attempt->userid, $this->quiz->id);
    }

    /**
     * This function is not part of the public api.
     */
    public function print_initials_bar() {
        global $OUTPUT;

        $ifirst = $this->get_initial_first();

        if (is_null($ifirst)) {
            $ifirst = '';
        }

        if ((!empty($ifirst) || !empty($ilast) || $this->use_initials)) {
            $prefixfirst = $this->request[TABLE_VAR_IFIRST];
            echo $OUTPUT->initials_bar(
                $ifirst,
                'firstinitial',
                get_string('usercode', 'local_quizanon'),
                $prefixfirst,
                $this->baseurl);
        }
    }
}
