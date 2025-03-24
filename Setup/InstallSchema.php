<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Create events table
        $table = $installer->getConnection()->newTable(
            $installer->getTable('idangerous_event')
        )->addColumn(
            'event_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Event ID'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Event Name'
        )->addColumn(
            'content',
            Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Event Content'
        )->addColumn(
            'event_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Event Date'
        )->addColumn(
            'publish_date',
            Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Publish Date'
        )->addColumn(
            'category',
            Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Event Category'
        )->addColumn(
            'photos',
            Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Event Photos'
        )->addColumn(
            'join_form_enabled',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Join Form Enabled'
        )->addColumn(
            'participation_limit',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Participation Limit'
        )->addColumn(
            'countdown_enabled',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Countdown Enabled'
        )->addColumn(
            'is_active',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 1],
            'Is Active'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->setComment(
            'IDangerous Event Table'
        );
        $installer->getConnection()->createTable($table);

        // Create event registrations table
        $table = $installer->getConnection()->newTable(
            $installer->getTable('idangerous_event_registration')
        )->addColumn(
            'registration_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Registration ID'
        )->addColumn(
            'event_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Event ID'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Name'
        )->addColumn(
            'email',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Email'
        )->addColumn(
            'phone',
            Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Phone'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        )->addIndex(
            $installer->getIdxName('idangerous_event_registration', ['event_id']),
            ['event_id']
        )->addForeignKey(
            $installer->getFkName('idangerous_event_registration', 'event_id', 'idangerous_event', 'event_id'),
            'event_id',
            $installer->getTable('idangerous_event'),
            'event_id',
            Table::ACTION_CASCADE
        )->setComment(
            'IDangerous Event Registration Table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}