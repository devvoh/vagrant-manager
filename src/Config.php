<?php

namespace VagrantManager;

use Parable\Console\Parameter;

class Config
{
    /**
     * @var Parameter
     */
    private $params;

    /**
     * @var Machine[]
     */
    private $machines = [];

    public function __construct(Parameter $params)
    {
        $this->params = $params;

        $this->getMachines();
    }

    public function loadMachines(): void
    {
        exec("vagrant global-status", $output);

        $this->machines = [];
        $atList = false;
        foreach ($output as $line) {
            if (empty($line)) {
                break;
            }
            if ($atList) {
                $machine = Machine::fromStatusLine($line);
                $this->machines[$machine->getId()] = $machine;
            }
            if (!$atList && strpos($line, "-") === 0) {
                $atList = true;
            }
        }
    }

    /**
     * @return Machine[]
     */
    public function getMachines(): array
    {
        if (!$this->machines) {
            $this->loadMachines();
        }
        return $this->machines;
    }

    public function getMachineById(string $id): ?Machine
    {
        return $this->machines[$id] ?? null;
    }

    public function getMachineByName(string $name): ?Machine
    {
        foreach ($this->machines as $machine) {
            if ($machine->getName() === $name) {
                return $machine;
            }
        }
        return null;
    }

    /**
     * @return Machine[]
     */
    public function getMachinesByNameMatch(string $name): array
    {
        $machines = [];
        foreach ($this->machines as $machine) {
            if (strpos($machine->getName(), $name) !== false || levenshtein($name, $machine->getName()) < 2) {
                $machines[] = $machine;
            }
        }
        return $machines;
    }
}
