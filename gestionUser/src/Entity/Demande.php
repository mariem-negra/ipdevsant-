<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateDureeActivite')]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $Date = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 10, notInRangeMessage: "L'eau consommée doit être entre 0 et 10 litres.")]
    private ?float $Eau = null;

    #[ORM\Column]
    #[Assert\Positive(message: "Le nombre de repas doit être un entier positif.")]
    private ?int $Nbr_Repas = null;

    #[ORM\Column]
    private ?bool $Snacks = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Les calories doivent être un nombre positif.")]
    private ?float $Calories = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'activité ne peut pas être vide.")]
    private ?string $Activity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Sommeil = null;

    #[ORM\Column]
    // Removed the simple Assert\Positive constraint here.
    private ?float $Duree_Activite = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?\App\Entity\Utilisateur $Patient = null;

    public function __construct()
    {
        $this->Date = new \DateTime(); // Automatically set the current date
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(?\DateTime $Date): static
    {
        $this->Date = $Date;
        return $this;
    }

    public function getEau(): ?float
    {
        return $this->Eau;
    }

    public function setEau(float $Eau): static
    {
        $this->Eau = $Eau;
        return $this;
    }

    public function getNbrRepas(): ?int
    {
        return $this->Nbr_Repas;
    }

    public function setNbrRepas(int $Nbr_Repas): static
    {
        $this->Nbr_Repas = $Nbr_Repas;
        return $this;
    }

    public function isSnacks(): ?bool
    {
        return $this->Snacks;
    }

    public function setSnacks(bool $Snacks): static
    {
        $this->Snacks = $Snacks;
        return $this;
    }

    public function getCalories(): ?float
    {
        return $this->Calories;
    }

    public function setCalories(?float $Calories): static
    {
        $this->Calories = $Calories;
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

    public function getSommeil(): ?string
    {
        return $this->Sommeil;
    }

    public function setSommeil(?string $Sommeil): static
    {
        $this->Sommeil = $Sommeil;
        return $this;
    }

    public function getDureeActivite(): ?float
    {
        return $this->Duree_Activite;
    }

    public function setDureeActivite(float $Duree_Activite): static
    {
        $this->Duree_Activite = $Duree_Activite;
        return $this;
    }

    public function getPatient(): ?\App\Entity\Utilisateur
    {
        return $this->Patient;
    }

    public function setPatient(?\App\Entity\Utilisateur $Patient): self
    {
        $this->Patient = $Patient;
        return $this;
    }

    /**
     * Custom callback to validate Duree_Activite.
     * If Activity is "aucune" (case-insensitive), duration can be 0 or positive.
     * Otherwise, duration must be > 0.
     */
    public function validateDureeActivite(ExecutionContextInterface $context, $payload): void
    {
        
            if (strtolower($this->getActivity()) === 'aucune') {
                // When no activity, 0 is allowed; if negative, it's invalid.
                if ($this->getDureeActivite() < 0) {
                    $context->buildViolation("La durée d'activité doit être 0 ou un nombre positif si aucune activité n'est effectuée.")
                        ->atPath('Duree_Activite')
                        ->addViolation();
                }
            } else {
                // When an activity is specified, duration must be > 0.
                if ($this->getDureeActivite() <= 0) {
                    $context->buildViolation("La durée d'activité doit être supérieure à 0 lorsqu'une activité est spécifiée.")
                        ->atPath('Duree_Activite')
                        ->addViolation();
                }
            }
        
    }
}
