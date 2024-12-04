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
 * Upgrade script for local_quizanon.
 *
 * @package    local_quizanon
 * @copyright  2024 Moodle
 * @author     Oscar Nadjar <oscar.nadjar@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_local_quizanon_upgrade(int $oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024040900) {
        // Define table local_quizanon to be created.
        $table = new xmldb_table('local_quizanon');

        // Adding fields to table local_quizanon.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('enable', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);
        $table->add_field('roles', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_quizanon.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for local_quizanon.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_quizanon_usercodes to be created.
        $table = new xmldb_table('local_quizanon_usercodes');

        // Adding fields to table local_quizanon_usercodes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('code', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_quizanon_usercodes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('quizuser', XMLDB_KEY_UNIQUE, ['quizid', 'userid']);
        $table->add_key('quizusercode', XMLDB_KEY_UNIQUE, ['quizid', 'userid', 'code']);

        // Conditionally launch create table for local_quizanon_usercodes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Quizanon savepoint reached.
        upgrade_plugin_savepoint(true, 2024040900, 'local', 'quizanon');
    }

    if ($oldversion < 2024120400) {
        // Table local_quizanon to be modified.
        $table = new xmldb_table('local_quizanon');
        // Index to be dropped.
        $index = new xmldb_index('usermodified', XMLDB_INDEX_NOTUNIQUE, ['usermodified']);

        // Conditionally launch drop index for local_quizanon.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Quizanon savepoint reached.
        upgrade_plugin_savepoint(true, 2024120400, 'local', 'quizanon');
    }

    return true;
}
