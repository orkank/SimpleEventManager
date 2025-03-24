<?php
/**
 * Copyright © IDangerous All rights reserved.
 * See COPYING.txt for license details.
 */
namespace IDangerous\SimpleEventManager\Api\Data;

interface EventInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const EVENT_ID = 'event_id';
    const NAME = 'name';
    const CONTENT = 'content';
    const EVENT_DATE = 'event_date';
    const PUBLISH_DATE = 'publish_date';
    const CATEGORY = 'category';
    const PHOTOS = 'photos';
    const PREVIEW_IMAGE = 'preview_image';
    const JOIN_FORM_ENABLED = 'join_form_enabled';
    const PARTICIPATION_LIMIT = 'participation_limit';
    const COUNTDOWN_ENABLED = 'countdown_enabled';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * Get event id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set event id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get event name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set event name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get event content
     *
     * @return string|null
     */
    public function getContent();

    /**
     * Set event content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content);

    /**
     * Get event date
     *
     * @return string|null
     */
    public function getEventDate();

    /**
     * Set event date
     *
     * @param string $eventDate
     * @return $this
     */
    public function setEventDate($eventDate);

    /**
     * Get publish date
     *
     * @return string|null
     */
    public function getPublishDate();

    /**
     * Set publish date
     *
     * @param string $publishDate
     * @return $this
     */
    public function setPublishDate($publishDate);

    /**
     * Get category
     *
     * @return string|null
     */
    public function getCategory();

    /**
     * Set category
     *
     * @param string $category
     * @return $this
     */
    public function setCategory($category);

    /**
     * Get photos
     *
     * @return string|null
     */
    public function getPhotos();

    /**
     * Set photos
     *
     * @param string $photos
     * @return $this
     */
    public function setPhotos($photos);

    /**
     * Get preview image
     *
     * @return string|null
     */
    public function getPreviewImage();

    /**
     * Set preview image
     *
     * @param string $previewImage
     * @return $this
     */
    public function setPreviewImage($previewImage);

    /**
     * Is join form enabled
     *
     * @return bool
     */
    public function isJoinFormEnabled();

    /**
     * Set join form enabled
     *
     * @param bool $joinFormEnabled
     * @return $this
     */
    public function setJoinFormEnabled($joinFormEnabled);

    /**
     * Get participation limit
     *
     * @return int|null
     */
    public function getParticipationLimit();

    /**
     * Set participation limit
     *
     * @param int $participationLimit
     * @return $this
     */
    public function setParticipationLimit($participationLimit);

    /**
     * Is countdown enabled
     *
     * @return bool
     */
    public function isCountdownEnabled();

    /**
     * Set countdown enabled
     *
     * @param bool $countdownEnabled
     * @return $this
     */
    public function setCountdownEnabled($countdownEnabled);

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive();

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}