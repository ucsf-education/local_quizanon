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

/**
 * Verify if the user has the role to access the quiz report or redirect to the local plugin.
 */
function local_quizanon_before_standard_top_of_body_html() {
    global $CFG, $PAGE, $COURSE, $DB, $USER;

    $pagename = $PAGE->pagetype;
    $coursemodule = $PAGE->cm;
    if (empty($coursemodule) || $coursemodule->modname !== 'quiz') {
        return;
    }
    $urlparams = $PAGE->url->params();
    $coursemoduleid = $PAGE->cm->id;
    $quizanonenabled = $DB->get_record('local_quizanon', ['quizid' => $coursemoduleid, 'enable' => 1]);
    $roles = !empty($quizanonenabled->roles) ? json_decode($quizanonenabled->roles) : [];
    $context = context_course::instance($COURSE->id);
    $userroles = get_user_roles($context, $USER->id);
    $userhasrole = false;

    foreach ($userroles as $role) {
        if (in_array($role->roleid, $roles)) {
            $userhasrole = true;
            break;
        }
    }

    $mode = !empty($urlparams['mode']) ? $urlparams['mode'] : '';
    $anonreportexists = is_readable($CFG->dirroot . '/local/quizanon/report/' . $mode . '/report.php');
    $redirect = !empty($quizanonenabled) && $userhasrole;
    switch($pagename) {
        case 'mod-quiz-report':
            $url = '/local/quizanon/report.php';
            $redirect = $redirect && $anonreportexists;
            break;
        case 'mod-quiz-review':
            $url = '/local/quizanon/review.php';
            break;
        case "mod-quiz-reviewquestion":
            $url = '/local/quizanon/reviewquestion.php';
            break;
        case "mod-quiz-comment":
            $url = '/local/quizanon/comment.php';
            break;
        case 'local-quizanon-report':
            $url = '/mod/quiz/report.php';
            $redirect = empty($quizanonenabled) || !$userhasrole;
            break;
        case 'local-quizanon-review':
            $url = '/mod/quiz/review.php';
            $redirect = empty($quizanonenabled) || !$userhasrole;
            break;
        case "local-quizanon-reviewquestion":
            $url = '/mod/quiz/reviewquestion.php';
            $redirect = empty($quizanonenabled) || !$userhasrole;
            break;
        case "local-quizanon-comment":
            $url = '/mod/quiz/comment.php';
            $redirect = empty($quizanonenabled) || !$userhasrole;
            break;
        default:
            $redirect = false;
            break;
    }
    if ($redirect) {
        $moodleurl = new moodle_url($url, $urlparams);
        redirect($moodleurl);
    }
}

/**
 * Extend quiz module form with the local plugin's settings.
 *
 * @param mod_quiz_mod_form $formwrapper
 * @param moodleform $mform
 */
function local_quizanon_coursemodule_standard_elements($formwrapper, $mform) {
    global $COURSE, $DB;
    if ($formwrapper instanceof mod_quiz_mod_form) {
        $cm = $formwrapper->get_coursemodule();
        $record = $DB->get_record('local_quizanon', ['quizid' => $cm->id]);
        $anonenable = !empty($record->enable) ? 1 : 0;
        $anonroles = !empty($record->roles) ? json_decode($record->roles) : [];

        $context = context_course::instance($COURSE->id);
        $mform->addElement('header', 'general', get_string('quizanonsettings', 'local_quizanon'));
        $mform->addElement('checkbox', 'anonenable', get_string('enablequizanon', 'local_quizanon'));
        $mform->setDefault('anonenable', $anonenable);

        $coursesroles = role_get_names($context);
        $rolesoptions = [];
        $options = [ 'multiple' => true ];
        foreach ($coursesroles as $role) {
            $rolesoptions[$role->id] = !empty($role->localname) ? $role->localname : $role->shortname;
        }
        $mform->addElement('autocomplete', 'anonroles', get_string('rolessetting', 'local_quizanon'), $rolesoptions, $options);
        $mform->setDefault('anonroles', $anonroles);
    }
}

/**
 * Save the local plugin's settings.
 *
 * @param stdClass $data
 * @return stdClass
 */
function local_quizanon_coursemodule_edit_post_actions($data) {
    global $DB, $USER;
    $quizid = $data->coursemodule;

    $arraydata = [
        'quizid' => $data->coursemodule,
        'enable' => !empty($data->anonenable) ? 1 : 0,
        'roles' => !empty($data->anonroles) ? json_encode($data->anonroles) : ''
    ];
    $exists = $DB->get_record('local_quizanon', ['quizid' => $quizid]);
    $recordid = !empty($exists->id) ? $exists->id : 0;
    $record = new local_quizanon\local\data\quizanon($recordid);
    $record->set_many($arraydata);
    $record->save();
    return $data;
}

/**
 * Get the usercode to anonymize the user.
 *
 * @param int $userid
 * @param int $quizid
 * @return string
 */
function local_anonquiz_get_usercode($userid, $quizid) {
    global $DB;
    $record = $DB->get_record('local_quizanon_usercodes', ['userid' => $userid, 'quizid' => $quizid]);
    if (!empty($record->code)) {
        $usercode = $record->code;
    } else {
        $record = new local_quizanon\local\data\quizanon_codes();
        // Higly unlikely but just in case we have a collision we don't want to loop forever.
        for ($i = 0; $i < 100; $i++) {
            $usercode = local_anonquiz_generate_usercode($userid, $quizid);
            if (!$DB->record_exists('local_quizanon_usercodes', ['quizid' => $quizid, 'code' => $usercode])) {
                break;
            }
        }
        $recordarray = [
            'userid' => $userid,
            'quizid' => $quizid,
            'code' => $usercode
        ];
        $record->set_many($recordarray);
        $record->save();
    }

    return $usercode;
}


/**
 * Generate a usercode to anonymize the user.
 *
 * @param int $userid
 * @param int $quizid
 * @return string
 */
function local_anonquiz_generate_usercode($userid, $quizid) {
    global $DB;
    $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
    $hash = sha1($userid . $quizid . time() . $user->firstname . $user->lastname . rand(1, 1000));
    $extractletters = preg_replace('/[^a-zA-Z]/', '', $hash);
    $extractnumbers = preg_replace('/[^0-9]/', '', $hash);
    $code = substr($extractletters, 0, 3) . substr($extractnumbers, 0, 3);
    $usercode = strtoupper($code);
    return $usercode;
}
