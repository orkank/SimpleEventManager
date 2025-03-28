<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Model\Event;

use IDangerous\SimpleEventManager\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $event) {
            $data = $event->getData();
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            // Add unique form submission key
            $data['form_submission_key'] = uniqid('form_', true);

            // Handle preview image
            if (isset($data['preview_image']) && $data['preview_image']) {
                $imageUrl = $mediaUrl . 'event/' . $data['preview_image'];
                $data['preview_image'] = [
                    [
                        'name' => $data['preview_image'],
                        'url' => $imageUrl,
                    ],
                ];
            }

            // Handle multiple photos
            if (isset($data['photos']) && !empty($data['photos'])) {
                $photoData = [];

                try {
                    $photoArray = json_decode($data['photos'], true);
                    if (is_array($photoArray)) {
                        foreach ($photoArray as $photoName) {
                            $photoUrl = $mediaUrl . 'event/' . $photoName;
                            $photoData[] = [
                                'name' => $photoName,
                                'url' => $photoUrl,
                            ];
                        }
                        $data['photos'] = $photoData;
                    }
                } catch (\Exception $e) {
                    // If there's an error parsing the JSON, just leave as is
                }
            }

            $this->loadedData[$event->getId()] = $data;
        }

        $data = $this->dataPersistor->get('idangerous_event');
        if (!empty($data)) {
            $event = $this->collection->getNewEmptyItem();
            $event->setData($data);
            // Add unique form submission key if not already present
            if (!isset($data['form_submission_key'])) {
                $event->setData('form_submission_key', uniqid('form_', true));
            }
            $this->loadedData[$event->getId()] = $event->getData();
            $this->dataPersistor->clear('idangerous_event');
        }

        // For new form with no loaded data
        if (empty($this->loadedData)) {
            $emptyData = [
                'form_submission_key' => uniqid('form_', true)
            ];
            $this->loadedData[''] = $emptyData;
        }

        return $this->loadedData;
    }
}