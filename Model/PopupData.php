<?php

declare(strict_types=1);

namespace Mageplaza\RecentSalesNotificationGraphQl\Model;

use Magento\Framework\App\Config\Value;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\RecentSalesNotification\Helper\Data;
use Mageplaza\RecentSalesNotification\Model\ResourceModel\RecentSalesNotification as ResourceModel;
use Psr\Log\LoggerInterface;


class PopupData
{
    protected $productRepository;
    protected $helperData;
    protected $resourceModel;
    protected $configValue;
    protected $logger;

    public function __construct(
        ProductRepository $productRepository,
        Data $helperData,
        ResourceModel $resourceModel,
        Value $configValue,
        LoggerInterface $logger
    ) {
        $this->productRepository              = $productRepository;
        $this->helperData                     = $helperData;
        $this->resourceModel                  = $resourceModel;
        $this->configValue                    = $configValue;
        $this->logger                         = $logger;
    }

    public function getData($popupId) {
        $configData  = $this->configValue->load(
            'mprecentsalesnotification_popupData_' . $popupId,
            'path'
        );
        $productList = Data::jsonDecode($configData->getValue());
        $popupList = $this->processRecentSalesPopup($productList);
        return $popupList;
    }

    public function processRecentSalesPopup($popupList)
    {
        if (empty($popupList)) {
            return $popupList;
        }
        try {
            foreach ($popupList as $key => $item) {
                $product = $this->productRepository->getById($item['product_id']);
                $productUrl = $product->getUrlKey();
                $item = [
                    'product_url_key' => $productUrl,
                    'customer_name'   => $item['customer_name'] ?? "",
                    'city' => $item['city'] ?? "",
                    'time' => $item['time'] ?? "recently"
                ];
                $popupList[$key] = $item;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }

        return $popupList;
    }
}