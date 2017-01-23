<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Form\Type;

use Darvin\AdminBundle\CKEditor\AbstractCKEditorWidget;
use Darvin\ContentBundle\Widget\WidgetPoolInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * CKEditor form type
 */
class CKEditorType extends AbstractType
{
    const CONFIG_NAME = 'darvin_admin';

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var \Darvin\ContentBundle\Widget\WidgetPoolInterface
     */
    private $widgetPool;

    /**
     * @var string
     */
    private $pluginFilename;

    /**
     * @var string
     */
    private $pluginsPath;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack   $requestStack   Request stack
     * @param \Symfony\Component\Routing\RouterInterface       $router         Router
     * @param \Darvin\ContentBundle\Widget\WidgetPoolInterface $widgetPool     Widget pool
     * @param string                                           $pluginFilename Plugin filename
     * @param string                                           $pluginsPath    Plugins path
     */
    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        WidgetPoolInterface $widgetPool,
        $pluginFilename,
        $pluginsPath
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->widgetPool = $widgetPool;
        $this->pluginFilename = $pluginFilename;
        $this->pluginsPath = $pluginsPath;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $plugins = [
            'lineutils' => [
                'path'     => $this->pluginsPath.'/lineutils/',
                'filename' => 'plugin.js',
            ],
            'widget' => [
                'path'     => $this->pluginsPath.'/widget/',
                'filename' => 'plugin.js',
            ],
        ];

        $extraPlugins = [
            'lineutils',
            'widget',
        ];

        foreach ($this->widgetPool->getAllWidgets() as $widget) {
            if (!$widget instanceof AbstractCKEditorWidget) {
                continue;
            }

            $widgetName = $widget->getName();

            $plugins[$widgetName] = [
                'path'     => $this->router->generate('darvin_admin_ckeditor_plugin_path', [
                    'widgetName' => $widgetName,
                ]),
                'filename' => 'plugin.js',
            ];
            $extraPlugins[] = $widgetName;
        }

        // Config
        $config = $view->vars['config'];

        $extraPluginsString = implode(',', $extraPlugins);
        $config['extraPlugins'] = isset($config['extraPlugins']) && !empty($config['extraPlugins'])
            ? $config['extraPlugins'].','.$extraPluginsString
            : $extraPluginsString;

        if (isset($config['toolbar'])) {
            $config['toolbar'] = array_merge($config['toolbar'], [$extraPlugins]);
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!isset($config['language']) && !empty($request)) {
            $config['language'] = $request->getLocale();
        }

        $view->vars['config'] = $config;

        // Plugins
        $view->vars['plugins'] = isset($view->vars['plugins'])
            ? array_merge($view->vars['plugins'], $plugins)
            : $plugins;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('config_name', self::CONFIG_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'Ivory\CKEditorBundle\Form\Type\CKEditorType';
    }
}
