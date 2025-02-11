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

require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_options.php');

/**
 * Quiz anon overview report settings form.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizanon_overview_options extends quiz_overview_options {
    /**
     * Get the URL to show the report with these options.
     * @return moodle_url the URL.
     */
    public function get_url() {
        $params = $this->get_url_params();
        $tifirst = optional_param('tifirst', null, PARAM_ALPHA);
        if (!empty($tifirst)) {
            $params['tifirst'] = $tifirst;
        }
        return new moodle_url('/local/quizanon/report.php', $params);
    }
}
