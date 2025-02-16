<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Suppression de la colonne 'Nomresev' pour ne conserver que 'nomreserv'
    #[ORM\Column(length: 255)]
    private ?string $nomreserv = null;

    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column]
    private ?int $nbrpersonne = null;
    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Event $Event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Getter et setter pour 'nomreserv'
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