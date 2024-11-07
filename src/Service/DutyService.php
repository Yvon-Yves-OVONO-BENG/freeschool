<?php

namespace App\Service;

use App\Entity\Teacher;
use App\Repository\DutyRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Request;

class DutyService
{
    public function __construct(
        protected TeacherRepository $teacherRepository, 
        protected DutyRepository $dutyRepository)
    {}

    /**
     * recupere les teachers selon le duty
     *
     * @param string $duty
     * @return array
     */
    public function getTeachersByDuty(Request $request, string $duty): array
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');

        if($duty != 'Enseignant')
        {
            return $this->teacherRepository->findBy([
                'duty' => $this->dutyRepository->findOneBy(['duty' => $duty]),
                'schoolYear' => $schoolYear
            ], [
                'fullName' => 'ASC'
                ]);
        }else 
        {
            return  $this->teacherRepository->findBy([
                'schoolYear' => $schoolYear
            ], [
                'fullName' => 'ASC'
                ]);
        }
    }


    /**
     * recupere une route en fonction du duty
     *
     * @param string $duty
     * @return string
     */
    public function getRouteByDuty(string $duty): string
    {
        switch ($duty) {
            case 'Enseignant':
                return 'evaluation_markRecorder';
                break;

            case 'Surveillant':
                return 'absence_absenceRecorder';
                break;

            case 'Censeur':
                return 'home_dashboard';
                break;
        }
    } 
}
