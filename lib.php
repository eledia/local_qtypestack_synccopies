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

//  @codingStandardsIgnoreStart
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../question/editlib.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
//  @codingStandardsIgnoreEnd

defined('MOODLE_INTERNAL') || die;

// Find the question bank entry ID to link a variant question to.
//
// Variant question versions are matched up to have the same seed. If the number
// or values of the seeds are changed in the base question, new question bank
// entries are created for the variant questions. Otherwise, the variant
// questions will share question bank entries and be versioned just like the
// base question.
/**
 * Find the question bank entry ID to link a variant question to.
 *
 * @param int $questionid
 * @param int $version
 * @param false|mixed $seed
 * @return false|mixed
 * @throws dml_exception
 */
function local_qtypestack_synccopies_get_variant_questionbankentry($questionid, $version, $seed) {
    global $DB;

    $otherquestionversions = 'SELECT questionid
                              FROM {question_versions}
                              WHERE questionbankentryid IN (
                                  SELECT questionbankentryid
                                  FROM {question_versions}
                                  WHERE questionid = :questionid
                              ) AND version != :version';

    $sql = 'SELECT questionbankentryid
            FROM {question_versions}
            WHERE questionid IN (
                SELECT variantquestionid
                FROM {local_qtypestack_synccopies} sc
                JOIN {qtype_stack_deployed_seeds} s
                ON sc.seedid = s.id
                WHERE sc.questionid IN
                (' . $otherquestionversions .')
                AND seed = :seed
            ) GROUP BY questionbankentryid';

    return $DB->get_field_sql($sql, ['questionid' => $questionid,
                                     'version' => $version,
                                     'seed' => $seed]);
}

/**
 * Get the questionbankentry ID that belongs to a given question ID.
 *
 * @param int $questionid
 * @return false|mixed
 */
function local_qtypestack_synccopies_get_questionbankentry($questionid) {
    global $DB;

    return $DB->get_field_sql('SELECT questionbankentryid
                               FROM {question_versions}
                               WHERE questionid = :questionid',
                              ['questionid' => $questionid]);
}

/**
 * Links the newly created variant question to its previous versions by finding
 * a variant question belonging to the previous base question that shares the
 * same seed.
 *
 * @param int $questionid
 * @param int $variantquestionid
 * @param int $version
 * @param false|mixed $seed
 * @return void
 * @throws dml_exception
 */
function local_qtypestack_synccopies_link_variantquestion_versions($questionid, $variantquestionid,
                                       $version, $seed) {
    global $DB;

    $record = $DB->get_record('question_versions',
                              ['questionid' => $variantquestionid]);
    $questionbankentryid = local_qtypestack_synccopies_get_variant_questionbankentry($questionid,
                                                         $version, $seed);

    if (!$questionbankentryid) {
        // No matching previous variant question found, still update the variant.
        // Question's versions to be equal to the base question's.
        $record->version = $version;
        $DB->update_record('question_versions', $record);
        return;
    }

    // We are joining our variant question to another question bank entry, which
    // makes the newly created one obsolete.
    $DB->delete_records('question_bank_entries',
                        ['id' => $record->questionbankentryid]);

    $record->version = $version;
    $record->questionbankentryid = $questionbankentryid;
    $DB->update_record('question_versions', $record);
}

// The question XML export and import is the only somewhat sane interface to use
// for our purpose of duplicating a question (the usual duplication procedure
// provides the user with a form, leading to ugliness we don't want to deal with
// here).
/**
 * The question XML export and import is the only somewhat sane interface to use
 * for our purpose of duplicating a question (the usual duplication procedure
 * provides the user with a form, leading to ugliness we don't want to deal with
 * here).
 *
 * @param int $seedid
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_qtypestack_synccopies_create_variant_copy($seedid) {
    global $DB;

    // Necessary to check this because of possible race condition.
    if ($DB->get_record('local_qtypestack_synccopies', ['seedid' => $seedid])) {
        return;
    }

    $deployedseeds = $DB->get_record('qtype_stack_deployed_seeds',
                                      ['id' => $seedid]);
    $questionid = $deployedseeds->questionid;
    $seed = $deployedseeds->seed;
    $question = $DB->get_record('question', ['id' => $questionid]);
    get_question_options($question, true);
    $version = $DB->get_field('question_versions', 'version',
                              ['questionid' => $questionid]);

    if (isset($question->categoryobject)) {
        $category = $question->categoryobject;
    } else {
        $category = $DB->get_record('question_categories',
                                    ['id' => $question->category]);
    }
    $question->contextid = $category->contextid;
    $thiscontext = context::instance_by_id($category->contextid);
    $contexts = new core_question\local\bank\question_edit_contexts(
        $thiscontext);
    $lowest = $contexts->lowest();
    if (!($lowest instanceof context_course)) {
        // Lowest context can be course or module. We want the course, but got
        // the module context. The course context is the module context's parent.
        $lowest = $lowest->get_parent_context();
    }
    $course = $DB->get_record('course', ['id' => $lowest->instanceid]);
    if (!$course) {
        $course = $DB->get_record('course', ['id' => 1]);
    }

    $questiondata = question_bank::load_question_data($questionid);
    $qformat = new qformat_xml();
    $qformat->setCourse($course);
    $qformat->setContexts($contexts->having_one_edit_tab_cap('export'));
    $qformat->setQuestions([$questiondata]);
    $qformat->setCattofile(true);
    $qformat->setContexttofile(true);
    $content = $qformat->exportprocess(false);

    $xml = new SimpleXMLElement($content);
    $result = $xml->xpath('question[@type="category"]');
    foreach ($result as $question) {
        $question->category->text .= '/synccopies';
        $question->idnumber = '';
    }
    $result = $xml->xpath('question[@type="stack"]');
    foreach ($result as $question) {
        $oldname = $question->name->text;
        $newname = 'synccopy of ' . $oldname . ' (' . $seed . ')';
        $question->name->text = $newname;
        $question->idnumber = '';
        unset($question->deployedseed);
        $question->deployedseed = $DB->get_field('qtype_stack_deployed_seeds',
                                                 'seed', ['id' => $seedid]);
        $question->tags->tag[0]->text = 'synccopy';
        $questionbankentryid = local_qtypestack_synccopies_get_questionbankentry($questionid);
        $question->tags->tag[1]->text = 'id' . $questionbankentryid;
        //  @codingStandardsIgnoreLine
        // TODO: Check if original question already has the tag.
        $tagtask = new \local_qtypestack_synccopies\task\add_question_tag();
        $tagtask->set_next_run_time(time() + 60);
        $tagtask->set_custom_data([
            'questionid' => $questionid,
            'questionbankentryid' => $questionbankentryid,
            'contextid' => $category->contextid,
        ]);
        \core\task\manager::queue_adhoc_task($tagtask);
    }

    $file = make_request_directory() . '/question.xml';
    file_put_contents($file, $xml->asXML());

    $qformat = new qformat_xml();
    $qformat->setCategory($category);
    $qformat->setContexts($contexts->having_one_edit_tab_cap('import'));
    $qformat->setFilename($file);
    $qformat->setRealfilename($file);
    $qformat->setMatchgrades(false);
    $qformat->setCatfromfile(true);
    $qformat->setContextfromfile(true);
    $qformat->setStoponerror(false);

    // We need to do this first, because the import process creates a
    // \core\event\question_created event which will lead to this function being
    // called again, at which point our entries should exist in the database.
    $record = new stdClass();
    $record->questionid = $questionid;
    $record->seedid = $seedid;
    $record->variantquestionid = 0; // Placeholder.
    $record->id = $DB->insert_record('local_qtypestack_synccopies', $record);

    // Do anything before that we need to.
    if (!$qformat->importpreprocess()) {
        $DB->delete_records('local_qtypestack_synccopies',
                            ['id' => $record->id]);
        throw new moodle_exception('cannotimport', '', $thispageurl->out());
    }

    // Process the uploaded file.
    if (!$qformat->importprocess()) {
        $DB->delete_records('local_qtypestack_synccopies',
                            ['id' => $record->id]);
        throw new moodle_exception('cannotimport', '', $thispageurl->out());
    }

    // In case anything needs to be done after.
    if (!$qformat->importpostprocess()) {
        $DB->delete_records('local_qtypestack_synccopies',
                            ['id' => $record->id]);
        throw new moodle_exception('cannotimport', '', $thispageurl->out());
    }

    $record->variantquestionid = $qformat->questionids[0];
    $DB->update_record('local_qtypestack_synccopies', $record);
    local_qtypestack_synccopies_link_variantquestion_versions($questionid, $record->variantquestionid,
                                  $version, $seed);
}

/**
 * Create missing variant copies.
 *
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_qtypestack_synccopies_create_missing_variant_copies() {
    global $DB;

    $seedids = $DB->get_fieldset_sql('SELECT id FROM {qtype_stack_deployed_seeds}
                                      WHERE id NOT IN (
                                          SELECT seedid
                                          FROM {local_qtypestack_synccopies}
                                      ) AND questionid NOT IN (
                                          SELECT variantquestionid
                                          FROM {local_qtypestack_synccopies}
                                      )');
    foreach ($seedids as $seedid) {
        local_qtypestack_synccopies_create_variant_copy($seedid);
    }
}

/**
 * Get variant copies.
 *
 * @param int $questionid
 * @return array
 * @throws dml_exception
 */
function local_qtypestack_synccopies_get_variant_copies($questionid) {
    global $DB;

    $variantquestionids = $DB->get_fieldset_select(
        'local_qtypestack_synccopies', 'variantquestionid',
        'questionid=?', [$questionid]);
    return $variantquestionids;
}

/**
 * Delete variant copies.
 *
 * @param int $questionid
 * @return void
 * @throws dml_exception
 */
function local_qtypestack_synccopies_delete_variant_copies($questionid) {
    global $DB;

    $variantquestionids = local_qtypestack_synccopies_get_variant_copies($questionid);
    foreach ($variantquestionids as $variantquestionid) {
        question_delete_question($variantquestionid);
    }
    $DB->delete_records('local_qtypestack_synccopies',
                        ['questionid' => $questionid]);
}
