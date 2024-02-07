<?php

declare(strict_types=1);

/**
 * File: ProductUrlProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2019 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\UrlProvider;

use LizardMedia\VarnishWarmer\Api\UrlProvider\ProductUrlProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class ProductUrlProvider
 * @package LizardMedia\VarnishWarmer\Model\UrlProvider
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProductUrlProvider implements ProductUrlProviderInterface
{
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ResourceConnectionFactory
     */
    protected $resourceConnectionFactory;

    /**
     * ProductUrlProvider constructor.
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ResourceConnectionFactory $resourceConnectionFactory
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnectionFactory $resourceConnectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnectionFactory = $resourceConnectionFactory;
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getProductUrls(int $productId): array
    {
        /** @var ResourceConnection $connection */
        $connection = $this->resourceConnectionFactory->create();
        /** @var AdapterInterface $conn */
        $conn = $connection->getConnection();

        $select = $conn
            ->select()
            ->from(
                [
                    'u' => $connection->getTableName('url_rewrite')
                ],
                'request_path'
            )->where(
                'u.entity_type=?',
                'product'
            )->where(
                'u.entity_id=?',
                $productId
            );

        return $conn->fetchAll($select);
    }

    /**
     * @return array
     */
    public function getActiveProductsUrls(): array
    {
        /** @var ResourceConnection $connection */
        $connection = $this->resourceConnectionFactory->create();
        /** @var AdapterInterface $conn */
        $conn = $connection->getConnection();

        $productIds = $this->getAvailableProductsIds();
        $rewrites = [];
        if (!empty($productIds)) {
            $select = $conn
                ->select()
                ->from(
                    [
                        'u' => $connection->getTableName('url_rewrite')
                    ],
                    'request_path'
                )->where(
                    'u.entity_type=?',
                    'product'
                )->where(
                    'u.entity_id IN (' . implode(',', $productIds) . ')'
                );

            $rewrites = $conn->fetchAll($select);
        }
        return $rewrites;
    }

    /**
     * @return array
     */
    protected function getAvailableProductsIds(): array
    {
        /** @var Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter(
            ProductInterface::VISIBILITY,
            [
                'in' => [
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH,
                    Visibility::VISIBILITY_BOTH
                ]
            ]
        );
        $productCollection->addFieldToFilter(
            ProductInterface::STATUS,
            Status::STATUS_ENABLED
        );
        return $productCollection->getAllIds();
    }
}
