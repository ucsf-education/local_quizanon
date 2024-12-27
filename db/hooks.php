<?php
/**
 * Hooks configuration for the Quiz Anonymization plugin
 *
 * @package    local_quizanon
 * @copyright  2024 UCSF Education IT
 * @author     Leon U. Bailey <leon.bailey@ucsf.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => '\local_quizanon\hook_callbacks::before_standard_top_of_body_html',
        'priority' => 0,
    ], 
];
