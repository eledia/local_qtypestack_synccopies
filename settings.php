<?php
// This file is part of Moodle - https://moodle.org/
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
 * Adds admin settings for the plugin.
 *
 * @package    local_qtypestack_synccopies
 * @copyright  2023 eLeDia GmbH
 * @author     Keno Goertz <support@eledia.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $pluginname = new lang_string('pluginname', 'local_qtypestack_synccopies');
    $admincategory = new admin_category('local_qtypestack_synccopies_settings',
                                        $pluginname);
    $ADMIN->add('localplugins', $admincategory);
    $managestr = new lang_string('manage', 'local_qtypestack_synccopies');
    $settingspage = new admin_settingpage('managelocalqtypestacksynccopies',
                                          $managestr);

    if ($ADMIN->fulltree) {
        $settingspage->add(new admin_setting_configcheckbox(
            'local_qtypestack_synccopies/listenevents',
            new lang_string('listenevents', 'local_qtypestack_synccopies'),
            new lang_string('listenevents_desc', 'local_qtypestack_synccopies'),
            1
        ));
    }

    $ADMIN->add('localplugins', $settingspage);
}
