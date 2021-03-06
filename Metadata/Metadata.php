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

/**
 * Metadata
 */
class Metadata
{
    /**
     * @var \Darvin\AdminBundle\Metadata\AssociatedMetadata
     */
    private $parent;

    /**
     * @var \Darvin\AdminBundle\Metadata\AssociatedMetadata[]
     */
    private $children;

    /**
     * @var string
     */
    private $baseTranslationPrefix;

    /**
     * @var string
     */
    private $entityTranslationPrefix;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var string
     */
    private $controllerId;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $filterFormTypeName;

    /**
     * @var string
     */
    private $formTypeName;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var array
     */
    private $mappings;

    /**
     * @var string
     */
    private $routingPrefix;

    /**
     * @var string
     */
    private $translationClass;

    /**
     * @param string $baseTranslationPrefix   Base translation prefix
     * @param string $entityTranslationPrefix Entity translation prefix
     * @param array  $configuration           Configuration
     * @param string $controllerId            Controller service ID
     * @param string $entityClass             Entity class
     * @param string $entityName              Entity name
     * @param string $filterFormTypeName      Filter form type name
     * @param string $formTypeName            Form type name
     * @param string $identifier              Identifier
     * @param array  $mappings                Mappings
     * @param string $routingPrefix           Routing prefix
     * @param string $translationClass        Translation class
     */
    public function __construct(
        $baseTranslationPrefix,
        $entityTranslationPrefix,
        array $configuration,
        $controllerId,
        $entityClass,
        $entityName,
        $filterFormTypeName,
        $formTypeName,
        $identifier,
        array $mappings,
        $routingPrefix,
        $translationClass
    ) {
        $this->baseTranslationPrefix = $baseTranslationPrefix;
        $this->entityTranslationPrefix = $entityTranslationPrefix;
        $this->configuration = $configuration;
        $this->controllerId = $controllerId;
        $this->entityClass = $entityClass;
        $this->entityName = $entityName;
        $this->filterFormTypeName = $filterFormTypeName;
        $this->formTypeName = $formTypeName;
        $this->identifier = $identifier;
        $this->mappings = $mappings;
        $this->routingPrefix = $routingPrefix;
        $this->translationClass = $translationClass;

        $this->children = [];
    }

    /**
     * @param string $property Property name
     *
     * @return bool
     */
    public function isAssociation($property)
    {
        return isset($this->mappings[$property]['targetEntity']);
    }

    /**
     * @return bool
     */
    public function isFilterFormEnabled()
    {
        return !empty($this->configuration['form']['filter']['type'])
            || !empty($this->configuration['form']['filter']['field_groups'])
            || !empty($this->configuration['form']['filter']['fields']);
    }

    /**
     * @param \Darvin\AdminBundle\Metadata\AssociatedMetadata $parent parent
     *
     * @return Metadata
     */
    public function setParent(AssociatedMetadata $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return \Darvin\AdminBundle\Metadata\AssociatedMetadata
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return !empty($this->parent);
    }

    /**
     * @param \Darvin\AdminBundle\Metadata\AssociatedMetadata $child Child
     *
     * @return Metadata
     */
    public function addChild(AssociatedMetadata $child)
    {
        $this->children[$child->getMetadata()->getEntityClass()] = $child;

        return $this;
    }

    /**
     * @return \Darvin\AdminBundle\Metadata\AssociatedMetadata[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param string $entityClass Child entity class
     *
     * @return bool
     */
    public function hasChild($entityClass)
    {
        return isset($this->children[$entityClass]);
    }

    /**
     * @param string $entityClass Child entity class
     *
     * @return \Darvin\AdminBundle\Metadata\AssociatedMetadata
     */
    public function getChild($entityClass)
    {
        return $this->children[$entityClass];
    }

    /**
     * @return string
     */
    public function getBaseTranslationPrefix()
    {
        return $this->baseTranslationPrefix;
    }

    /**
     * @return string
     */
    public function getEntityTranslationPrefix()
    {
        return $this->entityTranslationPrefix;
    }

    /**
     * @param array $configuration configuration
     *
     * @return Metadata
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getControllerId()
    {
        return $this->controllerId;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getFilterFormTypeName()
    {
        return $this->filterFormTypeName;
    }

    /**
     * @return string
     */
    public function getFormTypeName()
    {
        return $this->formTypeName;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * @return string
     */
    public function getRoutingPrefix()
    {
        return $this->routingPrefix;
    }

    /**
     * @return string
     */
    public function getTranslationClass()
    {
        return $this->translationClass;
    }
}
