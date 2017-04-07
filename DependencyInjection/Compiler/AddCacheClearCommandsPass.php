<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\DependencyInjection\Compiler;

use Darvin\Utils\Strings\StringsUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add cache clear commands compiler pass
 */
class AddCacheClearCommandsPass implements CompilerPassInterface
{
    const CACHES_CLEAR_COMMAND_ID = 'darvin_admin.cache.clear_command';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CACHES_CLEAR_COMMAND_ID)) {
            return;
        }

        $cacheClearCommands = $container->getParameter('darvin_admin.cache_clear_commands');

        if (empty($cacheClearCommands)) {
            return;
        }

        $cachesClearCommandDefinition = $container->getDefinition(self::CACHES_CLEAR_COMMAND_ID);

        $definitions = [];

        foreach ($cacheClearCommands as $cacheClearCommand) {
            $class = $cacheClearCommand['class'];

            $definition = new Definition($class);
            $definition
                ->setPublic(false)
                ->addTag('console.command');

            if (is_subclass_of($class, ContainerAwareCommand::class)) {
                $definition->addMethodCall('setContainer', [
                    new Reference('service_container'),
                ]);
            }

            $id = 'darvin_admin.cache.'.StringsUtil::toUnderscore(str_replace('\\', '_', $class));

            $cachesClearCommandDefinition->addMethodCall('addCacheClearCommand', [
                new Reference($id),
                $cacheClearCommand['input'],
            ]);

            $definitions[$id] = $definition;
        }

        $container->addDefinitions($definitions);
    }
}
