<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Event;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use IDangerous\SimpleEventManager\Model\EventFactory;
use IDangerous\SimpleEventManager\Model\RegistrationFactory;
use IDangerous\SimpleEventManager\Model\ResourceModel\Registration\CollectionFactory as RegistrationCollectionFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

class Register extends Action
{
    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var RegistrationFactory
     */
    protected $registrationFactory;

    /**
     * @var RegistrationCollectionFactory
     */
    protected $registrationCollectionFactory;

    /**
     * @var FormKeyValidator
     */
    protected $formKeyValidator;

    /**
     * @param Context $context
     * @param EventFactory $eventFactory
     * @param RegistrationFactory $registrationFactory
     * @param RegistrationCollectionFactory $registrationCollectionFactory
     * @param FormKeyValidator $formKeyValidator
     */
    public function __construct(
        Context $context,
        EventFactory $eventFactory,
        RegistrationFactory $registrationFactory,
        RegistrationCollectionFactory $registrationCollectionFactory,
        FormKeyValidator $formKeyValidator
    ) {
        $this->eventFactory = $eventFactory;
        $this->registrationFactory = $registrationFactory;
        $this->registrationCollectionFactory = $registrationCollectionFactory;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Register for event
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $resultRedirect->setPath('*/*/');
        }

        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->messageManager->addErrorMessage(__('Invalid data. Please try again.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $eventId = $data['event_id'] ?? null;
            if (!$eventId) {
                throw new LocalizedException(__('No event specified.'));
            }

            $event = $this->eventFactory->create()->load($eventId);
            if (!$event->getId() || !$event->isActive() || !$event->isJoinFormEnabled()) {
                throw new LocalizedException(__('Event not found or registration not allowed.'));
            }

            // Check if there's a participation limit
            if ($event->getParticipationLimit()) {
                $registrationCount = $this->registrationCollectionFactory->create()
                    ->addFieldToFilter('event_id', $eventId)
                    ->getSize();

                if ($registrationCount >= $event->getParticipationLimit()) {
                    throw new LocalizedException(__('Sorry, this event has reached its participation limit.'));
                }
            }

            // Check if email already registered
            if (!empty($data['email'])) {
                $existingRegistration = $this->registrationCollectionFactory->create()
                    ->addFieldToFilter('event_id', $eventId)
                    ->addFieldToFilter('email', $data['email'])
                    ->getFirstItem();

                if ($existingRegistration->getId()) {
                    throw new LocalizedException(__('You have already registered for this event.'));
                }
            }

            $registration = $this->registrationFactory->create();
            $registration->setData([
                'event_id' => $eventId,
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? ''
            ]);
            $registration->save();

            $this->messageManager->addSuccessMessage(__('Thank you for registering for this event!'));
            return $resultRedirect->setPath('events/event/view', ['id' => $eventId]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while registering for the event.'));
        }

        return $resultRedirect->setPath('events/event/view', ['id' => $eventId ?? null]);
    }
}