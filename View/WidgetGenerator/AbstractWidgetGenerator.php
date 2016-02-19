<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\View\WidgetGenerator;

use Darvin\AdminBundle\Metadata\MetadataManager;
use Darvin\ContentBundle\Translatable\TranslatableException;
use Darvin\Utils\Strings\StringsUtil;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * View widget generator abstract implementation
 */
abstract class AbstractWidgetGenerator implements WidgetGeneratorInterface
{
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var \Darvin\AdminBundle\Metadata\MetadataManager
     */
    protected $metadataManager;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    private $templating;

    /**
     * @var \Symfony\Component\OptionsResolver\OptionsResolver
     */
    private $optionsResolver;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker Authorization checker
     * @param \Darvin\AdminBundle\Metadata\MetadataManager                                 $metadataManager      Metadata manager
     * @param \Symfony\Component\PropertyAccess\PropertyAccessorInterface                  $propertyAccessor     Property accessor
     * @param \Symfony\Component\Templating\EngineInterface                                $templating           Templating
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        MetadataManager $metadataManager,
        PropertyAccessorInterface $propertyAccessor,
        EngineInterface $templating
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->metadataManager = $metadataManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->templating = $templating;
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);

        $this->alias = null;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($entity, array $options = array(), $property = null)
    {
        $this->validate($entity, $options);

        foreach ($this->getRequiredPermissions() as $permission) {
            if (!$this->isGranted($permission, $entity)) {
                return '';
            }
        }

        return $this->generateWidget($entity, $options, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        if (empty($this->alias)) {
            $parts = explode('\\', get_class($this));
            $this->alias = StringsUtil::toUnderscore(preg_replace('/Generator$/', '', array_pop($parts)));
        }

        return $this->alias;
    }

    /**
     * @param object $entity   Entity
     * @param array  $options  Options
     * @param string $property Property name
     *
     * @return string
     */
    abstract protected function generateWidget($entity, array $options, $property);

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver Options resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @return string
     */
    protected function getDefaultTemplate()
    {
        return sprintf('DarvinAdminBundle:Widget:%s.html.twig', $this->getAlias());
    }

    /**
     * @param object $entity       Entity
     * @param string $propertyPath Property path
     *
     * @return mixed
     * @throws \Darvin\AdminBundle\View\WidgetGenerator\WidgetGeneratorException
     */
    protected function getPropertyValue($entity, $propertyPath)
    {
        try {
            if (!$this->propertyAccessor->isReadable($entity, $propertyPath)) {
                $message = sprintf(
                    'Unable to get value of "%s::$%s" property: it is not readable.',
                    ClassUtils::getClass($entity),
                    $propertyPath
                );

                throw new WidgetGeneratorException($message);
            }
        } catch (TranslatableException $ex) {
            $message = sprintf(
                'Unable to get value of "%s::$%s" property: %s',
                ClassUtils::getClass($entity),
                $propertyPath,
                lcfirst($ex->getMessage())
            );

            throw new WidgetGeneratorException($message);
        }

        return $this->propertyAccessor->getValue($entity, $propertyPath);
    }

    /**
     * @return array
     */
    protected function getAllowedEntityClasses()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function getRequiredPermissions()
    {
        return array();
    }

    /**
     * @param string $permission Permission
     * @param object $entity     Entity
     *
     * @return bool
     */
    protected function isGranted($permission, $entity)
    {
        return $this->authorizationChecker->isGranted($permission, $entity);
    }

    /**
     * @param array $options        Options
     * @param array $templateParams Template parameters
     *
     * @return string
     */
    protected function render(array $options, array $templateParams = array())
    {
        $template = isset($options['template']) ? $options['template'] : $this->getDefaultTemplate();

        return $this->templating->render($template, array_merge($options, $templateParams));
    }

    /**
     * @param object $entity  Entity
     * @param array  $options Options
     *
     * @throws \Darvin\AdminBundle\View\WidgetGenerator\WidgetGeneratorException
     */
    private function validate($entity, array &$options)
    {
        $allowedEntityClasses = $this->getAllowedEntityClasses();

        if (!empty($allowedEntityClasses)) {
            $entityClassAllowed = false;

            foreach ($allowedEntityClasses as $allowedEntityClass) {
                if ($entity instanceof $allowedEntityClass) {
                    $entityClassAllowed = true;

                    break;
                }
            }
            if (!$entityClassAllowed) {
                $message = sprintf(
                    'View widget generator "%s" requires entity to be instance of one of "%s" classes.',
                    $this->getAlias(),
                    implode('", "', $allowedEntityClasses)
                );

                throw new WidgetGeneratorException($message);
            }
        }
        try {
            $options = $this->optionsResolver->resolve($options);
        } catch (ExceptionInterface $ex) {
            throw new WidgetGeneratorException(
                sprintf('View widget generator "%s" options are invalid: "%s".', $this->getAlias(), $ex->getMessage())
            );
        }
    }
}
