<?php

namespace App\Entity;

use App\Repository\SchoolYearRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchoolYearRepository::class)]
class SchoolYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $schoolYear = null;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Fees::class)]
    private Collection $fees;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: EtatDepense::class)]
    private Collection $etatDepenses;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: EtatFinance::class)]
    private Collection $etatFinances;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Subject::class)]
    private Collection $subjects;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Verrou::class)]
    private Collection $verrous;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: VerrouReport::class)]
    private Collection $verrouReports;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Teacher::class)]
    private Collection $teachers;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Classroom::class)]
    private Collection $classrooms;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Registration::class)]
    private Collection $registrations;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Progress::class)]
    private Collection $progress;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: School::class)]
    private Collection $schools;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Depense::class)]
    private Collection $depenses;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Department::class)]
    private Collection $departments;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: VerrouSequence::class)]
    private Collection $verrouSequences;

    #[ORM\OneToMany(mappedBy: 'schoolYear', targetEntity: Operateur::class)]
    private Collection $operateurs;

    public function __construct()
    {
        $this->fees = new ArrayCollection();
        $this->etatDepenses = new ArrayCollection();
        $this->etatFinances = new ArrayCollection();
        $this->subjects = new ArrayCollection();
        $this->verrous = new ArrayCollection();
        $this->verrouReports = new ArrayCollection();
        $this->teachers = new ArrayCollection();
        $this->classrooms = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->progress = new ArrayCollection();
        $this->schools = new ArrayCollection();
        $this->depenses = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->verrouSequences = new ArrayCollection();
        $this->operateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchoolYear(): ?string
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(string $schoolYear): self
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }

    /**
     * @return Collection<int, Fees>
     */
    public function getFees(): Collection
    {
        return $this->fees;
    }

    public function addFee(Fees $fee): self
    {
        if (!$this->fees->contains($fee)) {
            $this->fees->add($fee);
            $fee->setSchoolYear($this);
        }

        return $this;
    }

    public function removeFee(Fees $fee): self
    {
        if ($this->fees->removeElement($fee)) {
            // set the owning side to null (unless already changed)
            if ($fee->getSchoolYear() === $this) {
                $fee->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtatDepense>
     */
    public function getEtatDepenses(): Collection
    {
        return $this->etatDepenses;
    }

    public function addEtatDepense(EtatDepense $etatDepense): self
    {
        if (!$this->etatDepenses->contains($etatDepense)) {
            $this->etatDepenses->add($etatDepense);
            $etatDepense->setSchoolYear($this);
        }

        return $this;
    }

    public function removeEtatDepense(EtatDepense $etatDepense): self
    {
        if ($this->etatDepenses->removeElement($etatDepense)) {
            // set the owning side to null (unless already changed)
            if ($etatDepense->getSchoolYear() === $this) {
                $etatDepense->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EtatFinance>
     */
    public function getEtatFinances(): Collection
    {
        return $this->etatFinances;
    }

    public function addEtatFinance(EtatFinance $etatFinance): self
    {
        if (!$this->etatFinances->contains($etatFinance)) {
            $this->etatFinances->add($etatFinance);
            $etatFinance->setSchoolYear($this);
        }

        return $this;
    }

    public function removeEtatFinance(EtatFinance $etatFinance): self
    {
        if ($this->etatFinances->removeElement($etatFinance)) {
            // set the owning side to null (unless already changed)
            if ($etatFinance->getSchoolYear() === $this) {
                $etatFinance->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): self
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
            $subject->setSchoolYear($this);
        }

        return $this;
    }

    public function removeSubject(Subject $subject): self
    {
        if ($this->subjects->removeElement($subject)) {
            // set the owning side to null (unless already changed)
            if ($subject->getSchoolYear() === $this) {
                $subject->setSchoolYear(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, Verrou>
     */
    public function getVerrous(): Collection
    {
        return $this->verrous;
    }

    public function addVerrou(Verrou $verrou): self
    {
        if (!$this->verrous->contains($verrou)) {
            $this->verrous->add($verrou);
            $verrou->setSchoolYear($this);
        }

        return $this;
    }

    public function removeVerrou(Verrou $verrou): self
    {
        if ($this->verrous->removeElement($verrou)) {
            // set the owning side to null (unless already changed)
            if ($verrou->getSchoolYear() === $this) {
                $verrou->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VerrouReport>
     */
    public function getVerrouReports(): Collection
    {
        return $this->verrouReports;
    }

    public function addVerrouReport(VerrouReport $verrouReport): self
    {
        if (!$this->verrouReports->contains($verrouReport)) {
            $this->verrouReports->add($verrouReport);
            $verrouReport->setSchoolYear($this);
        }

        return $this;
    }

    public function removeVerrouReport(VerrouReport $verrouReport): self
    {
        if ($this->verrouReports->removeElement($verrouReport)) {
            // set the owning side to null (unless already changed)
            if ($verrouReport->getSchoolYear() === $this) {
                $verrouReport->setSchoolYear(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(Teacher $teacher): self
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
            $teacher->setSchoolYear($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): self
    {
        if ($this->teachers->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getSchoolYear() === $this) {
                $teacher->setSchoolYear(null);
            }
        }

        return $this;
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
            $classroom->setSchoolYear($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): self
    {
        if ($this->classrooms->removeElement($classroom)) {
            // set the owning side to null (unless already changed)
            if ($classroom->getSchoolYear() === $this) {
                $classroom->setSchoolYear(null);
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
            $student->setSchoolYear($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getSchoolYear() === $this) {
                $student->setSchoolYear(null);
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
            $registration->setSchoolYear($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getSchoolYear() === $this) {
                $registration->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Progress>
     */
    public function getProgress(): Collection
    {
        return $this->progress;
    }

    public function addProgress(Progress $progress): self
    {
        if (!$this->progress->contains($progress)) {
            $this->progress->add($progress);
            $progress->setSchoolYear($this);
        }

        return $this;
    }

    public function removeProgress(Progress $progress): self
    {
        if ($this->progress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getSchoolYear() === $this) {
                $progress->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, School>
     */
    public function getSchools(): Collection
    {
        return $this->schools;
    }

    public function addSchool(School $school): self
    {
        if (!$this->schools->contains($school)) {
            $this->schools->add($school);
            $school->setSchoolYear($this);
        }

        return $this;
    }

    public function removeSchool(School $school): self
    {
        if ($this->schools->removeElement($school)) {
            // set the owning side to null (unless already changed)
            if ($school->getSchoolYear() === $this) {
                $school->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Depense>
     */
    public function getDepenses(): Collection
    {
        return $this->depenses;
    }

    public function addDepense(Depense $depense): self
    {
        if (!$this->depenses->contains($depense)) {
            $this->depenses->add($depense);
            $depense->setSchoolYear($this);
        }

        return $this;
    }

    public function removeDepense(Depense $depense): self
    {
        if ($this->depenses->removeElement($depense)) {
            // set the owning side to null (unless already changed)
            if ($depense->getSchoolYear() === $this) {
                $depense->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            $department->setSchoolYear($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        if ($this->departments->removeElement($department)) {
            // set the owning side to null (unless already changed)
            if ($department->getSchoolYear() === $this) {
                $department->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VerrouSequence>
     */
    public function getVerrouSequences(): Collection
    {
        return $this->verrouSequences;
    }

    public function addVerrouSequence(VerrouSequence $verrouSequence): self
    {
        if (!$this->verrouSequences->contains($verrouSequence)) {
            $this->verrouSequences->add($verrouSequence);
            $verrouSequence->setSchoolYear($this);
        }

        return $this;
    }

    public function removeVerrouSequence(VerrouSequence $verrouSequence): self
    {
        if ($this->verrouSequences->removeElement($verrouSequence)) {
            // set the owning side to null (unless already changed)
            if ($verrouSequence->getSchoolYear() === $this) {
                $verrouSequence->setSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Operateur>
     */
    public function getOperateurs(): Collection
    {
        return $this->operateurs;
    }

    public function addOperateur(Operateur $operateur): self
    {
        if (!$this->operateurs->contains($operateur)) {
            $this->operateurs->add($operateur);
            $operateur->setSchoolYear($this);
        }

        return $this;
    }

    public function removeOperateur(Operateur $operateur): self
    {
        if ($this->operateurs->removeElement($operateur)) {
            // set the owning side to null (unless already changed)
            if ($operateur->getSchoolYear() === $this) {
                $operateur->setSchoolYear(null);
            }
        }

        return $this;
    }
}
