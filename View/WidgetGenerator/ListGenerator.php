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

use Darvin\AdminBundle\Security\Permissions\Permission;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * List view widget generator
 */
class ListGenerator extends AbstractWidgetGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function generateWidget($entity, $property, array $options)
    {
        $keys = $this->getPropertyValue($entity, $options['keys_property']);

        if (null === $keys) {
            return '';
        }
        if (!is_array($keys) && !$keys instanceof \Traversable) {
            $message = sprintf(
                'Keys property "%s::$%s" must contain array or instance of \Traversable, "%s" provided.',
                ClassUtils::getClass($entity),
                $options['keys_property'],
                gettype($keys)
            );

            throw new WidgetGeneratorException($message);
        }

        $list = $this->createList($keys, $this->getValues($options));

        if (empty($list)) {
            return '';
        }
        if (!isset($options['sort']) || $options['sort']) {
            sort($list);
        }

        return $this->render($options, array(
            'list' => $list,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(array(
                'keys_property',
                'values_callback',
            ))
            ->setAllowedTypes('keys_property', 'string')
            ->setAllowedTypes('values_callback', 'callable');
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

    /**
     * @param array $keys   Keys
     * @param array $values Values
     *
     * @return array
     */
    private function createList(array $keys, array $values)
    {
        $list = array();

        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                $list[$key] = $values[$key];
            }
        }

        return $list;
    }

    /**
     * @param array $options Options
     *
     * @return array
     * @throws \Darvin\AdminBundle\View\WidgetGenerator\WidgetGeneratorException
     */
    private function getValues(array $options)
    {
        $valuesCallback = $options['values_callback'];
        $values = $valuesCallback();

        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new WidgetGeneratorException(
                sprintf('Values callback must return array or instance of \Traversable, "%s" provided.', gettype($values))
            );
        }

        return $values;
    }
}
