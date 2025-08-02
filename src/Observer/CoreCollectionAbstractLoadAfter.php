<?php

declare(strict_types=1);

namespace Infrangible\BundleOption\Observer;

use Infrangible\BundleOption\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
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
                $bundleOptionIds = $object->getData(CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME);

                if ($bundleOptionIds && ! is_array($bundleOptionIds)) {
                    $object->setData(
                        CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME,
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
