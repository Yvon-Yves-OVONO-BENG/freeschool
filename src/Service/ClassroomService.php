<?php

namespace App\Service;

class ClassroomService 
{
    public function splitClassrooms(array $classrooms): array
    {
        $splitClassrooms = [
            'level1' => [],
            'level2' => [],
            'level3' => [],
            'level4' => [],
            'level5' => [],
            'level6' => [],
            'level7' => [],
        ];
        
        foreach ($classrooms as $classroom) 
        {
            switch ($classroom->getLevel()->getLevel()) 
            {
                case 1:
                    $splitClassrooms['level1'][] = $classroom;
                    break;
                
                case 2:
                    $splitClassrooms['level2'][] = $classroom;
                    break;
            
                case 3:
                    $splitClassrooms['level3'][] = $classroom;
                    break;
        
                case 4:
                    $splitClassrooms['level4'][] = $classroom;
                    break;
    
                case 5:
                    $splitClassrooms['level5'][] = $classroom;
                    break;

                case 6:
                    $splitClassrooms['level6'][] = $classroom;
                    break;

                case 7:
                    $splitClassrooms['level7'][] = $classroom;
                    break;
            }
        }
        return $splitClassrooms;
    }
}