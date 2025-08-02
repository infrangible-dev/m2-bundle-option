<?php

declare(strict_types=1);

namespace Infrangible\BundleOption\Setup;

use Infrangible\BundleOption\Plugin\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @throws \Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $optionTableName = $connection->getTableName('catalog_product_option');

        if (! $connection->tableColumnExists(
            $optionTableName,
            CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME
        )) {
            $connection->addColumn(
                $optionTableName,
                CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME,
                ['type' => Table::TYPE_TEXT, 'length' => 255, 'nullable' => true, 'comment' => 'Bundle Option Ids']
            );
        }

        $setup->endSetup();
    }
}
