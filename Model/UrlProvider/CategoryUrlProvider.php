<?php
/**
 * File: CategoryUrlProvider.php
 *
 * @author Maciej Sławik <maciej.slawik@lizardmedia.pl>
 * @copyright Copyright (C) 2018 Lizard Media (http://lizardmedia.pl)
 */

namespace LizardMedia\VarnishWarmer\Model\UrlProvider;

use LizardMedia\VarnishWarmer\Api\UrlProvider\CategoryUrlProviderInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class CategoryUrlProvider
 * @package LizardMedia\VarnishWarmer\Model\UrlProvider
 */
class CategoryUrlProvider implements CategoryUrlProviderInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var ResourceConnectionFactory
     */
    protected $resourceConnectionFactory;

    /**
     * CategoryUrlProvider constructor.
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ResourceConnectionFactory $resourceConnectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ResourceConnectionFactory $resourceConnectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceConnectionFactory = $resourceConnectionFactory;
    }

    /**
     * @return array
     */
    public function getActiveCategoriesUrls(): array
    {
        /** @var ResourceConnection $connection */
        $connection = $this->resourceConnectionFactory->create();
        /** @var AdapterInterface $conn */
        $conn = $connection->getConnection();

        $categoryIds = $this->getAvailableCategoriesIds();
        $select = $conn
            ->select()
            ->from(
                [
                    'u' => 'url_rewrite'
                ],
                'request_path'
            )->where(
                'u.entity_type=?',
                'category'
            )->where(
                'u.entity_id IN (' . implode(',', $categoryIds) . ')'
            );
        return $conn->fetchAll($select);
    }

    /**
     * @return array
     */
    protected function getAvailableCategoriesIds(): array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addFieldToFilter(
            'is_active',
            1
        );
        return $categoryCollection->getAllIds();
    }
}
