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
 * Provides the {@see local_amos_external_update_strings_file_testcase} class.
 *
 * @package     local_amos
 * @category    test
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Unit tests for external functions update_strings_file.
 *
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_amos_external_update_strings_file_testcase extends externallib_advanced_testcase {

    /**
     * Test the permission check for the update_strings_file external function.
     */
    public function test_update_strings_file_without_import_capability() {
        $this->resetAfterTest(true);

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $roleid = $this->assignUserCapability('local/amos:importstrings', SYSCONTEXTID);
        $this->unassignUserCapability('local/amos:importstrings', SYSCONTEXTID, $roleid);

        $this->expectException(required_capability_exception::class);

        \local_amos\external\update_strings_file::execute('Test User <test@example.com>', 'Just a test update', []);
    }

    /**
     * Test the behaviour of the update_strings_file external function.
     */
    public function test_update_strings_file() {
        $this->resetAfterTest(true);

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->assignUserCapability('local/amos:importstrings', SYSCONTEXTID);

        $raw = \local_amos\external\update_strings_file::execute(
            'Johny Developer <developer@example.com>',
            'First version of the tool_foobar',
            [
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.6',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php $string["pluginname"] = "Foo bar 3.6";',
                ],
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.5',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php $string["pluginname"] = "Foo bar 3.5";',
                ],
            ]
        );

        $clean = external_api::clean_returnvalue(\local_amos\external\update_strings_file::execute_returns(), $raw);

        $this->assertTrue(is_array($clean));
        $this->assertEquals(2, count($clean));

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.6',
            'language' => 'en',
            'status' => 'ok',
            'found' => 1,
            'changes' => 1,
        ], $clean);

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.5',
            'language' => 'en',
            'status' => 'ok',
            'found' => 1,
            'changes' => 1,
        ], $clean);

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_36_STABLE'));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar 3.6');
        $component->clear();

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_35_STABLE'));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar 3.5');
        $component->clear();

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_37_STABLE'));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar 3.6');
        $component->clear();

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_34_STABLE'));
        $this->assertFalse($component->has_string());
        $component->clear();
    }

    /**
     * Test that changes are processed from lower to highest moodle branch to avoid unnecessary commits.
     */
    public function test_moodle_version_procesing_order() {
        $this->resetAfterTest(true);

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->assignUserCapability('local/amos:importstrings', SYSCONTEXTID);

        $raw = \local_amos\external\update_strings_file::execute(
            'Johny Developer <developer@example.com>',
            'Foo bar strings',
            [
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.11',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php
                                                $string["pluginname"] = "Foo bar";
                                                $string["since39"] = "3.9 and higher";
                    ',
                ],
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.10',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php
                                                $string["pluginname"] = "Foo bar";
                                                $string["since39"] = "3.9 and higher";
                    ',
                ],
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.9',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php
                                                $string["pluginname"] = "Foo bar";
                                                $string["since39"] = "3.9 and higher";
                                                $string["in39only"] = "3.9 only";
                    ',
                ],
                [
                    'componentname' => 'tool_foobar',
                    'moodlebranch' => '3.8',
                    'language' => 'en',
                    'stringfilename' => 'tool_foobar.php',
                    'stringfilecontent' => '<?php
                                                $string["pluginname"] = "Foo bar";
                    ',
                ],
            ]
        );

        $clean = external_api::clean_returnvalue(\local_amos\external\update_strings_file::execute_returns(), $raw);

        $this->assertTrue(is_array($clean));
        $this->assertEquals(4, count($clean));

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.8',
            'language' => 'en',
            'status' => 'ok',
            'found' => 1,
            'changes' => 1,
        ], $clean);

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.9',
            'language' => 'en',
            'status' => 'ok',
            'found' => 3,
            'changes' => 2,
        ], $clean);

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.10',
            'language' => 'en',
            'status' => 'ok',
            'found' => 2,
            'changes' => 1,
        ], $clean);

        $this->assertContains([
            'componentname' => 'tool_foobar',
            'moodlebranch' => '3.11',
            'language' => 'en',
            'status' => 'ok',
            'found' => 2,
            'changes' => 0,
        ], $clean);

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_39_STABLE'));
        $this->assertEquals(3, count($component));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar');
        $this->assertEquals($component->get_string('since39')->text, '3.9 and higher');
        $this->assertEquals($component->get_string('in39only')->text, '3.9 only');
        $component->clear();

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_310_STABLE'));
        $this->assertEquals(2, count($component));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar');
        $this->assertEquals($component->get_string('since39')->text, '3.9 and higher');
        $component->clear();

        $component = mlang_component::from_snapshot('tool_foobar', 'en', mlang_version::by_branch('MOODLE_311_STABLE'));
        $this->assertEquals(2, count($component));
        $this->assertEquals($component->get_string('pluginname')->text, 'Foo bar');
        $this->assertEquals($component->get_string('since39')->text, '3.9 and higher');
        $component->clear();
    }
}
