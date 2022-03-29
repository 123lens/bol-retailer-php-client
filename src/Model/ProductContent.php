<?php

namespace Picqer\BolRetailerV6\Model;

// This class is auto generated by OpenApi\ModelGenerator
class ProductContent extends AbstractModel
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
            'internalReference' => [ 'model' => null, 'array' => false ],
            'attributes' => [ 'model' => Attribute::class, 'array' => true ],
            'assets' => [ 'model' => Asset::class, 'array' => true ],
        ];
    }

    /**
     * @var string A user defined unique reference to identify the products in the upload.
     */
    public $internalReference;

    /**
     * @var Attribute[] A list of attributes.
     */
    public $attributes = [];

    /**
     * @var Asset[]
     */
    public $assets = [];
}
