<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block\Adminhtml\Event\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use IDangerous\SimpleEventManager\Model\EventFactory;
use Magento\Backend\Block\Widget\Context;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @param Context $context
     * @param EventFactory $eventFactory
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory
    ) {
        parent::__construct($context, $eventFactory);
        $this->eventFactory = $eventFactory;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $eventId = $this->getEventId();
        if ($eventId) {
            $eventName = $this->getEventName($eventId);
            $confirmMessage = __('Are you sure you want to delete the event "%1"?', $eventName);

            $data = [
                'label' => __('Delete Event'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . $confirmMessage . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get event name
     *
     * @param int $eventId
     * @return string
     */
    protected function getEventName($eventId)
    {
        $event = $this->eventFactory->create()->load($eventId);
        return $event->getData('name') ?: __('Event');
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getEventId()]);
    }
}