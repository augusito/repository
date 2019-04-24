<?php

namespace Augusito\Repository;

use Closure;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Join;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Where;

abstract class AbstractBaseRepository implements BaseRepositoryInterface
{
    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var Sql
     */
    protected $sql = null;

    /**
     *
     * @var int
     */
    protected $lastInsertValue = null;

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * Initialize
     *
     * @return self
     * @throws Exception\RuntimeException
     */
    public function initialize()
    {
        if ($this->isInitialized) {
            return $this;
        }

        if (!$this->adapter instanceof AdapterInterface) {
            throw new Exception\RuntimeException('This repository does not have an Adapter setup');
        }

        if (!$this->sql instanceof Sql) {
            $this->sql = new Sql($this->adapter);
        }

        $this->isInitialized = true;

        return $this;
    }

    /**
     * Get adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get Sql
     *
     * @return Sql
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get connection
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->adapter->getDriver()->getConnection();
    }

    /**
     * Select
     *
     * @param string $table
     * @param Where|Closure|string|array|null $where
     * @param ResultSetInterface|null $resultSetPrototype
     * @return ResultSetInterface
     */
    public function select($table, $where = null, ResultSetInterface $resultSetPrototype = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $select = $this->sql->select($table);

        if ($where instanceof Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        return $this->selectWith($select, $resultSetPrototype);
    }

    /**
     * @param Select $select
     * @param ResultSetInterface|null $resultSetPrototype
     * @return ResultSetInterface
     */
    public function selectWith(Select $select, ResultSetInterface $resultSetPrototype = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return $this->executeSelect($select, $resultSetPrototype);
    }

    /**
     * @param Select $select
     * @param ResultSetInterface|null $resultSetPrototype
     * @return ResultSetInterface     *
     * @throws Exception\RuntimeException
     */
    protected function executeSelect(Select $select, ResultSetInterface $resultSetPrototype = null)
    {
        $selectState = $select->getRawState();

        if ($selectState['columns'] == [Select::SQL_STAR]
            && $this->columns !== []) {
            $select->columns($this->columns);
        }

        // prepare and execute
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        // build result set
        $resultSet = ($resultSetPrototype) ?: new ResultSet();
        $resultSet->initialize($result);

        return $resultSet;
    }

    /**
     * Insert
     *
     * @param string $table
     * @param array $set
     * @return int
     */
    public function insert($table, $set)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $insert = $this->sql->insert($table);
        $insert->values($set);

        return $this->executeInsert($insert);
    }

    /**
     * @param Insert $insert
     * @return int
     */
    public function insertWith(Insert $insert)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return $this->executeInsert($insert);
    }

    /**
     * @param Insert $insert
     * @return int
     * @throws Exception\RuntimeException
     *
     */
    protected function executeInsert(Insert $insert)
    {
        $insertState = $insert->getRawState();

        // Most RDBMS solutions do not allow using table aliases in INSERTs
        $unaliasedTable = false;
        if (is_array($insertState['table'])) {
            $tableData = array_values($insertState['table']);
            $unaliasedTable = array_shift($tableData);
            $insert->into($unaliasedTable);
        }

        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $result = $statement->execute();
        $this->lastInsertValue = $this->getConnection()->getLastGeneratedValue();

        // Reset original table information in Insert instance, if necessary
        if ($unaliasedTable) {
            $insert->into($insertState['table']);
        }

        return $result->getAffectedRows();
    }

    /**
     * Update
     *
     * @param string $table
     * @param array $set
     * @param string|array|Closure|null $where
     * @param null|array $joins
     * @return int
     */
    public function update($table, $set, $where = null, array $joins = null)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $sql = $this->sql;
        $update = $sql->update($table);
        $update->set($set);

        if ($where !== null) {
            $update->where($where);
        }

        if ($joins) {
            foreach ($joins as $join) {
                $type = isset($join['type']) ? $join['type'] : Join::JOIN_INNER;
                $update->join($join['name'], $join['on'], $type);
            }
        }

        return $this->executeUpdate($update);
    }

    /**
     * @param Update $update
     * @return int
     */
    public function updateWith(Update $update)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return $this->executeUpdate($update);
    }

    /**
     * @param Update $update
     * @return int
     * @throws Exception\RuntimeException
     *
     */
    protected function executeUpdate(Update $update)
    {
        $updateState = $update->getRawState();

        $unaliasedTable = false;
        if (is_array($updateState['table'])) {
            $tableData = array_values($updateState['table']);
            $unaliasedTable = array_shift($tableData);
            $update->table($unaliasedTable);
        }

        $statement = $this->sql->prepareStatementForSqlObject($update);
        $result = $statement->execute();

        // Reset original table information in Update instance, if necessary
        if ($unaliasedTable) {
            $update->table($updateState['table']);
        }

        return $result->getAffectedRows();
    }

    /**
     * Delete
     *
     * @param string $table
     * @param Where|Closure|string|array $where
     * @return int
     */
    public function delete($table, $where)
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $delete = $this->sql->delete($table);

        if ($where instanceof Closure) {
            $where($delete);
        } else {
            $delete->where($where);
        }

        return $this->executeDelete($delete);
    }

    /**
     * @param Delete $delete
     * @return int
     */
    public function deleteWith(Delete $delete)
    {
        $this->initialize();
        return $this->executeDelete($delete);
    }

    /**
     * @param Delete $delete
     * @return int
     * @throws Exception\RuntimeException
     */
    protected function executeDelete(Delete $delete)
    {
        $statement = $this->sql->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->getAffectedRows();
    }

    /**
     * Get last insert value
     *
     * @return int
     */
    public function getLastInsertValue()
    {
        return $this->lastInsertValue;
    }
}
