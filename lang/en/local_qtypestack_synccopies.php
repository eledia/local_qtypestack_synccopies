<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk//
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the STACK question type.
 *
 * @package    local_qtypestack_synccopies
 * @copyright  2023 eLeDia GmbH
 * @author     Keno Goertz <support@eledia.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname']        = 'STACK synccopy';
$string['pluginname_help']   = 'Creates and synchronizes copies for each deployed variant of a STACK question';
$string['pluginnamesummary'] = 'STACK questions can have multiple deployed variants. This plugin creates a seperate copy of the question for each deployed variant.';

$string['manage'] = 'Manage STACK synccopies';
$string['listenevents'] = 'Enable event listeners';
$string['listenevents_desc'] = 'Enables the event listeners so that synccopies are automatically created, updated and deleted on question creation, update or deletion. Upon first usage, this should be disabled and the synccopies should be created using cli/create_missing_variantcopies.php';
