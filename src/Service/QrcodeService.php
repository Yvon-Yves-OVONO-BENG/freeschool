<?php

namespace App\Service;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

class QrcodeService
{
    /**
     * @var BuilderInterface
     */
    protected $bulder;
    public function __construct(BuilderInterface $bulder)
    {
        $this->bulder = $bulder;
    }

    public function qrcode($query)
    {
        $url = 'https://www.google.com/search?q=';
        $result = $this->bulder
            ->data($query)
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
