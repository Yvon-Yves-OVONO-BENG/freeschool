<?php

namespace App\Service;

use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;

class SequenceService
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected SequenceRepository $sequenceRepository)
    {}


   /**
     * Remove Sequence 6 from the sequences list
     * 
     * @param array $sequences
     * @return array
     */
    public function removeSequence6(array $sequences, SchoolYear $schoolYear): array
    {
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);
        $education = $school->getEducation();

        if($education->getEducation() == ConstantsClass::TECHNICAL_EDUCATION)
        {
            $newSequences = [];
    
            foreach ($sequences as $sequence) 
            {
                if($sequence->getSequence() < 6)
                {
                    $newSequences[] = $sequence;
                }
            }

            return $newSequences;
            
        }

        return $sequences;
    }

}