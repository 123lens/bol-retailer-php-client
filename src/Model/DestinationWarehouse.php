<?php

namespace Picqer\BolRetailerV6\Model;

// This class is auto generated by OpenApi\ModelGenerator
class DestinationWarehouse extends AbstractModel
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
            'streetName' => [ 'model' => null, 'array' => false ],
            'houseNumber' => [ 'model' => null, 'array' => false ],
            'houseNumberExtension' => [ 'model' => null, 'array' => false ],
            'zipCode' => [ 'model' => null, 'array' => false ],
            'city' => [ 'model' => null, 'array' => false ],
            'countryCode' => [ 'model' => null, 'array' => false ],
            'attentionOf' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The street name of the pickup address.
     */
    public $streetName;

    /**
     * @var string The house number of the pickup address.
     */
    public $houseNumber;

    /**
     * @var string The extension of the house number.
     */
    public $houseNumberExtension;

    /**
     * @var string The zip code in '1234AB' format for NL and '0000' for BE addresses.
     */
    public $zipCode;

    /**
     * @var string The city of the pickup address.
     */
    public $city;

    /**
     * @var string The ISO 3166-2 country code.
     */
    public $countryCode;

    /**
     * @var string Name of the person responsible for this replenishment.
     */
    public $attentionOf;
}
