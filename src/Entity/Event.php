<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: "Le titre doit contenir au moins 4 caractères.",
        maxMessage: "Le titre ne peut pas dépasser 255 caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "Le titre ne doit contenir que des lettres et des espaces."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateevent = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: "Le lieu doit contenir au moins 4 caractères.",
        maxMessage: "Le lieu ne peut pas dépasser 255 caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "Le lieu ne doit contenir que des lettres et des espaces."
    )]
    private ?string $lieu = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: "La description doit contenir au moins 4 caractères.",
        maxMessage: "La description ne peut pas dépasser 255 caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "La description ne doit contenir que des lettres et des espaces."
    )]
    private ?string $discription = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre de places est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de places doit être un chiffre positif.")]
    private ?int $nbplace = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'Event')]
    private Collection $reservations;

    #[ORM\Column(nullable: true)]
    private ?int $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDateevent(): ?\DateTimeInterface
    {
        return $this->dateevent;
    }

    public function setDateevent(\DateTimeInterface $dateevent): static
    {
        $this->dateevent = $dateevent;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDiscription(): ?string
    {
        return $this->discription;
    }

    public function setDiscription(string $discription): static
    {
        $this->discription = $discription;
        return $this;
    }

    public function getNbplace(): ?int
    {
        return $this->nbplace;
    }

    public function setNbplace(int $nbplace): static
    {
        $this->nbplace = $nbplace;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEvent($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvent() === $this) {
                $reservation->setEvent(null);
            }
        }
        return $this;
    }

    public function getLatitude(): ?int
    {
        return $this->latitude;
    }

    public function setLatitude(?int $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?int
    {
        return $this->longitude;
    }

    public function setLongitude(?int $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
