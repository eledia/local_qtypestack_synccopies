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
 * Event observer class to create variant copies for qtype_stack questions
 *
 * @package    local_qtypestack_synccopies
 * @copyright  2023 eLeDia GmbH
 * @author     Keno Goertz <support@eledia.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_qtypestack_synccopies;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Event observer class.
 */
class event_observers {

    /**
     * Question deleted.
     *
     * @param \core\event\question_deleted $event
     * @return void
     * @throws \dml_exception
     */
    public static function question_deleted(\core\event\question_deleted $event) {
        // Note that this has a subtle bug that can't easily be fixed: If the
        // user selects to delete both the original question and the
        // corresponding variant question, the question_deleted event might be
        // triggered after deleting only the original question.
        //
        // This observer will then delete the variant question. Finally, moodle
        // will try to delete the variant question as requested by the
        // user. Since it has already been deleted by this observer, moodle will
        // throw an error.
        //
        // The database is in a consistent state and nothing bad actually
        // happened, but an error message is displayed to the user. This can not
        // be prevented without changing the core moodle code.

        global $DB;

        $qids = $DB->get_fieldset_sql('SELECT questionid
                                       FROM {local_qtypestack_synccopies} s
                                       LEFT JOIN {question} q
                                       ON q.id=s.questionid WHERE q.id IS NULL
                                       GROUP BY s.questionid');
        foreach ($qids as $qid) {
            local_qtypestack_synccopies_delete_variant_copies($qid);
        }

        // Question that was deleted was a variantquestion itself.
        $qids = $DB->get_fieldset_sql('SELECT variantquestionid
                                       FROM {local_qtypestack_synccopies} s
                                       LEFT JOIN {question} q
                                       ON q.id=s.variantquestionid
                                       WHERE q.id IS NULL
                                       GROUP BY s.variantquestionid');
        foreach ($qids as $qid) {
            $DB->delete_records('local_qtypestack_synccopies',
                                ['variantquestionid' => $qid]);
        }

    }

    /**
     * Question created.
     *
     * @param \core\event\question_created $event
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function question_created(\core\event\question_created $event) {
        if (!get_config('local_qtypestack_synccopies', 'listenevents')) {
            return;
        }
        local_qtypestack_synccopies_create_missing_variant_copies();
    }

    /**
     * Question updated.
     *
     * @param \core\event\question_updated $event
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function question_updated(\core\event\question_updated $event) {
        if (!get_config('local_qtypestack_synccopies', 'listenevents')) {
            return;
        }
        local_qtypestack_synccopies_create_missing_variant_copies();
    }
}
