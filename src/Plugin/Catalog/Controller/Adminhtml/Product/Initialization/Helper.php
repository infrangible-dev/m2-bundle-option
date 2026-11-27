<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Infrangible\BundleOption\Helper\Data;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Model\Product;

/**
 * Removes the bundle options of custom options in the product form of the admin.
 */
class Helper
{
    /**
     * Sets no bundle options for the posted custom options of bundle products without bundle options.
     *
     * The form does not post the field if no bundle option is selected, so the saved bundle options of the custom
     * option would be kept.
     *
     * @param InitializationHelper $subject
     * @param Product $product
     * @param array $productData
     * @phpstan-param array<string, mixed> $productData
     *
     * @return array
     * @phpstan-return array{Product, array<string, mixed>}
     *
     * @noinspection PhpUnusedParameterInspection the signature of the plugin is given
     */
    public function beforeInitializeFromData(
        InitializationHelper $subject,
        Product $product,
        array $productData
    ): array {
        $options = $productData[ 'options' ] ?? null;
        $typeId = $productData[ 'type_id' ] ?? $product->getTypeId();

        if ($typeId !== BundleType::TYPE_CODE || ! is_array($options)) {
            return [$product, $productData];
        }

        foreach ($options as $key => $option) {
            if (is_array($option) && ! array_key_exists(Data::BUNDLE_OPTION_IDS, $option)) {
                $options[ $key ][ Data::BUNDLE_OPTION_IDS ] = [];
            }
        }

        $productData[ 'options' ] = $options;

        return [$product, $productData];
    }
}
