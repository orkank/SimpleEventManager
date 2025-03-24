<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addPreviewImageField($setup);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addShortDescriptionField($setup);
        }

        $setup->endSetup();
    }

    /**
     * Add preview_image field to idangerous_event table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function addPreviewImageField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->addColumn(
            $setup->getTable('idangerous_event'),
            'preview_image',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Preview Image'
            ]
        );
    }

    /**
     * Add short_description field to idangerous_event table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function addShortDescriptionField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->addColumn(
            $setup->getTable('idangerous_event'),
            'short_description',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Short Description'
            ]
        );
    }
}