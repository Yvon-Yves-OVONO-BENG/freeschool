<?php

namespace App\Controller\SendTranscripts;

use Symfony\Component\Mime\Email;
use App\Repository\TermRepository;
use Symfony\Component\Mime\Address;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Service\PrintTranscriptService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\InternetConnectionCheckerService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class SendTranscriptAnnualController extends AbstractController
{
    public function __construct(
        protected MailerInterface $mailer,
        protected TermRepository $termRepository,
        protected TranslatorInterface $translator,
        protected LessonRepository $lessonRepository,
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository,
        protected SequenceRepository $sequenceRepository,
        protected ClassroomRepository $classroomRepository,
        protected PrintTranscriptService $printTranscriptService,
        protected InternetConnectionCheckerService $connectionCheckerService, 
        )
    {}

    #[Route("/send-transcript-annual-student/{slugStudent}/{slugTerm}", name:"send_transcript_annual_student")]
    #[Route("/send-transcript-annual-classroom/{slugClassroom}/{slugTerm}", name:"send_transcript_annual_classroom")]
    public function sendTranscriptAnnual(Request $request, 
    MailerInterface $mailer, TransportInterface $transport,  string $slugStudent = null, string $slugClassroom = null, string $slugTerm = null): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        // if (!$this->connectionCheckerService->isConnected()) {
        //     // Ne pas envoyer le mail si pas de connexion Internet
        //     $this->addFlash('error', $this->translator->trans('Unable to send email(s) : No Internet connection !'));

        //     return $this->redirectToRoute('transcript_student');
        // }

        /**
         * @var User
         */
        $user = $this->getUser();
        $session = $request->getSession();
        $session->set('user', $user);

        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $student = $this->studentRepository->findOneBy(['slug' => $slugStudent ]);

        $term = null;
        $sequence = null;
        $studentName = null;

        if ($slugTerm && !$slugClassroom) 
        {
            $term = $this->termRepository->findOneBy(['slug' => $slugTerm]);

            $releves = $this->lessonRepository->getAnnualReportByStudent($student->getId());
            
            // Génération du PDF
            if ($subSystem->getId() == 1 ) 
            {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/releves/Transcript of ' . $student->getFullName() .' - Annuel.pdf';
            }
            else
            {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/releves/Relevé de notes de ' . $student->getFullName().' - Annuel.pdf';
            }

            try 
            {
                // Générer et enregistrer le fichier PDF
                $envoie = 1;
                $this->printTranscriptService->printTranscriptStudentAnnual($subSystem, $schoolYear, $school, $student->getClassroom(), $releves, $student, $term, $sequence, $envoie, $filePath);

            } 
            catch (\Exception $e) 
            {
                $this->addFlash('danger', $this->translator->trans("Transcript generation error !"));
                return $this->redirectToRoute("transcript_student");
            }

            // Envoi de l'email avec pièce jointe
            $email = (new TemplatedEmail())
                ->from(new Address('lyceebilingueodza@freedomsoftwarepro.com', "LYCEE BILINGUE D'ODZA - G.B.H.S ODZA"))
                ->to($student->getEmailParent())
                ->subject("Le relevé de notes de votre enfant / The transcript for your children")
                ->htmlTemplate('emails/envoieEmail.html.twig')
                ->context([
                    'user' => $user,
                    'term' => $term,
                    'school' => $school,
                    'student' => $student,
                    'sequence' => $sequence,
                ])
                ->attachFromPath($filePath, 'Relevé de note de ' . $student->getFullName() .' - Annuel.pdf', 'application/pdf');
                
            try 
            {
                $transport->send($email);
                $mailer->send($email);
                $this->addFlash('info', $this->translator->trans("Transcript send with success !"));

            } 
            catch (TransportExceptionInterface $e)
            {
                $this->addFlash('danger', $this->translator->trans("Error sending mail !"));

                return $this->redirectToRoute("transcript_student");
            }

        } 
        
        
        if ($request->request->has('slugClassroom') && $request->request->has('term')) 
        {
            $term = $this->termRepository->find($request->request->get('term'));
            $classroom = $this->classroomRepository->findOneBy(['slug' => $request->request->get('slugClassroom')] );
            
            $relevesTermClasse = $this->lessonRepository->getAnnualReportByClassroom($classroom->getId());
            
            foreach ($relevesTermClasse as $releves) 
            {
                // Génération du PDF
                if ($subSystem->getId() == 1 ) 
                {
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/releves/Transcript of ' . $releves['studentName'] .' - Annuel.pdf';
                }
                else
                {
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/releves/Relevé de notes de ' . $releves['studentName'].' - Annuel.pdf';
                }
    
                try 
                {
                    // Générer et enregistrer le fichier PDF
                    $envoie = 1;
                    
                    $this->printTranscriptService->sendTranscriptStudentAnnual($subSystem, $schoolYear, $school, $classroom, $releves, $student, $releves['studentName'], $term, $sequence, $filePath);
                    
                } 
                catch (\Exception $e) 
                {
                    // dd($e);
                    $this->addFlash('danger', $this->translator->trans("Transcript generation error !"));

                    return $this->redirectToRoute("transcript_student");
                }
    
                // Envoi de l'email avec pièce jointe
                $email = (new TemplatedEmail())
                    ->from(new Address('lyceebilingueodza@freedomsoftwarepro.com', "LYCEE BILINGUE D'ODZA - G.B.H.S ODZA"))
                    ->to($releves['emailParent'])
                    ->subject("Le relevé de notes de votre enfant / The transcript for your children")
                    ->htmlTemplate('emails/envoieEmail.html.twig')
                    ->context([
                        'user' => $user,
                        'term' => $term,
                        'school' => $school,
                        'student' => $student,
                        'sequence' => $sequence,
                        'classroom' => $classroom,
                        'studentName' => $releves['studentName'],
                    ])
                    ->attachFromPath($filePath, 'Relevé de notes de ' . $releves['studentName'] .' -  Annuel.pdf', 'application/pdf');
                    
                try 
                {
                    $transport->send($email);
                    $mailer->send($email);
    
                } 
                catch (TransportExceptionInterface $e)
                {
                    $this->addFlash('danger', $this->translator->trans("Error sending mail !"));
    
                    return $this->redirectToRoute("transcript_student");
                }
            }
            
            $this->addFlash('info', $this->translator->trans("Transcript send with success !"));

        }

        
        return $this->redirectToRoute("transcript_student");
    }
}