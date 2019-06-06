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
