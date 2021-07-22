<?php

namespace Picqer\BolRetailer\Model;

// This class is auto generated by OpenApi\ModelGenerator
class ReducedReturnItem extends AbstractModel
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
            'rmaId' => [ 'model' => null, 'array' => false ],
            'orderId' => [ 'model' => null, 'array' => false ],
            'ean' => [ 'model' => null, 'array' => false ],
            'expectedQuantity' => [ 'model' => null, 'array' => false ],
            'returnReason' => [ 'model' => null, 'array' => false ],
            'returnReasonComments' => [ 'model' => null, 'array' => false ],
            'handled' => [ 'model' => null, 'array' => false ],
            'processingResults' => [ 'model' => ReturnProcessingResult::class, 'array' => true ],
        ];
    }

    /**
     * @var int The RMA (Return Merchandise Authorization) id that identifies this particular return.
     */
    public $rmaId;

    /**
     * @var string The id of the customer order this return item is in.
     */
    public $orderId;

    /**
     * @var string The EAN number associated with this product.
     */
    public $ean;

    /**
     * @var int The quantity that is expected to be returned by the customer. Note: this can be greater than 1 in case
     * the customer ordered a quantity greater than 1 of the same product in the same customer order.
     */
    public $expectedQuantity;

    /**
     * @var string The reason why the customer returned this product.
     */
    public $returnReason;

    /**
     * @var string Additional details from the customer as to why this item was returned.
     */
    public $returnReasonComments;

    /**
     * @var bool Indicates if this return item has been handled (by the retailer).
     */
    public $handled;

    /**
     * @var ReturnProcessingResult[]
     */
    public $processingResults = [];
}