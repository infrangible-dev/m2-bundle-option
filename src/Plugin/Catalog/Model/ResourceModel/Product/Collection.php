<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\BundleOption\Plugin\Catalog\Model\ResourceModel\Product;

use Infrangible\BundleOption\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Infrangible\Core\Helper\Stores;
use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\OptionFactory;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Collection
{
    /** @var OptionFactory */
    protected $productOptionFactory;

    /** @var Stores */
    protected $storeHelper;

    public function __construct(OptionFactory $productOptionFactory, Stores $storeHelper)
    {
        $this->productOptionFactory = $productOptionFactory;
        $this->storeHelper = $storeHelper;
    }

    public function afterAddOptionsToResult(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $subject,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $result
    ): \Magento\Catalog\Model\ResourceModel\Product\Collection {
        $bundleData = [];

        /** @var Product $product */
        foreach ($subject as $product) {
            if ($product->getTypeId() === Type::TYPE_CODE) {
                /** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
                $productResource = $product->getResource();

                $productId = $product->getData($productResource->getLinkField());

                /** @var Type $typeInstance */
                $typeInstance = $product->getTypeInstance();

                $bundleOptions = $typeInstance->getOptionsCollection($product);

                /** @var Option $bundleOptions */
                foreach ($bundleOptions as $bundleOption) {
                    $bundleOptionSelections = $typeInstance->getSelectionsCollection(
                        [$bundleOption->getId()],
                        $product
                    );

                    $bundleData[ $productId ][ $bundleOption->getId() ] = $bundleOptionSelections;
                }
            }
        }

        if (! empty($bundleData)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $optionsCollection */
            $optionsCollection = $this->productOptionFactory->create()->getCollection();

            $options = $optionsCollection->addTitleToResult($this->storeHelper->getStoreId());

            $optionsCollection->addPriceToResult($this->storeHelper->getStoreId());
            $optionsCollection->addProductToFilter(array_keys($bundleData));
            $optionsCollection->addValuesToResult();

            /** @var Product\Option $option */
            foreach ($options as $option) {
                $bundleOptionIds = $option->getData(CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME);

                if ($bundleOptionIds) {
                    $productId = $option->getProductId();

                    if (array_key_exists(
                        $productId,
                        $bundleData
                    )) {
                        foreach ($bundleData[ $productId ] as $bundleOptionId => $bundleOptionSelections) {
                            if (in_array(
                                $bundleOptionId,
                                $bundleOptionIds
                            )) {
                                /** @var Product $bundleOptionSelection */
                                foreach ($bundleOptionSelections as $bundleOptionSelection) {
                                    /** @var Product $product */
                                    foreach ($subject as $product) {
                                        if ($product->getId() == $bundleOptionSelection->getId()) {
                                            $product->addOption($option);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
