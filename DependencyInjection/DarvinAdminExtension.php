<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\DependencyInjection;

use Darvin\Utils\DependencyInjection\ConfigInjector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DarvinAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $configInjector = new ConfigInjector();
        $configInjector->inject($config, $container, $this->getAlias());

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('asset/provider.yml');
        $loader->load('breadcrumbs.yml');
        $loader->load('cache.yml');
        $loader->load('ckeditor.yml');
        $loader->load('configuration.yml');
        $loader->load('crud.yml');
        $loader->load('dashboard.yml');
        $loader->load('dropzone.yml');
        $loader->load('form.yml');
        $loader->load('image.yml');
        $loader->load('log.yml');
        $loader->load('menu.yml');
        $loader->load('metadata.yml');
        $loader->load('route.yml');
        $loader->load('security.yml');
        $loader->load('twig.yml');
        $loader->load('uploader.yml');
        $loader->load('view.yml');

        if ('dev' === $container->getParameter('kernel.environment')) {
            $loader->load('asset/compiler.yml');
        }
    }
}
