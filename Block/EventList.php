<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class EventList extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Context $context
     * @param CollectionFactory $eventCollectionFactory
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $eventCollectionFactory,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Get event collection
     *
     * @return \IDangerous\SimpleEventManager\Model\ResourceModel\Event\Collection
     */
    public function getEventCollection()
    {
        $collection = $this->eventCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $collection->addFieldToFilter('publish_date', ['lteq' => $now]);
        $collection->setOrder('event_date', 'ASC');

        return $collection;
    }

    /**
     * Get event URL
     *
     * @param \IDangerous\SimpleEventManager\Model\Event $event
     * @return string
     */
    public function getEventUrl($event)
    {
        return $this->getUrl('events/event/view', ['id' => $event->getId()]);
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
    public function formatDate($date = null, $format = \IntlDateFormatter::MEDIUM, $showTime = true, $timezone = null)
    {
        return $this->timezone->formatDateTime(
            $date,
            $format,
            $showTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE,
            null,
            $timezone
        );
    }
}