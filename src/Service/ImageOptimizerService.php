<?php 

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizerService
{
    private const MAX_WIDTH = 600;
    private const MAX_HEIGHT = 800;

    private $imagine; 

    public function __construct()
    {
        $this->imagine = new Imagine;
    }

    public function resize(?string $filename): void
    {
        if (!$filename || !is_file($filename)) {
            return;
        }

        try {
            $photo = $this->imagine->open($filename);
            $size = $photo->getSize();

            // Ne jamais agrandir une petite photo. Les grandes images gardent
            // leurs proportions et sont ramenées au maximum à 600 x 800 px.
            if ($size->getWidth() > self::MAX_WIDTH || $size->getHeight() > self::MAX_HEIGHT) {
                $ratio = min(
                    self::MAX_WIDTH / $size->getWidth(),
                    self::MAX_HEIGHT / $size->getHeight()
                );
                $photo->resize(new Box(
                    max(1, (int) round($size->getWidth() * $ratio)),
                    max(1, (int) round($size->getHeight() * $ratio))
                ));
            }

            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
            $options = match ($extension) {
                'jpg', 'jpeg' => ['jpeg_quality' => 78, 'strip' => true],
                'png' => ['png_compression_level' => 9],
                'webp' => ['webp_quality' => 78],
                default => [],
            };

            $photo->save($filename, $options);
        } catch (\Throwable $exception) {
            // L'inscription de l'élève ne doit jamais être perdue si le pilote
            // d'image du serveur ne reconnaît pas un format particulier.
        }
    }
}
