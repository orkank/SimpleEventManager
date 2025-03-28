<?php
/**
 * Simple Event Manager configuration helper
 */
namespace IDangerous\SimpleEventManager\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_ENABLED = 'simpleeventmanager/general/enabled';
    const XML_PATH_INDEX_SLUG = 'simpleeventmanager/general/index_slug';
    const XML_PATH_EVENT_MASTER_SLUG = 'simpleeventmanager/general/event_master_slug';
    const XML_PATH_ENABLE_CALENDAR = 'simpleeventmanager/general/enable_calendar';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get events index slug
     *
     * @param int|null $storeId
     * @return string
     */
    public function getIndexSlug($storeId = null)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_INDEX_SLUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get event master slug
     *
     * @param int|null $storeId
     * @return string
     */
    public function getEventMasterSlug($storeId = null)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_EVENT_MASTER_SLUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if calendar widget is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCalendarEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_CALENDAR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}