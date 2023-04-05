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

namespace local_amos\local;

/**
 * Unit tests for the {@see local_amos\local\util} class.
 *
 * @package     local_amos
 * @category    test
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util_test extends \advanced_testcase {

    /**
     * Test functionality of {@see local_amos\local\util::add_breaks()}.
     */
    public function test_add_breaks() {

        $input = 'a,b,c';
        $output = util::add_breaks($input);

        $this->assertEquals('a,&#x200b;b,&#x200b;c', \core_text::utf8_to_entities($output));
    }

    /**
     * Test functionality of {@see local_amos\local\util::standard_components_tree()}.
     */
    public function test_standard_components_tree() {
        $this->resetAfterTest();

        set_config('branchesall', '38,39,310', 'local_amos');
        set_config('standardcomponents', " mod_foobar\t\n  foobar_one  39  \n  foobar_two 37 -38\n\n\n_test\nfoobar_three 39 -39",
            'local_amos'
        );

        $tree = util::standard_components_tree();

        $this->assertDebuggingCalled('Unexpected standardcomponents line starting with: _test');
        $this->assertEquals(3, count($tree));
        $this->assertEquals(3, count($tree[38]));
        $this->assertEquals(4, count($tree[39]));
        $this->assertEquals(3, count($tree[310]));
        $this->assertEquals('core', $tree[38]['moodle']);
        $this->assertEquals('core', $tree[39]['moodle']);
        $this->assertEquals('core', $tree[310]['moodle']);
        $this->assertEquals('mod_foobar', $tree[38]['foobar']);
        $this->assertEquals('mod_foobar', $tree[39]['foobar']);
        $this->assertEquals('mod_foobar', $tree[310]['foobar']);
        $this->assertArrayNotHasKey('foobar_one', $tree[38]);
        $this->assertEquals('foobar_one', $tree[39]['foobar_one']);
        $this->assertEquals('foobar_one', $tree[310]['foobar_one']);
        $this->assertEquals('foobar_two', $tree[38]['foobar_two']);
        $this->assertArrayNotHasKey('foobar_two', $tree[39]);
        $this->assertArrayNotHasKey('foobar_two', $tree[310]);
        $this->assertArrayNotHasKey('foobar_three', $tree[38]);
        $this->assertEquals('foobar_three', $tree[39]['foobar_three']);
        $this->assertArrayNotHasKey('foobar_three', $tree[310]);

        set_config('standardcomponents', "mod_foobar\ninvalid_range 37 39", 'local_amos');

        $tree = util::standard_components_tree();

        $this->assertDebuggingCalled('Unexpected standardcomponents line versions range: invalid_range 37 39');
    }

    /**
     * Test functionality of {@see local_amos\local\util::standard_components_list()}.
     */
    public function test_standard_components_list() {
        $this->resetAfterTest();

        set_config('branchesall', '38,39,310', 'local_amos');
        set_config('standardcomponents', "mod_foobar\nfoobar_one 39\nfoobar_two 37 -38\ncore_barbar", 'local_amos');

        $list = util::standard_components_list();

        $this->assertEquals(5, count($list));
        $this->assertEquals('core', $list['moodle']);
        $this->assertEquals('mod_foobar', $list['foobar']);
        $this->assertEquals('foobar_one', $list['foobar_one']);
        $this->assertEquals('foobar_two', $list['foobar_two']);
        $this->assertEquals('core_barbar', $list['barbar']);
    }

    /**
     * Test functionality of {@see local_amos\local\util::standard_components_in_version()}.
     */
    public function test_standard_components_in_version() {
        $this->resetAfterTest();

        set_config('branchesall', '38,39,310', 'local_amos');
        set_config('standardcomponents', '', 'local_amos');

        foreach ([38, '39', 310] as $vercode) {
            $list = util::standard_components_in_version($vercode);
            $this->assertEquals(1, count($list));
            $this->assertEquals('core', $list['moodle']);
        }

        set_config('standardcomponents', implode(PHP_EOL, [
            'mod_foobar',
            'foobar_one -39',
            'foobar_two 37 -38',
            'core_barbar 310 -310',
            'barbar_one 39',
        ]), 'local_amos');

        $list = util::standard_components_in_version(38);

        $this->assertEquals(4, count($list));
        $this->assertEquals('core', $list['moodle']);
        $this->assertEquals('mod_foobar', $list['foobar']);
        $this->assertEquals('foobar_one', $list['foobar_one']);
        $this->assertEquals('foobar_two', $list['foobar_two']);

        $list = util::standard_components_in_version(310);

        $this->assertEquals(4, count($list));
        $this->assertEquals('core', $list['moodle']);
        $this->assertEquals('mod_foobar', $list['foobar']);
        $this->assertEquals('core_barbar', $list['barbar']);
        $this->assertEquals('barbar_one', $list['barbar_one']);

        $list = util::standard_components_in_version(16);
        $this->assertSame([], $list);
    }

    /**
     * Test functionality of {@see local_amos\local\util::standard_components_in_latest_version()}.
     */
    public function test_standard_components_in_latest_version() {
        $this->resetAfterTest();

        set_config('branchesall', '38,39,310', 'local_amos');
        set_config('standardcomponents', "mod_foobar\nfoobar_one -39\nfoobar_two 37 -38\ncore_barbar 310 -310", 'local_amos');

        $list = util::standard_components_in_latest_version();

        $this->assertEquals(3, count($list));
        $this->assertEquals('core', $list['moodle']);
        $this->assertEquals('mod_foobar', $list['foobar']);
        $this->assertEquals('core_barbar', $list['barbar']);

        set_config('branchesall', '38,39,310,311', 'local_amos');

        $list = util::standard_components_in_latest_version();

        $this->assertEquals(2, count($list));
        $this->assertEquals('core', $list['moodle']);
        $this->assertEquals('mod_foobar', $list['foobar']);
    }

    /**
     * Test functionality of {@see local_amos\local\util::standard_components_range_versions()}.
     */
    public function test_standard_components_range_versions() {
        $this->resetAfterTest();

        set_config('branchesall', '38,39,310', 'local_amos');
        set_config('standardcomponents', "mod_foobar\nfoobar_one 39\nfoobar_two 37 -38\nfoobar_three -310\ncore_barbar 310 -310",
            'local_amos');

        $list = util::standard_components_range_versions();

        $this->assertEquals(5, count($list));
        $this->assertEquals(PHP_INT_MIN, $list['mod_foobar'][0]);
        $this->assertEquals(PHP_INT_MAX, $list['mod_foobar'][1]);
        $this->assertEquals(39, $list['foobar_one'][0]);
        $this->assertEquals(PHP_INT_MAX, $list['foobar_one'][1]);
        $this->assertEquals(37, $list['foobar_two'][0]);
        $this->assertEquals(38, $list['foobar_two'][1]);
        $this->assertEquals(PHP_INT_MIN, $list['foobar_three'][0]);
        $this->assertEquals(310, $list['foobar_three'][1]);
        $this->assertEquals(310, $list['core_barbar'][0]);
        $this->assertEquals(310, $list['core_barbar'][1]);
    }
}
