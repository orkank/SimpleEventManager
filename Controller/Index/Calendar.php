<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use IDangerous\SimpleEventManager\Helper\Config;
use Magento\Framework\Controller\Result\RedirectFactory;

class Calendar extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        Config $config
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->config = $config;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        parent::__construct($context);
    }

    /**
     * Event calendar page
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Check if module and calendar feature are enabled
        if (!$this->config->isEnabled() || !$this->config->isCalendarEnabled()) {
            // Redirect to events index page
            $resultRedirect = $this->resultRedirectFactory->create();
            $indexUrl = $this->config->getIndexSlug() ?: 'events';
            return $resultRedirect->setPath($indexUrl);
        }

        // First check for date in URL path: /events/index/calendar/2025-03-20
        $urlPathDate = null;
        $request = $this->getRequest();
        $pathInfo = trim($request->getPathInfo(), '/');
        $pathParts = explode('/', $pathInfo);

        // If we have a date in the URL path (format: /events/index/calendar/YYYY-MM-DD)
        if (count($pathParts) > 3 && isset($pathParts[3])) {
            $urlPathDate = $pathParts[3];
        }

        // Then check query params (for backward compatibility)
        $queryDate = $request->getParam('date');

        // Use URL path date if available, otherwise use query param
        $date = $urlPathDate ?: $queryDate;

        // Validate date format (YYYY-MM-DD)
        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Register the selected date for use in blocks
            $this->registry->register('selected_calendar_date', $date);

            // Set page title with the selected date
            $formattedDate = date('F j, Y', strtotime($date));
            $pageTitle = __('Event Calendar - %1', $formattedDate);
        } else {
            // Default title if no date is selected
            $pageTitle = __('Event Calendar');
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        return $resultPage;
    }
}