<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\UserLog;
use App\Service\GeoLocationService;
use Jenssegers\Agent\Agent;
use App\Repository\DeviceTypeRepository;
use App\Repository\OperatingSystemRepository;
use App\Repository\BrowserRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Component\HttpFoundation\Request;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private GeoLocationService $geoService,
        private DeviceTypeRepository $deviceRepo,
        private OperatingSystemRepository $osRepo,
        private BrowserRepository $browserRepo,
        private SchoolYearRepository $schoolYearRepository
    ) {}

    public function onLoginSuccess(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $mySession = $request->getSession();

        $schoolYear = $mySession->get('schoolYear');

        $schoolYear = $this->schoolYearRepository->find($schoolYear->getId());

        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        // 📍 Géolocalisation
        $geo = $this->geoService->getCityAndCountry($ip);
        $ville = $geo['city'];
        $pays = $geo['country'];

        $mySession->set('ville', $ville);
        $mySession->set('pays', $pays);

        // 🔍 Récupération Device, OS, Browser
        $deviceName = $agent->deviceType(); // ex : mobile, desktop
        $osName = $agent->platform();       // ex : Android, Windows
        $browserName = $agent->browser();   // ex : Chrome, Safari
        
        $device = $this->deviceRepo->findOneBy(['deviceType' => $deviceName]);
        $os = $this->osRepo->findOneBy(['operatingSystem' => $osName]);
        $browser = $this->browserRepo->findOneBy(['browser' => $browserName]);

        // 💾 Création du log
        $log = new UserLog();
        $log->setUser($user)
        ->setIp($ip)
        ->setUserAgent($userAgent)
        ->setDeviceType($device)
        ->setOperatingSystem($os)
        ->setBrowser($browser)
        ->setVille($ville ? $ville : "unknow")
        ->setCountry($pays)
        ->setLogedAt(new \DateTime())
        ->setSchoolYear($schoolYear);

        $this->em->persist($log);
        $this->em->flush();
    }

     public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLoginSuccess',
        ];
    }
}