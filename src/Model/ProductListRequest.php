<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class ProductListRequest extends AbstractModel
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
            'countryCode' => [ 'model' => null, 'enum' => Enum\ProductListRequestCountryCode::class, 'array' => false ],
            'searchTerm' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'categoryId' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'filterRanges' => [ 'model' => ProductListFilterRange::class, 'enum' => null, 'array' => true ],
            'filterValues' => [ 'model' => ProductListFilterValue::class, 'enum' => null, 'array' => true ],
            'sort' => [ 'model' => null, 'enum' => Enum\ProductListRequestSort::class, 'array' => false ],
            'page' => [ 'model' => null, 'enum' => null, 'array' => false ],
        ];
    }

    /**
     * @var Enum\ProductListRequestCountryCode The country for which the products will be retrieved.
     */
    public $countryCode;

    /**
     * @var string The search term to get the associated products for.
     */
    public $searchTerm;

    /**
     * @var string The category to get the associated products for.
     */
    public $categoryId;

    /**
     * @var ProductListFilterRange[] The list of range filters to get associated products for.
     */
    public $filterRanges = [];

    /**
     * @var ProductListFilterValue[] The list of filter values in this filter.
     */
    public $filterValues = [];

    /**
     * @var Enum\ProductListRequestSort Determines the order of the products.
     */
    public $sort;

    /**
     * @var int The requested page number with a page size of 50 items.
     */
    public $page;

    /**
     * Returns an array with the filterValueIds from filterValues.
     * @return string[] FilterValueIds from filterValues.
     */
    public function getFilterValueIds(): array
    {
        return array_map(function ($model) {
            return $model->filterValueId;
        }, $this->filterValues);
    }

    /**
     * Sets filterValues by an array of filterValueIds.
     * @param string[] $filterValueIds FilterValueIds for filterValues.
     */
    public function setFilterValueIds(array $filterValueIds): void
    {
        $this->filterValues = array_map(function ($filterValueId) {
            return ProductListFilterValue::constructFromArray(['filterValueId' => $filterValueId]);
        }, $filterValueIds);
    }

    /**
     * Adds a new ProductListFilterValue to filterValues by filterValueId.
     * @param string $filterValueId FilterValueId for the ProductListFilterValue to add.
     */
    public function addFilterValueId(string $filterValueId): void
    {
        $this->filterValues[] = ProductListFilterValue::constructFromArray(['filterValueId' => $filterValueId]);
    }
}
