<?php
/**
 * Created by PhpStorm.
 * User: jess
 * Date: 27/09/18
 * Time: 11:54
 */

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HebergementMigrateCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName("copy-ftp")
            ->setHelp("Copy files by ftp")
            ->setDefinition(
                new InputDefinition(
                    array(
                        new InputOption('from', 'f', InputOption::VALUE_REQUIRED, "from index"),
                        new InputOption('count', 'c', InputOption::VALUE_REQUIRED, "elemnt count to transfer"),
                        new InputOption('source_folder','s', InputOption::VALUE_REQUIRED, "Source folder from ftp source"),
                        new InputOption('destination_folder', 'd', InputOption::VALUE_REQUIRED, "Destination folder from ftp destination"),
                    )
                )
            );
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getOption("from");
        $limit = $input->getOption("count");
        $fsource = $input->getOption("source_folder");
        $fdest = $input->getOption("destination_folder");
        $output->writeln("copying files from : " . $from . " limit size: " . $limit);

        //set ADR_SERVER_SOURCE as your need
        $source_id = ftp_connect(ADR_SERVER_SOURCE);

        //set ADR_SERVER_DESTINATION as your need
        $destination_id = ftp_connect(ADR_SERVER_DESTINATION);
        $connect_dest = ftp_login($destination_id, "your_login_server_1", "your_mdp_server_1");
        $connect_source = ftp_login($source_id, "your_login_server_2", "your_login_mdp_2");
        $output->writeln("Connecting to remote server using ftp...");
        if ($connect_source && $connect_dest) {
            //Pass to passive mode to be able to list and read remote folders / files
            ftp_pasv($source_id, true);
            ftp_pasv($destination_id, true);

            $output->writeln("Successfully connected to server");

            if (ftp_chdir($source_id,$fsource)) {
                $output->writeln("directory succesfully changed to :" . ftp_pwd($source_id));
                $contents = ftp_pwd($source_id);
                $lists = ftp_nlist($source_id, $contents);
                
                //Limiter le nombre de fichier à copier
                $toExtract = array_slice($lists, $from, $limit - 1);
                $nb_element = count($toExtract);
                foreach ($toExtract as $item) {
                
                    $output->writeln("Nombre d'élement restant : " . $nb_element);

                    //write instruction to get extension
                    $local_file = "temp-" . $from . ".jpg";
                    $output->writeln("file to find : " . $item;
                
                    $output->writeln("writting file to local machine...");

                    if (ftp_get($source_id, $local_file, $item, FTP_BINARY)) {
                        try {
                            ftp_put(
                                $destination_id,
                                $fdest."/" . $item,
                                $local_file,
                                FTP_BINARY
                            );
                        } catch (\Exception $exception) {
                            $output->writeln("Error : " . $exception->getTraceAsString());
                        }
                    } else {
                        $output->writeln("cannot download file from server");
                    }   
                    $nb_element --;
                }
            } else {
                $output->writeln("Cannot change directory");
            }
        }
        ftp_close($source_id);
        ftp_close($destination_id);
    }
}
