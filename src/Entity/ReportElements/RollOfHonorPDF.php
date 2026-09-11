<?php

namespace App\Entity\ReportElements;

/**
 * PDF dédié au tableau d'honneur : pas de pied de page générique et quelques
 * primitives graphiques supplémentaires pour le badge premium.
 */
class RollOfHonorPDF extends PDF
{
    public function Footer(): void
    {
        // Le tableau d'honneur occupe toute la page.
    }

    public function Circle(float $x, float $y, float $radius, string $style = 'D'): void
    {
        $this->Ellipse($x - $radius, $y - $radius, 2 * $radius, 2 * $radius, $style);
    }

    public function Ellipse(float $x, float $y, float $width, float $height, string $style = 'D'): void
    {
        $op = match ($style) {
            'F' => 'f',
            'FD', 'DF' => 'B',
            default => 'S',
        };

        $k = $this->k;
        $pageHeight = $this->h;
        $rx = $width / 2;
        $ry = $height / 2;
        $cx = $x + $rx;
        $cy = $y + $ry;
        $lx = 4 / 3 * (sqrt(2) - 1) * $rx;
        $ly = 4 / 3 * (sqrt(2) - 1) * $ry;

        $this->_out(sprintf('%.2F %.2F m', ($cx + $rx) * $k, ($pageHeight - $cy) * $k));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx + $rx) * $k,
            ($pageHeight - ($cy - $ly)) * $k,
            ($cx + $lx) * $k,
            ($pageHeight - ($cy - $ry)) * $k,
            $cx * $k,
            ($pageHeight - ($cy - $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx - $lx) * $k,
            ($pageHeight - ($cy - $ry)) * $k,
            ($cx - $rx) * $k,
            ($pageHeight - ($cy - $ly)) * $k,
            ($cx - $rx) * $k,
            ($pageHeight - $cy) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($cx - $rx) * $k,
            ($pageHeight - ($cy + $ly)) * $k,
            ($cx - $lx) * $k,
            ($pageHeight - ($cy + $ry)) * $k,
            $cx * $k,
            ($pageHeight - ($cy + $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($cx + $lx) * $k,
            ($pageHeight - ($cy + $ry)) * $k,
            ($cx + $rx) * $k,
            ($pageHeight - ($cy + $ly)) * $k,
            ($cx + $rx) * $k,
            ($pageHeight - $cy) * $k,
            $op
        ));
    }

    /** @param array<int, float|int> $points */
    public function Polygon(array $points, string $style = 'D'): void
    {
        if (count($points) < 6 || count($points) % 2 !== 0) {
            return;
        }

        $op = match ($style) {
            'F' => 'f',
            'FD', 'DF' => 'B',
            default => 's',
        };
        $k = $this->k;
        $pageHeight = $this->h;

        $path = sprintf('%.2F %.2F m', $points[0] * $k, ($pageHeight - $points[1]) * $k);

        for ($i = 2, $length = count($points); $i < $length; $i += 2) {
            $path .= sprintf(' %.2F %.2F l', $points[$i] * $k, ($pageHeight - $points[$i + 1]) * $k);
        }

        $this->_out($path . ' h ' . $op);
    }
}
