<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class TransportEvent extends AbstractModel
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
            'eventCode' => [ 'model' => null, 'enum' => Enum\TransportEventEventCode::class, 'array' => false ],
            'eventDateTime' => [ 'model' => null, 'enum' => null, 'array' => false ],
        ];
    }

    /**
     * @var Enum\TransportEventEventCode The transport event code indicates the location of the parcel within the
     * distribution process.
     */
    public $eventCode;

    /**
     * @var string The date time of the transport event.
     */
    public $eventDateTime;

    public function getEventDateTime(): ?\DateTime
    {
        if (empty($this->eventDateTime)) {
            return null;
        }

        return \DateTime::createFromFormat(\DateTime::ATOM, $this->eventDateTime);
    }
}
