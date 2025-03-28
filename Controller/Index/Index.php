<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use IDangerous\SimpleEventManager\Helper\Config;
use Magento\Framework\Controller\Result\ForwardFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * @param Config $config
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Config $config,
        ForwardFactory $resultForwardFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->config = $config;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Event list page
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        // Check if module is enabled
        if (!$this->config->isEnabled()) {
            // Forward to CMS/noroute
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Events'));

        return $resultPage;
    }
}