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
 * Add question tag.
 *
 * Ad-hoc task to attach a tag to the base question. Needed because the tags are
 * written _after_ the question_created hook, so any tags we write during the
 * hook are overwritten later by the core questionlib code. We mitigate this
 * with an ad-hoc task that is delayed by a couple minutes
 *
 * @package    local_qtypestack_synccopies
 * @copyright  2023 eLeDia GmbH
 * @author     Keno Goertz <support@eledia.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_qtypestack_synccopies\task;

defined('MOODLE_INTERNAL') || die;

//  @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../../config.php');

/**
 * Add question tag.
 */
class add_question_tag extends \core\task\adhoc_task {

    /**
     * @var int
     */
    protected $questionid;

    /**
     * @var context $context
     */
    protected $context;

    /**
     * Execute.
     *
     * @return void
     * @throws \coding_exception
     */
    public function execute () {
        $questionid = $this->get_custom_data()->questionid;
        $questionbankentryid = $this->get_custom_data()->questionbankentryid;
        $contextid = $this->get_custom_data()->contextid;
        $context = \context::instance_by_id($contextid);

        $tags = \core_tag_tag::get_item_tags('core_question', 'question',
                                            $questionid);
        $taginstanceids = [];
        foreach ($tags as $tag) {
            if (preg_match('/^id[0-9]+$/', $tag->get_display_name())) {
                array_push($taginstanceids, $tag->taginstanceid);
            }
        }
        \core_tag_tag::delete_instances_by_id($taginstanceids);

        \core_tag_tag::add_item_tag('core_question', 'question',
                                    $questionid, $context,
                                    'id' . $questionbankentryid);
    }
}
