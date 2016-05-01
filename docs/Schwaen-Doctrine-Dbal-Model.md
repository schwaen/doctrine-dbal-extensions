Schwaen\Doctrine\Dbal\Model
===============

Model




* Class name: Model
* Namespace: Schwaen\Doctrine\Dbal





Properties
----------


### $conn

    protected \Doctrine\DBAL\Connection $conn = null

The DBAL connection



* Visibility: **protected**


### $sm

    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $sm = null

Shortcut for the return of getSchemaManager()



* Visibility: **protected**


### $table_name

    protected string $table_name = ''

Table-Name



* Visibility: **protected**


### $columns

    protected array<mixed,\Doctrine\DBAL\Schema\Column> $columns = array()

Array with the Columns



* Visibility: **protected**


### $column_types

    protected array $column_types = array()

Shortcut with Column-Types



* Visibility: **protected**


### $has_autoincrement

    protected boolean $has_autoincrement = null

Caching variable if the table has an auto increment column



* Visibility: **protected**


Methods
-------


### __construct

    mixed Schwaen\Doctrine\Dbal\Model::__construct(string $table_name, \Schwaen\Doctrine\Dbal\Connection $conn)

constructor



* Visibility: **public**


#### Arguments
* $table_name **string**
* $conn **[Schwaen\Doctrine\Dbal\Connection](Schwaen-Doctrine-Dbal-Connection.md)**



### hasAutoIncrement

    boolean Schwaen\Doctrine\Dbal\Model::hasAutoIncrement()

Tells of the table has an auto increment column



* Visibility: **public**




### getColumn

    \Doctrine\DBAL\Schema\Column Schwaen\Doctrine\Dbal\Model::getColumn(string $column_name)

Returns the Column with $column_name



* Visibility: **protected**


#### Arguments
* $column_name **string**



### create

    \Schwaen\Doctrine\Dbal\Ambigous Schwaen\Doctrine\Dbal\Model::create(array $data)

Creates a new datarow



* Visibility: **public**


#### Arguments
* $data **array**



### read

    \Schwaen\Doctrine\Dbal\array: Schwaen\Doctrine\Dbal\Model::read(array $columns, array $filters, string $limit, array $order_by, boolean $fetch_with_php_types)

read $columns from $table_name



* Visibility: **public**


#### Arguments
* $columns **array**
* $filters **array**
* $limit **string**
* $order_by **array**
* $fetch_with_php_types **boolean**



### update

    integer Schwaen\Doctrine\Dbal\Model::update(array $data, array $filters)





* Visibility: **public**


#### Arguments
* $data **array**
* $filters **array**



### delete

    integer Schwaen\Doctrine\Dbal\Model::delete(array $filters)

Delete rows



* Visibility: **public**


#### Arguments
* $filters **array**



### copy

    array Schwaen\Doctrine\Dbal\Model::copy(array $filters, string $limit, array $order_by, array $changes)

copy some records and maybe change some values



* Visibility: **public**


#### Arguments
* $filters **array**
* $limit **string**
* $order_by **array**
* $changes **array**



### buildWhere

    \Schwaen\Doctrine\Dbal\Model Schwaen\Doctrine\Dbal\Model::buildWhere(\Doctrine\DBAL\Query\QueryBuilder $qb, array $filters)

Builds the WHERE clause from $filter



* Visibility: **protected**


#### Arguments
* $qb **Doctrine\DBAL\Query\QueryBuilder**
* $filters **array** - &lt;p&gt;should be an array with arrays wich contains 3 datas
[
[&#039;column_name&#039;, &#039;expr_type&#039;, &#039;value&#039;],
[],
...
]
expr_types: &#039;eq&#039;, &#039;neq&#039;, &#039;lt&#039;, &#039;lte&#039;, &#039;gt&#039;, &#039;gte&#039;, &#039;like&#039;, &#039;in&#039;, &#039;notIn&#039;, &#039;notLike&#039;&lt;/p&gt;



### buildLimit

    \Schwaen\Doctrine\Dbal\Model Schwaen\Doctrine\Dbal\Model::buildLimit(\Doctrine\DBAL\Query\QueryBuilder $qb, mixed $limit)

Build up dynamilcy the LIMIT part



* Visibility: **protected**


#### Arguments
* $qb **Doctrine\DBAL\Query\QueryBuilder**
* $limit **mixed**



### buildOrderBy

    \Schwaen\Doctrine\Dbal\Model Schwaen\Doctrine\Dbal\Model::buildOrderBy(\Doctrine\DBAL\Query\QueryBuilder $qb, array $order_by)

Builds the ORDER BY part



* Visibility: **protected**


#### Arguments
* $qb **Doctrine\DBAL\Query\QueryBuilder**
* $order_by **array**



### getTableName

    string Schwaen\Doctrine\Dbal\Model::getTableName()

Returns the table-Name



* Visibility: **public**




### getExpressionTypes

    \Schwaen\Doctrine\Dbal\multitype:string Schwaen\Doctrine\Dbal\Model::getExpressionTypes()

List all possible expression types



* Visibility: **public**




### getColumnNames

    \Schwaen\Doctrine\Dbal\multitype:string Schwaen\Doctrine\Dbal\Model::getColumnNames()

List all possible column names



* Visibility: **public**



