<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use IDangerous\SimpleEventManager\Model\EventFactory;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

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
        $this->context = $context;
        $this->eventFactory = $eventFactory;
    }

    /**
     * Return event ID
     *
     * @return int|null
     */
    public function getEventId()
    {
        try {
            $id = $this->context->getRequest()->getParam('id');
            if ($id) {
                $event = $this->eventFactory->create()->load($id);
                if ($event->getId()) {
                    return $event->getId();
                }
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}