<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Test\Unit\Helper;

use Infrangible\BundleOption\Helper\Data;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests reading the bundle options of custom options.
 */
class DataTest extends TestCase
{
    /**
     * Lists and comma-separated texts give the IDs without empty and duplicate values; other values give none.
     *
     * @return void
     */
    public function testGetBundleOptionIds(): void
    {
        $helper = new Data();

        $this->assertSame(
            ['3', '1'],
            $helper->getBundleOptionIds(new DataObject([Data::BUNDLE_OPTION_IDS => '3, 1,,3']))
        );
        $this->assertSame(
            ['3', '4'],
            $helper->getBundleOptionIds(new DataObject([Data::BUNDLE_OPTION_IDS => [3, '4', '']]))
        );
        $this->assertSame([], $helper->getBundleOptionIds(new DataObject([Data::BUNDLE_OPTION_IDS => null])));
        $this->assertSame([], $helper->getBundleOptionIds(new DataObject()));
    }
}
