<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Controller\Adminhtml\Event\Image;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Upload extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'IDangerous_SimpleEventManager::event';

    /**
     * @var ImageUploader
     */
    protected $imageUploader;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var File
     */
    protected $fileIo;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @param Context $context
     * @param ImageUploader $imageUploader
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param File $fileIo
     * @param UploaderFactory $uploaderFactory
     */
    public function __construct(
        Context $context,
        ImageUploader $imageUploader,
        Filesystem $filesystem,
        LoggerInterface $logger,
        File $fileIo,
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->fileIo = $fileIo;
        $this->uploaderFactory = $uploaderFactory;
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            // Get the field name from request, default to 'preview_image'
            $fieldName = $this->getRequest()->getParam('field') ?: 'preview_image';

            // Create directories if they don't exist
            $this->createDirectories();

            // Check if file is available
            $fileData = $this->getRequest()->getFiles($fieldName);
            if (empty($fileData) || !isset($fileData['tmp_name']) || empty($fileData['tmp_name'])) {
                throw new LocalizedException(__('No file was uploaded or file is too large.'));
            }

            // Log pre-upload information
            $this->logger->info('Attempting to upload file', [
                'field' => $fieldName,
                'file_name' => $fileData['name'] ?? 'unknown',
                'file_size' => $fileData['size'] ?? 0,
                'file_type' => $fileData['type'] ?? 'unknown',
                'request_data' => json_encode($fileData)
            ]);

            // Try direct uploader as a fallback method if the regular method fails
            try {
                $result = $this->imageUploader->saveFileToTmpDir($fieldName);
            } catch (\Exception $e) {
                $this->logger->warning('Standard uploader failed, trying fallback method', [
                    'error' => $e->getMessage()
                ]);

                $result = $this->fallbackUpload($fieldName);
            }

            // Log successful upload with detailed information
            $this->logger->info('File uploaded successfully to temp directory', [
                'field' => $fieldName,
                'file' => $result['name'] ?? 'unknown',
                'tmp_name' => $result['tmp_name'] ?? 'unknown',
                'size' => $result['size'] ?? 0,
                'type' => $result['type'] ?? 'unknown'
            ]);

            // Store the uploaded temp file info in session for debugging
            $session = $this->_getSession();
            $uploadedFiles = $session->getUploadedFiles() ?: [];
            $uploadedFiles[$result['name']] = [
                'tmp_name' => $result['tmp_name'] ?? '',
                'time' => time()
            ];
            $session->setUploadedFiles($uploadedFiles);

            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            // Log the error with detailed information
            $this->logger->error('Error uploading file: ' . $e->getMessage(), [
                'field' => $fieldName ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'request_data' => json_encode($this->getRequest()->getFiles($fieldName) ?? [])
            ]);

            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Fallback upload method in case the standard uploader fails
     *
     * @param string $fieldName
     * @return array
     * @throws LocalizedException
     */
    protected function fallbackUpload($fieldName)
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpDir = $mediaDirectory->getAbsolutePath('event/tmp');

        try {
            // Create uploader
            $uploader = $this->uploaderFactory->create(['fileId' => $fieldName]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // Save file
            $result = $uploader->save($tmpDir);

            if (!isset($result['path']) || !isset($result['file'])) {
                throw new LocalizedException(__('File was not properly saved.'));
            }

            // Set URL and other required properties
            $result['url'] = $this->getMediaUrl($result['file']);
            $result['tmp_name'] = $result['path'] . '/' . $result['file'];
            $result['name'] = $result['file'];

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Fallback upload also failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(__('Could not save file: %1', $e->getMessage()));
        }
    }

    /**
     * Get media URL for the temp file
     *
     * @param string $file
     * @return string
     */
    protected function getMediaUrl($file)
    {
        return $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . 'event/tmp/' . $file;
    }

    /**
     * Create the necessary directories for image uploads
     */
    protected function createDirectories()
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Create all necessary directories with proper permissions
            $directories = [
                'event',                  // Base directory
                'event/tmp',              // Temporary upload directory
                'event/preview',          // Preview images directory
                'event/photo',            // Photo gallery images directory
            ];

            foreach ($directories as $dir) {
                if (!$mediaDirectory->isExist($dir)) {
                    $mediaDirectory->create($dir);
                    $mediaDirectory->changePermissions($dir, 0777);
                } else {
                    // Ensure the directory has proper permissions
                    $mediaDirectory->changePermissions($dir, 0777);
                }
            }

            // Double-check with native PHP to ensure permissions are set
            $basePath = $mediaDirectory->getAbsolutePath();
            foreach ($directories as $dir) {
                $fullPath = $basePath . $dir;
                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0777, true);
                } elseif (!is_writable($fullPath)) {
                    chmod($fullPath, 0777);
                }
            }

            $this->logger->info('Directories created/checked successfully', [
                'media_base' => $mediaDirectory->getAbsolutePath(),
                'event_dir' => $mediaDirectory->getAbsolutePath('event') . ' - ' .
                    (file_exists($mediaDirectory->getAbsolutePath('event')) ?
                    substr(sprintf('%o', fileperms($mediaDirectory->getAbsolutePath('event'))), -4) : 'not exists'),
                'tmp_dir' => $mediaDirectory->getAbsolutePath('event/tmp') . ' - ' .
                    (file_exists($mediaDirectory->getAbsolutePath('event/tmp')) ?
                    substr(sprintf('%o', fileperms($mediaDirectory->getAbsolutePath('event/tmp'))), -4) : 'not exists'),
                'preview_dir' => $mediaDirectory->getAbsolutePath('event/preview') . ' - ' .
                    (file_exists($mediaDirectory->getAbsolutePath('event/preview')) ?
                    substr(sprintf('%o', fileperms($mediaDirectory->getAbsolutePath('event/preview'))), -4) : 'not exists'),
                'photo_dir' => $mediaDirectory->getAbsolutePath('event/photo') . ' - ' .
                    (file_exists($mediaDirectory->getAbsolutePath('event/photo')) ?
                    substr(sprintf('%o', fileperms($mediaDirectory->getAbsolutePath('event/photo'))), -4) : 'not exists')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating directories: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Could not create required directories: ' . $e->getMessage());
        }
    }
}