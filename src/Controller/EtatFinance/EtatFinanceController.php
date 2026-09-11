<?php

namespace App\Controller\EtatFinance;

use App\Entity\ConstantsClass;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatDepenseRepository;
use App\Repository\EtatFinanceRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/etat-finance')]
class EtatFinanceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService,
        protected DepenseRepository $depenseRepository,
        protected SchoolYearRepository $schoolYearRepository,
        protected EtatFinanceRepository $etatFinanceRepository,
        protected EtatDepenseRepository $etatDepenseRepository,
        protected RegistrationRepository $registrationRepository,
    ) {
    }

    #[Route('/finance', name: 'etat_finance')]
    public function etatFinance(Request $request): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        /*
         * Vérification de l'année scolaire enregistrée en session.
         */
        $sessionSchoolYear = $mySession->get('schoolYear');

        if ($sessionSchoolYear === null) {
            return $this->redirectToRoute('app_logout');
        }

        $subSystem = $mySession->get('subSystem');

        /*
         * Récupération de l'établissement lié à l'année scolaire.
         */
        $school = $this->schoolRepository->findOneBySchoolYear([
            'schoolYear' => $sessionSchoolYear,
        ]);

        /*
         * Récupération de l'entité SchoolYear depuis la base de données.
         */
        $schoolYear = $this->schoolYearRepository->findOneBy([
            'schoolYear' => $sessionSchoolYear->getSchoolYear(),
        ]);

        if ($schoolYear === null) {
            throw $this->createNotFoundException(
                $this->translator->trans('The selected school year does not exist.')
            );
        }

        /*
         * Récupération de toutes les dépenses de l'année scolaire.
         *
         * Si, dans ton entité Depense, la propriété ne s'appelle pas
         * "schoolYear", remplace-la ici par le vrai nom de la propriété.
         */
        $depenses = $this->depenseRepository->findBy(
            [
                'schoolYear' => $schoolYear,
            ],
            [
                'createdAt' => 'DESC',
            ]
        );

        /*
         * Nombre total de dépenses affichées dans la page.
         */
        $numberOfDepenses = count($depenses);

        /*
         * Récupération de l'état financier.
         */
        $etatFinance = $this->registrationRepository
            ->getEtatFinancier($schoolYear);

        /*
         * Récupération des sommes dépensées par rubrique.
         */
        $etatDepenses = $this->depenseRepository
            ->getSumSpendingPerRubrique($schoolYear);

        $apee = 0;
        $computer = 0;
        $cleanSchool = 0;
        $medicalBooklet = 0;
        $stamp = 0;
        $photo = 0;

        foreach ($etatDepenses as $etatDepense) {
            $rubrique = $etatDepense['RUBRIQUE'] ?? null;
            $somme = $etatDepense['SOMME'] ?? 0;

            if ($rubrique === ConstantsClass::APEE) {
                $apee = $somme;
            } elseif ($rubrique === ConstantsClass::COMPUTER) {
                $computer = $somme;
            } elseif ($rubrique === ConstantsClass::CLEAN_SCHOOL) {
                $cleanSchool = $somme;
            } elseif ($rubrique === ConstantsClass::MEDICAL_BOOKLET) {
                $medicalBooklet = $somme;
            } elseif ($rubrique === ConstantsClass::STAMP) {
                $stamp = $somme;
            } elseif ($rubrique === ConstantsClass::PHOTO) {
                $photo = $somme;
            }
        }

        return $this->render('etat_finance/displayEtatFinance.html.twig', [
            'etatFinance' => $etatFinance[0] ?? null,

            // Variables nécessaires au tableau Twig
            'depenses' => $depenses,
            'numberOfDepenses' => $numberOfDepenses,

            'apee' => $apee,
            'computer' => $computer,
            'cleanSchool' => $cleanSchool,
            'medicalBooklet' => $medicalBooklet,
            'stamp' => $stamp,
            'photo' => $photo,
            'school' => $school,

            // Envoyée explicitement pour éviter une autre variable Twig absente
            'mySession' => $mySession,
        ]);
    }
}