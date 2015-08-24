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

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * List view widget generator
 */
class ListGenerator extends AbstractWidgetGenerator
{
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @param \Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor Property accessor
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($entity, array $options = array())
    {
        $this->validate($entity, $options);

        if (!$this->propertyAccessor->isReadable($entity, $options['keys_property'])) {
            $message = sprintf(
                'Property "%s::$%s" is not readable. Make sure it has public access.',
                ClassUtils::getClass($entity),
                $options['keys_property']
            );

            throw new WidgetGeneratorException($message);
        }

        $keys = $this->propertyAccessor->getValue($entity, $options['keys_property']);

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

        $valuesCallback = $options['values_callback'];

        if (!is_callable($valuesCallback)) {
            throw new WidgetGeneratorException('Values callback is not callable.');
        }

        $values = $valuesCallback();

        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new WidgetGeneratorException(
                sprintf('Values callback must return array or instance of \Traversable, "%s" provided.', gettype($values))
            );
        }

        $list = array();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $list[$key] = $values[$key];
        }
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
    public function getAlias()
    {
        return 'list';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultTemplate()
    {
        return 'DarvinAdminBundle:widget:list.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredOptions()
    {
        return array(
            'keys_property',
            'values_callback',
        );
    }
}