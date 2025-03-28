<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use IDangerous\SimpleEventManager\Model\EventFactory;
use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'IDangerous_SimpleEventManager::event';

    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var CollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CollectionFactory $eventCollectionFactory
     * @param SessionManagerInterface $session
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        DataPersistorInterface $dataPersistor,
        CollectionFactory $eventCollectionFactory,
        SessionManagerInterface $session = null,
        Validator $formKeyValidator = null
    ) {
        $this->eventFactory = $eventFactory;
        $this->dataPersistor = $dataPersistor;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->session = $session ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SessionManagerInterface::class);
        $this->formKeyValidator = $formKeyValidator ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(Validator::class);
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        // Validate form key to prevent CSRF
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please refresh the page and try again.'));
            return $resultRedirect->setPath('*/*/');
        }

        // Check if this is a duplicate submission
        $formSubmissionKey = $this->getRequest()->getParam('form_submission_key');
        $lastFormKey = $this->session->getLastFormKey();

        if ($formSubmissionKey && $lastFormKey === $formSubmissionKey) {
            // This is a duplicate submission, redirect without processing
            return $resultRedirect->setPath('*/*/');
        }

        // Save the current form key
        $this->session->setLastFormKey($formSubmissionKey);

        $data = $this->getRequest()->getPostValue();
        if ($data) {
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = 1;
            }

            // Get ID from the request - might be 'id' or 'event_id'
            $id = $this->getRequest()->getParam('id');
            if (!$id) {
                $id = $this->getRequest()->getParam('event_id');
            }

            if (empty($id)) {
                $data['event_id'] = null;

                // Check for potential duplicate event
                if (!empty($data['name'])) {
                    $collection = $this->eventCollectionFactory->create();
                    $collection->addFieldToFilter('name', $data['name']);

                    // If event date is provided, also check for same date
                    if (!empty($data['event_date'])) {
                        $collection->addFieldToFilter('event_date', $data['event_date']);
                    }

                    // Check if we already have a similar event
                    if ($collection->getSize() > 0) {
                        $this->messageManager->addWarningMessage(
                            __('A similar event already exists. Please check if you are creating a duplicate.')
                        );
                    }
                }
            } else {
                // Ensure event_id is set in data
                $data['event_id'] = $id;
            }

            /** @var \IDangerous\SimpleEventManager\Model\Event $model */
            $model = $this->eventFactory->create();

            if ($id) {
                try {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This event no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This event no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the event.'));
                $this->dataPersistor->clear('idangerous_event');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the event.'));
            }

            $this->dataPersistor->set('idangerous_event', $data);
            // Use the model's ID for redirection, not the request param
            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId() ? $model->getId() : null]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}