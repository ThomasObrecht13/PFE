<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NoteRepository::class)
 */
class Note
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $soutenance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rapport;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $technique;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notes")
     */
    private $User;

    /**
     * @ORM\ManyToOne(targetEntity=Projet::class, inversedBy="notes")
     */
    private $Projet;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSoutenance(): ?float
    {
        return $this->soutenance;
    }

    public function setSoutenance(?float $soutenance): self
    {
        $this->soutenance = $soutenance;

        return $this;
    }

    public function getRapport(): ?float
    {
        return $this->rapport;
    }

    public function setRapport(?float $rapport): self
    {
        $this->rapport = $rapport;

        return $this;
    }

    public function getTechnique(): ?float
    {
        return $this->technique;
    }

    public function setTechnique(?float $technique): self
    {
        $this->technique = $technique;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

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
