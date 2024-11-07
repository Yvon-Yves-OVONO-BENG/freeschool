<?php

namespace App\Entity\ReportElements;

use Fpdf\Fpdf;

class NoFooter extends Fpdf
{
    function Footer()
    {
        //Poitionnement à 1 cm du bas
        $this->setY(-8);

		$this->AliasNbPages('{totalPages}');
		$this->SetFont('Arial','BI',5);
		// Page number
		$this->Cell(0, 5, utf8_decode(""), 0, 0, 'C');
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
}