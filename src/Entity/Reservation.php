<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\Validator\Constraints as Assert;
=======
>>>>>>> ff5014e (third commit)

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
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
=======
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomreserv = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mail = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbrpersonne = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Event $event = null;
>>>>>>> ff5014e (third commit)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomreserv(): ?string
    {
        return $this->nomreserv;
    }

<<<<<<< HEAD
    public function setNomreserv(string $nomreserv): self
    {
        $this->nomreserv = $nomreserv;
=======
    public function setNomreserv(?string $nomreserv): static
    {
        $this->nomreserv = $nomreserv;

>>>>>>> ff5014e (third commit)
        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

<<<<<<< HEAD
    public function setMail(string $mail): static
    {
        $this->mail = $mail;
=======
    public function setMail(?string $mail): static
    {
        $this->mail = $mail;

>>>>>>> ff5014e (third commit)
        return $this;
    }

    public function getNbrpersonne(): ?int
    {
        return $this->nbrpersonne;
    }

<<<<<<< HEAD
    public function setNbrpersonne(int $nbrpersonne): static
    {
        $this->nbrpersonne = $nbrpersonne;
=======
    public function setNbrpersonne(?int $nbrpersonne): static
    {
        $this->nbrpersonne = $nbrpersonne;

>>>>>>> ff5014e (third commit)
        return $this;
    }

    public function getEvent(): ?Event
    {
<<<<<<< HEAD
        return $this->Event;
    }

    public function setEvent(?Event $Event): static
    {
        $this->Event = $Event;
=======
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

>>>>>>> ff5014e (third commit)
        return $this;
    }
}
