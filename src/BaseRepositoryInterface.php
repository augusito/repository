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
