<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Command;

use Darvin\AdminBundle\Metadata\MetadataManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Debug admin section configuration command
 */
class DebugConfigCommand extends Command
{
    /**
     * @var \Darvin\AdminBundle\Metadata\MetadataManager
     */
    private $metadataManager;

    /**
     * @param string                                       $name            Command name
     * @param \Darvin\AdminBundle\Metadata\MetadataManager $metadataManager Admin metadata manager
     */
    public function __construct($name, MetadataManager $metadataManager)
    {
        parent::__construct($name);

        $this->metadataManager = $metadataManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dumps admin section configuration.')
            ->setDefinition([
                new InputArgument('entity', InputArgument::REQUIRED, 'Entity class'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        (new SymfonyStyle($input, $output))->writeln(
            Yaml::dump($this->metadataManager->getConfiguration($input->getArgument('entity')), 10)
        );
    }
}
