<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Plugin\Bundle\Model\Product;

use Infrangible\BundleOption\Helper\Data;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;

/**
 * Passes the values of custom options of a bundle product to the products selected in the linked bundle options.
 */
class Type
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Adds the custom options of the bundle product that are linked to bundle options to the products selected in them,
     * with their values: the products of the cart items get "option_ids" and "option_<id>" like the custom options of
     * their own.
     *
     * @param BundleType $subject
     * @param mixed $result the products of the cart items, or an error message
     *
     * @return mixed
     *
     * @noinspection PhpUnusedParameterInspection the signature of the plugin is given
     */
    public function afterPrepareForCartAdvanced(BundleType $subject, mixed $result): mixed
    {
        if (! is_array($result)) {
            return $result;
        }

        $valuesByBundleOption = [];

        foreach ($result as $product) {
            if ($product instanceof Product && $product->getTypeId() === BundleType::TYPE_CODE) {
                foreach ($this->getLinkedOptionValues($product) as $bundleOptionId => $values) {
                    $valuesByBundleOption[ $bundleOptionId ] =
                        ($valuesByBundleOption[ $bundleOptionId ] ?? []) + $values;
                }
            }
        }

        if (count($valuesByBundleOption) === 0) {
            return $result;
        }

        foreach ($result as $product) {
            if (! $product instanceof Product || $product->getTypeId() === BundleType::TYPE_CODE) {
                continue;
            }

            $bundleOptionId = $product->getData('option_id');
            $values = is_scalar($bundleOptionId) ? $valuesByBundleOption[ (string)$bundleOptionId ] ?? [] : [];

            if (count($values) === 0) {
                continue;
            }

            $product->addCustomOption('option_ids', implode(',', array_keys($values)));

            foreach ($values as $customOptionId => $value) {
                $product->addCustomOption(sprintf('option_%d', $customOptionId), $value);
            }
        }

        return $result;
    }

    /**
     * Returns the values of the custom options of the bundle product that are linked to bundle options.
     *
     * The values are grouped by bundle option ID and custom option ID.
     *
     * @param Product $product
     *
     * @return array<string, array<int, mixed>>
     */
    private function getLinkedOptionValues(Product $product): array
    {
        $optionIds = $product->getCustomOption('option_ids')?->getValue();

        if (! is_string($optionIds) || $optionIds === '') {
            return [];
        }

        $values = [];

        foreach (explode(',', $optionIds) as $optionId) {
            if (! is_numeric($optionId)) {
                continue;
            }

            $customOptionId = (int)$optionId;
            $customOption = $product->getCustomOption(sprintf('option_%d', $customOptionId));
            $productOption = $product->getOptionById($customOptionId);

            if ($customOption === null || $productOption === null) {
                continue;
            }

            foreach ($this->helper->getBundleOptionIds($productOption) as $bundleOptionId) {
                $values[ $bundleOptionId ][ $customOptionId ] = $customOption->getValue();
            }
        }

        return $values;
    }
}
