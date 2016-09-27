<?php

namespace As3\Bundle\ModlrBundle\Command\Schema;

use As3\Modlr\Store\Store;
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
     * @var Store
     */
    private $store;

    /**
     * Constructor.
     *
     * @param   Store   $store
     */
    public function __construct(Store $store)
    {
        parent::__construct();
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('as3:modlr:schema:create')
            ->setDescription('Creates model schema.')
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

        $output->writeln(sprintf('Creating schemata for <info>%s</info>', $types));

        $types = null === $type ? $this->store->getModelTypes() : [$type];
        foreach ($types as $type) {
            $output->writeln(sprintf('    Creating schemata for <info>%s</info>', $type));
            $metadata = $this->store->getMetadataForType($type);
            $persister = $this->store->getPersisterFor($type);
            $persister->createSchemata($metadata);
        }
        $output->writeln('<info>Done!</info>');
    }
}
