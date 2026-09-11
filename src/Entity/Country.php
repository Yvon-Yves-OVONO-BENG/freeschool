<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'country', targetEntity: UserLog::class)]
    private Collection $userLogs;

    #[ORM\Column(length: 255)]
    private ?string $logo = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->userLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): self
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setCountry($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getCountry() === $this) {
                $student->setCountry(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserLog>
     */
    public function getUserLogs(): Collection
    {
        return $this->userLogs;
    }

    public function addUserLog(UserLog $userLog): self
    {
        if (!$this->userLogs->contains($userLog)) {
            $this->userLogs->add($userLog);
            $userLog->setCountry($this);
        }

        return $this;
    }

    public function removeUserLog(UserLog $userLog): self
    {
        if ($this->userLogs->removeElement($userLog)) {
            // set the owning side to null (unless already changed)
            if ($userLog->getCountry() === $this) {
                $userLog->setCountry(null);
            }
        }

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }
}
