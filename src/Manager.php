<?php

namespace VagrantManager;

use Parable\Console\Output;
use Parable\Console\Parameter;

class Manager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Output
     */
    private $output;

    /**
     * @var Parameter
     */
    private $parameter;

    private $commands = [
        "list", "up", "ssh", "up-ssh", "up-all", "halt",
        "halt-all", "reload", "reload-ssh", "reload-all", "destroy"
    ];

    public function __construct(
        Config $config,
        Output $output,
        Parameter $parameter
    ) {
        $this->config = $config;
        $this->output = $output;
        $this->parameter = $parameter;

        $this->parameter->checkCommandOptions();
        $this->parameter->checkCommandArguments();
    }

    public function run(): int
    {
        if (!$this->parameter->getCommandName()) {
            $this->showHelp();
            return 0;
        }

        if (!in_array($this->parameter->getCommandName(), $this->commands)) {
            $this->output->writeln("<red>Unknown command: {$this->parameter->getCommandName()}</red>");
            $this->showHelp();
            return 1;
        }

        if ($this->parameter->getArgument('box') === null) {
            if ($this->parameter->getCommandName() === "list") {
                $machineOutput = [];
                $longestLine   = 0;
                foreach ($this->getFilteredMachines() as $machine) {
                    $line = (string)$machine;
                    if (mb_strlen($line) > $longestLine) {
                        $longestLine = mb_strlen($line);
                    }
                    $machineOutput[] = $line;
                }

                $header = implode("", [
                    str_pad('id', 10),
                    str_pad('name', 20),
                    str_pad('provider', 15),
                    str_pad('status', 10),
                    'path'
                ]);
                $this->output->writeln($header);
                $this->output->writeln(str_repeat("-", $longestLine));
                foreach ($machineOutput as $line) {
                    $this->output->writeln($line);
                }
                return 0;
            } elseif (!in_array($this->parameter->getCommandName(), ['up-all', 'halt-all', 'reload-all'])) {
                $this->output->writeln("Box name is required for command {$this->parameter->getCommandName()}.");
                return 1;
            }
        }

        if (in_array($this->parameter->getCommandName(), ['up-all', 'halt-all', 'reload-all'])) {
            $this->output->writeln(
                "<info>Attempting to perform " . $this->parameter->getCommandName() . " on all relevant boxes...</info>"
            );
            $this->output->writeln(str_repeat("-", 75));
            switch ($this->parameter->getCommandName()) {
                case "up-all":
                    foreach ($this->getFilteredMachines() as $machine) {
                        $machine->up();
                    }
                    break;
                case "halt-all":
                    foreach ($this->getFilteredMachines() as $machine) {
                        $machine->halt();
                    }
                    break;
                case "reload-all":
                    foreach ($this->getFilteredMachines() as $machine) {
                        $machine->reload();
                    }
                    break;
            }
            $this->output->writeln(str_repeat("-", 75));
            return 0;
        }

        $parameter = $this->parameter->getArgument('box');

        $machine = $this->config->getMachineById($parameter);
        if (!$machine) {
            $machine = $this->config->getMachineByName($parameter);
        }

        if (!$machine) {
            $likelyMachines = $this->config->getMachinesByNameMatch($parameter);
            $this->output->writeln("Box could not be found: '{$parameter}'");
            if (count($likelyMachines) === 1) {
                $machine = $likelyMachines[0];
                $this->output->writeln("Using only likely matching machine:");
                $this->output->writeln("  [" . $machine->getId() . "] " . $machine->getName());
                $this->output->newline();
            } elseif (count($likelyMachines) > 1) {
                $this->output->writeln("Possible matches:");
                foreach ($likelyMachines as $machine) {
                    $this->output->writeln("  [" . $machine->getId() . "] " . $machine->getName());
                }
                return 1;
            } else {
                return 1;
            }
        }

        $this->output->writeln(
            "Attempting to perform '" . $this->parameter->getCommandName() . "' on " . $machine->getName()
        );

        $this->output->writeln(str_repeat("-", 75));
        switch ($this->parameter->getCommandName()) {
            case "up":
                $machine->up();
                break;
            case "up-ssh":
                $machine->up();
                $machine->ssh();
                break;
            case "halt":
                $machine->halt();
                break;
            case "ssh":
                $machine->ssh();
                break;
            case "reload":
                $machine->reload();
                break;
            case "reload-ssh":
                $machine->reload();
                $machine->ssh();
                break;
            case "destroy":
                $machine->destroy();
                break;
        }
        $this->output->writeln(str_repeat("-", 75));

        return 0;
    }

    /**
     * @return array|Machine[]
     */
    public function getFilteredMachines(): array
    {
        $filter = $this->parameter->getOption('filter');
        if (!is_string($filter)) {
            return $this->config->getMachines();
        }

        $this->output->writeln("Filtering with '{$filter}'.");
        $filteredMachines = [];
        foreach ($this->config->getMachines() as $id => $machine) {
            if (strpos($machine->getName(), $filter) !== false) {
                $filteredMachines[$id] = $machine;
            }
        }
        return $filteredMachines;
    }

    public function showHelp(): void
    {
        $this->output->writeln("Vagrant Manager 0.1.0");
        $this->output->newline();
        $this->output->writeln("    <yellow>Usage: {$this->parameter->getScriptName()} command box [--options]</yellow>");
        $this->output->newline();
        $this->output->writeln("Commands:");
        $this->output->writeln("    <green>list</green>              List the boxes");
        $this->output->writeln("    <green>ssh [box]</green>         Connect to box using SSH");
        $this->output->writeln("    <green>up [box]</green>          Start the box");
        $this->output->writeln("    <green>up-ssh [box]</green>      Start and then connect to the box");
        $this->output->writeln("    <green>up-all</green>            Start all non-running boxes");
        $this->output->writeln("    <green>halt [box]</green>        Stop the box");
        $this->output->writeln("    <green>halt-all</green>          Stop all running boxes");
        $this->output->writeln("    <green>reload [box]</green>      Reload the box");
        $this->output->writeln("    <green>reload-ssh [box]</green>  Reload and then connects to the box");
        $this->output->writeln("    <green>reload-all</green>        Reload all running boxes");
        $this->output->writeln("    <red>destroy [box]</red>     Destroy the box");
        $this->output->newline();
        $this->output->writeln("Options:");
        $this->output->writeln("    <yellow>--filter=string</yellow>   Filter on machine name");
    }

    public static function navigateHome(): void
    {
        chdir(HOMEDIR);
    }

    public static function navigateToMachine(Machine $machine): void
    {
        chdir($machine->getPath());
    }
}
