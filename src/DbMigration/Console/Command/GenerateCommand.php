<?php
namespace ryunosuke\DbMigration\Console\Command;

use ryunosuke\DbMigration\Transporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('dbal:generate')->setDescription('Generate to Record file.');
        $this->setDefinition(array(
            new InputArgument('files', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Definitation files. First argument is meaned schema.'),
            new InputOption('where', 'w', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Where condition.'),
            new InputOption('ignore', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Ignore column.'),
        ));
        $this->setHelp(<<<EOT
Generate to SQL file baseed on extension.
 e.g. `dbal:generate table.sql record.yml`
 e.g. `dbal:generate table.sql record.yml --where t_table.column=1`
 e.g. `dbal:generate table.sql record.yml --ignore t_table.column`
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln(var_export($input->getArguments(), true));
            $output->writeln(var_export($input->getOptions(), true));
        }

        // normalize file
        $files = $this->normalizeFile($input);

        // option
        $wheres = (array) $input->getOption('where') ?: array();
        $ignores = (array) $input->getOption('ignore') ?: array();

        // get target Connection
        $conn = $this->getHelper('db')->getConnection();

        // export sql files from argument
        $transporter = new Transporter($conn);
        $ddl = $transporter->exportDDL(array_shift($files));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE){
            $output->writeln($ddl);
        }
        foreach ($files as $filename) {
            $dml = $transporter->exportDML($filename, $wheres, $ignores);
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE){
                $output->writeln($dml);
            }
        }
    }

    private function normalizeFile(InputInterface $input)
    {
        $files = (array) $input->getArgument('files');

        $result = array();

        foreach ($files as $file) {
            $filePath = realpath($file);

            if (false === $filePath) {
                $filePath = $file;
            }

            if (is_dir($filePath)) {
                throw new \InvalidArgumentException(sprintf("Record file '<info>%s</info>' is directory.", $filePath));
            }

            $result[] = $filePath;
        }

        return $result;
    }
}