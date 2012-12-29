<?php

namespace Scrutinizer\RabbitMQ\Rpc;

use JMS\Serializer\Exclusion\VersionExclusionStrategy;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;

class GroupsVersionExclusionStrategy implements ExclusionStrategyInterface
{
    private $versionStrategy;
    private $groupStrategy;

    public function __construct($version, array $groups)
    {
        $this->versionStrategy = new VersionExclusionStrategy($version);
        $this->groupStrategy = new GroupsExclusionStrategy($groups);
    }

    /**
     * Whether the class should be skipped.
     *
     * @param ClassMetadata $metadata
     * @param NavigatorContext $navigatorContext
     *
     * @return boolean
     */
    public function shouldSkipClass(ClassMetadata $metadata, NavigatorContext $navigatorContext)
    {
        return false;
    }

    /**
     * Whether the property should be skipped.
     *
     * @param PropertyMetadata $property
     * @param NavigatorContext $navigatorContext
     *
     * @return boolean
     */
    public function shouldSkipProperty(PropertyMetadata $property, NavigatorContext $navigatorContext)
    {
        return $this->versionStrategy->shouldSkipProperty($property, $navigatorContext)
                    || $this->groupStrategy->shouldSkipProperty($property, $navigatorContext);
    }
}