<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ImportJob
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_ERROR   = 'ERROR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255)]
    private string $filePath;

    #[ORM\Column(type: 'integer')]
    private int $totalRows = 0;

    #[ORM\Column(type: 'integer')]
    private int $processedRows = 0;

    #[ORM\Column(type: 'bigint')]
    private int $byteOffset = 0; // où reprendre dans le fichier

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->startedAt = new \DateTimeImmutable('now');
        
    }
#[ORM\Column(type: 'integer')]
private int $insertedRows = 0;

#[ORM\Column(type: 'integer')]
private int $updatedRows = 0;

#[ORM\Column(type: 'integer')]
private int $skippedRows = 0;

public function getInsertedRows(): int { return $this->insertedRows; }
public function setInsertedRows(int $v): self { $this->insertedRows = $v; return $this; }
public function addInserted(int $v = 1): self { $this->insertedRows += $v; return $this; }

public function getUpdatedRows(): int { return $this->updatedRows; }
public function setUpdatedRows(int $v): self { $this->updatedRows = $v; return $this; }
public function addUpdated(int $v = 1): self { $this->updatedRows += $v; return $this; }

public function getSkippedRows(): int { return $this->skippedRows; }
public function setSkippedRows(int $v): self { $this->skippedRows = $v; return $this; }
public function addSkipped(int $v = 1): self { $this->skippedRows += $v; return $this; }


    // GETTERS / SETTERS
    public function getId(): ?int { return $this->id; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $filePath): self { $this->filePath = $filePath; return $this; }

    public function getTotalRows(): int { return $this->totalRows; }
    public function setTotalRows(int $totalRows): self { $this->totalRows = $totalRows; return $this; }

    public function getProcessedRows(): int { return $this->processedRows; }
    public function setProcessedRows(int $processedRows): self { $this->processedRows = $processedRows; return $this; }

    public function getByteOffset(): int { return $this->byteOffset; }
    public function setByteOffset(int $byteOffset): self { $this->byteOffset = $byteOffset; return $this; }

    public function getLastMessage(): ?string { return $this->lastMessage; }
    public function setLastMessage(?string $lastMessage): self { $this->lastMessage = $lastMessage; return $this; }

    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(\DateTimeImmutable $startedAt): self { $this->startedAt = $startedAt; return $this; }

    public function getFinishedAt(): ?\DateTimeImmutable { return $this->finishedAt; }
    public function setFinishedAt(?\DateTimeImmutable $finishedAt): self { $this->finishedAt = $finishedAt; return $this; }
}