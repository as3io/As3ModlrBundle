<?php

namespace As3\Bundle\ModlrBundle\Command\Schema;

use As3\Bundle\ModlrBundle\Schema\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Utilizes the Schema Manager to create or update indices
 *
 * @author  Josh Worden <solocommand@gmail.com>
 */
class Create extends Command
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * Constructor.
     *
     * @param   Manager     $manager
     */
    public function __construct(Manager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('as3:modlr:schema:create')
            ->setDescription('Creates model indices.')
            ->addArgument('type', InputArgument::OPTIONAL, 'Specify the model type to create for.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type') ?: null;
        $types = (null === $type) ? 'all types' : sprintf('model type "%s"', $type);

        $count = count($this->manager->getIndices($type));
        $output->writeln(sprintf('Creating <info>%s</info> %s for <info>%s</info>',$count, $count == 1 ? 'index' : 'indices', $types));

        foreach ($this->manager->getIndices($type) as $index) {
            $output->writeln(sprintf('    Creating index <info>%s</info> for model <info>%s</info>', $index['name'], $index['model_type']));
            $this->manager->createIndex($index);
        }

        $output->writeln('<info>Done!</info>');
    }
}
