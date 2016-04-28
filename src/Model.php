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
    private $conn = null;

    /**
     * Shortcut for the return of getSchemaManager()
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    private $sm = null;

    /**
     * Table-Name
     * @var string
     */
    private $table_name = '';

    /**
     * Array with the Columns
     * @var \Doctrine\DBAL\Schema\Column[]
     */
    private $columns = [];

    /**
     * Caching variable if the table has an auto increment column
     * @var bool
     */
    private $has_autoincrement = null;

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
        $this->columns = $this->sm->listTableColumns($this->table_name);
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
     * Checks if the table has a column names $column_name
     * @param string $column_name
     * @return boolean
     */
    public function hasColumn($column_name)
    {
        return isset($this->columns[$column_name]) || isset($this->columns[$this->conn->quoteIdentifier($column_name)]);
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
            if (!$this->hasColumn($column)) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->setValue($this->conn->quoteIdentifier($column), $qb->createNamedParameter($value));
        }
        $return = (bool) $this->conn->executeUpdate($qb->getSQL(), $qb->getParameters(), $qb->getParameterTypes());
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
     * @return array:
     */
    public function read(array $columns, array $filters = [], $limit = null, array $order_by = [])
    {
        $return = [];
        $qb = $this->conn->createQueryBuilder();
        foreach ($columns as $column) {
            if (!$this->hasColumn($column)) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->addSelect($this->conn->quoteIdentifier($column));
        }
        $qb->from($this->conn->quoteIdentifier($this->table_name));
        $this->buildWhere($qb, $filters);
        $this->buildOrderBy($qb, $order_by);
        $this->buildLimit($qb, $limit);
        $res = $this->conn->executeQuery($qb->getSQL(), $qb->getParameters(), $qb->getParameterTypes());
        $return = $res->fetchAll(\PDO::FETCH_ASSOC);
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
            if (!$this->hasColumn($column)) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->set($this->conn->quoteIdentifier($column), $qb->createNamedParameter($value));
        }
        $this->buildWhere($qb, $filters);
        return $this->conn->executeUpdate($qb->getSQL(), $qb->getParameters(), $qb->getParameterTypes());
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
        return $this->conn->executeUpdate($qb->getSQL(), $qb->getParameters(), $qb->getParameterTypes());
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
     * @return void
     */
    private function buildWhere(QueryBuilder $qb, array $filters = [])
    {
        if (empty($filters)) {
            return;
        }
        $expr = $qb->expr()->andX();
        foreach ($filters as $f) {
            $column = $f[0];
            $expr_type = $f[1];
            $value = isset($f[2]) ? $f[2] : null;
            $type = \PDO::PARAM_STR;
            if (!$this->hasColumn($column)) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            if (!in_array($expr_type, ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'like', 'in', 'notIn', 'notLike'])) { //ToDo: not null, is null
                throw new \Exception($expr_type.' is not a valid expr_type');
            }
            if (in_array($expr_type, ['in', 'notIn']) && is_array($value)) {
                switch ($this->columns[$column]->getType()->getName()) {
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

    /**
     * Build up dynamilcy the LIMIT part
     * @param QueryBuilder $qb
     * @param mixed $limit
     */
    private function buildLimit(QueryBuilder $qb, $limit = null)
    {
        if ($limit === null) {
            return;
        } elseif (is_int($limit) || is_numeric($limit)) {
            $qb->setMaxResults((int) $limit);
        } elseif (is_array($limit) && count($limit) === 2) {
            $qb->setFirstResult((int) $limit[0]);
            $qb->setMaxResults((int) $limit[1]);
        }
    }

    /**
     * Builds the ORDER BY part
     * @param QueryBuilder $qb
     * @param array $order_by
     */
    private function buildOrderBy(QueryBuilder $qb, array $order_by = [])
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
            if ($column === null || !$this->hasColumn($column)) {
                throw Schema\SchemaException::columnDoesNotExist($column, $this->table_name);
            }
            $qb->addOrderBy($this->conn->quoteIdentifier($column), $direction);
        }
    }

    /**
     * Returns the table-Name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }
}
