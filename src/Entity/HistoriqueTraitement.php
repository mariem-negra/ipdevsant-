<?php

namespace App\Entity;

use App\Repository\HistoriqueTraitementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: HistoriqueTraitementRepository::class)]
class HistoriqueTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    #[Assert\Length(min: 3, max: 50, minMessage: "Le nom doit avoir au moins 3 caractères.", maxMessage: "Le nom ne peut pas dépasser 50 caractères.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/",
        message: "Le nom ne doit contenir que des lettres.")]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide.")]
    #[Assert\Length(min: 3, max: 50, minMessage: "Le prénom doit avoir au moins 3 caractères.", maxMessage: "Le prénom ne peut pas dépasser 50 caractères.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/",
        message: "Le prénom ne doit contenir que des lettres.")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez préciser la maladie.")]

    private ?string $maladie = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins 10 caractères.")]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le type de traitement est obligatoire.")]
    private ?string $type_traitement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\File(
        maxSize: "10M",
        mimeTypes: ["image/jpeg", "image/png", "application/pdf"],
        mimeTypesMessage: "Veuillez uploader un fichier valide (JPG, PNG, PDF)."
    )]
    private ?string $bilan = null;

    /**
     * @var Collection<int, SuivieMedical>
     */
    #[ORM\OneToMany(targetEntity: SuivieMedical::class, mappedBy: 'id_historique')]
    private Collection $id_historique;

    public function __construct()
    {
        $this->id_historique = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getMaladie(): ?string
    {
        return $this->maladie;
    }

    public function setMaladie(?string $maladie): static
    {
        $this->maladie = $maladie;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTypeTraitement(): ?string
    {
        return $this->type_traitement;
    }

    public function setTypeTraitement(?string $type_traitement): static
    {
        $this->type_traitement = $type_traitement;

        return $this;
    }

    public function getBilan(): ?string
    {
        return $this->bilan;
    }

    public function setBilan(?string $bilan): static
    {
        $this->bilan = $bilan;

        return $this;
    }

    /**
     * @return Collection<int, SuivieMedical>
     */
    public function getIdHistorique(): Collection
    {
        return $this->id_historique;
    }

    public function addIdHistorique(SuivieMedical $idHistorique): static
    {
        if (!$this->id_historique->contains($idHistorique)) {
            $this->id_historique->add($idHistorique);
            $idHistorique->setIdHistorique($this);
        }

        return $this;
    }

    public function removeIdHistorique(SuivieMedical $idHistorique): static
    {
        if ($this->id_historique->removeElement($idHistorique)) {
            // set the owning side to null (unless already changed)
            if ($idHistorique->getIdHistorique() === $this) {
                $idHistorique->setIdHistorique(null);
            }
        }

        return $this;
    }
}
