<?php
namespace App\Entity;
use App\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide.")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide.")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide.")]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères.")]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Za-z])(?=.*\d).+$/",
        message: "Le mot de passe doit contenir à la fois des lettres et des chiffres."
    )]
    private ?string $motDePasse = null;

    #[ORM\Column(type: "string", enumType: UserRole::class)]
    #[Assert\NotBlank(message: "Le rôle ne peut pas être vide.")]
    private UserRole $role;

    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\NotBlank(groups: ["strict"])] // Use an array for groups
    #[Assert\LessThanOrEqual(
        value: "today",
        message: "La date de naissance ne peut pas être dans le futur."
    )]
    private ?\DateTimeInterface $dateNaissance = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "La spécialité ne peut pas être vide.", groups: ['medecin'])]
    private ?string $specialite = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le téléphone ne peut pas être vide.")]
    #[Assert\Length(
        min: 8,
        max: 8,
        exactMessage: "Le téléphone doit contenir exactement {{ limit }} chiffres."
        )]    
    private ?int $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le diplôme est requis.", groups: ['medecin'])]
    private ?string $diploma = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isVerified = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $loginAttempts = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 6, nullable: true)]
    private ?string $otp = null;

    // Add these getters and setters to your Utilisateur entity

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeInterface $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }

    public function getOtp(): ?string
    {
        return $this->otp;
    }

    public function setOtp(?string $otp): self
    {
        $this->otp = $otp;
        return $this;
    }
    // Add getters and setters
    public function getLoginAttempts(): int
    {
        return $this->loginAttempts;
    }

    public function setLoginAttempts(int $loginAttempts): self
    {
        $this->loginAttempts = $loginAttempts;
        return $this;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeInterface $lockedUntil): self
    {
        $this->lockedUntil = $lockedUntil;
        return $this;
    }

    public function isLocked(): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }
        return $this->lockedUntil > new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }


    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }
    public function __construct()
    {
        // Initialize the role property with a default value
        $this->role = UserRole::PATIENT; // Replace DEFAULT_ROLE with an appropriate default value
        $this->isVerified = false; // Ensure the field is initialized

    }
    // src/Entity/Utilisateur.php
public function getRole(): UserRole
{
    return $this->role; // Return the role as a single value
}


    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        // Set specialite to null if the role is PATIENT
        if ($role === UserRole::PATIENT) {
            $this->specialite = null;
        }

        return $this;
    }


    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }
    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): self
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function getTelephone(): ?int
    {
        return $this->telephone;
    }

    public function setTelephone(int $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }
    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }
    public function getRoles(): array
    {
        // Map your custom roles to Symfony's expected ROLE_* format
        $roleMapping = [
            'Admin' => 'ROLE_ADMIN',
            'Médecin' => 'ROLE_MEDECIN',
            'Patient' => 'ROLE_PATIENT',
        ];
    
        // Get the Symfony role based on the user's custom role
        $symfonyRole = $roleMapping[$this->role->value] ?? 'ROLE_USER';
    
        // Ensure all users have at least ROLE_USER
        return array_unique([$symfonyRole, 'ROLE_USER']);
    }
    public function setPassword(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

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

    public function getDiploma(): ?string
    {
        return $this->diploma;
    }

    public function setDiploma(?string $diploma): self
    {
        $this->diploma = $diploma;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }
    
}
