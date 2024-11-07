<?php 

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizerService
{
    private const MAX_WIDTH = 150;
    private const MAX_HEIGHT = 200;

    private $imagine; 

    public function __construct()
    {
        $this->imagine = new Imagine;
    }

    public function resize(?string $filename): void
    {
        if($filename)
        {
            $photo = $this->imagine->open($filename);
            $photo->resize(new Box(self::MAX_WIDTH, self::MAX_HEIGHT))->save($filename);
        }
    }
}