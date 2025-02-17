<?php

namespace App\Entity;

use App\Repository\RecommandationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecommandationRepository::class)]
class Recommandation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Demande $demande = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le petit-déjeuner ne peut pas être vide.")]
    private ?string $Petit_Dejeuner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le déjeuner ne peut pas être vide.")]
    private ?string $Dejeuner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le dîner ne peut pas être vide.")]
    private ?string $Diner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'activité recommandée ne peut pas être vide.")]
    private ?string $Activity = null;

    #[ORM\Column]
    #[Assert\Positive(message: "Les calories doivent être un nombre positif.")]
    private ?float $Calories = null;

    #[ORM\Column]
    #[Assert\Positive(message: "La durée d'activité doit être un nombre positif.")]
    private ?float $Duree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Supplements = null;
    /*#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?demande $demande = null;

    #[ORM\Column(length: 255)]
    private ?string $Petit_Dejeuner = null;

    #[ORM\Column(length: 255)]
    private ?string $Dejeuner = null;

    #[ORM\Column(length: 255)]
    private ?string $Diner = null;

    #[ORM\Column(length: 255)]
    private ?string $Activity = null;

    #[ORM\Column]
    private ?float $Calories = null;

    #[ORM\Column]
    private ?float $Duree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Supplements = null;*/

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): ?demande
    {
        return $this->demande;
    }

    public function setDemande(demande $demande): static
    {
        $this->demande = $demande;

        return $this;
    }

    public function getPetitDejeuner(): ?string
    {
        return $this->Petit_Dejeuner;
    }

    public function setPetitDejeuner(string $Petit_Dejeuner): static
    {
        $this->Petit_Dejeuner = $Petit_Dejeuner;

        return $this;
    }

    public function getDejeuner(): ?string
    {
        return $this->Dejeuner;
    }

    public function setDejeuner(string $Dejeuner): static
    {
        $this->Dejeuner = $Dejeuner;

        return $this;
    }

    public function getDiner(): ?string
    {
        return $this->Diner;
    }

    public function setDiner(string $Diner): static
    {
        $this->Diner = $Diner;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->Activity;
    }

    public function setActivity(string $Activity): static
    {
        $this->Activity = $Activity;

        return $this;
    }

    public function getCalories(): ?float
    {
        return $this->Calories;
    }

    public function setCalories(float $Calories): static
    {
        $this->Calories = $Calories;

        return $this;
    }

    public function getDuree(): ?float
    {
        return $this->Duree;
    }

    public function setDuree(float $Duree): static
    {
        $this->Duree = $Duree;

        return $this;
    }

    public function getSupplements(): ?string
    {
        return $this->Supplements;
    }

    public function setSupplements(?string $Supplements): static
    {
        $this->Supplements = $Supplements;

        return $this;
    }
}
