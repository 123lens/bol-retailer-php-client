<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class ProductDestination extends AbstractModel
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
            'destinationWarehouse' => [ 'model' => ProductDestinationWarehouse::class, 'enum' => null, 'array' => false ],
            'eans' => [ 'model' => Ean::class, 'enum' => null, 'array' => true ],
        ];
    }

    /**
     * @var ProductDestinationWarehouse
     */
    public $destinationWarehouse;

    /**
     * @var Ean[]
     */
    public $eans = [];

    /**
     * Returns address from destinationWarehouse.
     * @return ProductDestinationAddress Address from destinationWarehouse.
     */
    public function getDestinationWarehouseAddress(): ProductDestinationAddress
    {
        return $this->destinationWarehouse->address;
    }

    /**
     * Sets destinationWarehouse by address.
     * @param ProductDestinationAddress $address Address for destinationWarehouse.
     */
    public function setDestinationWarehouseAddress(ProductDestinationAddress $address): void
    {
        $this->destinationWarehouse = ProductDestinationWarehouse::constructFromArray(['address' => $address]);
    }

    /**
     * Returns an array with the eans from eans.
     * @return string[] Eans from eans.
     */
    public function getEans(): array
    {
        return array_map(function ($model) {
            return $model->ean;
        }, $this->eans);
    }

    /**
     * Sets eans by an array of eans.
     * @param string[] $eans Eans for eans.
     */
    public function setEans(array $eans): void
    {
        $this->eans = array_map(function ($ean) {
            return Ean::constructFromArray(['ean' => $ean]);
        }, $eans);
    }

    /**
     * Adds a new Ean to eans by ean.
     * @param string $ean Ean for the Ean to add.
     */
    public function addEan(string $ean): void
    {
        $this->eans[] = Ean::constructFromArray(['ean' => $ean]);
    }
}
