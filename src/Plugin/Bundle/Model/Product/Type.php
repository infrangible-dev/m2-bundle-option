<?php

declare(strict_types=1);

namespace Infrangible\BundleOption\Plugin\Bundle\Model\Product;

use Magento\Catalog\Model\Product;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Type
{
    public function afterPrepareForCartAdvanced(\Magento\Bundle\Model\Product\Type $subject, $result)
    {
        if (is_array($result)) {
            $bundleOptionIds = [];

            foreach ($result as $product) {
                if ($product instanceof Product) {
                    if ($product->getTypeId() === $subject::TYPE_CODE) {
                        $customOptionIds = $product->getCustomOption('option_ids');

                        if ($customOptionIds) {
                            $customOptionIdsValue = $customOptionIds->getValue();

                            if ($customOptionIdsValue) {
                                $optionIds = explode(
                                    ',',
                                    $customOptionIdsValue
                                );

                                foreach ($optionIds as $optionId) {
                                    $customOption = $product->getCustomOption(
                                        sprintf(
                                            'option_%d',
                                            $optionId
                                        )
                                    );

                                    $optionValue = $customOption->getValue();

                                    $productOption = $product->getOptionById($optionId);

                                    $bundleOptionIds = $productOption->getData('bundle_option_ids');

                                    if ($bundleOptionIds) {
                                        foreach ($bundleOptionIds as $bundleOptionId) {
                                            $bundleOptionIds[ $bundleOptionId ][ $optionId ] = $optionValue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (count($bundleOptionIds) > 0) {
                foreach ($result as $product) {
                    if ($product instanceof Product) {
                        if ($product->getTypeId() !== $subject::TYPE_CODE) {
                            $optionId = $product->getData('option_id');

                            if (array_key_exists(
                                $optionId,
                                $bundleOptionIds
                            )) {
                                $customOptions = $bundleOptionIds[ $optionId ];

                                $product->addCustomOption(
                                    'option_ids',
                                    implode(
                                        ',',
                                        array_keys($customOptions)
                                    )
                                );

                                foreach ($customOptions as $customOptionId => $customOptionValue) {
                                    $product->addCustomOption(
                                        sprintf(
                                            'option_%d',
                                            $customOptionId
                                        ),
                                        $customOptionValue
                                    );
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
