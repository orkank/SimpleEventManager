<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Model;

use Magento\Framework\Model\AbstractModel;
use IDangerous\SimpleEventManager\Model\ResourceModel\Registration as RegistrationResource;

class Registration extends AbstractModel
{
    /**
     * Registration cache tag
     */
    const CACHE_TAG = 'idangerous_event_registration';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'idangerous_event_registration';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(RegistrationResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}