<?php

namespace Picqer\BolRetailerV10\Model;

// This class is auto generated by OpenApi\ModelGenerator
class PriceStarBoundaries extends AbstractModel
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
            'lastModifiedDateTime' => [ 'model' => null, 'array' => false ],
            'priceStarBoundaryLevels' => [ 'model' => PriceStarBoundaryLevels::class, 'array' => true ],
        ];
    }

    /**
     * @var string The date and time in ISO 8601 format when boundaries updated for the last time.
     */
    public $lastModifiedDateTime;

    /**
     * @var PriceStarBoundaryLevels[]
     */
    public $priceStarBoundaryLevels = [];

    public function getLastModifiedDateTime(): ?\DateTime
    {
        if (empty($this->lastModifiedDateTime)) {
            return null;
        }

        return \DateTime::createFromFormat(\DateTime::ATOM, $this->lastModifiedDateTime);
    }
}