<?php

use Augusito\Repository\AbstractBaseRepository;
use PHPUnit\Framework\TestCase;
use Zend\Db\Sql;

class AbstractBaseRepositoryTest extends TestCase
{
    /**
     * @var PHPUnit\Framework\MockObject\Generator
     */
    protected $mockAdapter;

    /**
     * @var PHPUnit\Framework\MockObject\Generator
     */
    protected $mockSql;

    /**
     * @var PHPUnit\Framework\MockObject\Generator
     */
    protected $mockConnection;

    /**
     * @var AbstractBaseRepository
     */
    protected $baseRepository;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->getMockBuilder('Zend\Db\Adapter\Driver\ResultInterface')->getMock();
        $mockResult->expects($this->any())->method('getAffectedRows')->will($this->returnValue(5));

        $mockStatement = $this->getMockBuilder('Zend\Db\Adapter\Driver\StatementInterface')->getMock();
        $mockStatement->expects($this->any())->method('execute')->will($this->returnValue($mockResult));

        $this->mockConnection = $this->getMockBuilder('Zend\Db\Adapter\Driver\ConnectionInterface')->getMock();
        $this->mockConnection->expects($this->any())->method('getLastGeneratedValue')->will($this->returnValue(10));

        $mockDriver = $this->getMockBuilder('Zend\Db\Adapter\Driver\DriverInterface')->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->will($this->returnValue($mockStatement));
        $mockDriver->expects($this->any())->method('getConnection')->will($this->returnValue($this->mockConnection));

        $this->mockAdapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')
            ->setMethods()
            ->setConstructorArgs([$mockDriver])
            ->getMock();

        $this->mockSql = $this->getMockBuilder('Zend\Db\Sql\Sql')
            ->setMethods(['select', 'insert', 'update', 'delete'])
            ->setConstructorArgs([$this->mockAdapter, 'foo'])
            ->getMock();

        $this->mockSql->expects($this->any())->method('select')->will($this->returnValue(
            $this->getMockBuilder('Zend\Db\Sql\Select')
                ->setMethods(['where', 'getRawState'])
                ->setConstructorArgs(['foo'])
                ->getMock()
        ));

        $this->mockSql->expects($this->any())->method('insert')->will($this->returnValue(
            $this->getMockBuilder('Zend\Db\Sql\Insert')
                ->setMethods(['prepareStatement', 'values'])
                ->setConstructorArgs(['foo'])
                ->getMock()
        ));

        $this->mockSql->expects($this->any())->method('update')->will($this->returnValue(
            $this->getMockBuilder('Zend\Db\Sql\Update')
                ->setMethods(['where', 'join'])
                ->setConstructorArgs(['foo'])
                ->getMock()
        ));

        $this->mockSql->expects($this->any())->method('delete')->will($this->returnValue(
            $this->getMockBuilder('Zend\Db\Sql\Delete')
                ->setMethods(['where'])
                ->setConstructorArgs(['foo'])
                ->getMock()
        ));

        $this->baseRepository = $this->getMockForAbstractClass(
            'Augusito\Repository\AbstractBaseRepository'
        );

        $tgReflection = new ReflectionClass('Augusito\Repository\AbstractBaseRepository');

        foreach ($tgReflection->getProperties() as $tgPropReflection) {
            $tgPropReflection->setAccessible(true);
            switch ($tgPropReflection->getName()) {
                case 'adapter':
                    $tgPropReflection->setValue($this->baseRepository, $this->mockAdapter);
                    break;
                case 'sql':
                    $tgPropReflection->setValue($this->baseRepository, $this->mockSql);
                    break;
            }
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::getAdapter
     */
    public function testGetAdapter()
    {
        self::assertSame($this->mockAdapter, $this->baseRepository->getAdapter());
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::getSql
     */
    public function testGetSql()
    {
        self::assertInstanceOf('Zend\Db\Sql\Sql', $this->baseRepository->getSql());
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::getConnection
     */
    public function testGetConnetion()
    {
        self::assertSame($this->mockConnection, $this->baseRepository->getConnection());
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::select
     * @covers \Augusito\Repository\AbstractBaseRepository::selectWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeSelect
     */
    public function testSelectWithNoWhere()
    {
        $resultSet = $this->baseRepository->select('foo');

        // check return types
        self::assertInstanceOf('Zend\Db\ResultSet\ResultSet', $resultSet);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::select
     * @covers \Augusito\Repository\AbstractBaseRepository::selectWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeSelect
     */
    public function testSelectWithWhereString()
    {
        $mockSelect = $this->mockSql->select();

        $mockSelect->expects($this->any())
            ->method('getRawState')
            ->will($this->returnValue([
                'table' => 'foo',
                'columns' => [],
            ]));

        // assert select::from() is called
        $mockSelect->expects($this->once())
            ->method('where')
            ->with($this->equalTo('foo'));

        $this->baseRepository->select('foo', 'foo');
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::select
     * @covers \Augusito\Repository\AbstractBaseRepository::selectWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeSelect
     */
    public function testSelectWithArrayTable()
    {
        // Case 1

        $select1 = $this->getMockBuilder('Zend\Db\Sql\Select')->setMethods(['getRawState'])->getMock();
        $select1->expects($this->once())
            ->method('getRawState')
            ->will($this->returnValue([
                'table' => 'foo',               // Standard table name format, valid according to Select::from()
                'columns' => null,
            ]));
        $return = $this->baseRepository->selectWith($select1);
        self::assertNotNull($return);

        // Case 2

        $select1 = $this->getMockBuilder('Zend\Db\Sql\Select')->setMethods(['getRawState'])->getMock();
        $select1->expects($this->once())
            ->method('getRawState')
            ->will($this->returnValue([
                'table' => ['f' => 'foo'], // Alias table name format, valid according to Select::from()
                'columns' => null,
            ]));
        $return = $this->baseRepository->selectWith($select1);
        self::assertNotNull($return);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::insert
     * @covers \Augusito\Repository\AbstractBaseRepository::insertWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeInsert
     */
    public function testInsert()
    {
        $mockInsert = $this->mockSql->insert();

        $mockInsert->expects($this->once())
            ->method('prepareStatement')
            ->with($this->mockAdapter);


        $mockInsert->expects($this->once())
            ->method('values')
            ->with($this->equalTo(['foo' => 'bar']));

        $affectedRows = $this->baseRepository->insert('foo', ['foo' => 'bar']);
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::update
     * @covers \Augusito\Repository\AbstractBaseRepository::updateWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeUpdate
     */
    public function testUpdate()
    {
        $mockUpdate = $this->mockSql->update();

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $affectedRows = $this->baseRepository->update('foo', ['foo' => 'bar'], 'id = 2');
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::update
     * @covers \Augusito\Repository\AbstractBaseRepository::updateWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeUpdate
     */
    public function testUpdateWithJoin()
    {
        $mockUpdate = $this->mockSql->update();

        $joins = [
            [
                'name' => 'baz',
                'on' => 'foo.fooId = baz.fooId',
                'type' => Sql\Join::JOIN_LEFT,
            ],
        ];

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], $joins[0]['type']);

        $affectedRows = $this->baseRepository->update('foo', ['foo.field' => 'bar'], 'id = 2', $joins);
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::update
     * @covers \Augusito\Repository\AbstractBaseRepository::updateWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeUpdate
     */
    public function testUpdateWithJoinDefaultType()
    {
        $mockUpdate = $this->mockSql->update();

        $joins = [
            [
                'name' => 'baz',
                'on' => 'foo.fooId = baz.fooId',
            ],
        ];

        // assert select::from() is called
        $mockUpdate->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id = 2'));

        $mockUpdate->expects($this->once())
            ->method('join')
            ->with($joins[0]['name'], $joins[0]['on'], Sql\Join::JOIN_INNER);

        $affectedRows = $this->baseRepository->update('foo', ['foo.field' => 'bar'], 'id = 2', $joins);
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::update
     * @covers \Augusito\Repository\AbstractBaseRepository::updateWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeUpdate
     */
    public function testUpdateWithNoCriteria()
    {
        $affectedRows = $this->baseRepository->update('foo', ['foo' => 'bar']);
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::delete
     * @covers \Augusito\Repository\AbstractBaseRepository::deleteWith
     * @covers \Augusito\Repository\AbstractBaseRepository::executeDelete
     */
    public function testDelete()
    {
        $mockDelete = $this->mockSql->delete();

        // assert select::from() is called
        $mockDelete->expects($this->once())
            ->method('where')
            ->with($this->equalTo('foo'));

        $affectedRows = $this->baseRepository->delete('foo', 'foo');
        self::assertEquals(5, $affectedRows);
    }

    /**
     * @covers \Augusito\Repository\AbstractBaseRepository::getLastInsertValue
     */
    public function testGetLastInsertValue()
    {
        $this->baseRepository->insert('foo', ['foo' => 'bar']);
        self::assertEquals(10, $this->baseRepository->getLastInsertValue());
    }
}
