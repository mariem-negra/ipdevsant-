<?php

namespace App\Entity;

use App\Repository\SuivieMedicalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;  


#[ORM\Entity(repositoryClass: SuivieMedicalRepository::class)]
class SuivieMedical
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date ne peut pas être vide')]
    #[Assert\Type('\DateTimeInterface', message: 'La date n\'est pas valide')]
    #[Assert\GreaterThanOrEqual("today", message: "La date doit être aujourd'hui ou dans le futur.")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'id_historique')]
    private ?HistoriqueTraitement $id_historique = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getIdHistorique(): ?HistoriqueTraitement
    {
        return $this->id_historique;
    }

    public function setIdHistorique(?HistoriqueTraitement $id_historique): self
    {
        $this->id_historique = $id_historique;

        return $this;
    }
    
}
