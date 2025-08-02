<?php

declare(strict_types=1);

namespace Infrangible\BundleOption\Plugin\Sales\Model\Order;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductOptionExtensionFactory;
use Magento\Catalog\Model\CustomOptions\CustomOption;
use Magento\Catalog\Model\ProductOptionFactory;
use Magento\Catalog\Model\ProductOptionProcessor;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ProductOption
{
    /** @var ProductOptionFactory */
    protected $productOptionFactory;

    /** @var ProductOptionExtensionFactory */
    protected $extensionFactory;

    /** @var ProductOptionProcessor */
    protected $productOptionProcessor;

    public function __construct(
        ProductOptionFactory $productOptionFactory,
        ProductOptionExtensionFactory $extensionFactory,
        ProductOptionProcessor $productOptionProcessor
    ) {
        $this->productOptionFactory = $productOptionFactory;
        $this->extensionFactory = $extensionFactory;
        $this->productOptionProcessor = $productOptionProcessor;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterAdd(
        \Magento\Sales\Model\Order\ProductOption $subject,
        $result,
        OrderItemInterface $orderItem
    ): void {
        if ($orderItem instanceof Item) {
            if ($orderItem->getParentItemId()) {
                if ($orderItem->getParentItem()->getProductType() === Type::TYPE_CODE) {
                    $productOptions = $orderItem->getProductOptionByCode('options');

                    if ($productOptions) {
                        $productOptionIds = [];

                        foreach ($productOptions as $productOption) {
                            $productOptionIds[] = $productOption[ 'option_id' ];
                        }

                        $request = $orderItem->getBuyRequest();

                        $request->setDataUsingMethod(
                            'product_options',
                            $orderItem->getProductOptions()
                        );

                        $requestOptions = $request->getData('options');

                        foreach (array_keys($requestOptions) as $requestOptionId) {
                            if (! in_array(
                                $requestOptionId,
                                $productOptionIds
                            )) {
                                unset($requestOptions[ $requestOptionId ]);
                            }
                        }

                        $request->setData(
                            'options',
                            $requestOptions
                        );

                        $data = $this->productOptionProcessor->convertToProductOption($request);

                        if ($data) {
                            $this->setProductOption(
                                $orderItem,
                                $data
                            );
                        }
                    }
                }
            } elseif ($orderItem->getProductType() === Type::TYPE_CODE) {
                $productOptionIds = [];

                foreach ($orderItem->getChildrenItems() as $childOrderItem) {
                    if ($childOrderItem instanceof Item) {
                        $productOptions = $childOrderItem->getProductOptionByCode('options');

                        if ($productOptions) {
                            foreach ($productOptions as $productOption) {
                                $productOptionIds[] = $productOption[ 'option_id' ];
                            }
                        }
                    }
                }

                $productOption = $orderItem->getProductOption();

                $productOptionExtensionAttributes = $productOption->getExtensionAttributes();

                if ($productOptionExtensionAttributes) {
                    $customOptions = $productOptionExtensionAttributes->getCustomOptions();

                    if ($customOptions) {
                        /** @var CustomOption $customOption */
                        foreach ($customOptions as $key => $customOption) {
                            if (in_array(
                                $customOption->getOptionId(),
                                $productOptionIds
                            )) {
                                unset($customOptions[ $key ]);
                            }
                        }
                    }

                    $productOptionExtensionAttributes->setCustomOptions($customOptions);

                    $productOption->setExtensionAttributes($productOptionExtensionAttributes);
                }
            }
        }
    }

    private function setProductOption(OrderItemInterface $orderItem, array $data): void
    {
        $productOption = $orderItem->getProductOption();

        if (! $productOption) {
            $productOption = $this->productOptionFactory->create();

            $orderItem->setProductOption($productOption);
        }

        $extensionAttributes = $productOption->getExtensionAttributes();

        if (! $extensionAttributes) {
            $extensionAttributes = $this->extensionFactory->create();

            $productOption->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes->setData(
            key($data),
            current($data)
        );
    }
}
