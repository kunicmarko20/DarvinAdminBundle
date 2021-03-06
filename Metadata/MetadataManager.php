<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Metadata;

use Darvin\AdminBundle\Event\Metadata\MetadataEvent;
use Darvin\AdminBundle\Event\Metadata\MetadataEvents;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Metadata manager
 */
class MetadataManager
{
    const CACHE_ID = 'darvinAdminMetadata';

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \Darvin\AdminBundle\Metadata\MetadataPool
     */
    private $metadataPool;

    /**
     * @var bool
     */
    private $cacheDisabled;

    /**
     * @var array
     */
    private $entityOverride;

    /**
     * @var array
     */
    private $checkedIfHasMetadataClasses;

    /**
     * @var bool
     */
    private $initialized;

    /**
     * @var \Darvin\AdminBundle\Metadata\Metadata[]
     */
    private $metadata;

    /**
     * @param \Doctrine\Common\Cache\Cache                                $cache           Cache
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher Event dispatcher
     * @param \Darvin\AdminBundle\Metadata\MetadataPool                   $metadataPool    Metadata pool
     * @param bool                                                        $cacheDisabled   Is cache disabled
     * @param array                                                       $entityOverride  Entity override configuration
     */
    public function __construct(
        Cache $cache,
        EventDispatcherInterface $eventDispatcher,
        MetadataPool $metadataPool,
        $cacheDisabled,
        array $entityOverride
    ) {
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
        $this->metadataPool = $metadataPool;
        $this->cacheDisabled = $cacheDisabled;
        $this->entityOverride = $entityOverride;

        $this->checkedIfHasMetadataClasses = $this->metadata = [];
        $this->initialized = false;
    }

    /**
     * @param mixed $entityOrClass Entity or class
     *
     * @return bool
     */
    public function hasMetadata($entityOrClass)
    {
        $entityClass = is_object($entityOrClass) ? ClassUtils::getClass($entityOrClass) : $entityOrClass;

        if (isset($this->entityOverride[$entityClass])) {
            $entityClass = $this->entityOverride[$entityClass];
        }
        if (!isset($this->checkedIfHasMetadataClasses[$entityClass])) {
            $this->checkedIfHasMetadataClasses[$entityClass] = true;

            try {
                $this->getMetadata($entityClass);
            } catch (MetadataException $ex) {
                $this->checkedIfHasMetadataClasses[$entityClass] = false;
            }
        }

        return $this->checkedIfHasMetadataClasses[$entityClass];
    }

    /**
     * @param mixed $entityOrClass Entity or class
     *
     * @return array
     */
    public function getConfiguration($entityOrClass)
    {
        return $this->getMetadata($entityOrClass)->getConfiguration();
    }

    /**
     * @param mixed $entityOrClass Entity or class
     *
     * @return \Darvin\AdminBundle\Metadata\Metadata
     * @throws \Darvin\AdminBundle\Metadata\MetadataException
     */
    public function getMetadata($entityOrClass)
    {
        $this->init();

        $entityClass = is_object($entityOrClass) ? ClassUtils::getClass($entityOrClass) : $entityOrClass;

        if (isset($this->entityOverride[$entityClass])) {
            $entityClass = $this->entityOverride[$entityClass];
        }
        if (!isset($this->metadata[$entityClass])) {
            $childClass = $entityClass;

            while ($parentClass = get_parent_class($childClass)) {
                if (isset($this->metadata[$parentClass])) {
                    $this->metadata[$entityClass] = $this->metadata[$parentClass];

                    return $this->metadata[$parentClass];
                }

                $childClass = $parentClass;
            }

            throw new MetadataException(sprintf('Unable to get metadata for class "%s".', $entityClass));
        }

        return $this->metadata[$entityClass];
    }

    /**
     * @return \Darvin\AdminBundle\Metadata\Metadata[]
     */
    public function getAllMetadata()
    {
        $this->init();

        return $this->metadata;
    }

    private function init()
    {
        if ($this->initialized) {
            return;
        }
        if (!$this->initFromCache()) {
            $this->initAndCache();
        }

        $this->buildTree(array_keys($this->metadata));

        foreach ($this->metadata as $meta) {
            $this->eventDispatcher->dispatch(MetadataEvents::LOADED, new MetadataEvent($meta));
        }

        $this->initialized = true;
    }

    private function initAndCache()
    {
        $this->metadata = $this->metadataPool->getAllMetadata();

        if ($this->cacheDisabled) {
            return;
        }

        $serialized = serialize($this->metadata);

        if (!$this->cache->save(self::CACHE_ID, $serialized)) {
            throw new MetadataException('Unable to cache metadata.');
        }
    }

    /**
     * @return bool
     */
    private function initFromCache()
    {
        if ($this->cacheDisabled) {
            return false;
        }

        $cached = $this->cache->fetch(self::CACHE_ID);

        if (false === $cached) {
            return false;
        }

        $unserialized = @unserialize($cached);

        if (!is_array($unserialized)) {
            return false;
        }

        $this->metadata = $unserialized;

        return true;
    }

    /**
     * @param array $parentEntities Parent entity classes
     *
     * @throws \Darvin\AdminBundle\Metadata\MetadataException
     */
    private function buildTree(array $parentEntities)
    {
        foreach ($parentEntities as $parentEntity) {
            if (isset($this->entityOverride[$parentEntity])) {
                $parentEntity = $this->entityOverride[$parentEntity];
            }

            $parentMeta = $this->metadata[$parentEntity];
            $parentConfiguration = $parentMeta->getConfiguration();

            foreach ($parentConfiguration['children'] as $key => $childEntity) {
                if (isset($this->entityOverride[$childEntity])) {
                    $childEntity = $this->entityOverride[$childEntity];
                }
                if (!isset($this->metadata[$childEntity])) {
                    unset($parentConfiguration['children'][$key]);

                    continue;
                }

                $childMeta = $this->metadata[$childEntity];
                $associated = false;

                foreach ($childMeta->getMappings() as $property => $mapping) {
                    if (!$childMeta->isAssociation($property)
                        || ($mapping['targetEntity'] !== $parentEntity && !in_array($mapping['targetEntity'], class_parents($parentEntity)))
                    ) {
                        continue;
                    }

                    $childMeta->setParent(new AssociatedMetadata($property, $parentMeta));
                    $parentMeta->addChild(new AssociatedMetadata($property, $childMeta));
                    $associated = true;
                }
                if (!$associated) {
                    throw new MetadataException(
                        sprintf('Entity "%s" is not associated with entity "%s".', $childEntity, $parentEntity)
                    );
                }
            }

            $this->buildTree($parentConfiguration['children']);
        }
    }
}
