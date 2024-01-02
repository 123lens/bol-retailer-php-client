<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class Details extends AbstractModel
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
            'period' => [ 'model' => PerformanceIndicatorPeriod::class, 'enum' => null, 'array' => false ],
            'score' => [ 'model' => Score::class, 'enum' => null, 'array' => false ],
            'norm' => [ 'model' => Norm::class, 'enum' => null, 'array' => false ],
        ];
    }

    /**
     * @var PerformanceIndicatorPeriod The period for which the performance is measured.
     */
    public $period;

    /**
     * @var Score The score for this measurement. In case there are no scores for an indicator, this element is omitted
     * from the response.
     */
    public $score;

    /**
     * @var Norm Service norm for this indicator.
     */
    public $norm;
}
