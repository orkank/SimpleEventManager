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
     * @param Context $context
     * @param ImageUploader $imageUploader
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ImageUploader $imageUploader,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
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

            // Log pre-upload information
            $this->logger->info('Attempting to upload file', [
                'field' => $fieldName,
                'request_data' => json_encode($this->getRequest()->getFiles($fieldName))
            ]);

            $result = $this->imageUploader->saveFileToTmpDir($fieldName);

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
     * Create the necessary directories for image uploads
     */
    protected function createDirectories()
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // Create event directory and tmp subdirectory if they don't exist
            $eventDir = 'event';
            $tmpDir = $eventDir . '/tmp';

            if (!$mediaDirectory->isExist($eventDir)) {
                $mediaDirectory->create($eventDir);
                $mediaDirectory->changePermissions($eventDir, 0755);
            }

            if (!$mediaDirectory->isExist($tmpDir)) {
                $mediaDirectory->create($tmpDir);
                $mediaDirectory->changePermissions($tmpDir, 0755);
            }

            $this->logger->info('Directories created/checked successfully', [
                'event_dir' => $mediaDirectory->getAbsolutePath($eventDir),
                'tmp_dir' => $mediaDirectory->getAbsolutePath($tmpDir)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating directories: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}