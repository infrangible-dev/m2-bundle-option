<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\BundleOption\Helper;

use Magento\Framework\DataObject;

/**
 * The bundle options that custom options of bundle products are linked to.
 *
 * A custom option stores the IDs of its bundle options in "bundle_option_ids", in the database as comma-separated
 * list, in the loaded option as list. The products selected in these bundle options get the value of the custom option
 * in the cart and in orders.
 */
class Data
{
    /** Field of custom options with the IDs of their bundle options. */
    public const BUNDLE_OPTION_IDS = 'bundle_option_ids';

    /**
     * Returns the IDs of the bundle options of the custom option.
     *
     * They are read from the list or from the comma-separated text of the database; empty and duplicate values are
     * left out.
     *
     * @param DataObject $option
     *
     * @return string[]
     */
    public function getBundleOptionIds(DataObject $option): array
    {
        $bundleOptionIds = $option->getData(self::BUNDLE_OPTION_IDS);

        if (is_string($bundleOptionIds)) {
            $bundleOptionIds = explode(',', $bundleOptionIds);
        }

        if (! is_array($bundleOptionIds)) {
            return [];
        }

        $ids = [];

        foreach ($bundleOptionIds as $bundleOptionId) {
            $id = is_scalar($bundleOptionId) ? trim((string)$bundleOptionId) : '';

            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
