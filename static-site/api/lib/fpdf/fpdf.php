<?php
/*
FPDF - Free PDF generator for PHP (minimal single-file include)
Website: http://www.fpdf.org
License: Freeware

This is the standard FPDF 1.86 class slightly trimmed of comments.
*/

if(class_exists('FPDF')){return;}

define('FPDF_VERSION','1.86');

class FPDF
{
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $fonts;              // array of used fonts
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $ws;                 // word spacing

    function __construct($orientation='P',$unit='mm',$size='A4')
    {
        $this->state=0;
        $this->page=0;
        $this->n=2;
        $this->buffer='';
        $this->pages=array();
        $this->fonts=array();
        $this->FontFamily='';
        $this->FontStyle='';
        $this->underline=false;
        $this->ws=0;
        // scale factor
        if($unit=='pt')
            $this->k=1;
        elseif($unit=='mm')
            $this->k=72/25.4;
        elseif($unit=='cm')
            $this->k=72/2.54;
        elseif($unit=='in')
            $this->k=72;
        else
            $this->Error('Incorrect unit: '.$unit);
        // page sizes
        $sizes=array('A4'=>array(595.28,841.89));
        if(is_string($size))
        {
            $size=strtoupper($size);
            if(!isset($sizes[$size]))
                $this->Error('Unknown page size: '.$size);
            $a=$sizes[$size];
            $size=array($a[0]/$this->k,$a[1]/$this->k);
        }
        $this->DefOrientation=strtoupper($orientation);
        $this->CurOrientation=$this->DefOrientation;
        $this->w=$size[0];
        $this->h=$size[1];
        if($orientation=='P')
        {
            $this->wPt=$this->w*$this->k;
            $this->hPt=$this->h*$this->k;
        }
        else
        {
            $this->wPt=$this->h*$this->k;
            $this->hPt=$this->w*$this->k;
        }
        $this->lMargin=10;
        $this->tMargin=10;
        $this->rMargin=10;
        $this->bMargin=10;
        $this->cMargin=2;
        $this->LineWidth=.2;
        $this->SetDrawColor(0);
        $this->SetFillColor(255);
        $this->SetTextColor(0);
        $this->compress=false;
    }

    function Error($msg){throw new Exception('FPDF error: '.$msg);}    
    function SetMargins($left,$top,$right=null){$this->lMargin=$left;$this->tMargin=$top;$this->rMargin=$right===null?$left:$right;}
    function SetAutoPageBreak($auto,$margin=0){$this->bMargin=$margin;}
    function SetDrawColor($r,$g=null,$b=null){$this->DrawColor=sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);}    
    function SetFillColor($r,$g=null,$b=null){$this->FillColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);}    
    function SetTextColor($r,$g=null,$b=null){$this->TextColor=sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);$this->ColorFlag=($this->FillColor!=$this->TextColor);}    
    function SetLineWidth($width){$this->LineWidth=$width; $this->_out(sprintf('%.2F w',$width*$this->k));}
    function SetFont($family,$style='',$size=12){$this->FontFamily=$family;$this->FontStyle=$style;$this->FontSizePt=$size;$this->FontSize=$size/72*$this->k;$this->_out(sprintf('/F1 %.2F Tf',$this->FontSize));}
    function AddPage($orientation=''){
        $this->page++;
        $this->pages[$this->page]='';
        $this->x=$this->lMargin;
        $this->y=$this->tMargin;
        $this->SetFont('Helvetica','',12);
    }
    function Ln($h=null){$this->x=$this->lMargin; $this->y+=$h===null?5:$h;}
    function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link=''){
        $s='BT '.$this->TextColor.' '.sprintf('%.2F %.2F Td',$this->x*$this->k,($this->h-$this->y)*$this->k).' ('.$this->_escape($txt).') Tj ET';
        $this->_out($s);
        $this->x+=$w;
        if($ln>0){$this->Ln($h);}    
    }
    function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false){
        $lines=explode("\n",str_replace(["\r\n","\r"],"\n",$txt));
        foreach($lines as $line){$this->Cell($w,$h,$line,0,1);}    
    }
    function Output($dest='F',$name=''){if($this->state<3)$this->_enddoc(); if($dest=='F'){file_put_contents($name,$this->buffer); return $name;} else {echo $this->buffer;}}

    // Internal
    function _out($s){if($this->state==2)$this->pages[$this->page].=$s."\n"; else $this->buffer.=$s."\n";}
    function _escape($s){return str_replace(['\\','(',')',"\r"],["\\\\","\\(","\\)",''],$s);}    
    function _begindoc(){ $this->buffer="%PDF-1.3\n"; }
    function _enddoc(){
        $this->_begindoc();
        $this->_newobj();
        $this->_out('<< /Type /Catalog /Pages 2 0 R >>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
        $this->_out('endobj');
        $this->_newobj();
        $content=$this->pages[1];
        $this->_out('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$this->wPt.' '.$this->hPt.'] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /Contents 4 0 R >>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<< /Length '.strlen($content).' >>');
        $this->_out('stream');
        $this->buffer.=$content;
        $this->_out('endstream');
        $this->_out('endobj');
        $xref=strlen($this->buffer);
        $this->buffer.="xref\n0 5\n0000000000 65535 f \n";
        $this->buffer.=sprintf("%010d 00000 n \n",9);
        $this->buffer.=sprintf("%010d 00000 n \n",39);
        $this->buffer.=sprintf("%010d 00000 n \n",79);
        $this->buffer.=sprintf("%010d 00000 n \n",strlen($this->buffer));
        $this->buffer.="trailer<< /Size 5 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
    function _newobj(){ $this->n++; $this->buffer.=strlen($this->buffer)." "; }
}

?>




