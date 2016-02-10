<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2016, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\View\WidgetGenerator;

use Darvin\AdminBundle\Security\Permissions\Permission;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple list view widget generator
 */
class SimpleListGenerator extends AbstractWidgetGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function generateWidget($entity, array $options, $property)
    {
        if (isset($options['property'])) {
            $property = $options['property'];
        }

        $items = $this->getPropertyValue($entity, $property);

        if (!is_array($items) && !$items instanceof \Traversable) {
            $message = sprintf(
                'Property "%s::$%s" must contain array or instance of \Traversable, "%s" provided.',
                ClassUtils::getClass($entity),
                $property,
                gettype($items)
            );

            throw new WidgetGeneratorException($message);
        }

        return $this->render($options, array(
            'items' => $items,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined('property')
            ->setAllowedTypes('property', 'string');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredPermissions()
    {
        return array(
            Permission::VIEW,
        );
    }
}