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
 * File containing the SCORM module local library function tests.
 *
 * @package mod_scorm
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/scorm/lib.php');
require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_13lib.php');

/**
 * Class containing the SCORM 2004 data model tests.
 *
 * @package mod_scorm
 * @category test
 * @copyright 2017 Christian Lawson-Perfect <christian.perfect@ncl.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scorm_datamodel_scorm13_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();

        // Create users.
        $this->teacher = self::getDataGenerator()->create_user();
        $this->student = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    public function test_scorm12_review() {
        global $CFG, $USER;

        $USER = $this->student;

        $record = new stdClass();
        $record->course = $this->course->id;
        $record->packagefilepath = $CFG->dirroot.'/mod/scorm/tests/packages/RuntimeBasicCalls_SCORM20043rdEdition.zip';
        $scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $this->course->id));

        $scoes = scorm_get_scoes($scorm->id);
        $sco = array_pop($scoes);

        $userdata = new stdClass();
        get_scorm_default($userdata, $scorm, $sco->id, 1, 'review');
        $this->assertObjectNotHasAttribute('cmi.raw_score',$userdata);

        scorm_insert_track($this->student->id, $scorm->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        scorm_insert_track($this->student->id, $scorm->id, $sco->id, 1, 'cmi.raw_score', '0.5');

        $userdata = new stdClass();
        get_scorm_default($userdata, $scorm, $sco->id, 1, 'review');

        $this->assertObjectHasAttribute('cmi.raw_score', $userdata);
        $this->assertEquals($userdata->{'cmi.raw_score'}, '0.5');
    }
}
