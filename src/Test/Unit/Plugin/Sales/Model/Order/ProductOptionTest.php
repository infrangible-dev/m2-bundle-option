<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Test\Unit\Plugin\Sales\Model\Order;

use Infrangible\BundleOption\Plugin\Sales\Model\Order\ProductOption;
use Magento\Catalog\Api\Data\CustomOptionInterface;
use Magento\Catalog\Api\Data\ProductOptionExtensionFactory;
use Magento\Catalog\Api\Data\ProductOptionExtensionInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Catalog\Model\ProductOptionFactory;
use Magento\Catalog\Model\ProductOptionProcessor;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ProductOption as OrderProductOption;
use PHPUnit\Framework\TestCase;

/**
 * Tests moving linked custom options from the bundle item to the items of the selected products.
 */
class ProductOptionTest extends TestCase
{
    /**
     * Returns a custom option of the product option with the ID, a number like the product option processor sets it.
     *
     * @param int $optionId
     *
     * @return CustomOptionInterface
     */
    private function createCustomOption(int $optionId): CustomOptionInterface
    {
        $customOption = $this->createStub(CustomOptionInterface::class);
        $customOption->method('getOptionId')->willReturn($optionId);

        return $customOption;
    }

    /**
     * The bundle item loses the custom options that the items of its selected products have, also if the IDs are
     * numbers on one side and texts on the other.
     *
     * @return void
     */
    public function testBundleItem(): void
    {
        $engraving = $this->createCustomOption(1);
        $note = $this->createCustomOption(2);
        $saved = null;

        $extensionAttributes = $this->createStub(ProductOptionExtensionInterface::class);
        $extensionAttributes->method('getCustomOptions')->willReturn([$engraving, $note]);
        $extensionAttributes->method('setCustomOptions')->willReturnCallback(
            function (array $customOptions) use (&$saved, $extensionAttributes): ProductOptionExtensionInterface {
                $saved = $customOptions;

                return $extensionAttributes;
            }
        );

        $productOption = $this->createStub(ProductOptionInterface::class);
        $productOption->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $strapItem = $this->createStub(Item::class);
        $strapItem->method('getProductOptions')->willReturn(['options' => [['option_id' => '1', 'value' => 'X']]]);

        $ballItem = $this->createStub(Item::class);
        $ballItem->method('getProductOptions')->willReturn([]);

        $bundleItem = $this->createStub(Item::class);
        $bundleItem->method('getParentItemId')->willReturn(null);
        $bundleItem->method('getProductType')->willReturn('bundle');
        $bundleItem->method('getChildrenItems')->willReturn([$strapItem, $ballItem]);
        $bundleItem->method('getProductOption')->willReturn($productOption);

        (new ProductOption(
            $this->createStub(ProductOptionFactory::class),
            $this->createStub(ProductOptionExtensionFactory::class),
            $this->createStub(ProductOptionProcessor::class)
        ))->afterAdd($this->createStub(OrderProductOption::class), null, $bundleItem);

        $this->assertSame([$note], $saved);
    }
}
