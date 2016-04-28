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
     * @return \Schwaen\Doctrine\Dbal\Model
     */
    public function getModel($table_name)
    {
        return new Model($table_name, $this);
    }
}
