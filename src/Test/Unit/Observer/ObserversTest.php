<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Test\Unit\Observer;

use Infrangible\BundleOption\Helper\Data;
use Infrangible\BundleOption\Observer\CoreCollectionAbstractLoadAfter;
use Infrangible\BundleOption\Observer\ModelLoadAfter;
use Infrangible\BundleOption\Observer\ModelSaveBefore;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests converting the bundle options of custom options when they are loaded and saved.
 */
#[AllowMockObjectsWithoutExpectations]
class ObserversTest extends TestCase
{
    /**
     * Returns a custom option with the data.
     *
     * @param array<string, mixed> $data
     *
     * @return Option
     */
    private function createOption(array $data): Option
    {
        $option = $this->getMockBuilder(Option::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $option->setData($data);

        return $option;
    }

    /**
     * A loaded option gets a list; an option without bundle options keeps null (before, it failed with a TypeError).
     *
     * @return void
     */
    public function testModelLoadAfter(): void
    {
        $observer = new ModelLoadAfter(new Data());

        $linked = $this->createOption([Data::BUNDLE_OPTION_IDS => '3,1']);
        $observer->execute(new Observer(['object' => $linked]));
        $this->assertSame(['3', '1'], $linked->getData(Data::BUNDLE_OPTION_IDS));

        $unlinked = $this->createOption([Data::BUNDLE_OPTION_IDS => null]);
        $observer->execute(new Observer(['object' => $unlinked]));
        $this->assertNull($unlinked->getData(Data::BUNDLE_OPTION_IDS));
    }

    /**
     * The options of a loaded option collection get lists.
     *
     * @return void
     */
    public function testCollectionLoadAfter(): void
    {
        $linked = $this->createOption([Data::BUNDLE_OPTION_IDS => '3']);
        $unlinked = $this->createOption([Data::BUNDLE_OPTION_IDS => null]);

        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$linked, $unlinked]));

        (new CoreCollectionAbstractLoadAfter(new Data()))->execute(new Observer(['collection' => $collection]));

        $this->assertSame(['3'], $linked->getData(Data::BUNDLE_OPTION_IDS));
        $this->assertNull($unlinked->getData(Data::BUNDLE_OPTION_IDS));
    }

    /**
     * Saved lists become comma-separated texts, empty lists null; options without the field and other objects are not
     * changed.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testModelSaveBefore(): void
    {
        $observer = new ModelSaveBefore(new Data());

        $linked = $this->createOption([Data::BUNDLE_OPTION_IDS => ['3', '1']]);
        $observer->execute(new Observer(['object' => $linked]));
        $this->assertSame('3,1', $linked->getData(Data::BUNDLE_OPTION_IDS));

        $unlinked = $this->createOption([Data::BUNDLE_OPTION_IDS => []]);
        $observer->execute(new Observer(['object' => $unlinked]));
        $this->assertNull($unlinked->getData(Data::BUNDLE_OPTION_IDS));
        $this->assertTrue($unlinked->hasData(Data::BUNDLE_OPTION_IDS));

        $untouched = $this->createOption(['title' => 'Gift Note']);
        $observer->execute(new Observer(['object' => $untouched]));
        $this->assertFalse($untouched->hasData(Data::BUNDLE_OPTION_IDS));

        $other = new DataObject([Data::BUNDLE_OPTION_IDS => ['x']]);
        $observer->execute(new Observer(['object' => $other]));
        $this->assertSame(['x'], $other->getData(Data::BUNDLE_OPTION_IDS));
    }

    /**
     * Bundle option IDs that are no numbers are rejected.
     *
     * @return void
     * @throws LocalizedException
     */
    public function testInvalidBundleOptionIds(): void
    {
        $this->expectException(LocalizedException::class);

        (new ModelSaveBefore(new Data()))->execute(
            new Observer(
                ['object' => $this->createOption(['title' => 'Engraving', Data::BUNDLE_OPTION_IDS => ['3', 'x']])]
            )
        );
    }
}
