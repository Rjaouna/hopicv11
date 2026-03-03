<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ImportSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $directoryPath = null; // ex: /var/www/project/imports

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;      // ex: articles.csv

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getFullPath(): string
    {
        $dir = rtrim((string)$this->directoryPath, '/');
        return $dir . '/' . ltrim((string)$this->fileName, '/');
    }

    // GETTERS / SETTERS
    public function getId(): ?int { return $this->id; }

    public function getDirectoryPath(): ?string { return $this->directoryPath; }
    public function setDirectoryPath(string $directoryPath): self
    {
        $this->directoryPath = $directoryPath;
        return $this;
    }

    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}