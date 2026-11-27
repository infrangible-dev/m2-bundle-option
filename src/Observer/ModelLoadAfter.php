<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Observer;

use Infrangible\BundleOption\Helper\Data;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Converts the bundle options of a custom option that is loaded on its own into a list.
 */
class ModelLoadAfter implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Converts the bundle options of the loaded custom option into a list; options without bundle options keep null.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $option = $observer->getData('object');

        if ($option instanceof Option && $option->getData(Data::BUNDLE_OPTION_IDS) !== null) {
            $option->setData(Data::BUNDLE_OPTION_IDS, $this->helper->getBundleOptionIds($option));
        }
    }
}
