<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class ShipmentRequest extends AbstractModel
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
            'orderItems' => [ 'model' => OrderItem::class, 'enum' => null, 'array' => true ],
            'shipmentReference' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'shippingLabelId' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'transport' => [ 'model' => TransportInstruction::class, 'enum' => null, 'array' => false ],
        ];
    }

    /**
     * @var OrderItem[] List of order items to ship.
     */
    public $orderItems = [];

    /**
     * @var string A user-defined reference that you can provide to add to the shipment. Can be used for own
     * administration purposes. Only 'null' or non-empty strings accepted.
     */
    public $shipmentReference;

    /**
     * @var string The identifier of the purchased shipping label.
     */
    public $shippingLabelId;

    /**
     * @var TransportInstruction
     */
    public $transport;
}
