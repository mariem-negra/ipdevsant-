<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la réservation est obligatoire.")]
    #[Assert\Length(
        min: 4,
        max: 255,
        minMessage: "Le nom doit contenir au moins 4 caractères.",
        maxMessage: "Le nom ne peut pas dépasser 255 caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-]+$/",
        message: "Le nom ne doit contenir que des lettres et des espaces."
    )]
    private ?string $nomreserv = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    private ?string $mail = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre de personnes est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de personnes doit être un chiffre positif.")]
    private ?int $nbrpersonne = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Event $Event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomreserv(): ?string
    {
        return $this->nomreserv;
    }

    public function setNomreserv(string $nomreserv): self
    {
        $this->nomreserv = $nomreserv;
        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;
        return $this;
    }

    public function getNbrpersonne(): ?int
    {
        return $this->nbrpersonne;
    }

    public function setNbrpersonne(int $nbrpersonne): static
    {
        $this->nbrpersonne = $nbrpersonne;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->Event;
    }

    public function setEvent(?Event $Event): static
    {
        $this->Event = $Event;
        return $this;
    }
}
