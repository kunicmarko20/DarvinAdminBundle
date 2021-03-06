<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Security\Authorization\Voter;

use Darvin\AdminBundle\Metadata\MetadataManager;
use Darvin\AdminBundle\Security\Configuration\SecurityConfigurationPool;
use Darvin\AdminBundle\Security\Permissions\Permission;
use Darvin\UserBundle\Entity\BaseUser;
use Doctrine\Common\Util\ClassUtils;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Admin authorization voter
 */
class AdminVoter extends Voter
{
    /**
     * @var \Darvin\AdminBundle\Metadata\MetadataManager
     */
    private $metadataManager;

    /**
     * @var \Darvin\AdminBundle\Security\Configuration\SecurityConfigurationPool
     */
    private $securityConfigurationPool;

    /**
     * @var array
     */
    private $entityOverride;

    /**
     * @var array
     */
    private $supportedClasses;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var bool
     */
    private $initialized;

    /**
     * @param \Darvin\AdminBundle\Metadata\MetadataManager                         $metadataManager           Metadata manager
     * @param \Darvin\AdminBundle\Security\Configuration\SecurityConfigurationPool $securityConfigurationPool Security configuration pool
     * @param array                                                                $entityOverride            Entity override
     */
    public function __construct(MetadataManager $metadataManager, SecurityConfigurationPool $securityConfigurationPool, array $entityOverride)
    {
        $this->metadataManager = $metadataManager;
        $this->securityConfigurationPool = $securityConfigurationPool;
        $this->entityOverride = $entityOverride;

        $this->supportedClasses = $this->permissions = [];
        $this->initialized = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $objectOrClass, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof BaseUser) {
            return false;
        }
        if ($this->metadataManager->hasMetadata($objectOrClass)
            && $this->metadataManager->getConfiguration($objectOrClass)['oauth_only']
            && !$token instanceof OAuthToken
        ) {
            return false;
        }

        $this->init();

        $class = $this->getClass($objectOrClass);

        if (isset($this->permissions[$class][$user->getId()][$attribute])) {
            return $this->permissions[$class][$user->getId()][$attribute];
        }
        foreach (class_parents($class) as $parentClass) {
            if (isset($this->permissions[$parentClass][$user->getId()][$attribute])) {
                return $this->permissions[$parentClass][$user->getId()][$attribute];
            }
        }

        $defaultPermissions = Permission::getDefaultPermissions($user);

        return isset($defaultPermissions[$attribute]) ? $defaultPermissions[$attribute] : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $objectOrClass)
    {
        if (!in_array($attribute, Permission::getAllPermissions())) {
            return false;
        }

        $this->init();

        $class = $this->getClass($objectOrClass);

        if (in_array($class, $this->supportedClasses)) {
            return true;
        }
        foreach ($this->supportedClasses as $supportedClass) {
            if (is_subclass_of($class, $supportedClass)) {
                return true;
            }
        }

        return false;
    }

    private function init()
    {
        if ($this->initialized) {
            return;
        }
        foreach ($this->securityConfigurationPool->getAllConfigurations() as $configuration) {
            foreach ($configuration->getPermissions() as $objectPermissions) {
                $objectClass = $objectPermissions->getObjectClass();

                $this->supportedClasses[] = $objectClass;

                $this->permissions[$objectClass] = [];

                foreach ($objectPermissions->getUserPermissionsSet() as $userPermissions) {
                    $this->permissions[$objectClass][$userPermissions->getUserId()] = $userPermissions->getPermissions();
                }
            }
        }

        $this->initialized = true;
    }

    /**
     * @param mixed $objectOrClass Object or class
     *
     * @return string
     */
    private function getClass($objectOrClass)
    {
        $class = is_object($objectOrClass) ? ClassUtils::getClass($objectOrClass) : $objectOrClass;

        return isset($this->entityOverride[$class]) ? $this->entityOverride[$class] : $class;
    }
}
