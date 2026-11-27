<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Test\Unit\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Infrangible\BundleOption\Helper\Data;
use Infrangible\BundleOption\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

/**
 * Tests that custom options posted without bundle options lose their bundle options.
 */
class HelperTest extends TestCase
{
    /**
     * Posted custom options of bundle products without the field get no bundle options; posted bundle options and
     * other product types are kept.
     *
     * @return void
     */
    public function testBeforeInitializeFromData(): void
    {
        $plugin = new Helper();
        $subject = $this->createStub(InitializationHelper::class);
        $product = $this->createStub(Product::class);
        $options = [
            ['option_id' => '1', 'title' => 'Strap Engraving'],
            ['option_id' => '2', 'title' => 'Gift Note', Data::BUNDLE_OPTION_IDS => ['3']]
        ];

        [, $bundleData] = $plugin->beforeInitializeFromData(
            $subject,
            $product,
            ['type_id' => 'bundle', 'options' => $options]
        );

        $this->assertSame(
            [
                ['option_id' => '1', 'title' => 'Strap Engraving', Data::BUNDLE_OPTION_IDS => []],
                ['option_id' => '2', 'title' => 'Gift Note', Data::BUNDLE_OPTION_IDS => ['3']]
            ],
            $bundleData['options']
        );

        [, $simpleData] = $plugin->beforeInitializeFromData(
            $subject,
            $product,
            ['type_id' => 'simple', 'options' => $options]
        );

        $this->assertSame($options, $simpleData['options']);
    }
}
