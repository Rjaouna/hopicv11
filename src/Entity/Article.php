<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "article")]
#[ORM\Index(columns: ["FamilyName"], name: "idx_family")]
#[ORM\Index(columns: ["xx_Marque"], name: "idx_marque")]
class Article
{
    #[ORM\Id]
    #[ORM\Column(name: "UniqueId", type: "string", length: 36)]
    private ?string $uniqueId = null;

    #[ORM\Column(name: "Id", type: "string", length: 64, unique: true)]
    private ?string $id = null;

    #[ORM\Column(name: "DesComClear", type: "string", length: 255)]
    private ?string $desComClear = null;

    #[ORM\Column(name: "RealStock", type: "integer")]
    private int $realStock = 0;

    // DECIMAL: Doctrine renvoie souvent une string, c’est normal (évite les erreurs float)
    #[ORM\Column(name: "SalePriceVatIncluded", type: "decimal", precision: 10, scale: 2)]
    private string $salePriceVatIncluded = "0.00";

    #[ORM\Column(name: "SalePriceVatExcluded", type: "decimal", precision: 10, scale: 2)]
    private string $salePriceVatExcluded = "0.00";

    #[ORM\Column(name: "xx_Annee", type: "string", length: 255)]
    private ?string $xxAnnee = null;

    #[ORM\Column(name: "xx_Vehicule", type: "string", length: 255)]
    private ?string $xxVehicule = null;

    #[ORM\Column(name: "xx_Marque", type: "string", length: 100)]
    private ?string $xxMarque = null;

    #[ORM\Column(name: "sysModifiedDate", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $sysModifiedDate = null;

    #[ORM\Column(name: "sysCreatedDate", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $sysCreatedDate = null;

    #[ORM\Column(name: "FamilyName", type: "string", length: 100)]
    private ?string $familyName = null;

    #[ORM\Column(name: "ExportStartedAt", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $exportStartedAt = null;

    #[ORM\Column(name: "FtpFinishedAt", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $ftpFinishedAt = null;

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(string $uniqueId): self
    {
        $this->uniqueId = $uniqueId;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDesComClear(): ?string
    {
        return $this->desComClear;
    }

    public function setDesComClear(string $desComClear): self
    {
        $this->desComClear = $desComClear;
        return $this;
    }

    public function getRealStock(): int
    {
        return $this->realStock;
    }

    public function setRealStock(int $realStock): self
    {
        $this->realStock = $realStock;
        return $this;
    }

    public function getSalePriceVatIncluded(): string
    {
        return $this->salePriceVatIncluded;
    }

    public function setSalePriceVatIncluded(string $salePriceVatIncluded): self
    {
        $this->salePriceVatIncluded = $salePriceVatIncluded;
        return $this;
    }

    public function getSalePriceVatExcluded(): string
    {
        return $this->salePriceVatExcluded;
    }

    public function setSalePriceVatExcluded(string $salePriceVatExcluded): self
    {
        $this->salePriceVatExcluded = $salePriceVatExcluded;
        return $this;
    }

    public function getXxAnnee(): ?string
    {
        return $this->xxAnnee;
    }

    public function setXxAnnee(string $xxAnnee): self
    {
        $this->xxAnnee = $xxAnnee;
        return $this;
    }

    public function getXxVehicule(): ?string
    {
        return $this->xxVehicule;
    }

    public function setXxVehicule(string $xxVehicule): self
    {
        $this->xxVehicule = $xxVehicule;
        return $this;
    }

    public function getXxMarque(): ?string
    {
        return $this->xxMarque;
    }

    public function setXxMarque(string $xxMarque): self
    {
        $this->xxMarque = $xxMarque;
        return $this;
    }

    public function getSysModifiedDate(): ?\DateTimeImmutable
    {
        return $this->sysModifiedDate;
    }

    public function setSysModifiedDate(?\DateTimeImmutable $sysModifiedDate): self
    {
        $this->sysModifiedDate = $sysModifiedDate;
        return $this;
    }

    public function getSysCreatedDate(): ?\DateTimeImmutable
    {
        return $this->sysCreatedDate;
    }

    public function setSysCreatedDate(?\DateTimeImmutable $sysCreatedDate): self
    {
        $this->sysCreatedDate = $sysCreatedDate;
        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): self
    {
        $this->familyName = $familyName;
        return $this;
    }

    public function getExportStartedAt(): ?\DateTimeImmutable
    {
        return $this->exportStartedAt;
    }

    public function setExportStartedAt(?\DateTimeImmutable $exportStartedAt): self
    {
        $this->exportStartedAt = $exportStartedAt;
        return $this;
    }

    public function getFtpFinishedAt(): ?\DateTimeImmutable
    {
        return $this->ftpFinishedAt;
    }

    public function setFtpFinishedAt(?\DateTimeImmutable $ftpFinishedAt): self
    {
        $this->ftpFinishedAt = $ftpFinishedAt;
        return $this;
    }
}