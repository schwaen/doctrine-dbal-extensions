<?php
namespace Schwaen\Doctrine\Dbal;

class QueryBuilderException extends \Exception
{
    /**
     * @param string $direction
     * @return \Schwaen\Doctrine\Dbal\QueryBuilderException
     */
    public static function orderByDirectionDoesNotExist($direction)
    {
        return new self('The ORDER BY direction "'.$direction.'" does not exist.');
    }

    /**
     * @param string $expr_type
     * @return \Schwaen\Doctrine\Dbal\QueryBuilderException
     */
    public static function expressionTypeDoesNotExist($expr_type)
    {
        return new self('The Expression-Type "'.$expr_type.'" does not exist.');
    }
}
