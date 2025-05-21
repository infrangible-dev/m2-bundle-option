<?php

declare(strict_types=1);

namespace Infrangible\BundleOption\Observer;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CoreCollectionAbstractLoadAfter implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $collection = $observer->getData('collection');

        if ($collection instanceof Collection) {
            /** @var Option $object */
            foreach ($collection as $object) {
                $bundleOptionIds = $object->getData('bundle_option_ids');

                if ($bundleOptionIds && ! is_array($bundleOptionIds)) {
                    $object->setData(
                        'bundle_option_ids',
                        explode(
                            ',',
                            $bundleOptionIds
                        )
                    );
                }
            }
        }
    }
}
