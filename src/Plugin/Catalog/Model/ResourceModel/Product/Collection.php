<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Plugin\Catalog\Model\ResourceModel\Product;

use Infrangible\BundleOption\Helper\Data;
use Infrangible\Core\Helper\Stores;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;

/**
 * Adds the custom options of bundle products that are linked to bundle options to the products of these bundle
 * options, when both are loaded in the same collection with their options (for example the products of the cart).
 */
class Collection
{
    /**
     * @param OptionCollectionFactory $optionCollectionFactory
     * @param Stores $storeHelper
     * @param Data $helper
     * @param ProductResource $productResource
     */
    public function __construct(
        protected OptionCollectionFactory $optionCollectionFactory,
        protected Stores $storeHelper,
        protected Data $helper,
        protected ProductResource $productResource
    ) {
    }

    /**
     * Adds the linked custom options of the bundle products of the collection to the products of the bundle options.
     *
     * Only products of the linked bundle options that are part of the collection get them.
     *
     * @param ProductCollection $subject
     * @param ProductCollection $result
     *
     * @return ProductCollection
     */
    public function afterAddOptionsToResult(ProductCollection $subject, ProductCollection $result): ProductCollection
    {
        $selectionProductIds = $this->getSelectionProductIds($subject);

        if (count($selectionProductIds) === 0) {
            return $result;
        }

        $storeId = $this->storeHelper->getStoreId();

        $options = $this->optionCollectionFactory->create();
        $options->addTitleToResult($storeId);
        $options->addPriceToResult($storeId);
        $options->addProductToFilter(array_keys($selectionProductIds));
        $options->addValuesToResult($storeId);

        foreach ($options as $option) {
            if (! $option instanceof Option) {
                continue;
            }

            $productOptions = $selectionProductIds[ (string)$option->getProductId() ] ?? [];

            foreach ($this->helper->getBundleOptionIds($option) as $bundleOptionId) {
                foreach ($productOptions[ $bundleOptionId ] ?? [] as $productId) {
                    $product = $subject->getItemById($productId);

                    if ($product instanceof Product) {
                        $product->addOption($option);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the IDs of the selectable products of the bundle products of the collection.
     *
     * They are grouped by the link field of the bundle product (the product ID of the custom options) and the bundle
     * option ID.
     *
     * @param ProductCollection $collection
     *
     * @return array<string, array<string, list<int|string>>>
     */
    private function getSelectionProductIds(ProductCollection $collection): array
    {
        $selectionProductIds = [];
        $linkField = $this->productResource->getLinkField();

        foreach ($collection as $product) {
            if (! $product instanceof Product || $product->getTypeId() !== BundleType::TYPE_CODE) {
                continue;
            }

            $typeInstance = $product->getTypeInstance();

            if (! $typeInstance instanceof BundleType) {
                continue;
            }

            $linkId = $product->getData($linkField);

            if (! is_scalar($linkId)) {
                continue;
            }

            $selections = $typeInstance->getSelectionsCollection($typeInstance->getOptionsIds($product), $product);

            foreach ($selections as $selection) {
                if (! $selection instanceof Product) {
                    continue;
                }

                $bundleOptionId = $selection->getData('option_id');
                $productId = $selection->getId();

                if (is_scalar($bundleOptionId)) {
                    $selectionProductIds[ (string)$linkId ][ (string)$bundleOptionId ][] = $productId;
                }
            }
        }

        return $selectionProductIds;
    }
}
