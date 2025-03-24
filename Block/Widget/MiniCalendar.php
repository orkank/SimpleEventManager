<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block\Widget;

use IDangerous\SimpleEventManager\Block\EventCustomCalendar;
use Magento\Framework\View\Element\Template\Context;
use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Registry;
use Magento\Widget\Block\BlockInterface;

class MiniCalendar extends EventCustomCalendar implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'IDangerous_SimpleEventManager::widget/mini_calendar.phtml';

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
        parent::__construct(
            $context,
            $eventCollectionFactory,
            $timezone,
            $jsonSerializer,
            $registry,
            $data
        );
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getWidgetTitle()
    {
        return $this->getData('title') ?? '';
    }

    /**
     * Get widget calendar target URL
     *
     * @return string
     */
    public function getCalendarPageUrl()
    {
        return $this->getUrl('events/index/calendar');
    }
}