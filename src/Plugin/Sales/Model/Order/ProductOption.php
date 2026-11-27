<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Plugin\Sales\Model\Order;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\CustomOptionInterface;
use Magento\Catalog\Api\Data\ProductOptionExtensionFactory;
use Magento\Catalog\Model\ProductOptionFactory;
use Magento\Catalog\Model\ProductOptionProcessor;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ProductOption as OrderProductOption;

/**
 * Moves the custom options of bundle products that are linked to bundle options from the bundle item to the items of
 * the selected products in the product options of order items (for example in the order API).
 */
class ProductOption
{
    /**
     * @param ProductOptionFactory $productOptionFactory
     * @param ProductOptionExtensionFactory $extensionFactory
     * @param ProductOptionProcessor $productOptionProcessor
     */
    public function __construct(
        protected ProductOptionFactory $productOptionFactory,
        protected ProductOptionExtensionFactory $extensionFactory,
        protected ProductOptionProcessor $productOptionProcessor
    ) {
    }

    /**
     * Moves the custom options of the bundle product to the items of the selected products that got them.
     *
     * The items of selected products get them in their product option, the bundle item loses them.
     *
     * @param OrderProductOption $subject
     * @param mixed $result
     * @param OrderItemInterface $orderItem
     *
     * @return void
     *
     * @noinspection PhpUnusedParameterInspection the signature of the plugin is given
     */
    public function afterAdd(OrderProductOption $subject, mixed $result, OrderItemInterface $orderItem): void
    {
        if (! $orderItem instanceof Item) {
            return;
        }

        if ($orderItem->getParentItemId()) {
            $parentItem = $orderItem->getParentItem();

            if ($parentItem instanceof Item && $parentItem->getProductType() === BundleType::TYPE_CODE) {
                $this->addChildOptions($orderItem);
            }
        } elseif ($orderItem->getProductType() === BundleType::TYPE_CODE) {
            $this->removeChildOptions($orderItem);
        }
    }

    /**
     * Adds the custom options that the item of a selected product got to its product option.
     *
     * @param Item $orderItem
     *
     * @return void
     */
    private function addChildOptions(Item $orderItem): void
    {
        $optionIds = $this->getOptionIds($orderItem);

        if (count($optionIds) === 0) {
            return;
        }

        $request = $orderItem->getBuyRequest();
        $request->setDataUsingMethod('product_options', $orderItem->getProductOptions());

        $requestOptions = $request->getData('options');

        if (! is_array($requestOptions)) {
            return;
        }

        $request->setData(
            'options',
            array_filter(
                $requestOptions,
                fn(mixed $optionId): bool => in_array($this->getKey($optionId), $optionIds, true),
                ARRAY_FILTER_USE_KEY
            )
        );

        $customOptions = $this->productOptionProcessor->convertToProductOption($request)[ 'custom_options' ] ?? [];

        if (is_array($customOptions) && count($customOptions) > 0) {
            $this->setCustomOptions($orderItem, $customOptions);
        }
    }

    /**
     * Removes the custom options that the items of selected products got from the product option of the bundle item.
     *
     * @param Item $orderItem
     *
     * @return void
     */
    private function removeChildOptions(Item $orderItem): void
    {
        $childOptionIds = [];

        foreach ($orderItem->getChildrenItems() as $childOrderItem) {
            if ($childOrderItem instanceof Item) {
                $childOptionIds[] = $this->getOptionIds($childOrderItem);
            }
        }

        $childOptionIds = array_merge([], ...$childOptionIds);

        $extensionAttributes = $orderItem->getProductOption()?->getExtensionAttributes();
        $customOptions = $extensionAttributes?->getCustomOptions();

        if ($extensionAttributes === null || ! is_array($customOptions)) {
            return;
        }

        $extensionAttributes->setCustomOptions(
            array_values(
                array_filter(
                    $customOptions,
                    fn(CustomOptionInterface $customOption): bool => ! in_array(
                        $this->getKey($customOption->getOptionId()),
                        $childOptionIds,
                        true
                    )
                )
            )
        );
    }

    /**
     * Returns the IDs of the custom options in the product options of the order item.
     *
     * @param Item $orderItem
     *
     * @return string[]
     */
    private function getOptionIds(Item $orderItem): array
    {
        // getProductOptionByCode() returns null without options, which its PHPDoc does not declare
        $productOptions = $orderItem->getProductOptions()[ 'options' ] ?? null;
        $optionIds = [];

        foreach (is_array($productOptions) ? $productOptions : [] as $productOption) {
            $optionId = is_array($productOption) ? $productOption[ 'option_id' ] ?? null : null;

            if (is_scalar($optionId)) {
                $optionIds[] = $this->getKey($optionId);
            }
        }

        return $optionIds;
    }

    /**
     * Returns the ID of a custom option as text for comparisons.
     *
     * The IDs are numbers or texts, depending on where they come from, also where the PHPDoc declares texts (the option
     * ID of CustomOptionInterface is a number in custom options of the product option processor).
     *
     * @param mixed $optionId
     *
     * @return string
     */
    private function getKey(mixed $optionId): string
    {
        return is_scalar($optionId) ? (string)$optionId : '';
    }

    /**
     * Sets the custom options in the extension attributes of the product option of the item.
     *
     * @param Item $orderItem
     * @param CustomOptionInterface[] $customOptions
     *
     * @return void
     */
    private function setCustomOptions(Item $orderItem, array $customOptions): void
    {
        $productOption = $orderItem->getProductOption();

        if ($productOption === null) {
            $productOption = $this->productOptionFactory->create();

            $orderItem->setProductOption($productOption);
        }

        $extensionAttributes = $productOption->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();

            $productOption->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes->setCustomOptions($customOptions);
    }
}
