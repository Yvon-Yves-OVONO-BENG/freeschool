<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\StudentRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $birthday = null;

    #[ORM\Column(length: 255)]
    private ?string $birthplace = null;
    
    #[Vich\UploadableField(mapping:"student_image", fileNameProperty:"photo")]
    /**
    *
    * @var File|null
     */
    private $imageFile;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 255)]
    private ?string $registrationNumber = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Classroom $classroom = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Sex $sex = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Decision $decision = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Repeater $repeater = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?User $updatedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephonePere = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Responsability $responsability = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?EthnicGroup $ethnicGroup = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Movement $movement = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Handicap $handicap = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?HandicapType $handicapType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nextClassroomName = null;

    #[ORM\Column(nullable: true)]
    private ?int $prevId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fatherName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motherName = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Report::class)]
    private Collection $reports;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Country $country = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: RegistrationHistory::class)]
    private Collection $registrationHistories;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Absence::class)]
    private Collection $absences;

    // #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    // private ?Registration $registration = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Registration::class)]
    private Collection $registrations;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?Operateur $operateur = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?ModeAdmission $modeAdmission = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroHcr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionPere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionMere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephoneTuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personneAContacterEnCasUergence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephonePersonneEnCasUrgence = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePremiereEntreeEtablissementAt = null;

    #[ORM\ManyToOne(inversedBy: 'studentsEntree')]
    private ?Classroom $classeEntree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etablisementFrequenteAnDernier = null;

    #[ORM\Column(nullable: true)]
    private ?bool $drepanocytose = null;

    #[ORM\Column(nullable: true)]
    private ?bool $apte = null;

    #[ORM\Column(nullable: true)]
    private ?bool $asthme = null;

    #[ORM\Column(nullable: true)]
    private ?bool $covid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autresMaladies = null;

    #[ORM\Column(nullable: true)]
    private ?bool $allergie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siOuiAllergie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rhesus = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubMulticulturel = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubScientifique = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubJournal = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubEnvironnement = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubSante = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubRethorique = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autreClub = null;

    #[ORM\Column(nullable: true)]
    private ?bool $frere = null;

    #[ORM\Column(nullable: true)]
    private ?bool $soeur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enseignant = null;

    #[ORM\ManyToOne(inversedBy: 'studentFrereSoeurs')]
    private ?Classroom $classeFrereSoeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autreConnaisanceEtablissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomPersonneEtablissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephonePersonneEtablissement = null;

    #[ORM\Column(nullable: true)]
    private ?bool $autochtone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephoneMere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodeFiche = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubBilingue = null;

    #[ORM\Column(nullable: true)]
    private ?bool $clubLv2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroWhatsapp = null;

    #[ORM\Column(nullable: true)]
    private ?bool $solvable = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    private ?SubSystem $subSystem = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Evaluation::class)]
    private Collection $evaluations;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Conseil::class)]
    private Collection $conseils;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $supprime = null;

    #[ORM\ManyToOne]
    private ?User $deletedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $qrCodeRollOfHonor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionTuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailParent = null;

    public function __construct()
    {
        $this->reports = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->registrationHistories = new ArrayCollection();
        $this->registrations = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->conseils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    public function setBirthplace(string $birthplace): self
    {
        $this->birthplace = $birthplace;

        return $this;
    }

    /**
     * Set the image file
     *
     * @param File|null $imageFile
     * @return void
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if($imageFile !== null)
        {
            $this->setUpdatedAt(new \DateTime());
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function getSex(): ?Sex
    {
        return $this->sex;
    }

    public function setSex(?Sex $sex): self
    {
        $this->sex = $sex;

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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }

    public function getDecision(): ?Decision
    {
        return $this->decision;
    }

    public function setDecision(?Decision $decision): self
    {
        $this->decision = $decision;

        return $this;
    }

    public function getRepeater(): ?Repeater
    {
        return $this->repeater;
    }

    public function setRepeater(?Repeater $repeater): self
    {
        $this->repeater = $repeater;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getTelephonePere(): ?string
    {
        return $this->telephonePere;
    }

    public function setTelephonePere(?string $telephonePere): self
    {
        $this->telephonePere = $telephonePere;

        return $this;
    }

    public function getResponsability(): ?Responsability
    {
        return $this->responsability;
    }

    public function setResponsability(?Responsability $responsability): self
    {
        $this->responsability = $responsability;

        return $this;
    }

    public function getEthnicGroup(): ?EthnicGroup
    {
        return $this->ethnicGroup;
    }

    public function setEthnicGroup(?EthnicGroup $ethnicGroup): self
    {
        $this->ethnicGroup = $ethnicGroup;

        return $this;
    }

    public function getMovement(): ?Movement
    {
        return $this->movement;
    }

    public function setMovement(?Movement $movement): self
    {
        $this->movement = $movement;

        return $this;
    }

    public function getHandicap(): ?Handicap
    {
        return $this->handicap;
    }

    public function setHandicap(?Handicap $handicap): self
    {
        $this->handicap = $handicap;

        return $this;
    }

    public function getHandicapType(): ?HandicapType
    {
        return $this->handicapType;
    }

    public function setHandicapType(?HandicapType $handicapType): self
    {
        $this->handicapType = $handicapType;

        return $this;
    }

    public function getNextClassroomName(): ?string
    {
        return $this->nextClassroomName;
    }

    public function setNextClassroomName(?string $nextClassroomName): self
    {
        $this->nextClassroomName = $nextClassroomName;

        return $this;
    }

    public function getPrevId(): ?int
    {
        return $this->prevId;
    }

    public function setPrevId(?int $prevId): self
    {
        $this->prevId = $prevId;

        return $this;
    }

    public function getFatherName(): ?string
    {
        return $this->fatherName;
    }

    public function setFatherName(?string $fatherName): self
    {
        $this->fatherName = $fatherName;

        return $this;
    }

    public function getMotherName(): ?string
    {
        return $this->motherName;
    }

    public function setMotherName(?string $motherName): self
    {
        $this->motherName = $motherName;

        return $this;
    }

   

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): self
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setStudent($this);
        }

        return $this;
    }

    public function removeReport(Report $report): self
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getStudent() === $this) {
                $report->setStudent(null);
            }
        }

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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, RegistrationHistory>
     */
    public function getRegistrationHistories(): Collection
    {
        return $this->registrationHistories;
    }

    public function addRegistrationHistory(RegistrationHistory $registrationHistory): self
    {
        if (!$this->registrationHistories->contains($registrationHistory)) {
            $this->registrationHistories->add($registrationHistory);
            $registrationHistory->setStudent($this);
        }

        return $this;
    }

    public function removeRegistrationHistory(RegistrationHistory $registrationHistory): self
    {
        if ($this->registrationHistories->removeElement($registrationHistory)) {
            // set the owning side to null (unless already changed)
            if ($registrationHistory->getStudent() === $this) {
                $registrationHistory->setStudent(null);
            }
        }

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
            $absence->setStudent($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getStudent() === $this) {
                $absence->setStudent(null);
            }
        }

        return $this;
    }

    // public function getRegistration(): ?Registration
    // {
    //     return $this->registration;
    // }

    // public function setRegistration(?Registration $registration): self
    // {
    //     $this->registration = $registration;

    //     return $this;
    // }

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
            $registration->setStudent($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getStudent() === $this) {
                $registration->setStudent(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;

        return $this;
    }

    // public function __toString()
    // {
    //     return $this->fullName;
    // }

    public function getOperateur(): ?Operateur
    {
        return $this->operateur;
    }

    public function setOperateur(?Operateur $operateur): self
    {
        $this->operateur = $operateur;

        return $this;
    }

    public function getModeAdmission(): ?ModeAdmission
    {
        return $this->modeAdmission;
    }

    public function setModeAdmission(?ModeAdmission $modeAdmission): self
    {
        $this->modeAdmission = $modeAdmission;

        return $this;
    }

    public function getNumeroHcr(): ?string
    {
        return $this->numeroHcr;
    }

    public function setNumeroHcr(?string $numeroHcr): self
    {
        $this->numeroHcr = $numeroHcr;

        return $this;
    }

    public function getProfessionPere(): ?string
    {
        return $this->professionPere;
    }

    public function setProfessionPere(?string $professionPere): self
    {
        $this->professionPere = $professionPere;

        return $this;
    }

    public function getProfessionMere(): ?string
    {
        return $this->professionMere;
    }

    public function setProfessionMere(?string $professionMere): self
    {
        $this->professionMere = $professionMere;

        return $this;
    }

    public function getTuteur(): ?string
    {
        return $this->tuteur;
    }

    public function setTuteur(?string $tuteur): self
    {
        $this->tuteur = $tuteur;

        return $this;
    }

    public function getTelephoneTuteur(): ?string
    {
        return $this->telephoneTuteur;
    }

    public function setTelephoneTuteur(?string $telephoneTuteur): self
    {
        $this->telephoneTuteur = $telephoneTuteur;

        return $this;
    }

    public function getPersonneAContacterEnCasUergence(): ?string
    {
        return $this->personneAContacterEnCasUergence;
    }

    public function setPersonneAContacterEnCasUergence(?string $personneAContacterEnCasUergence): self
    {
        $this->personneAContacterEnCasUergence = $personneAContacterEnCasUergence;

        return $this;
    }

    public function getTelephonePersonneEnCasUrgence(): ?string
    {
        return $this->telephonePersonneEnCasUrgence;
    }

    public function setTelephonePersonneEnCasUrgence(?string $telephonePersonneEnCasUrgence): self
    {
        $this->telephonePersonneEnCasUrgence = $telephonePersonneEnCasUrgence;

        return $this;
    }

    public function getDatePremiereEntreeEtablissementAt(): ?\DateTimeInterface
    {
        return $this->datePremiereEntreeEtablissementAt;
    }

    public function setDatePremiereEntreeEtablissementAt(?\DateTimeInterface $datePremiereEntreeEtablissementAt): self
    {
        $this->datePremiereEntreeEtablissementAt = $datePremiereEntreeEtablissementAt;

        return $this;
    }

    public function getClasseEntree(): ?Classroom
    {
        return $this->classeEntree;
    }

    public function setClasseEntree(?Classroom $classeEntree): self
    {
        $this->classeEntree = $classeEntree;

        return $this;
    }

    public function getEtablisementFrequenteAnDernier(): ?string
    {
        return $this->etablisementFrequenteAnDernier;
    }

    public function setEtablisementFrequenteAnDernier(?string $etablisementFrequenteAnDernier): self
    {
        $this->etablisementFrequenteAnDernier = $etablisementFrequenteAnDernier;

        return $this;
    }

    public function isDrepanocytose(): ?bool
    {
        return $this->drepanocytose;
    }

    public function setDrepanocytose(?bool $drepanocytose): self
    {
        $this->drepanocytose = $drepanocytose;

        return $this;
    }

    public function isApte(): ?bool
    {
        return $this->apte;
    }

    public function setApte(?bool $apte): self
    {
        $this->apte = $apte;

        return $this;
    }

    public function isAsthme(): ?bool
    {
        return $this->asthme;
    }

    public function setAsthme(?bool $asthme): self
    {
        $this->asthme = $asthme;

        return $this;
    }

    public function isCovid(): ?bool
    {
        return $this->covid;
    }

    public function setCovid(?bool $covid): self
    {
        $this->covid = $covid;

        return $this;
    }

    public function getAutresMaladies(): ?string
    {
        return $this->autresMaladies;
    }

    public function setAutresMaladies(?string $autresMaladies): self
    {
        $this->autresMaladies = $autresMaladies;

        return $this;
    }

    public function isAllergie(): ?bool
    {
        return $this->allergie;
    }

    public function setAllergie(?bool $allergie): self
    {
        $this->allergie = $allergie;

        return $this;
    }

    public function getSiOuiAllergie(): ?string
    {
        return $this->siOuiAllergie;
    }

    public function setSiOuiAllergie(?string $siOuiAllergie): self
    {
        $this->siOuiAllergie = $siOuiAllergie;

        return $this;
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;

        return $this;
    }

    public function getRhesus(): ?string
    {
        return $this->rhesus;
    }

    public function setRhesus(?string $rhesus): self
    {
        $this->rhesus = $rhesus;

        return $this;
    }

    public function isClubMulticulturel(): ?bool
    {
        return $this->clubMulticulturel;
    }

    public function setClubMulticulturel(?bool $clubMulticulturel): self
    {
        $this->clubMulticulturel = $clubMulticulturel;

        return $this;
    }

    public function isClubScientifique(): ?bool
    {
        return $this->clubScientifique;
    }

    public function setClubScientifique(?bool $clubScientifique): self
    {
        $this->clubScientifique = $clubScientifique;

        return $this;
    }

    public function isClubJournal(): ?bool
    {
        return $this->clubJournal;
    }

    public function setClubJournal(?bool $clubJournal): self
    {
        $this->clubJournal = $clubJournal;

        return $this;
    }

    public function isClubEnvironnement(): ?bool
    {
        return $this->clubEnvironnement;
    }

    public function setClubEnvironnement(?bool $clubEnvironnement): self
    {
        $this->clubEnvironnement = $clubEnvironnement;

        return $this;
    }

    public function isClubSante(): ?bool
    {
        return $this->clubSante;
    }

    public function setClubSante(?bool $clubSante): self
    {
        $this->clubSante = $clubSante;

        return $this;
    }

    public function isClubRethorique(): ?bool
    {
        return $this->clubRethorique;
    }

    public function setClubRethorique(?bool $clubRethorique): self
    {
        $this->clubRethorique = $clubRethorique;

        return $this;
    }

    public function getAutreClub(): ?string
    {
        return $this->autreClub;
    }

    public function setAutreClub(?string $autreClub): self
    {
        $this->autreClub = $autreClub;

        return $this;
    }

    public function isFrere(): ?bool
    {
        return $this->frere;
    }

    public function setFrere(?bool $frere): self
    {
        $this->frere = $frere;

        return $this;
    }

    public function isSoeur(): ?bool
    {
        return $this->soeur;
    }

    public function setSoeur(?bool $soeur): self
    {
        $this->soeur = $soeur;

        return $this;
    }

    public function isEnseignant(): ?bool
    {
        return $this->enseignant;
    }

    public function setEnseignant(?bool $enseignant): self
    {
        $this->enseignant = $enseignant;

        return $this;
    }

    public function getClasseFrereSoeur(): ?Classroom
    {
        return $this->classeFrereSoeur;
    }

    public function setClasseFrereSoeur(?Classroom $classeFrereSoeur): self
    {
        $this->classeFrereSoeur = $classeFrereSoeur;

        return $this;
    }

    public function getAutreConnaisanceEtablissement(): ?string
    {
        return $this->autreConnaisanceEtablissement;
    }

    public function setAutreConnaisanceEtablissement(?string $autreConnaisanceEtablissement): self
    {
        $this->autreConnaisanceEtablissement = $autreConnaisanceEtablissement;

        return $this;
    }

    public function getNomPersonneEtablissement(): ?string
    {
        return $this->nomPersonneEtablissement;
    }

    public function setNomPersonneEtablissement(?string $nomPersonneEtablissement): self
    {
        $this->nomPersonneEtablissement = $nomPersonneEtablissement;

        return $this;
    }

    public function getTelephonePersonneEtablissement(): ?string
    {
        return $this->telephonePersonneEtablissement;
    }

    public function setTelephonePersonneEtablissement(?string $telephonePersonneEtablissement): self
    {
        $this->telephonePersonneEtablissement = $telephonePersonneEtablissement;

        return $this;
    }

    public function isAutochtone(): ?bool
    {
        return $this->autochtone;
    }

    public function setAutochtone(?bool $autochtone): self
    {
        $this->autochtone = $autochtone;

        return $this;
    }

    public function getTelephoneMere(): ?string
    {
        return $this->telephoneMere;
    }

    public function setTelephoneMere(?string $telephoneMere): self
    {
        $this->telephoneMere = $telephoneMere;

        return $this;
    }

    public function getQrCodeFiche(): ?string
    {
        return $this->qrCodeFiche;
    }

    public function setQrCodeFiche(?string $qrCodeFiche): self
    {
        $this->qrCodeFiche = $qrCodeFiche;

        return $this;
    }

    public function isClubBilingue(): ?bool
    {
        return $this->clubBilingue;
    }

    public function setClubBilingue(?bool $clubBilingue): self
    {
        $this->clubBilingue = $clubBilingue;

        return $this;
    }

    public function isClubLv2(): ?bool
    {
        return $this->clubLv2;
    }

    public function setClubLv2(?bool $clubLv2): self
    {
        $this->clubLv2 = $clubLv2;

        return $this;
    }

    public function getNumeroWhatsapp(): ?string
    {
        return $this->numeroWhatsapp;
    }

    public function setNumeroWhatsapp(?string $numeroWhatsapp): self
    {
        $this->numeroWhatsapp = $numeroWhatsapp;

        return $this;
    }

    public function isSolvable(): ?bool
    {
        return $this->solvable;
    }

    public function setSolvable(?bool $solvable): self
    {
        $this->solvable = $solvable;

        return $this;
    }

    public function getSubSystem(): ?SubSystem
    {
        return $this->subSystem;
    }

    public function setSubSystem(?SubSystem $subSystem): self
    {
        $this->subSystem = $subSystem;

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
            $evaluation->setStudent($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getStudent() === $this) {
                $evaluation->setStudent(null);
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
            $conseil->setStudent($this);
        }

        return $this;
    }

    public function removeConseil(Conseil $conseil): self
    {
        if ($this->conseils->removeElement($conseil)) {
            // set the owning side to null (unless already changed)
            if ($conseil->getStudent() === $this) {
                $conseil->setStudent(null);
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

    public function isSupprime(): ?bool
    {
        return $this->supprime;
    }

    public function setSupprime(bool $supprime): self
    {
        $this->supprime = $supprime;

        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?User $deletedBy): self
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getQrCodeRollOfHonor(): ?string
    {
        return $this->qrCodeRollOfHonor;
    }

    public function setQrCodeRollOfHonor(string $qrCodeRollOfHonor): self
    {
        $this->qrCodeRollOfHonor = $qrCodeRollOfHonor;

        return $this;
    }

    public function getProfessionTuteur(): ?string
    {
        return $this->professionTuteur;
    }

    public function setProfessionTuteur(?string $professionTuteur): self
    {
        $this->professionTuteur = $professionTuteur;

        return $this;
    }

    public function getEmailParent(): ?string
    {
        return $this->emailParent;
    }

    public function setEmailParent(?string $emailParent): self
    {
        $this->emailParent = $emailParent;

        return $this;
    }

}
