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
 * Seetting for the quizanon plugin.
 *
 * @package    local_quizanon
 * @copyright  Moodle US
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $pluginname = get_string('pluginname', 'local_quizanon');

    $settings = new admin_settingpage('local_quizanon', $pluginname);
    $settings->add(new admin_setting_configcheckbox('local_quizanon/enablequizanon',
        get_string('enablequizanon', 'local_quizanon'), get_string('enablequizanon_desc', 'local_quizanon'), 0));
    $ADMIN->add('localplugins', $settings);
}
