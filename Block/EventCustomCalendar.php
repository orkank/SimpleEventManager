<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block;

use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Registry;

class EventCustomCalendar extends EventCalendar
{
    /**
     * @var \IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory
     */
    protected $_eventCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var Json
     */
    protected $_jsonSerializer;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * Constructor
     *
     * @param Context $context
     * @param CollectionFactory $eventCollectionFactory
     * @param TimezoneInterface $timezone
     * @param Json $jsonSerializer
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $eventCollectionFactory,
        TimezoneInterface $timezone,
        Json $jsonSerializer,
        Registry $registry,
        array $data = []
    ) {
        $this->_eventCollectionFactory = $eventCollectionFactory;
        $this->_timezone = $timezone;
        $this->_jsonSerializer = $jsonSerializer;
        $this->_registry = $registry;
        parent::__construct($context, $eventCollectionFactory, $timezone, $jsonSerializer, $data);
    }

    /**
     * Get events with debugging information
     *
     * @return \IDangerous\SimpleEventManager\Model\ResourceModel\Event\Collection
     */
    public function getEvents()
    {
        // Get current date and time
        $now = new \DateTime();
        $currentDate = $now->format('Y-m-d H:i:s');

        // Get collection factory
        $collection = $this->_eventCollectionFactory->create();

        // Add filters
        $collection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('publish_date', ['lteq' => $currentDate])
            ->setOrder('event_date', 'ASC');

        // Debug - add this to log what's happening
        $collectionSize = $collection->getSize();
        $sql = $collection->getSelect()->__toString();

        return $collection;
    }

    /**
     * Get events as formatted array for JavaScript calendar
     *
     * @return string
     */
    public function getEventsForCalendar()
    {
        try {
            $events = $this->getEvents();
            $result = [];
            $addedEvents = []; // To track already added events and prevent duplicates

            // Debug information
            $debug = [
                'collection_size' => $events ? $events->getSize() : 0,
                'collection_sql' => $events ? $events->getSelect()->__toString() : 'No collection',
            ];

            // Add debug log to see what's happening
            error_log('EventCustomCalendar Debug: Collection size = ' . $debug['collection_size']);
            error_log('EventCustomCalendar Debug: SQL = ' . $debug['collection_sql']);

            // Check if events exist
            if ($events && (is_array($events) || is_object($events))) {
                error_log('EventCustomCalendar Debug: Processing events...');
                foreach ($events as $event) {
                    error_log('EventCustomCalendar Debug: Event ID = ' . $event->getId() . ', Name = ' . $event->getName());
                    $date = $event->getEventDate();
                    if ($date) {
                        $dateObj = new \DateTime($date);
                        $formattedDate = $dateObj->format('Y-m-d');
                        error_log('EventCustomCalendar Debug: Event date = ' . $formattedDate);

                        // Get time values if they exist - try different methods
                        $startTime = null;
                        $endTime = null;

                        // Try to get time from different possible fields
                        if (method_exists($event, 'getStartTime')) {
                            $startTime = $event->getStartTime();
                        }
                        if (method_exists($event, 'getEndTime')) {
                            $endTime = $event->getEndTime();
                        }

                        // Fall back to defaults if no time fields exist
                        $startTime = $startTime ?: "08:00:00";
                        $endTime = $endTime ?: "09:30:00";

                        // Get short description first, fall back to description, or provide a placeholder
                        $shortDescription = '';
                        if (method_exists($event, 'getShortDescription')) {
                            $shortDescription = $event->getShortDescription() ?: '';
                        }

                        // If no short description, use a portion of the regular description
                        if (empty($shortDescription) && method_exists($event, 'getDescription')) {
                            $shortDescription = $event->getDescription();
                        }

                        // Use content field if description is not available
                        if (empty($shortDescription) && method_exists($event, 'getContent')) {
                            $shortDescription = $event->getContent();
                        }

                        // Truncate description if too long
                        if (strlen($shortDescription) > 150) {
                            $shortDescription = substr($shortDescription, 0, 147) . '...';
                        }

                        // Create a unique identifier for this event
                        $eventKey = $event->getId() . '-' . $formattedDate . '-' . $event->getName() . '-' . $startTime;

                        // Only add this event if it hasn't been added before
                        if (!isset($addedEvents[$eventKey])) {
                            $addedEvents[$eventKey] = true; // Mark as added

                            $eventData = [
                                'date' => $formattedDate,
                                'name' => $event->getName(),
                                'url' => $this->getUrl('events/event/view', ['id' => $event->getId()]),
                                'id' => $event->getId(),
                                'publish_date' => $event->getPublishDate(),
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'description' => $shortDescription
                            ];

                            $result[] = $eventData;
                            error_log('EventCustomCalendar Debug: Added event to result: ' . json_encode($eventData));
                        }
                    } else {
                        error_log('EventCustomCalendar Debug: Event has no date - ID = ' . $event->getId());
                    }
                }
            } else {
                error_log('EventCustomCalendar Debug: No events found or events is not iterable');
            }

        // If no results found but we're in development mode, add test data
        // if (empty($result)) {
        //     // Add some dummy events for testing
        //     $today = new \DateTime();
        //     $tomorrow = new \DateTime('+1 day');
        //     $nextWeek = new \DateTime('+7 days');

        //     // Add test events but ensure no duplicates
        //     $testEvents = [
        //         [
        //             'date' => $tomorrow->format('Y-m-d'),
        //             'name' => __('Important Conference'),
        //             'url' => '#',
        //             'id' => 'test2',
        //             'start_time' => '10:00:00',
        //             'end_time' => '12:30:00',
        //             'description' => __('Lorem ipsum dolor sit amet consectetur')
        //         ],
        //         [
        //             'date' => $nextWeek->format('Y-m-d'),
        //             'name' => __('Workshop'),
        //             'url' => '#',
        //             'id' => 'test3',
        //             'start_time' => '13:00:00',
        //             'end_time' => '15:30:00',
        //             'description' => __('Lorem ipsum dolor sit amet consectetur')
        //         ],
        //         [
        //             'date' => $today->format('Y-m-d'),
        //             'name' => __('Afternoon Session'),
        //             'url' => '#',
        //             'id' => 'test4',
        //             'start_time' => '14:00:00',
        //             'end_time' => '16:30:00',
        //             'description' => __('Lorem ipsum dolor sit amet consectetur')
        //         ]
        //     ];

        //     foreach ($testEvents as $event) {
        //         $eventKey = $event['id'] . '-' . $event['date'] . '-' . $event['name'] . '-' . $event['start_time'];

        //         // Only add if not a duplicate
        //         if (!isset($addedEvents[$eventKey])) {
        //             $addedEvents[$eventKey] = true;
        //             $result[] = $event;
        //         }
        //     }
        // }

            error_log('EventCustomCalendar Debug: Final result count = ' . count($result));
            return json_encode($result);
        } catch (\Exception $e) {
            // Log error and return empty array
            error_log('Error getting events for calendar: ' . $e->getMessage());
            return json_encode([]);
        }
    }

    /**
     * Get selected date if any
     *
     * @return string|null
     */
    public function getSelectedDate()
    {
        return $this->_registry->registry('selected_calendar_date');
    }

    /**
     * Format a date as Y-m-d
     *
     * @param \DateTime|string $date
     * @return string
     */
    public function formatDateYmd($date)
    {
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            return (new \DateTime($date))->format('Y-m-d');
        }

        return '';
    }

    /**
     * Get selected date components for JS calendar initialization
     *
     * @return array
     */
    public function getSelectedDateComponents()
    {
        $selectedDate = $this->getSelectedDate();

        if (!$selectedDate) {
            return [
                'year' => null,
                'month' => null,
                'day' => null
            ];
        }

        $dateObj = new \DateTime($selectedDate);
        return [
            'year' => (int)$dateObj->format('Y'),
            'month' => (int)$dateObj->format('n') - 1, // JS months are 0-based
            'day' => (int)$dateObj->format('j')
        ];
    }
}