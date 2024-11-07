<?php

namespace App\Service;

use App\Entity\Fees;
use App\Entity\Classroom;

class FeesService
{

    public function getFeesTable(Classroom $classroom, Fees $fees): array
    {
        $classroomLevel = $classroom->getLevel()->getLevel();

        // On recupère assigne les frias en fonction du cycle
        if($classroom->getLevel()->getCycle()->getCycle() == 1)
        {
            $feesTable['schoolFees'] = $fees->getSchoolFees1();
            $feesTable['apeeFees'] = $fees->getApeeFees1();
            $feesTable['computerFees'] = $fees->getComputerFees1();

            if($classroomLevel == 4)
            {
                $feesTable['stampFees'] = $fees->getStampFees3eme();
                $feesTable['examFees'] = $fees->getExamFees3eme();
            }
        }else
        {
            $feesTable['schoolFees'] = $fees->getSchoolFees2();
            $feesTable['apeeFees'] = $fees->getApeeFees2();
            $feesTable['computerFees'] = $fees->getComputerFees2();

            if($classroomLevel == 6)
            {
                $feesTable['stampFees'] = $fees->getStampFees1ere();
                $feesTable['examFees'] = $fees->getExamFees1ere();

            }elseif($classroomLevel == 7)
            {
                $feesTable['stampFees'] = $fees->getStampFeesTle();
                $feesTable['examFees'] = $fees->getExamFeesTle();

            }
        }

        $feesTable['medicalBookletFees'] = $fees->getMedicalBookletFees();
        $feesTable['cleanSchoolFees'] = $fees->getCleanSchoolFees();
        $feesTable['photoFees'] = $fees->getPhotoFees();

        
        return $feesTable;
    }


}