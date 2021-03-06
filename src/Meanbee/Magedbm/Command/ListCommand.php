<?php
namespace Meanbee\Magedbm\Command;

use Aws\Common\Exception\InstanceProfileCredentialsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseCommand
{

    /**
     * Configure the command parameters.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ls')
            ->setDescription('List available backups')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Project identifier'
            )
            ->addOption(
                '--region',
                '-r',
                InputOption::VALUE_REQUIRED,
                'Optionally specify region, otherwise default configuration will be used.'
            )
            ->addOption(
                '--bucket',
                '-b',
                InputOption::VALUE_REQUIRED,
                'Optionally specify bucket, otherwise default configuration will be used. '
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $s3 = $this->getS3Client($input->getOption('region'));
        $config = $this->getConfig($input);

        try {
            $results = $s3->getIterator(
                'ListObjects',
                array('Bucket' => $config['bucket'], 'Prefix' => $input->getArgument('name'))
            );

            foreach ($results as $item) {
                $itemKeyChunks = explode('/', $item['Key']);
                $this->getOutput()->writeln(sprintf('%s %dMB', array_pop($itemKeyChunks) , $item['Size'] / 1024 / 1024));
            }

            if (!$results->count()) {
                // Credentials Exception would have been thrown by now, so now we can safely check for item count.
                $this->getOutput()->writeln(sprintf('<error>No backups found for %s</error>', $input->getArgument('name')));
            }
        } catch (InstanceProfileCredentialsException $e) {
            $this->getOutput()->writeln('<error>AWS credentials not found. Please run `configure` command.</error>');
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

}