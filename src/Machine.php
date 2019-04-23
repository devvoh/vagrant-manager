<?php

namespace VagrantManager;

class Machine
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $path;

    /**
     * We only allow new instances to be created from a line.
     */
    private function __construct()
    {
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @throws \Exception
     */
    public static function fromStatusLine(string $line): self
    {
        $data = preg_split('/\s+/', $line);

        if (count($data) !== 5) {
            throw new \Exception(
                sprintf('Status line invalid, expected 5 fields, got %d instead', count($data))
            );
        }

        $machine = new self();

        $machine->setId($data[0]);
        $machine->setName($data[1]);
        $machine->setProvider($data[2]);
        $machine->setStatus($data[3]);
        $machine->setPath($data[4]);

        return $machine;
    }

    public function up(): void
    {
        $this->perform('up');
    }

    public function reload(): void
    {
        $this->perform('reload');
    }

    public function halt(): void
    {
        $this->perform('halt');
    }

    public function ssh(): void
    {
        $this->perform('ssh');
    }

    public function destroy(): void
    {
        $this->perform('destroy');
    }

    private function perform(string $actionString): void
    {
        Manager::navigateToMachine($this);
        passthru('vagrant ' . $actionString . ' ' . $this->getId());
        Manager::navigateHome();
    }

    public function __toString(): string
    {
        $data = [
            str_pad($this->getId(), 10),
            str_pad($this->getName(), 20),
            str_pad($this->getProvider(), 15),
            str_pad($this->getStatus(), 10),
            $this->getPath()
        ];
        $string = implode("", $data);

        if ($this->getStatus() === "running") {
            return "<green>{$string}</green>";
        }
        return "<red>{$string}</red>";
    }
}
