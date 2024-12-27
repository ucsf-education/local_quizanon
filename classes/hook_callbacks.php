<?php

namespace local_quizanon;

use \core\hook\output\before_standard_top_of_body_html_generation;

defined('MOODLE_INTERNAL') || die;

class hook_callbacks {
    /**
     * Hook callback for before standard top of body HTML generation.
     * Handles quiz anonymization redirects based on user roles and page types.
     * Verify if the user has the role to access the quiz report or redirect to the local plugin.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html(before_standard_top_of_body_html_generation $hook): void {
        local_quizanon_before_standard_top_of_body_html();       
    }
}
