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
use Magento\Framework\Exception\LocalizedException;

/**
 * Validates the bundle options of a saved custom option and stores them as comma-separated list.
 */
class ModelSaveBefore implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Validates the bundle options of the saved custom option and stores them as comma-separated list.
     *
     * Without bundle options the field is null.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $option = $observer->getData('object');

        if (! $option instanceof Option || ! $option->hasData(Data::BUNDLE_OPTION_IDS)) {
            return;
        }

        $bundleOptionIds = $this->helper->getBundleOptionIds($option);

        foreach ($bundleOptionIds as $bundleOptionId) {
            if (preg_match('/^\d+$/', $bundleOptionId) !== 1) {
                throw new LocalizedException(
                    __('No valid bundle option ID(s) were provided for the option with title: %1', $option->getTitle())
                );
            }
        }

        $option->setData(
            Data::BUNDLE_OPTION_IDS,
            count($bundleOptionIds) > 0 ? implode(',', $bundleOptionIds) : null
        );
    }
}
