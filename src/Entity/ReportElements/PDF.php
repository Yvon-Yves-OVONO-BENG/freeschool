<?php

namespace App\Entity\ReportElements;

use Fpdf\Fpdf;

class PDF extends Fpdf
{
    function Footer()
    {
        //Poitionnement à 1 cm du bas
        $this->setY(-10);

		$this->AliasNbPages('{totalPages}');
		$this->SetFont('Arial','BI',5);
		// Page number
		// $this->Cell(0, 5, utf8_decode("L.B NGUELEMENDOUKA / G.B.H.S NGUELEMENDOUKA"), 0, 0, 'R');
		// $this->Cell(0, 5, utf8_decode("C.E.S. Ankom / G.H.S Ankom"), 0, 0, 'R');
		// $this->Cell(0, 5, utf8_decode("Lycée Bilingue d'Odza / G.B.H.S ODZA"), 0, 0, 'R');
		$this->Cell(0, 5, utf8_decode("Lycée Technique d'Ayos / G.T.H.S Ayos"), 0, 0, 'R');
		// $this->Cell(0, 5, utf8_decode("Lycée de Martap / G.H.S Martap"), 0, 0, 'R');
		// $this->Cell(0, 5, utf8_decode("Lycée Technique d'Akonolinga / G.T.H.S Akonolinga"), 0, 0, 'R');
    }

    public function RotatedText($x,$y,$txt,$angle)
    {
        //Rotation du texte autour de son origines
        $this->Rotate($angle,$x,$y);
        $this->Text($x,$y,$txt);
        $this->Rotate(0);
    }

    public function RotatedImage($file,$x,$y,$w,$h,$angle)
    {
        //Rotation de l'image autour du coin supérieur gauche
        $this->Rotate($angle,$x,$y);
        $this->Image($file,$x,$y,$w,$h);
        $this->Rotate(0);
    }

    var $angle=0;

	public function Rotate($angle,$x=-1,$y=-1)
	{
		if($x==-1)
			$x=$this->x;
		if($y==-1)
			$y=$this->y;
		if($this->angle!=0)
			$this->_out('Q');
		$this->angle=$angle;
		if($angle!=0)
		{
			$angle*=M_PI/180;
			$c=cos($angle);
			$s=sin($angle);
			$cx=$x*$this->k;
			$cy=($this->h-$y)*$this->k;
			$this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
		}
	}

	public function _endpage()
	{
		if($this->angle!=0)
		{
			$this->angle=0;
			$this->_out('Q');
		}
		parent::_endpage();
	}

	function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}