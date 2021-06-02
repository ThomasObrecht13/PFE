<?php

namespace App\Entity;

use App\Repository\MembreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MembreRepository::class)
 */
class Membre
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $chefProjet;

    /**
     * @ORM\ManyToOne(targetEntity=Projet::class, inversedBy="membres")
     */
    private $Projet;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="membres")
     */
    private $User;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChefProjet(): ?bool
    {
        return $this->chefProjet;
    }

    public function setChefProjet(bool $chefProjet): self
    {
        $this->chefProjet = $chefProjet;

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

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }
}
