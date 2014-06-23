<?php
/**
 * @package	    FrameworkOnFramework.UnitTest
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2010 - 2014 Akeeba Ltd. All rights reserved.
 * @license	    GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once JPATH_TESTS.'/unit/core/table/nested.php';
require_once 'nestedDataprovider.php';

class F0FTableNestedTest extends FtestCaseDatabase
{
    protected function setUp()
    {
        $loadDataset = true;
        $annotations = $this->getAnnotations();

        // Do I need a dataset for this set or not?
        if (isset($annotations['method']) && isset($annotations['method']['preventDataLoading'])) {
            $loadDataset = false;
        }

        parent::setUp($loadDataset);

        F0FPlatform::forceInstance(null);
        F0FTable::forceInstance(null);
    }

    /**
     * @group               nested__contruct
     * @group               F0FTableNested
     * @covers              F0FTableNested::__construct
     * @dataProvider        NestedDataprovider::getTest__construct
     * @preventDataLoading
     */
    public function test__construct($test, $check)
    {
        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $db = JFactory::getDbo();

        new F0FTableNested($test['table'], $test['id'], $db);
    }

    /**
     * @group               nestedTestCheck
     * @group               F0FTableNested
     * @covers              F0FTableNested::check
     * @dataProvider        NestedDataprovider::getTestCheck
     * @preventDataLoading
     */
    public function testCheck($test, $check)
    {
        $db = JFactory::getDbo();

        $table = $this->getMock('F0FTableNested', array('resetTreeCache'), array($test['table'], $test['id'], &$db));
        $table->expects($this->any())->method('resetTreeCache')->willReturn($this->returnValue(null));

        foreach($test['fields'] as $field => $value)
        {
            $table->$field = $value;
        }

        $return = $table->check();

        $this->assertEquals($check['return'], $return, 'F0FTableNested::check returned the wrong value');

        foreach($check['fields'] as $field => $expected)
        {
            if(is_null($expected))
            {
                $this->assertObjectNotHasAttribute($field, $table, 'F0FTableNested::check set the field '.$field.' even if it should not');
            }
            else
            {
                $this->assertEquals($expected, $table->$field, 'F0FTableNested::check failed to set the field '.$field);
            }
        }
    }

    /**
     * @group               nestedTestDelete
     * @group               F0FTableNested
     * @covers              F0FTableNested::delete
     * @dataProvider        NestedDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        $db = JFactory::getDbo();

        $table = F0FTable::getAnInstance('Nestedset', 'FoftestTable');

        if($test['loadid'])
        {
            $table->load($test['loadid']);
        }

        $return = $table->delete($test['delete'], $test['recursive']);

        $this->assertEquals($check['return'], $return, 'F0FTableNested::delete returned the wrong value');

        $query = $db->getQuery(true)->select($table->getKeyName())->from($table->getTableName());
        $items = $db->setQuery($query)->loadColumn();

        $this->assertEmpty(array_intersect($check['deleted'], $items), 'F0FTableNested::delete failed to delete all the items');
    }

    /**
     * @group               nestedTestReorder
     * @group               F0FTableNested
     * @covers              F0FTableNested::reorder
     * @preventDataLoading
     */
    public function testReorder()
    {
        $this->setExpectedException('RuntimeException');

        $db = JFactory::getDbo();

        $table = new F0FTableNested('#__foftest_nestedsets', 'id', $db);
        $table->reorder();
    }

    /**
     * @group               nestedTestMove
     * @group               F0FTableNested
     * @covers              F0FTableNested::move
     * @preventDataLoading
     */
    public function testMove()
    {
        $this->setExpectedException('RuntimeException');

        $db = JFactory::getDbo();

        $table = new F0FTableNested('#__foftest_nestedsets', 'id', $db);
        $table->move(1);
    }

    /**
     * @group               nestedTestCreate
     * @group               F0FTableNested
     * @covers              F0FTableNested::create
     * @dataProvider        NestedDataprovider::getTestCreate
     */
    public function testCreate($test)
    {
        $db = JFactory::getDbo();

        $matcher = $this->never();

        if(!$test['root'])
        {
            $matcher = $this->once();
        }

        $table = $this->getMock('F0FTableNested', array('insertAsChildOf', 'getParent'), array('#__foftest_nestedsets', 'foftest_nestedset_id', &$db));
        $table->expects($this->once())->method('insertAsChildOf')->will($this->returnValue(null));
        // This is just a little trick, so insertAsChildOf won't complain about the argument passed
        $table->expects($matcher)->method('getParent')->willReturnSelf();

        $table->load($test['loadid']);
        $table->create($test['data']);
    }

    /**
     * @group               nestedTestCreate
     * @group               F0FTableNested
     * @covers              F0FTableNested::create
     * @preventDataLoading
     */
    public function testCreateNotLoaded()
    {
        $this->setExpectedException('RuntimeException');

        $table = F0FTable::getAnInstance('Nestedset', 'FoftestTable');
        $table->create(array());
    }

    /**
     * @group               nestedTestInsertAsRoot
     * @group               F0FTableNested
     * @covers              F0FTableNested::insertAsRoot
     */
    public function testInsertAsRoot()
    {
        $table = F0FTable::getAnInstance('Nestedset', 'FoftestTable');

        $table->title = 'New root';
        $table->insertAsRoot();

        $this->assertTrue($table->isRoot(), 'F0FTableNested::insertAsRoot failed to create a new root');
    }

    /**
     * @group               nestedTestInsertAsRoot
     * @group               F0FTableNested
     * @covers              F0FTableNested::insertAsRoot
     */
    public function testInsertAsRootException()
    {
        $this->setExpectedException('RuntimeException');

        $table = F0FTable::getAnInstance('Nestedset', 'FoftestTable');

        $table->load(1);
        $table->insertAsRoot();
    }

    /**
     * @group               nestedTestMakeRoot
     * @group               F0FTableNested
     * @covers              F0FTableNested::makeRoot
     * @dataProvider        NestedDataprovider::getTestMakeRoot
     */
    public function testMakeRoot($test)
    {
        $db = JFactory::getDbo();

        if($test['setup'])
        {
            $db->setQuery('TRUNCATE #__foftest_nestedsets')->execute();

            foreach($test['setup'] as $row)
            {
                $dummy = (object) $row;
                $db->insertObject('#__foftest_nestedsets', $dummy);
            }
        }

        $table = F0FTable::getAnInstance('Nestedset', 'FoftestTable');

        $table->load($test['loadid']);

        $result = $table->makeRoot();

        // Let's wipe the cache, so I can run all the logic again
        TestReflection::invoke($table, 'resetTreeCache');

        $this->assertInstanceOf('F0FTableNested', $result, 'F0FTableNested::makeRoot should return an instance of itself for chaining');
        $this->assertTrue($table->isRoot(), 'F0FTableNested::makeRoot the new node is not a root one');
    }
}
