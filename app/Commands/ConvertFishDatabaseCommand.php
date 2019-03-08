<?php

namespace App\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Implements the command line idealstack-agent command, which runs inside containers to bootstrap them and apply idealstack config settings
 * Class AgentCommand
 * @package App
 */
class ConvertFishDatabaseCommand extends Command
{

    private $output_file = [];
    private $lines_written = [];
    private $fh_output = [];

    //NIWA publishes this as an XLS file here https://www.niwa.co.nz/freshwater-and-estuaries/nzffd/user-guide/tips
    const SPECIES = [
        'aldfor' => ['Aldrichetta forsteri', 'Yelloweye mullet'],
        'ameneb' => ['Ameiurus nebulosus', 'Catfish'],
        'angaus' => ['Anguilla australis', 'Shortfin eel'],
        'angdie' => ['Anguilla dieffenbachii', 'Longfin eel'],
        'angrei' => ['Anguilla reinhardtii', 'Australian longfin eel'],
        'anguil' => ['Anguilla spp.', 'Unidentified eel'],
        'caraur' => ['Carassius auratus', 'Goldfish'],
        'chefos' => ['Cheimarrichthys fosteri', 'Torrentfish'],
        'cteide' => ['Ctenopharyngodon idella', 'Grass carp'],
        'cypcar' => ['Cyprinus carpio', 'Koi carp'],
        'galano' => ['Galaxias anomalus', 'Roundhead galaxias'],
        'galarg' => ['Galaxias argenteus', 'Giant kokopu'],
        'galaxi' => ['Galaxias spp.', 'Unidentified galaxiid'],
        'galbre' => ['Galaxias brevipinnis', 'Koaro'],
        'galcob' => ['Galaxias cobitinis', 'Lowland longjaw galaxias'],
        'galdep' => ['Galaxias depressiceps', 'Flathead galaxias'],
        'galdiv' => ['Galaxias divergens', 'Dwarf galaxias'],
        'galeld' => ['Galaxias eldoni', 'Eldons galaxias'],
        'galfas' => ['Galaxias fasciatus', 'Banded kokopu'],
        'galgol' => ['Galaxias gollumoides', 'Gollum galaxias'],
        'galgra' => ['Galaxias gracilis', 'Dwarf inanga'],
        'galmac' => ['Galaxias maculatus', 'Inanga'],
        'galmar' => ['Galaxias macronasus', 'Bignose galaxias'],
        'galpau' => ['Galaxias paucispondylus', 'Alpine galaxias'],
        'galpos' => ['Galaxias postvectis', 'Shortjaw kokopu'],
        'galpro' => ['Galaxias prognathus', 'Upland longjaw galaxias'],
        'galpul' => ['Galaxias pullus', 'Dusky galaxias'],
        'galspd' => ['Galaxias "species D"', 'Galaxias "species D"'],
        'galspn' => ['Galaxias "northern"', 'Galaxias "northern"'],
        'galsps' => ['Galaxias "southern"', 'Galaxias "southern"'],
        'galspt' => ['Galaxias "teviot"', 'Galaxias "teviot"'],
        'galvul' => ['Galaxias vulgaris', 'Canterbury galaxias'],
        'gamaff' => ['Gambusia affinis', 'Gambusia'],
        'geoaus' => ['Geotria australis', 'Lamprey'],
        'gobalp' => ['Gobiomorphus alpinus', 'Tarndale bully'],
        'gobbas' => ['Gobiomorphus basalis', 'Crans bully'],
        'gobbre' => ['Gobiomorphus breviceps', 'Upland bully'],
        'gobcot' => ['Gobiomorphus cotidianus', 'Common bully'],
        'gobgob' => ['Gobiomorphus gobioides', 'Giant bully'],
        'gobhub' => ['Gobiomorphus hubbsi', 'Bluegill bully'],
        'gobhut' => ['Gobiomorphus huttoni', 'Redfin bully'],
        'gobiom' => ['Gobiomorphus spp.', 'Unidentified bully'],
        'graham' => ['Grahamina sp.', 'Estuarine triplefin'],
        'hypmol' => ['Hypophthalmichthys molitrix', 'Silver carp'],
        'hyrmen' => ['Hyridella menziesi', 'Freshwater mussel'],
        'leuidu' => ['Leuciscus idus', 'Golden orfe'],
        'marine' => ['Marine', 'Marine species'],
        'mugcep' => ['Mugil cephalus', 'Grey mullet'],
        'mugil' => ['Mugil', 'Unidentified mullet'],
        'neoapo' => ['Neochanna apoda', 'Brown mudfish'],
        'neobur' => ['Neochanna burrowsius', 'Canterbury mudfish'],
        'neodiv' => ['Neochanna diversus', 'Black mudfish'],
        'neohel' => ['Neochanna heleios', 'Burgundy mudfish'],
        'neorek' => ['Neochanna rekohua', 'Chatham mudfish'],
        'nospec' => ['Nil', 'No species recorded'],
        'oncmyk' => ['Oncorhynchus mykiss', 'Rainbow trout'],
        'oncner' => ['Oncorhynchus nerka', 'Sockeye salmon'],
        'onctsh' => ['Oncorhynchus tshawytscha', 'Chinook salmon'],
        'parane' => ['Paranephrops spp.', 'Koura'],
        'parcur' => ['Paratya curvirostris', 'Freshwater shrimp'],
        'parmar' => ['Parioglossus marginalis', 'Dart goby'],
        'perflu' => ['Perca fluviatilis', 'Perch'],
        'poelat' => ['Poecilia latipinna', 'Sailfin molly'],
        'poeret' => ['Poecilia reticulata', 'Guppy'],
        'prooxy' => ['Prototroctes oxyrhynchus', 'Grayling'],
        'retret' => ['Retropinna retropinna', 'Common smelt'],
        'rhombo' => ['Rhombosolea spp.', 'Unidentified flounder'],
        'rhoret' => ['Rhombosolea retiaria', 'Black flounder'],
        'salfon' => ['Salvelinus fontinalis', 'Brook char'],
        'salmo' => ['Salmo', 'Unidentified salmonid'],
        'salsal' => ['Salmo salar', 'Atlantic salmon'],
        'saltru' => ['Salmo trutta', 'Brown trout'],
        'scaery' => ['Scardinius erythrophthalmus', 'Rudd'],
        'stoani' => ['Stokellia anisodon', 'Stokells smelt'],
        'tintin' => ['Tinca tinca', 'Tench'],
    ];

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('convert-csv-to-google-maps')
            ->setDescription('Convert NIWA CSV file to format suitable for importing to google maps')
            ->addArgument('file', InputArgument::REQUIRED,
                'The NIWA CSV File.  Download from https://nzffdms.niwa.co.nz/search')
            ->addArgument('output_dir', InputArgument::REQUIRED, 'The output directory')
            ->addOption('file-per-species', null, InputOption::VALUE_NONE,
                'Create a separate output file for each species')
            ->addOption('location', null, InputOption::VALUE_REQUIRED,
                'A decimal latitude/longitude pair - we will only generate data within 1degree (approx 110km) radius of that location')
            ->addOption('radius', null, InputOption::VALUE_REQUIRED,
                'Radius (in degrees of latitude/longitude) to calculate around location.  Default is 0.25');
    }


    private function writeRow(
        InputInterface $input,
        OutputInterface $output,
        string $dir,
        string $prefix,
        array $header,
        array $row
    ) {
        //Create a new output file every 2k lines
        if (
            !array_key_exists($prefix, $this->output_file) ||
            !$this->output_file[$prefix] ||
            !array_key_exists($prefix, $this->lines_written) ||
            !($this->lines_written[$prefix] % 2000)
        ) {
            if (!array_key_exists($prefix, $this->lines_written)) {
                $this->lines_written[$prefix] = 0;
            }
            $i = (int)($this->lines_written[$prefix] / 2000) + 1;
            $this->output_file[$prefix] = $dir . "/$prefix" . ($i != 1 ? $i : '') . ".csv";
            $this->fh_output[$prefix] = fopen($this->output_file[$prefix], "w");
//            $output->writeln("<comment>Outputting lines ".$this->lines_written[$prefix]."-" . ($this->lines_written[$prefix] + 2000) . " to ".$this->output_file[$prefix]."</comment>");
            fputcsv($this->fh_output[$prefix], $header);
            $this->lines_written[$prefix]++;

        }

        fputcsv($this->fh_output[$prefix], $row);
        $this->lines_written[$prefix]++;
    }

    function getLines($file)
    {
        $f = fopen($file, 'rb');
        $lines = 0;

        while (!feof($f)) {
            $lines += substr_count(fread($f, 8192), "\n");
        }

        fclose($f);

        return $lines;
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section1 = $output->section();

        $csv_file = $input->getArgument('file');
        $nzcoords = new \App\Services\NzCoordinates();

        $output_dir = realpath($input->getArgument('output_dir'));


        //Support only calculating a local area, defined as quarter of  degree lat/long around a given coordinate
        $lat = 0;
        $long = 0;
        if ($input->getOption('location')) {
            [$lat, $long] = explode(',', $input->getOption('location'));
            [$base_northing, $base_easting] = $nzcoords->nzGd1949ToNzmg($lat, $long);

            $radius = $input->getOption('radius') ?? 0.25;
            //What is 1 degree difference in lat/long converted into NZMG easting/northing
            $coords1 = $nzcoords->nzGd1949ToNzmg(-34, 172);
            $coords2 = $nzcoords->nzGd1949ToNzmg(-34 - $radius, 172 - $radius);

            $northing_difference = abs($coords1[0] - $coords2[0]);
            $easting_difference = abs($coords1[1] - $coords2[1]);
        }

        $file_per_species = false;
        if ($input->getOption('file-per-species')) {
            $file_per_species = true;
        }

        $output->writeln("\n<comment>\nConverting $csv_file into $output_dir/* " .
            ($input->getOption('file-per-species') ? ' - file per species' : '') .
            ($input->getOption('location') ? ' - within one degree of ' . $input->getOption('location') : '') .
            "</comment>");

        $fh = fopen($csv_file, "r");
        $lines = $this->getLines($csv_file);

        $header = fgetcsv($fh);
        $header[] = 'location';
        $header[] = 'species';

        $header_index = array_flip($header);
        $prefix = 'output';

        $i = 0;
        $start = time();
        while ($row = fgetcsv($fh)) {
            $i++;

            $north = $row[$header_index['north']];
            $east = $row[$header_index['east']];

            //If we are only outputting a subset of the data around a geographic area, skip if we're outside that
            if ($lat && $long) {
                if (abs($north - $base_northing) > $northing_difference
                    || abs($east - $base_easting) > $easting_difference) {
                    continue;
                }
            }

            //Convert NZMG easting and northing to lat and longitude  - Note this is slow!
            $coords = $nzcoords->nzmgToNzGd1949($north, $east);

            if ($file_per_species) {
                $prefix = $row[$header_index['spcode']];
            }

            $row[$header_index['location']] = "$coords[0],$coords[1]";
            $row[$header_index['species']] = implode(', ', self::SPECIES[$row[$header_index['spcode']]]);
            $this->writeRow($input, $output, $output_dir, $prefix, $header, $row);
            $now = time();

            $elapsed = $now - $start;
            $total_time = $elapsed / ($i/$lines);
            $remaining = $total_time - $elapsed;

            $section1->overwrite($i .'/'.$lines . ' approx '.sprintf("%0.0f elapsed %.0f",$elapsed,$remaining) .' seconds remaining');

        }

        $output->writeln('<comment>convert-csv-to-google-maps run complete: OK</comment>');
    }
}