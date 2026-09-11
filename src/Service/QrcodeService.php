<?php

namespace App\Service;

use App\Repository\TermRepository;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Builder\BuilderInterface;
use App\Service\InternetConnectionCheckerService;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

class QrcodeService
{
    /**
     * @var BuilderInterface
     */
    public function __construct(
        protected BuilderInterface $bulder,
        protected TermRepository $termRepository,
        protected InternetConnectionCheckerService $connectionCheckerService,)
    {}

    public function qrcode($query, $slugStudent, $school)
    {
        $slugTerm = $this->termRepository->findOneBy(['term' => 0])->getSlug();

        $url = 'http://localhost/freeschool/public/display-transcript/'.$slugStudent."/".$slugTerm;
        
        if (!$this->connectionCheckerService->isConnected())
        {
            $donnees = $query;
        }
        else 
        {
            $donnees = $url;
        }
    
        $result = $this->bulder
            ->data($donnees)
            ->logoPath(\dirname(__DIR__, 2).'/public/images/school/'.$school->getLogo())
            ->logoResizeToWidth(100)
            ->logoPunchoutBackground(true)
            ->size(400)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->margin(10)
            ->build();

        $namePng = uniqid('', '') . '.png';
        $result->saveToFile((\dirname(__DIR__, 2) . '/public/images/qrcode/' . $namePng));
        return $namePng;
    }
}
