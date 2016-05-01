<?php
namespace Schwaen\Doctrine\Dbal;

use \Doctrine\DBAL\Schema;
use \Doctrine\DBAL\Query\QueryBuilder;

/**
 * Model
 */
class Model
{
    /**
     * The DBAL connection
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn = null;

    /**
     * Shortcut for the return of getSchemaManager()
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm = null;

    /**
     * Table-Name
     * @var string
     */
    protected $table_name = '';

    /**
     * Array with the Columns
     * @var \Doctrine\DBAL\Schema\Column[]
     */
    protected $columns = [];

    /**
     * Shortcut with Column-Types
     * @var array
     */
    protected $column_types = [];

    /**
     * Caching variable if the table has an auto increment column
     * @var bool
     */
    protected $has_autoincrement = null;

    /**
     * constructor
     * @param string $table_name
     * @param Connection $conn
     */
    public function __construct($table_name, \Doctrine\DBAL\Connection $conn)
    {
        $this->table_name = $table_name;
        $this->conn = $conn;
        $this->sm = $this->conn->getSchemaManager();
        if (!$this->sm->tablesExist([$table_name])) {
            throw Schema\SchemaException::tableDoesNotExist($table_name);
        }
        foreach ($this->sm->listTableColumns($this->table_name) as $colum) {
            $this->columns[$colum->getName()] = $colum;
            $this->column_types[$colum->getName()] = $colum->getType()->getName();
        }
    }

    /**
     * Tells of the table has an auto increment column
     * @return boolean
     */
    public function hasAutoIncrement()
    {
        if ($this->has_autoincrement === null) {
            $this->has_autoincrement = false;
            foreach ($this->columns as $column) {
                if ($column->getAutoincrement()) {
                    $this->has_autoincrement = true;
                    break;
                }
            }
        }
        return $this->has_autoincrement;
    }

    /**
     * Returns the Column with $column_name
     * @param string $column_name
     * @return \Doctrine\DBAL\Schema\Column or null if the Column doesn't exist
     */
    protected function getColumn($column_name)
    {
        return isset($this->columns[$column_name]) ? $this->columns[$column_name] : null;
    }

    /**
     * Creates a new datarow
     * @param array $data
     * @return Ambigous <boolean, string> false on error, true on success or an string with the last insert id if the table has an auto increment column
     */
    public function create(array $data)
    {
        $return = false;
        $qb = $this->conn->createQueryBuilder()->insert($this->conn->quoteIdentifier($this->table_name));
        foreach ($data as $column => $value) {
            if ($this->getColumn($column) === null) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->setValue($this->conn->quoteIdentifier($column), $qb->createNamedParameter($value));
        }
        $return = (bool) $qb->execute();
        if ($this->hasAutoIncrement()) {
            $return = $this->conn->lastInsertId();
        }
        return $return;
    }

    /**
     * read $columns from $table_name
     * @param array $columns
     * @param array $filters
     * @param string $limit
     * @param array $order_by
     * @param bool $fetch_with_php_types
     * @return array:
     */
    public function read(array $columns = [], array $filters = [], $limit = null, array $order_by = [], $fetch_with_php_types = true)
    {
        $return = [];
        $qb = $this->conn->createQueryBuilder();
        if (empty($columns)) {
            $columns = $this->getColumnNames();
        }
        foreach ($columns as $column) {
            if ($this->getColumn($column) === null) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->addSelect($this->conn->quoteIdentifier($column));
        }
        $qb->from($this->conn->quoteIdentifier($this->table_name));
        $this
            ->buildWhere($qb, $filters)
            ->buildOrderBy($qb, $order_by)
            ->buildLimit($qb, $limit)
        ;
        $res = $qb->execute();
        $return = $res->fetchAll(\PDO::FETCH_ASSOC);
        if ($fetch_with_php_types) {
            foreach ($return as $index => $row) {
                foreach ($row as $column => $value) {
                    switch ($this->column_types[$column]) {
                        case 'integer':
                            $return[$index][$column] = (int) $value;
                            break;
                        case 'decimal':
                            $return[$index][$column] = (float) $value;
                            break;
                    }
                }
            }
        }
        return $return;
    }

    /**
     *
     * @param array $data
     * @param array $filters
     * @return integer The number of rows
     */
    public function update(array $data, array $filters = [])
    {
        $qb = $this->conn->createQueryBuilder()->update($this->conn->quoteIdentifier($this->table_name));
        foreach ($data as $column => $value) {
            if ($this->getColumn($column) === null) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->set($this->conn->quoteIdentifier($column), $qb->createNamedParameter($value));
        }
        $this->buildWhere($qb, $filters);
        return $qb->execute();
    }

    /**
     * Delete rows
     * @param array $filters
     * @return integer The number of rows
     */
    public function delete(array $filters = [])
    {
        $qb = $this->conn->createQueryBuilder()->delete($this->conn->quoteIdentifier($this->table_name));
        $this->buildWhere($qb, $filters);
        return $qb->execute();
    }

    /**
     * copy some records and maybe change some values
     * @param array $filters
     * @param string $limit
     * @param array $order_by
     * @param array $changes
     * @return array the result of each $this->create()
     */
    public function copy(array $filters = [], $limit = null, array $order_by = [], array $changes = [])
    {
        $return = [];
        //read
        $data = $this->read($this->getColumnNames(), $filters, $limit, $order_by, false);
        foreach ($data as $row) {
            foreach ($row as $column_name => $value) {
                $column = $this->getColumn($column_name);
                //changes
                if (isset($changes[$column_name])) {
                    $row[$column_name] = $changes[$column_name];
                }
                //remove autoincrements
                if ($column->getAutoincrement()) {
                    unset($row[$column_name]);
                }
            }
            $return[] = $this->create($row);
        }
        return $return;
    }

    /**
     * Builds the WHERE clause from $filter
     * @param QueryBuilder $qb
     * @param array $filters should be an array with arrays wich contains 3 datas
     * [
     *    ['column_name', 'expr_type', 'value'],
     *    [],
     *    ...
     * ]
     * expr_types: 'eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'like', 'in', 'notIn', 'notLike'
     * @return self
     */
    protected function buildWhere(QueryBuilder $qb, array $filters = [])
    {
        if (!empty($filters)) {
            $expr = $qb->expr()->andX();
            foreach ($filters as $f) {
                $column = $f[0];
                $expr_type = $f[1];
                $value = isset($f[2]) ? $f[2] : null;
                $type = \PDO::PARAM_STR;
                if ($this->getColumn($column) === null) {
                    throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
                }
                if (!in_array($expr_type, $this->getExpressionTypes())) {
                    throw new \Exception($expr_type.' is not a valid expr_type');
                }
                if (in_array($expr_type, ['in', 'notIn']) && is_array($value)) {
                    switch ($this->getColumn($column)->getType()->getName()) {
                        case 'integer':
                            $type = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
                            break;
                        case 'string':
                            $type = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                            break;
                    }
                }
                $expr->add($qb->expr()->$expr_type($this->conn->quoteIdentifier($column), $qb->createNamedParameter($value, $type)));
            }
            $qb->where($expr);
        }
        return $this;
    }

    /**
     * Build up dynamilcy the LIMIT part
     * @param QueryBuilder $qb
     * @param mixed $limit
     * @return self
     */
    protected function buildLimit(QueryBuilder $qb, $limit = null)
    {
        if (is_int($limit) || is_numeric($limit)) {
            $qb->setMaxResults((int) $limit);
        } elseif (is_array($limit)) {
            switch (count($limit)) {
                case 2:
                    $qb->setFirstResult((int) $limit[0]);
                    $qb->setMaxResults((int) $limit[1]);
                    break;
                case 1:
                    $qb->setMaxResults((int) $limit[0]);
                    break;
            }
        }
        return $this;
    }

    /**
     * Builds the ORDER BY part
     * @param QueryBuilder $qb
     * @param array $order_by
     * @return self
     */
    protected function buildOrderBy(QueryBuilder $qb, array $order_by = [])
    {
        foreach ($order_by as $order) {
            $column = null;
            $direction = 'ASC';
            if (is_string($order)) {
                $column = $order;
            } elseif (is_array($order) && count($order) === 2) {
                $column = $order[0];
                $direction = $order[1];
            }
            if ($column === null || $this->getColumn($column) === null) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->addOrderBy($this->conn->quoteIdentifier($column), $direction);
        }
        return $this;
    }

    /**
     * Returns the table-Name
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * List all possible expression types
     * @return multitype:string
     */
    public function getExpressionTypes()
    {
        return ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'like', 'in', 'notIn', 'notLike', 'isNull', 'isNotNull'];
    }

    /**
     * List all possible column names
     * @return multitype:string
     */
    public function getColumnNames()
    {
        return array_keys($this->columns);
    }
}
