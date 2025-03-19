<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

class EconomicOperator extends AbstractModel
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
            'id' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'externalReference' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'name' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'address' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'contactInformation' => [ 'model' => null, 'enum' => null, 'array' => true ],
            'status' => [ 'model' => null, 'enum' => null, 'array' => false ],
        ];
    }

    /**
     * @var string Unique identifier for an offer.
     */
    public $id;

    public $externalReference;

    public $name;

    public $address;

    public $contactInformation;

    public $status;
}
