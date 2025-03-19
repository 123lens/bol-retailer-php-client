<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

class EconomicOperators extends AbstractModel
{
    /**
     * Returns the definition of the model: an associative array with field names as key and
     * field definition as value. The field definition contains of
     * model: Model class or null if it is a scalar type
     * array: Boolean whether it is an array
     * @return array The model definition
     */
    public function getModelDefinition(): array
    {
        return [
            'operators' => [ 'model' => EconomicOperator::class, 'enum' => null, 'array' => true ],
            'page' => [ 'model' => null, 'enum' => null, 'array' => false ],
        ];
    }

    public $operators;

    public $page;
}
