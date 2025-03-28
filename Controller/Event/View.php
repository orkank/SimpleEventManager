<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Event;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use IDangerous\SimpleEventManager\Model\EventFactory;
use Magento\Framework\Registry;
use IDangerous\SimpleEventManager\Helper\Config;
use Magento\Framework\Controller\Result\ForwardFactory;

class View extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param EventFactory $eventFactory
     * @param Registry $coreRegistry
     * @param Config $config
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        EventFactory $eventFactory,
        Registry $coreRegistry,
        Config $config,
        ForwardFactory $resultForwardFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->eventFactory = $eventFactory;
        $this->coreRegistry = $coreRegistry;
        $this->config = $config;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Event view page
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        // Check if module is enabled
        if (!$this->config->isEnabled()) {
            // Forward to CMS/noroute
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $indexSlug = $this->config->getIndexSlug() ?: 'events';
            return $resultRedirect->setPath($indexSlug);
        }

        $event = $this->eventFactory->create();
        $event->load($id);

        if (!$event->getId() || !$event->isActive()) {
            $this->messageManager->addErrorMessage(__('Event not found or not active.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $indexSlug = $this->config->getIndexSlug() ?: 'events';
            return $resultRedirect->setPath($indexSlug);
        }

        $this->coreRegistry->register('current_event', $event);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set($event->getName());

        return $resultPage;
    }
}