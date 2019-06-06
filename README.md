# Repository
A  base repository layer built on top of zend-db component

The package contains three classes:

- `BaseRepositoryInterface`, that defines the signatures that must be implemented 
by the `AbstractBaseRepository`.

- `AbstarctBaseRepository`, which is an extensible  class, so that one can add custom logic.

- `BaseRepository`, a concrete class extending and providing a sensible constructor to the 
`AbstarctBaseRepository`.

### BaseRepositoryInterface
 In code, the interface resembles:
 
 ```php
<?php
 
 namespace Augusito\Repository;
 
 interface BaseRepositoryInterface
 {
     public function getAdapter();
 
     public function select($table, $where = null);
 
     public function insert($table, $set);
 
     public function update($table, $set, $where = null);
 
     public function delete($table, $where);
 }
```
### AbstractBaseRepository
This class provides a basic abstract implementation of `BaseRepositoryInterface`.
The class defines functions for `select()`, `insert()`, `update()`, and `delete()`
that work with a single database table at one level. At another level, the class defines 
an additional API for doing these same kinds of tasks with explicit `Zend\Db\Sql` objects: 
`selectWith()`, `insertWith()`, `updateWith()`, and `deleteWith()`. It is in this additional 
level, that one is able to define `Zend\Db\Sql` objects with multiple tables without restrictions.

### BaseRepository
This is a concrete class extending the `AbstarctBaseRepository` class by simply adding a 
sensible constructor. In code, the class resembles:

```php
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
```

## Usage
The following examples uses `Augusito\Repository\BaseRepository`, which defines the following API:

```php
<?php

namespace Augusito\Repository;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;

class BaseRepository extends AbstractBaseRepository
{
     /** Inherited from AbstractBaseRepository */
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
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @param Sql|null $sql
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(AdapterInterface $adapter, Sql $sql = null)
    
     /** Inherited from AbstractBaseRepository */
     
     /**
      * @return bool
      */
     public function isInitialized();
 
     /**
      * Initialize
      *
      * @return self
      * @throws Exception\RuntimeException
      */
     public function initialize();
 
     /**
      * Get adapter
      *
      * @return AdapterInterface
      */
     public function getAdapter();
 
     /**
      * Get columns
      *
      * @return array
      */
     public function getColumns();
 
     /**
      * Get Sql
      *
      * @return Sql
      */
     public function getSql();
 
     /**
      * Get connection
      *
      * @return ConnectionInterface
      */
     public function getConnection();
 
     /**
      * Select
      *
      * @param string $table
      * @param Where|Closure|string|array|null $where
      * @param ResultSetInterface|null $resultSetPrototype
      * @return ResultSetInterface
      */
     public function select($table, $where = null, ResultSetInterface $resultSetPrototype = null);
 
     /**
      * @param Select $select
      * @param ResultSetInterface|null $resultSetPrototype
      * @return ResultSetInterface
      */
     public function selectWith(Select $select, ResultSetInterface $resultSetPrototype = null);
 
     /**
      * @param Select $select
      * @param ResultSetInterface|null $resultSetPrototype
      * @return ResultSetInterface
      * @throws Exception\RuntimeException
      */
     protected function executeSelect(Select $select, ResultSetInterface $resultSetPrototype = null);
 
     /**
      * Insert
      *
      * @param string $table
      * @param array $set
      * @return int
      */
     public function insert($table, $set);
 
     /**
      * @param Insert $insert
      * @return int
      */
     public function insertWith(Insert $insert);
 
     /**
      * @param Insert $insert
      * @return int
      * @throws Exception\RuntimeException
      *
      */
     protected function executeInsert(Insert $insert);
 
     /**
      * Update
      *
      * @param string $table
      * @param array $set
      * @param string|array|Closure|null $where
      * @param null|array $joins
      * @return int
      */
     public function update($table, $set, $where = null, array $joins = null);
 
     /**
      * @param Update $update
      * @return int
      */
     public function updateWith(Update $update);
 
     /**
      * @param Update $update
      * @return int
      * @throws Exception\RuntimeException
      *
      */
     protected function executeUpdate(Update $update);
 
     /**
      * Delete
      *
      * @param string $table
      * @param Where|Closure|string|array $where
      * @return int
      */
     public function delete($table, $where);
 
     /**
      * @param Delete $delete
      * @return int
      */
     public function deleteWith(Delete $delete);
 
     /**
      * @param Delete $delete
      * @return int
      * @throws Exception\RuntimeException
      */
     protected function executeDelete(Delete $delete);
 
     /**
      * Get last insert value
      *
      * @return int
      */
     public function getLastInsertValue();
}
```

### Example database
We use a set of simple tables to illustrate usage of the classes and methods. 
These example tables could store information for tracking bugs in a software 
development project. The database contains four tables:

*accounts* stores information about each user of the bug-tracking database.

*products* stores information about each product for which a bug can be logged.

*bugs* stores information about bugs, including that current state of the bug, the person who reported the bug, the person who is assigned to fix the bug, and the person who is assigned to verify the fix.

*bugs_products* stores a relationship between bugs and products. This implements a many-to-many relationship, because a given bug may be relevant to multiple products, and of course a given product can have multiple bugs.

The following SQL data definition (MySQL) describes the tables in this example database.

```sql

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `account_name` varchar(100) NOT NULL,
  PRIMARY KEY (`account_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` VALUES ('duck'),('goofy'),('mouse');

--
-- Table structure for table `bugs`
--

DROP TABLE IF EXISTS `bugs`;
CREATE TABLE `bugs` (
  `bug_id` int(11) NOT NULL,
  `bug_description` varchar(100) DEFAULT NULL,
  `bug_status` varchar(20) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `reported_by` varchar(100) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `verified_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `bugs`
--

INSERT INTO `bugs` 
VALUES 
(1,'System needs electricity to run','NEW','2019-04-24 00:00:00','2019-04-24 00:00:00','goofy','mouse','duck'),
(2,'Implement Do What I Mean function','VERIFIED','2019-04-25 00:00:00','2019-04-25 00:00:00','goofy','mouse','duck'),
(3,'Where are my keys?','FIXED','2019-04-26 00:00:00','2019-04-26 00:00:00','duck','mouse','duck'),
(4,'Bug no product','INCOMPLETE','2019-04-27 00:00:00','2019-04-27 00:00:00','mouse','goofy','duck');

--
-- Table structure for table `bugs_products`
--

DROP TABLE IF EXISTS `bugs_products`;
CREATE TABLE `bugs_products` (
  `bug_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`bug_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `bugs_products`
--

INSERT INTO `bugs_products` VALUES (1,1),(1,2),(1,3),(2,3),(3,2),(3,3);

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` VALUES (1,'Windows'),(2,'Linux'),(3,'OS X');

```
