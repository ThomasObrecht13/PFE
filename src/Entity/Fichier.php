<?php

namespace App\Entity;

use App\Repository\FichierRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FichierRepository::class)
 */
class Fichier
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateDepot;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $brochureFilename;

    /**
     * @ORM\ManyToOne(targetEntity=Projet::class, inversedBy="fichiers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Projet;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepot(): ?\DateTimeInterface
    {
        return $this->dateDepot;
    }

    public function setDateDepot(\DateTimeInterface $dateDepot): self
    {
        $this->dateDepot = $dateDepot;

        return $this;
    }

    public function getBrochureFilename(): ?string
    {
        return $this->brochureFilename;
    }

    public function setBrochureFilename(string $brochureFilename): self
    {
        $this->brochureFilename = $brochureFilename;

        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->Projet;
    }

    public function setProjet(?Projet $Projet): self
    {
        $this->Projet = $Projet;

        return $this;
    }
}
