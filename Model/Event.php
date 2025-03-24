<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Model;

use Magento\Framework\Model\AbstractModel;
use IDangerous\SimpleEventManager\Model\ResourceModel\Event as EventResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class Event extends AbstractModel
{
    /**
     * Event cache tag
     */
    const CACHE_TAG = 'idangerous_event';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'idangerous_event';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        StoreManagerInterface $storeManager = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->_logger = $context->getLogger();
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EventResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Check if the event is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getData('is_active');
    }

    /**
     * Check if join form is enabled
     *
     * @return bool
     */
    public function isJoinFormEnabled()
    {
        return (bool)$this->getData('join_form_enabled');
    }

    /**
     * Check if countdown is enabled
     *
     * @return bool
     */
    public function isCountdownEnabled()
    {
        return (bool)$this->getData('countdown_enabled');
    }

    /**
     * Get full preview image URL
     *
     * @return string|null
     */
    public function getPreviewImageUrl()
    {
        $image = $this->getPreviewImage();
        if (!$image) {
            return null;
        }

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'event/' . $image;
    }

    /**
     * Process image before save
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        if ($this->hasDataChanges()) {
            $this->setUpdatedAt(date('Y-m-d H:i:s'));
        }

        // Get image uploader instance
        $imageUploader = ObjectManager::getInstance()->get('IDangerous\SimpleEventManager\EventImageUploader');

        // Create directories if they don't exist
        $this->createMediaDirectories();

        // Process preview image
        $previewImage = $this->getPreviewImage();
        if (is_array($previewImage)) {
            if (isset($previewImage[0]['deleted']) && $previewImage[0]['deleted']) {
                // Image was marked for deletion
                $this->setData('preview_image', null);
            } elseif (!isset($previewImage[0]['name']) || (isset($previewImage[0]['error']) && $previewImage[0]['error'] > 0)) {
                // No valid image data or upload error
                if (empty($previewImage)) {
                    $this->setData('preview_image', null);
                }
            } else {
                // New image uploaded
                $imageName = $previewImage[0]['name'];

                // Sanitize the filename to avoid issues
                $imageName = $this->sanitizeFileName($imageName);

                $this->setData('preview_image', $imageName);

                // Move the image from temporary directory to the final location
                try {
                    if (isset($previewImage[0]['tmp_name']) && $previewImage[0]['tmp_name']) {
                        $imageUploader->moveFileFromTmp($imageName);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with save
                    $this->_logger->error('Error processing image: ' . $e->getMessage(), [
                        'file' => $imageName,
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new LocalizedException(__('Error processing image: %1', $e->getMessage()));
                }
            }
        }

        // Process multiple photos
        $photos = $this->getPhotos();
        $currentPhotos = [];

        // If we have existing photos stored as JSON, decode them
        $existingPhotosData = $this->getOrigData('photos');
        if ($existingPhotosData) {
            try {
                $existingPhotos = json_decode($existingPhotosData, true);
                if (is_array($existingPhotos)) {
                    $currentPhotos = $existingPhotos;
                }
            } catch (\Exception $e) {
                // If there's an error parsing JSON, start with empty array
                $currentPhotos = [];
            }
        }

        if (is_array($photos) && !empty($photos)) {
            $photoNames = [];

            foreach ($photos as $photo) {
                // Skip deleted photos
                if (isset($photo['deleted']) && $photo['deleted']) {
                    continue;
                }

                if (isset($photo['name']) && !empty($photo['name'])) {
                    try {
                        // Sanitize filename
                        $photoName = $this->sanitizeFileName($photo['name']);

                        // Move each photo from temporary directory to final location if it's a new upload
                        if (isset($photo['tmp_name']) && $photo['tmp_name']) {
                            // Check if file exists in temp directory
                            $filesystem = ObjectManager::getInstance()->get(\Magento\Framework\Filesystem::class);
                            $mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                            $tempFilePath = 'event/tmp/' . $photoName;

                            // Log the info about the file being moved
                            $this->_logger->info('Attempting to move file', [
                                'file' => $photoName,
                                'tmp_path' => $tempFilePath,
                                'exists' => $mediaDirectory->isExist($tempFilePath) ? 'Yes' : 'No'
                            ]);

                            if (!$mediaDirectory->isExist($tempFilePath)) {
                                // Try to find a matching file with similar name (case-insensitive)
                                $matchingFile = $this->findMatchingFile($photoName);
                                if ($matchingFile) {
                                    $this->_logger->info('Found matching file', [
                                        'original' => $photoName,
                                        'found' => $matchingFile
                                    ]);
                                    $photoName = $matchingFile;
                                }
                            }

                            // Move the file from temporary directory to final location
                            $imageUploader->moveFileFromTmp($photoName);
                            $photoNames[] = $photoName;
                        }
                    } catch (\Exception $e) {
                        // Log error with more details
                        $this->_logger->error('Error processing photo: ' . $e->getMessage(), [
                            'file' => $photo['name'] ?? 'unknown',
                            'trace' => $e->getTraceAsString(),
                            'code' => $e->getCode()
                        ]);

                        // Continue processing other photos instead of failing completely
                        continue;
                    }
                }
            }

            // Store as JSON encoded array of filenames
            if (!empty($photoNames)) {
                $this->setData('photos', json_encode($photoNames));
            } else {
                // If all photos were deleted, set to NULL
                $this->setData('photos', null);
            }
        } elseif (empty($photos) && !empty($currentPhotos)) {
            // If photos field is empty but we had photos before, it means all were deleted
            $this->setData('photos', null);
        }

        return parent::beforeSave();
    }

    /**
     * Create the media directories needed for image storage
     *
     * @return void
     */
    protected function createMediaDirectories()
    {
        try {
            $filesystem = ObjectManager::getInstance()->get(\Magento\Framework\Filesystem::class);
            $mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

            $eventDir = 'event';
            $tmpDir = $eventDir . '/tmp';

            // Create main event directory if it doesn't exist
            if (!$mediaDirectory->isExist($eventDir)) {
                $mediaDirectory->create($eventDir);
                $mediaDirectory->changePermissions($eventDir, 0755);
            }

            // Create temporary directory if it doesn't exist
            if (!$mediaDirectory->isExist($tmpDir)) {
                $mediaDirectory->create($tmpDir);
                $mediaDirectory->changePermissions($tmpDir, 0755);
            }
        } catch (\Exception $e) {
            // Log error but continue
            $logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Error creating media directories: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize a filename to make it safe for storage
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFileName($filename)
    {
        // Remove invalid characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        // Ensure filename isn't too long
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $basename = substr($basename, 0, 90);
            $filename = $basename . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Find a file in the temporary directory with a name similar to the provided one
     * This helps with case-sensitivity issues in file names
     *
     * @param string $fileName
     * @return string|null
     */
    protected function findMatchingFile($fileName)
    {
        try {
            $filesystem = ObjectManager::getInstance()->get(\Magento\Framework\Filesystem::class);
            $mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            $tempDirPath = $mediaDirectory->getAbsolutePath('event/tmp');

            // Get the base name without extension for comparison
            $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);

            // Look for files with similar names using a pattern
            $filePattern = $baseFileName . '.*';
            $matchingFiles = glob($tempDirPath . '/' . $filePattern);

            $this->_logger->info('Searching for matching files', [
                'pattern' => $filePattern,
                'dir' => $tempDirPath,
                'matches' => $matchingFiles
            ]);

            if (!empty($matchingFiles)) {
                // Return just the file name of the first match
                return basename($matchingFiles[0]);
            }
        } catch (\Exception $e) {
            $this->_logger->error('Error finding matching file: ' . $e->getMessage(), [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}