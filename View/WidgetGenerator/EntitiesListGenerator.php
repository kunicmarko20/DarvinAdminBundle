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
 * Entities list view widget generator
 */
class EntitiesListGenerator extends AbstractWidgetGenerator
{
    /**
     * @var \Darvin\AdminBundle\View\WidgetGenerator\WidgetGeneratorPool
     */
    private $widgetGeneratorPool;

    /**
     * @param \Darvin\AdminBundle\View\WidgetGenerator\WidgetGeneratorPool $widgetGeneratorPool View widget generator pool
     */
    public function setWidgetGeneratorPool(WidgetGeneratorPool $widgetGeneratorPool)
    {
        $this->widgetGeneratorPool = $widgetGeneratorPool;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateWidget($entity, array $options, $property)
    {
        $collection = $this->getPropertyValue($entity, isset($options['property']) ? $options['property'] : $property);

        if (empty($collection)) {
            return '';
        }
        if (!is_array($collection)) {
            if (!is_object($collection)) {
                throw new WidgetGeneratorException(
                    sprintf('Entities collection must be array or object, "%s" provided.', gettype($collection))
                );
            }
            if (!$collection instanceof \Traversable) {
                throw new WidgetGeneratorException(
                    sprintf('Entities collection object "%s" must be instance of \Traversable.', ClassUtils::getClass($collection))
                );
            }
        }
        if (empty($options['item_widget_alias'])) {
            return $this->render($options, array(
                'widgets' => $collection,
            ));
        }

        $widgetGenerator = $this->widgetGeneratorPool->getWidgetGenerator($options['item_widget_alias']);

        $widgets = array();

        foreach ($collection as $item) {
            $widgets[] = $widgetGenerator->generate($item, $options['item_widget_options']);
        }

        return $this->render($options, array(
            'widgets' => $widgets,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults(array(
                'item_widget_alias'   => ShowLinkGenerator::ALIAS,
                'item_widget_options' => array(
                    'text_link' => true,
                ),
            ))
            ->setDefined('property')
            ->setAllowedTypes('item_widget_alias', array(
                'null',
                'string',
            ))
            ->setAllowedTypes('item_widget_options', 'array')
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