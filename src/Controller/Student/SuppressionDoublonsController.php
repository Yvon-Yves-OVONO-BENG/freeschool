<?php

namespace App\Controller\Student;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SuppressionDoublonsController extends AbstractController
{
    #[Route('/supprimer-doublons', name: 'supprimer_doublons')]
    public function supprimerDoublons(Request $request, EntityManagerInterface $em, 
    TranslatorInterface $translator, )
    {
        if ($request->isMethod('POST')) {
            $classeId = $request->request->get('classroom');
           
            $sql1 = "DELETE r FROM registration r 
                        JOIN student st1 ON r.student_id = st1.id
                        JOIN student st2 ON st1.full_name = st2.full_name 
                        AND st1.classroom_id = st2.classroom_id
                        AND st1.registration_number = st2.registration_number
                        AND st1.id > st2.id 
                        WHERE st1.classroom_id = :classroom_id";

            $sql2 = "
                DELETE e1 FROM student e1
                JOIN student e2
                  ON e1.full_name = e2.full_name
                  AND e1.registration_number = e2.registration_number
                  AND e1.classroom_id = e2.classroom_id
                  AND e1.id > e2.id
                WHERE e1.classroom_id = :classroom_id
            ";

            $conn = $em->getConnection();
            $conn->executeQuery($sql1, ['classroom_id' => $classeId]);
            $conn->executeQuery($sql2, ['classroom_id' => $classeId]);
            

            $this->addFlash('info', $translator->trans('Duplicate deleted with success !'));

            return $this->redirectToRoute('student_displayStudent', ['suppression' => 1 ]);
        }

    }
}
