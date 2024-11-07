<?php

namespace App\Controller;

use App\Service\SmsService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SmsController extends AbstractController
{
    #[Route("/envoie-sms", name:"envoie_sms")]
    public function sendSms(SmsService $smsService)
    {
        $smsService->sendSms('+237697993386', 'Bonjour, voici un message test !');
        
        // Faites quelque chose avec la réponse du service si nécessaire
    }
}
