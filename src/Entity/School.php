<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Serializable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: SchoolRepository::class)]
class School implements \Serializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchName = null;

    #[ORM\Column(length: 255)]
    private ?string $englishName = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchMotto = null;

    #[ORM\Column(length: 255)]
    private ?string $englishMotto = null;

    #[ORM\Column(length: 255)]
    private ?string $pobox = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    private ?string $place = null;

    #[Vich\UploadableField(mapping:"school_image", fileNameProperty:"logo")]
    /**
    *
    * @var File|null
     */
    private $logoFile;

    #[ORM\Column(length: 255)]
    private ?string $logo = null;

    #[Vich\UploadableField(mapping:"school_image", fileNameProperty:"filigree")]
    /**
    *
    * @var File|null
     */
    private $filigreeFile;

    #[ORM\Column(length: 255)]
    private ?string $filigree = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $frenchCountry = null;

    #[ORM\Column(length: 50)]
    private ?string $englishCountry = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchMinister = null;

    #[ORM\Column(length: 255)]
    private ?string $englishMinister = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchRegion = null;

    #[ORM\Column(length: 255)]
    private ?string $englishRegion = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchDivision = null;

    #[ORM\Column(length: 255)]
    private ?string $englishDivision = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchSubDivision = null;

    #[ORM\Column(length: 255)]
    private ?string $englishSubDivision = null;

    #[ORM\Column(length: 255)]
    private ?string $frenchCountryMotto = null;

    #[ORM\Column(length: 255)]
    private ?string $englishCountryMotto = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'schools')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'schools')]
    private ?Teacher $headmaster = null;

    #[ORM\Column(length: 255)]
    private ?string $serviceNote = null;

    #[ORM\ManyToOne(inversedBy: 'schools')]
    private ?Education $education = null;

    #[ORM\Column(nullable: true)]
    private ?bool $public = null;

    #[ORM\Column(nullable: true)]
    private ?bool $lycee = null;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Classroom::class)]
    private Collection $classrooms;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Department::class)]
    private Collection $departments;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Depense::class)]
    private Collection $depenses;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: EtatDepense::class)]
    private Collection $etatDepenses;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Fees::class)]
    private Collection $fees;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Teacher::class)]
    private Collection $teachers;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: User::class)]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: Verrou::class)]
    private Collection $verrous;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: VerrouInsolvable::class)]
    private Collection $verrouInsolvables;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: VerrouReport::class)]
    private Collection $verrouReports;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: VerrouSequence::class)]
    private Collection $verrouSequences;

    public function __construct()
    {
        $this->classrooms = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->depenses = new ArrayCollection();
        $this->etatDepenses = new ArrayCollection();
        $this->fees = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->teachers = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->verrous = new ArrayCollection();
        $this->verrouInsolvables = new ArrayCollection();
        $this->verrouReports = new ArrayCollection();
        $this->verrouSequences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFrenchName(): ?string
    {
        return $this->frenchName;
    }

    public function setFrenchName(string $frenchName): self
    {
        $this->frenchName = $frenchName;

        return $this;
    }

    public function getEnglishName(): ?string
    {
        return $this->englishName;
    }

    public function setEnglishName(string $englishName): self
    {
        $this->englishName = $englishName;

        return $this;
    }

    public function getFrenchMotto(): ?string
    {
        return $this->frenchMotto;
    }

    public function setFrenchMotto(string $frenchMotto): self
    {
        $this->frenchMotto = $frenchMotto;

        return $this;
    }

    public function getEnglishMotto(): ?string
    {
        return $this->englishMotto;
    }

    public function setEnglishMotto(string $englishMotto): self
    {
        $this->englishMotto = $englishMotto;

        return $this;
    }

    public function getPobox(): ?string
    {
        return $this->pobox;
    }

    public function setPobox(string $pobox): self
    {
        $this->pobox = $pobox;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Set the logo file
     *
     * @param File|null $logoFile
     * @return void
     */
    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if($logoFile !== null)
        {
            $this->setUpdatedAt(new \DateTime());
        }
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
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

    /**
     * Set the Filigree file
     *
     * @param File|null $filigrreFile
     * @return void
     */
    public function setFiligreeFile(?File $filigreeFile = null): void
    {
        $this->filigreeFile = $filigreeFile;

        if($filigreeFile !== null)
        {
            $this->setUpdatedAt(new \DateTime());
        }
    }

    public function getFiligreeFile(): ?File
    {
        return $this->filigreeFile;
    }

    public function getFiligree(): ?string
    {
        return $this->filigree;
    }

    public function setFiligree(string $filigree): self
    {
        $this->filigree = $filigree;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFrenchCountry(): ?string
    {
        return $this->frenchCountry;
    }

    public function setFrenchCountry(string $frenchCountry): self
    {
        $this->frenchCountry = $frenchCountry;

        return $this;
    }

    public function getEnglishCountry(): ?string
    {
        return $this->englishCountry;
    }

    public function setEnglishCountry(string $englishCountry): self
    {
        $this->englishCountry = $englishCountry;

        return $this;
    }

    public function getFrenchMinister(): ?string
    {
        return $this->frenchMinister;
    }

    public function setFrenchMinister(string $frenchMinister): self
    {
        $this->frenchMinister = $frenchMinister;

        return $this;
    }

    public function getEnglishMinister(): ?string
    {
        return $this->englishMinister;
    }

    public function setEnglishMinister(string $englishMinister): self
    {
        $this->englishMinister = $englishMinister;

        return $this;
    }

    public function getFrenchRegion(): ?string
    {
        return $this->frenchRegion;
    }

    public function setFrenchRegion(string $frenchRegion): self
    {
        $this->frenchRegion = $frenchRegion;

        return $this;
    }

    public function getEnglishRegion(): ?string
    {
        return $this->englishRegion;
    }

    public function setEnglishRegion(string $englishRegion): self
    {
        $this->englishRegion = $englishRegion;

        return $this;
    }

    public function getFrenchDivision(): ?string
    {
        return $this->frenchDivision;
    }

    public function setFrenchDivision(string $frenchDivision): self
    {
        $this->frenchDivision = $frenchDivision;

        return $this;
    }

    public function getEnglishDivision(): ?string
    {
        return $this->englishDivision;
    }

    public function setEnglishDivision(string $englishDivision): self
    {
        $this->englishDivision = $englishDivision;

        return $this;
    }

    public function getFrenchSubDivision(): ?string
    {
        return $this->frenchSubDivision;
    }

    public function setFrenchSubDivision(string $frenchSubDivision): self
    {
        $this->frenchSubDivision = $frenchSubDivision;

        return $this;
    }

    public function getEnglishSubDivision(): ?string
    {
        return $this->englishSubDivision;
    }

    public function setEnglishSubDivision(string $englishSubDivision): self
    {
        $this->englishSubDivision = $englishSubDivision;

        return $this;
    }

    public function getFrenchCountryMotto(): ?string
    {
        return $this->frenchCountryMotto;
    }

    public function setFrenchCountryMotto(string $frenchCountryMotto): self
    {
        $this->frenchCountryMotto = $frenchCountryMotto;

        return $this;
    }

    public function getEnglishCountryMotto(): ?string
    {
        return $this->englishCountryMotto;
    }

    public function setEnglishCountryMotto(string $englishCountryMotto): self
    {
        $this->englishCountryMotto = $englishCountryMotto;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(?SchoolYear $schoolYear): self
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }

    public function getHeadmaster(): ?Teacher
    {
        return $this->headmaster;
    }

    public function setHeadmaster(?Teacher $headmaster): self
    {
        $this->headmaster = $headmaster;

        return $this;
    }

    public function getServiceNote(): ?string
    {
        return $this->serviceNote;
    }

    public function setServiceNote(string $serviceNote): self
    {
        $this->serviceNote = $serviceNote;

        return $this;
    }

    public function getEducation(): ?Education
    {
        return $this->education;
    }

    public function setEducation(?Education $education): self
    {
        $this->education = $education;

        return $this;
    }

    public function serialize()
    {
        $this->logo = base64_encode($this->logo);
    }

    public function unserialize($serialized)
    {
        $this->filigree = base64_decode($this->filigree);

    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): self
    {
        $this->public = $public;

        return $this;
    }

    public function isLycee(): ?bool
    {
        return $this->lycee;
    }

    public function setLycee(?bool $lycee): self
    {
        $this->lycee = $lycee;

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getClassrooms(): Collection
    {
        return $this->classrooms;
    }

    

   

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

   

   

    /**
     * @return Collection<int, Depense>
     */
    public function getDepenses(): Collection
    {
        return $this->depenses;
    }

   

   

    /**
     * @return Collection<int, EtatDepense>
     */
    public function getEtatDepenses(): Collection
    {
        return $this->etatDepenses;
    }

    

    

    /**
     * @return Collection<int, Fees>
     */
    public function getFees(): Collection
    {
        return $this->fees;
    }

    

   

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return Collection<int, Verrou>
     */
    public function getVerrous(): Collection
    {
        return $this->verrous;
    }

    /**
     * @return Collection<int, VerrouInsolvable>
     */
    public function getVerrouInsolvables(): Collection
    {
        return $this->verrouInsolvables;
    }

    /**
     * @return Collection<int, VerrouReport>
     */
    public function getVerrouReports(): Collection
    {
        return $this->verrouReports;
    }

    /**
     * @return Collection<int, VerrouSequence>
     */
    public function getVerrouSequences(): Collection
    {
        return $this->verrouSequences;
    }

}
