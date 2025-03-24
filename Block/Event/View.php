<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block\Event;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;

class View extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var CollectionFactory
     */
    protected $_eventCollectionFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $eventCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        TimezoneInterface $timezone,
        CollectionFactory $eventCollectionFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->timezone = $timezone;
        $this->_eventCollectionFactory = $eventCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get current event
     *
     * @return \IDangerous\SimpleEventManager\Model\Event
     */
    public function getEvent()
    {
        return $this->coreRegistry->registry('current_event');
    }

    /**
     * Format date
     *
     * @param string|null $date
     * @param int $format
     * @param bool $showTime
     * @param string|null $timezone
     * @return string
     */
    public function formatDate($date = null, $format = \IntlDateFormatter::LONG, $showTime = true, $timezone = null)
    {
        return $this->timezone->formatDateTime(
            $date,
            $format,
            $showTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }

    /**
     * Get event photos as array
     *
     * @return array
     */
    public function getEventPhotos()
    {
        $photos = [];
        $event = $this->getEvent();
        if ($event && $event->getPhotos()) {
            $photoUrls = explode("\n", $event->getPhotos());
            foreach ($photoUrls as $url) {
                $url = trim($url);
                if (!empty($url)) {
                    $photos[] = $url;
                }
            }
        }
        return $photos;
    }

    /**
     * Get countdown target date in JavaScript format
     *
     * @return string
     */
    public function getCountdownTargetDate()
    {
        $event = $this->getEvent();
        if ($event && $event->getEventDate()) {
            $date = new \DateTime($event->getEventDate());
            return $date->format('Y/m/d H:i:s');
        }
        return '';
    }

    /**
     * Get latest events for the sidebar
     *
     * @param int $limit Number of events to fetch
     * @return array|null
     */
    public function getLatestEvents($limit = 5)
    {
        try {
            $currentEventId = $this->getEvent()->getId();
            $collection = $this->_eventCollectionFactory->create();

            // Add filters to get upcoming events
            $collection->addFieldToFilter('is_active', 1);

            // Exclude current event if we have an ID
            if ($currentEventId) {
                $collection->addFieldToFilter('event_id', ['neq' => $currentEventId]);
            }

            // Only future and current events
            $collection->addFieldToFilter('event_date', ['gteq' => date('Y-m-d')]);

            // Order by closest date first
            $collection->setOrder('event_date', 'ASC');
            $collection->setPageSize($limit);

            return $collection->getItems();
        } catch (\Exception $e) {
            $this->_logger->error('Error getting latest events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get URL for a specific event
     *
     * @param \IDangerous\SimpleEventManager\Model\Event $event
     * @return string
     */
    public function getEventUrl($event)
    {
        return $this->getUrl('events/event/view', ['id' => $event->getId()]);
    }
}