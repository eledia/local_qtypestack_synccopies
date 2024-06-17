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
 * Library functions to create variant copies for qtype_stack questions
 *
 * @package    local_qtypestack_synccopies
 * @copyright  2023 eLeDia GmbH
 * @author     Keno Goertz <support@eledia.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../lib.php');
require_once($CFG->libdir . '/clilib.php');

global $USER;
$USER->id = get_admin();

local_qtypestack_synccopies_get_entries();

/**
 * Fixing tags for question.
 *
 * @param int $questionid
 * @param int $idtagstring
 * @throws coding_exception
 * @throws dml_exception
 * @return void
 */
function local_qtypestack_synccopies_fix_tags($questionid, $idtagstring) {
    cli_writeln("Fixing tag for question with ID "
                . $questionid .
                " to ID " . $idtagstring);
    global $DB;

    $tags = \core_tag_tag::get_item_tags('core_question', 'question',
                                         $questionid);
    $taginstanceids = [];
    foreach ($tags as $tag) {
        if (preg_match('/^id[0-9]+$/', $tag->get_display_name())) {
            array_push($taginstanceids, $tag->taginstanceid);
        }
    }
    \core_tag_tag::delete_instances_by_id($taginstanceids);

    $question = $DB->get_record('question', ['id' => $questionid]);
    get_question_options($question, true);
    if (isset($question->categoryobject)) {
        $category = $question->categoryobject;
    } else {
        $category = $DB->get_record('question_categories',
                                    ['id' => $question->category]);
    }
    $context = \context::instance_by_id($category->contextid);

    \core_tag_tag::add_item_tag('core_question', 'question',
                                $questionid, $context,
                                'id' . $idtagstring);
}

/**
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function local_qtypestack_synccopies_get_entries() {
    global $DB;

    $entries = $DB->get_records('local_qtypestack_synccopies');

    foreach ($entries as $entry) {
        $questionbankentryid = local_qtypestack_synccopies_get_questionbankentry($entry->questionid);
        local_qtypestack_synccopies_fix_tags($entry->questionid, $questionbankentryid);
        local_qtypestack_synccopies_fix_tags($entry->variantquestionid, $questionbankentryid);
    }
}
