<?php

use Augusito\Repository\BaseRepository;
use PHPUnit\Framework\TestCase;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Sql\Update;

class BaseRepositoryTest extends TestCase
{
    protected $mockAdapter;

    protected function setUp()
    {
        // mock the adapter, driver, and parts
        $mockResult = $this->getMockBuilder('Zend\Db\Adapter\Driver\ResultInterface')->getMock();
        $mockStatement = $this->getMockBuilder('Zend\Db\Adapter\Driver\StatementInterface')->getMock();
        $mockStatement->expects($this->any())->method('execute')->will($this->returnValue($mockResult));
        $mockConnection = $this->getMockBuilder('Zend\Db\Adapter\Driver\ConnectionInterface')->getMock();
        $mockDriver = $this->getMockBuilder('Zend\Db\Adapter\Driver\DriverInterface')->getMock();
        $mockDriver->expects($this->any())->method('createStatement')->will($this->returnValue($mockStatement));
        $mockDriver->expects($this->any())->method('getConnection')->will($this->returnValue($mockConnection));

        // setup mock adapter
        $this->mockAdapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')
            ->setMethods()
            ->setConstructorArgs([$mockDriver])
            ->getMock();
    }

    /**
     * Beside other tests checks for plain string table identifier
     */
    public function testConstructor()
    {
        // constructor with only required args
        $table = new BaseRepository(
            $this->mockAdapter
        );

        self::assertSame($this->mockAdapter, $table->getAdapter());
        self::assertInstanceOf('Zend\Db\Sql\Sql', $table->getSql());

        // injecting all args
        $table = new BaseRepository(
            $this->mockAdapter,
            $sql = new Sql($this->mockAdapter, 'foo')
        );

        self::assertSame($this->mockAdapter, $table->getAdapter());
        self::assertSame($sql, $table->getSql());
    }

    public function aliasedTables()
    {
        $identifier = new TableIdentifier('Users');
        return [
            'simple-alias' => [['U' => 'Users'], 'Users'],
            'identifier-alias' => [['U' => $identifier], $identifier],
        ];
    }

    /**
     * @dataProvider aliasedTables
     */
    public function testInsertShouldResetTableToUnaliasedTable($tableValue, $expected)
    {
        $insert = new Insert();
        $insert->into($tableValue);

        $result = $this->getMockBuilder('Zend\Db\Adapter\Driver\ResultInterface')
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->will($this->returnValue(1));

        $statement = $this->getMockBuilder('Zend\Db\Adapter\Driver\StatementInterface')
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result));

        $statementExpectation = function ($insert) use ($expected, $statement) {
            $state = $insert->getRawState();
            self::assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder('Zend\Db\Sql\Sql')
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->once())
            ->method('insert')
            ->will($this->returnValue($insert));
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($insert))
            ->will($this->returnCallback($statementExpectation));

        $table = new BaseRepository(
            $this->mockAdapter,
            $sql
        );

        $table->insert('foo', [
            'foo' => 'FOO',
        ]);

        $state = $insert->getRawState();
        self::assertISArray($state['table']);
        self::assertEquals(
            $tableValue,
            $state['table']
        );
    }

    /**
     * @dataProvider aliasedTables
     */
    public function testUpdateShouldResetTableToUnaliasedTable($tableValue, $expected)
    {
        $update = new Update();
        $update->table($tableValue);

        $result = $this->getMockBuilder('Zend\Db\Adapter\Driver\ResultInterface')
            ->getMock();
        $result->expects($this->once())
            ->method('getAffectedRows')
            ->will($this->returnValue(1));

        $statement = $this->getMockBuilder('Zend\Db\Adapter\Driver\StatementInterface')
            ->getMock();
        $statement->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result));

        $statementExpectation = function ($update) use ($expected, $statement) {
            $state = $update->getRawState();
            self::assertSame($expected, $state['table']);
            return $statement;
        };

        $sql = $this->getMockBuilder('Zend\Db\Sql\Sql')
            ->disableOriginalConstructor()
            ->getMock();
        $sql->expects($this->once())
            ->method('update')
            ->will($this->returnValue($update));
        $sql->expects($this->once())
            ->method('prepareStatementForSqlObject')
            ->with($this->equalTo($update))
            ->will($this->returnCallback($statementExpectation));

        $table = new BaseRepository(
            $this->mockAdapter,
            $sql
        );

        $table->update('foo', [
            'foo' => 'FOO',
        ], [
            'bar' => 'BAR',
        ]);

        $state = $update->getRawState();
        self::assertIsArray($state['table']);
        self::assertEquals(
            $tableValue,
            $state['table']
        );
    }

}
