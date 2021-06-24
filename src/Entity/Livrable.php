<?php

namespace App\Entity;

use App\Repository\LivrableRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LivrableRepository::class)
 */
class Livrable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $TitreLivrable;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelleLivrable;

    /**
     * @ORM\ManyToOne(targetEntity=Projet::class, inversedBy="livrables")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Projet;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreLivrable(): ?string
    {
        return $this->TitreLivrable;
    }

    public function setTitreLivrable(string $TitreLivrable): self
    {
        $this->TitreLivrable = $TitreLivrable;

        return $this;
    }

    public function getLibelleLivrable(): ?string
    {
        return $this->libelleLivrable;
    }

    public function setLibelleLivrable(string $libelleLivrable): self
    {
        $this->libelleLivrable = $libelleLivrable;

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
