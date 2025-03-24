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
use Magento\Framework\Serialize\Serializer\Json;

class EventCalendar extends Template
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
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @param Context $context
     * @param CollectionFactory $eventCollectionFactory
     * @param TimezoneInterface $timezone
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $eventCollectionFactory,
        TimezoneInterface $timezone,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->timezone = $timezone;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $data);
    }

    /**
     * Get events as JSON for calendar
     *
     * @return string
     */
    public function getEventsJson()
    {
        $collection = $this->eventCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $collection->addFieldToFilter('publish_date', ['lteq' => $now]);

        $events = [];
        foreach ($collection as $event) {
            $eventDate = new \DateTime($event->getEventDate());
            $events[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
                'date' => $eventDate->format('Y-m-d'),
                'url' => $this->getUrl('events/event/view', ['id' => $event->getId()])
            ];
        }

        return $this->jsonSerializer->serialize($events);
    }
}