<?php

namespace Augusito\Repository;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;

class BaseRepository extends AbstractBaseRepository
{
    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @param Sql|null $sql
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(AdapterInterface $adapter, Sql $sql = null)
    {
        // adapter
        $this->adapter = $adapter;

        // Sql object (factory for select, insert, update, delete)
        $this->sql = ($sql) ?: new Sql($this->adapter);

        $this->initialize();
    }
}
