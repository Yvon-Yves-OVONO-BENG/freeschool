<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @Assert\EqualTo(propertyPath="password", message="Les mots de passe doivent être identiques")
     */
    private $confirmPassword;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Classroom::class)]
    private Collection $classrooms;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Registration::class)]
    private Collection $registrations;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Teacher $teacher = null;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Absence::class)]
    private Collection $absences;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: AbsenceTeacher::class)]
    private Collection $absenceTeachers;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Evaluation::class)]
    private Collection $evaluations;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Conseil::class)]
    private Collection $conseils;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: Conseil::class)]
    private Collection $userUpdated;

    #[ORM\OneToMany(mappedBy: 'enregistrePar', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(nullable: true)]
    private ?bool $bloque = null;

    #[ORM\Column(nullable: true)]
    private ?bool $supprime = null;

    public function __construct()
    {
        $this->classrooms = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->absenceTeachers = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->conseils = new ArrayCollection();
        $this->userUpdated = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getConfirmPassword(): string
    {
        return (string) $this->confirmPassword;
    }

    public function setConfirmPassword(string $confirmPassword): self
    {
        $this->confirmPassword = $confirmPassword;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getClassrooms(): Collection
    {
        return $this->classrooms;
    }

    public function addClassroom(Classroom $classroom): self
    {
        if (!$this->classrooms->contains($classroom)) {
            $this->classrooms->add($classroom);
            $classroom->setCreatedBy($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): self
    {
        if ($this->classrooms->removeElement($classroom)) {
            // set the owning side to null (unless already changed)
            if ($classroom->getCreatedBy() === $this) {
                $classroom->setCreatedBy(null);
            }
        }

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
            $student->setCreatedBy($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getCreatedBy() === $this) {
                $student->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): self
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setCreatedBy($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getCreatedBy() === $this) {
                $registration->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setCreatedBy($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getCreatedBy() === $this) {
                $absence->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AbsenceTeacher>
     */
    public function getAbsenceTeachers(): Collection
    {
        return $this->absenceTeachers;
    }

    public function addAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if (!$this->absenceTeachers->contains($absenceTeacher)) {
            $this->absenceTeachers->add($absenceTeacher);
            $absenceTeacher->setCreatedBy($this);
        }

        return $this;
    }

    public function removeAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if ($this->absenceTeachers->removeElement($absenceTeacher)) {
            // set the owning side to null (unless already changed)
            if ($absenceTeacher->getCreatedBy() === $this) {
                $absenceTeacher->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setCreatedBy($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getCreatedBy() === $this) {
                $evaluation->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conseil>
     */
    public function getConseils(): Collection
    {
        return $this->conseils;
    }

    public function addConseil(Conseil $conseil): self
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->setCreatedBy($this);
        }

        return $this;
    }

    public function removeConseil(Conseil $conseil): self
    {
        if ($this->conseils->removeElement($conseil)) {
            // set the owning side to null (unless already changed)
            if ($conseil->getCreatedBy() === $this) {
                $conseil->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conseil>
     */
    public function getUserUpdated(): Collection
    {
        return $this->userUpdated;
    }

    public function addUserUpdated(Conseil $userUpdated): self
    {
        if (!$this->userUpdated->contains($userUpdated)) {
            $this->userUpdated->add($userUpdated);
            $userUpdated->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeUserUpdated(Conseil $userUpdated): self
    {
        if ($this->userUpdated->removeElement($userUpdated)) {
            // set the owning side to null (unless already changed)
            if ($userUpdated->getUpdatedBy() === $this) {
                $userUpdated->setUpdatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HistoriqueTeacher>
     */
    public function getHistoriqueTeachers(): Collection
    {
        return $this->historiqueTeachers;
    }

    public function addHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if (!$this->historiqueTeachers->contains($historiqueTeacher)) {
            $this->historiqueTeachers->add($historiqueTeacher);
            $historiqueTeacher->setEnregistrePar($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getEnregistrePar() === $this) {
                $historiqueTeacher->setEnregistrePar(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function isBloque(): ?bool
    {
        return $this->bloque;
    }

    public function setBloque(?bool $bloque): self
    {
        $this->bloque = $bloque;

        return $this;
    }

    public function isSupprime(): ?bool
    {
        return $this->supprime;
    }

    public function setSupprime(?bool $supprime): self
    {
        $this->supprime = $supprime;

        return $this;
    }


}
