<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Observer;

use Infrangible\BundleOption\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Converts the bundle options of the custom options of a loaded collection into lists.
 */
class CoreCollectionAbstractLoadAfter implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Converts the bundle options of the custom options of the loaded collection into lists.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $collection = $observer->getData('collection');

        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $option) {
            if ($option instanceof DataObject && $option->getData(Data::BUNDLE_OPTION_IDS) !== null) {
                $option->setData(Data::BUNDLE_OPTION_IDS, $this->helper->getBundleOptionIds($option));
            }
        }
    }
}
