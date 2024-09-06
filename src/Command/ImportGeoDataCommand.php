<?php

namespace App\Command;

use App\Entity\Department;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-geo-data',
    description: 'Import geographic data for France from CSV files',
)]
class ImportGeoDataCommand extends Command
{
    private $entityManager;
    private $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting geographic data import...');

        $this->importRegions($output);
        $this->importDepartments($output);

        $output->writeln('Geographic data import completed successfully.');

        return Command::SUCCESS;
    }

    private function importRegions(OutputInterface $output): void
    {
        $output->writeln('Importing regions...');
        $regions = $this->readCsv($this->parameterBag->get('kernel.project_dir') . '/data/regions.csv');
        $progressBar = new ProgressBar($output, count($regions));

        foreach ($regions as $regionName) {
            $region = new Region();
            $region->setName($regionName[0]);  // Le nom de la région est dans la première (et seule) colonne
            $this->entityManager->persist($region);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $output->writeln('');
    }

    private function importDepartments(OutputInterface $output)
    {
        $output->writeln('Importing departments...');
        $departments = $this->readCsv($this->parameterBag->get('kernel.project_dir') . '/data/departments.csv');
        $progressBar = new ProgressBar($output, count($departments));

        foreach ($departments as $departmentData) {
            $department = new Department();
            $department->setCode($departmentData[0]);
            $department->setName($departmentData[1]);

            $region = $this->entityManager->getRepository(Region::class)->findOneBy(['name' => $departmentData[2]]);
            if (!$region) {
                $output->writeln("Region not found: " . $departmentData[2]);
                continue;
            }

            $department->setRegion($region);
            $this->entityManager->persist($department);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $output->writeln('');
    }

    private function readCsv($filename): array
    {
        $data = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }
}