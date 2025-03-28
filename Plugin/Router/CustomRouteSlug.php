<?php
/**
 * Plugin to customize the route slugs based on configuration
 */
namespace IDangerous\SimpleEventManager\Plugin\Router;

use IDangerous\SimpleEventManager\Helper\Config;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\App\Router\Base;

class CustomRouteSlug
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Around match plugin to customize route slugs
     *
     * @param Base $subject
     * @param \Closure $proceed
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function aroundMatch(
        Base $subject,
        \Closure $proceed,
        RequestInterface $request
    ) {
        // If module is disabled, proceed with the original behavior
        if (!$this->config->isEnabled()) {
            return $proceed($request);
        }

        $identifier = trim($request->getPathInfo(), '/');
        $parts = explode('/', $identifier);

        // Get custom slugs from configuration
        $indexSlug = $this->config->getIndexSlug();
        $eventSlug = $this->config->getEventMasterSlug();

        // Check if the request is for the event module
        $isEventPath = false;

        // Check if the first part matches the custom index slug
        if (!empty($parts[0]) && $parts[0] === $indexSlug) {
            $isEventPath = true;
            $parts[0] = 'events'; // Replace with original module route

            // Reset the path info with the original module route
            $newPathInfo = '/' . implode('/', $parts);
            $request->setPathInfo($newPathInfo);
        }

        // Check if it's an event view URL with custom master slug
        if (count($parts) >= 3 && $parts[0] === $eventSlug && $parts[1] === 'view') {
            $isEventPath = true;
            $parts[0] = 'events';
            $parts[1] = 'event';

            // Reset the path info with the original module route
            $newPathInfo = '/' . implode('/', $parts);
            $request->setPathInfo($newPathInfo);
        }

        // If it's not an event path or module is disabled, proceed with the original router
        if (!$isEventPath) {
            return $proceed($request);
        }

        // Process the request with modified path
        return $proceed($request);
    }
}