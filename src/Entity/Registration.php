<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
class Registration
{

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registration_date = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registration_date;
    }

    public function setRegistrationDate(\DateTimeInterface $registration_date): static
    {
        $this->registration_date = $registration_date;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): static
    {
        $this->activity = $activity;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
