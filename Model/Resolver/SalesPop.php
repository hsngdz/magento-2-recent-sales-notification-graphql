<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SalesPopGraphQl
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
declare(strict_types=1);

namespace Mageplaza\SalesPopGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\SalesPop\Helper\Data;
use Mageplaza\SalesPopGraphQl\Model\Resolver\SalesPop\DataProvider;

/**
 * Class SalesPop
 * @package Mageplaza\SalesPopGraphQl\Model\Resolver
 */
class SalesPop implements ResolverInterface
{
    /**
     * @var DataProvider
     */
    protected $dataProvider;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * SalesPop constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataProvider $dataProvider
     * @param Data $helperData
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataProvider $dataProvider,
        Data $helperData
    ) {
        $this->dataProvider          = $dataProvider;
        $this->helperData            = $helperData;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$this->helperData->isEnabled()) {
            throw new GraphQlInputException(__('The module is disabled'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if (!isset($args['filter'])) {
            throw new GraphQlInputException(__("'filter' input argument is required."));
        }
        $searchCriteria = $this->searchCriteriaBuilder->build($field->getName(), $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $collection = $this->dataProvider->getData($searchCriteria);
        $collectionSize = $collection->getSize();
        //possible division by 0
        $pageSize = $collection->getPageSize();
        if ($pageSize) {
            $maxPages = ceil($collectionSize / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $collection->getCurPage();
        if ($collectionSize > 0 && $searchCriteria->getCurrentPage() > $maxPages) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        return [
            'total_count' => $collection->getSize(),
            'items' => $collection->getItems(),
            'page_info'   => [
                'page_size'    => $pageSize,
                'current_page' => $currentPage,
                'total_pages'  => $maxPages
            ]
        ];
    }
}
