<?php

namespace App\Entity;

use App\Repository\ReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReminderRepository::class)]
class Reminder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTime = null;

    #[ORM\Column(nullable: true)]
    private ?string $notifyBefore = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $repeatType = 'none';

    #[ORM\Column(nullable: true)]
    private ?bool $soundEnabled = null;

    #[ORM\Column(nullable: true)]
    private ?bool $viewed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(?\DateTimeInterface $dateTime): static
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getNotifyBefore(): ?int
    {
        return $this->notifyBefore;
    }

    public function setNotifyBefore(?int $notifyBefore): static
    {
        $this->notifyBefore = $notifyBefore;

        return $this;
    }

    public function getRepeatType(): ?string
    {
        return $this->repeatType;
    }

    public function setRepeatType(?string $repeatType): static
    {
        $this->repeatType = $repeatType;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        if ($this->dateTime) {
            return \DateTime::createFromFormat('H:i:s', $this->dateTime->format('H:i:s'));
        }
        return null;    }

    public function setTime(?\DateTimeInterface $time): static
    {
        if ($time && $this->dateTime) {
            $this->dateTime = new \DateTime(
                $this->dateTime->format('Y-m-d') . ' ' . $time->format('H:i:s')
            );
        }
        return $this;
    
    }

    public function isSoundEnabled(): ?bool
    {
        return $this->soundEnabled;
    }

    public function setSoundEnabled(?bool $soundEnabled): static
    {
        $this->soundEnabled = $soundEnabled;

        return $this;
    }

    public function isViewed(): ?bool
    {
        return $this->viewed;
    }

    public function setViewed(?bool $viewed): static
    {
        $this->viewed = $viewed;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
