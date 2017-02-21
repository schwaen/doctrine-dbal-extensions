<?php
namespace Schwaen\Doctrine\Dbal;

/**
 * Connection
 */
class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * Return a generic Model by $table_name
     * @param string $table_name
     * @param string $model_class_name
     * @return \Schwaen\Doctrine\Dbal\Model
     */
    public function getModel($table_name, $model_class_name = Model::class)
    {
        return new $model_class_name($table_name, $this);
    }
}
