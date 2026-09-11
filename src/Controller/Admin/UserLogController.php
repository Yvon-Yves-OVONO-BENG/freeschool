<?php

namespace App\Controller\Admin;

use App\Repository\SchoolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserLogRepository;
use Symfony\Component\HttpFoundation\Request;

class UserLogController extends AbstractController
{
    #[Route('/admin/user-log/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}', name: 'admin_user_log')]
    public function index(Request $request, UserLogRepository $userLogRepository, SchoolRepository $schoolRepository, int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        //Connexions d'une période
        if ($request->request->has('dateDebut') && $request->request->has('dateFin') && $request->request->has('afficherConnexionPeriode')) 
        {
            $dateDebut = date_create($request->request->get('dateDebut'));
            $dateFin = date_create($request->request->get('dateFin'));

            $dateDebut->setTime(0, 0, 0);
            $dateFin->setTime(23, 59, 59);
            
            $logs = $userLogRepository->findLogsBetweenDates($schoolYear, $dateDebut, $dateFin);

        }
        else
        {
            $logs = $userLogRepository->findLogsOfToday($schoolYear);
        }

        return $this->render('admin/user_log.html.twig', [
            'logs' => $logs,
            'school' => $school,
        ]);
    }
}