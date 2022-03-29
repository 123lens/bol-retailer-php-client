<?php

namespace Picqer\BolRetailerV6\Model;

// This class is auto generated by OpenApi\ModelGenerator
class ShipmentTransport extends AbstractModel
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
            'transportId' => [ 'model' => null, 'array' => false ],
            'transporterCode' => [ 'model' => null, 'array' => false ],
            'trackAndTrace' => [ 'model' => null, 'array' => false ],
            'shippingLabelId' => [ 'model' => null, 'array' => false ],
            'transportEvents' => [ 'model' => TransportEvent::class, 'array' => true ],
        ];
    }

    /**
     * @var string The transport id.
     */
    public $transportId;

    /**
     * @var string Specify the transporter that will carry out the shipment.
     */
    public $transporterCode;

    /**
     * @var string The track and trace code that is associated with this transport.
     */
    public $trackAndTrace;

    /**
     * @var string The shipping label id.
     */
    public $shippingLabelId;

    /**
     * @var TransportEvent[]
     */
    public $transportEvents = [];
}
