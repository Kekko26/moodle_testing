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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Provides {@see local_amos_importfile_form} class.
 *
 * @package     local_amos
 * @copyright   2010 David Mudrak <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import file form.
 *
 * @package     local_amos
 * @copyright   2010 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_amos_importfile_form extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('select', 'version', get_string('version', 'local_amos'), $this->_customdata['versions']);
        $mform->setDefault('version', $this->_customdata['versioncurrent']);
        $mform->addRule('version', null, 'required', null, 'client');

        $mform->addElement('select', 'language', get_string('language', 'local_amos'), $this->_customdata['languages']);
        $mform->setDefault('language', $this->_customdata['languagecurrent']);
        $mform->addRule('language', null, 'required', null, 'client');

        $fpoptions = array('accepted_types' => ['.php', '.zip'], 'maxbytes' => 2 * 1024 * 1024);
        $mform->addElement('filepicker', 'importfile', get_string('file'), null,  $fpoptions);
        $mform->addRule('importfile', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('import'));
    }
}
