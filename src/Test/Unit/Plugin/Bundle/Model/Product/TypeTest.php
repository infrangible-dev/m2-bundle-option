<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Test\Unit\Plugin\Bundle\Model\Product;

use Infrangible\BundleOption\Helper\Data;
use Infrangible\BundleOption\Plugin\Bundle\Model\Product\Type;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Catalog\Model\Product\Option;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests passing the values of linked custom options to the products of bundle options in the cart.
 */
#[AllowMockObjectsWithoutExpectations]
class TypeTest extends TestCase
{
    /** @var array<string, array<string, mixed>> the custom options added to the products by SKU */
    private array $added = [];

    /**
     * Returns a product of the cart with the type, the bundle option and the custom options of the cart item.
     *
     * @param string $sku
     * @param string $typeId
     * @param string|null $bundleOptionId
     * @param array<string, string> $customOptions
     * @param array<int, Option> $options the custom options of the product by ID
     *
     * @return Product
     */
    private function createProduct(
        string $sku,
        string $typeId,
        ?string $bundleOptionId,
        array $customOptions = [],
        array $options = []
    ): Product {
        $customOptionStubs = [];

        foreach ($customOptions as $code => $value) {
            $customOption = $this->createStub(OptionInterface::class);
            $customOption->method('getValue')->willReturn($value);
            $customOptionStubs[ $code ] = $customOption;
        }

        $product = $this->createStub(Product::class);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getData')->willReturnCallback(
            fn(string $key = ''): ?string => $key === 'option_id' ? $bundleOptionId : null
        );
        $product->method('getCustomOption')->willReturnCallback(
            fn(string $code): ?OptionInterface => $customOptionStubs[ $code ] ?? null
        );
        $product->method('getOptionById')->willReturnCallback(fn(int $id): ?Option => $options[ $id ] ?? null);
        $product->method('addCustomOption')->willReturnCallback(
            function (string $code, mixed $value) use ($sku, $product): Product {
                $this->added[ $sku ][ $code ] = $value;

                return $product;
            }
        );

        return $product;
    }

    /**
     * Returns a custom option linked to the bundle options.
     *
     * @param string|null $bundleOptionIds
     *
     * @return Option
     */
    private function createOption(?string $bundleOptionIds): Option
    {
        $option = $this->getMockBuilder(Option::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $option->setData(Data::BUNDLE_OPTION_IDS, $bundleOptionIds);

        return $option;
    }

    /**
     * The products of the linked bundle options get the values of the linked custom options; custom options without
     * value or without option are skipped.
     *
     * @return void
     */
    public function testAfterPrepareForCartAdvanced(): void
    {
        $bundle = $this->createProduct(
            'kit',
            BundleType::TYPE_CODE,
            null,
            ['option_ids' => '1,2,5', 'option_1' => 'ENGRAVED', 'option_2' => 'Happy birthday', 'option_6' => 'x'],
            [1 => $this->createOption('3,1'), 2 => $this->createOption(null)]
        );
        $result = [
            $bundle,
            $this->createProduct('ball', 'simple', '1'),
            $this->createProduct('brick', 'simple', '2'),
            $this->createProduct('strap', 'simple', '3')
        ];

        $this->assertSame(
            $result,
            (new Type(new Data()))->afterPrepareForCartAdvanced(
                $this->createStub(BundleType::class),
                $result
            )
        );
        $this->assertSame(
            [
                'ball'  => ['option_ids' => '1', 'option_1' => 'ENGRAVED'],
                'strap' => ['option_ids' => '1', 'option_1' => 'ENGRAVED']
            ],
            $this->added
        );
    }

    /**
     * Error messages are returned unchanged.
     *
     * @return void
     */
    public function testErrorMessage(): void
    {
        $this->assertSame(
            'Please specify product option(s).',
            (new Type(new Data()))->afterPrepareForCartAdvanced(
                $this->createStub(BundleType::class),
                'Please specify product option(s).'
            )
        );
    }
}
