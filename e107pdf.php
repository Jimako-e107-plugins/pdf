<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Plugin - PDF generator
 *
 * $URL$
 * $Id$
 */


/**
 *	e107 pdf generation plugin
 *
 *	@package	e107_plugins
 *	@subpackage	pdf
 *	@version 	$Id$;
 */

if (!defined('e107_INIT')) { exit; }

ob_start(); // DO NOT REMOVE, USED TO PREVENT TCPDF SENTE HEADERS ERROR!!!!!!!
/*
TODO:
1. Look at using disc cache
2. More detailed check on image paths
*/

// Debug option - adds entries to rolling log (only works in 0.8)
//define ('PDF_DEBUG', TRUE);
define ('PDF_DEBUG', FALSE);

define('K_PATH_MAIN', '');
define('K_PATH_URL', SITEURL);			// Used with forms (TODO: check validity)
define('K_CELL_HEIGHT_RATIO', 1.25);

// Following may be used (among others)
	define ('K_PATH_FONTS', K_PATH_MAIN.'tcpdf/fonts/');
	define ('K_PATH_CACHE', e_CACHE_CONTENT);
	define ('K_PATH_URL_CACHE', K_PATH_URL.e_CACHE_CONTENT);
	define ('K_PATH_IMAGES', K_PATH_MAIN.'images/');
	define ('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');

/*
The full tcpdf distribution includes a utility to generate new fonts, as well as lots of other fonts.
It can be downloaded from: http://sourceforge.net/projects/tcpdf/
*/


require_once(e_PLUGIN.'pdf/tcpdf/tcpdf.php');		//require the ufpdf class
include_lan(e_PLUGIN.'pdf/languages/'.e_LANGUAGE.'_admin_pdf.php');


//extend tcpdf class from package with custom functions
class e107PDF extends TCPDF
{
	protected	$pdfPref = array();			// Prefs - loaded before creating a pdf

	/**
	 *	Constructor
	 * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li></ul>
	 * @param string $unit User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul><br />A point equals 1/72 of inch, that is to say about 0.35 mm (an inch being 2.54 cm). This is a very common unit in typography; font sizes are expressed in that unit.
	 * @param mixed $format The format used for pages. It can be either one of the following values (case insensitive) or a custom format in the form of a two-element array containing the width and the height (expressed in the unit given by unit).<ul><li>4A0</li><li>2A0</li><li>A0</li><li>A1</li><li>A2</li><li>A3</li><li>A4 (default)</li><li>A5</li><li>A6</li><li>A7</li><li>A8</li><li>A9</li><li>A10</li><li>B0</li><li>B1</li><li>B2</li><li>B3</li><li>B4</li><li>B5</li><li>B6</li><li>B7</li><li>B8</li><li>B9</li><li>B10</li><li>C0</li><li>C1</li><li>C2</li><li>C3</li><li>C4</li><li>C5</li><li>C6</li><li>C7</li><li>C8</li><li>C9</li><li>C10</li><li>RA0</li><li>RA1</li><li>RA2</li><li>RA3</li><li>RA4</li><li>SRA0</li><li>SRA1</li><li>SRA2</li><li>SRA3</li><li>SRA4</li><li>LETTER</li><li>LEGAL</li><li>EXECUTIVE</li><li>FOLIO</li></ul>
	 * @param boolean $unicode TRUE means that the input text is unicode (default = true)
	 * @param boolean $diskcache if TRUE reduce the RAM memory usage by caching temporary data on filesystem (slower).
	 * @param String $encoding charset encoding; default is UTF-8
	 */
	public function __construct($orientation='P',$unit='mm',$format='A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
	{
		$this->getPDFPrefs();

		//Call parent constructor
		parent::__construct($orientation,$unit,$format, $unicode, $encoding, $diskcache);
		//Initialization
		$this->setCellHeightRatio(1.25);			// Should already be the default
	}



	//default preferences if none present
	function getDefaultPDFPrefs()
	{
		$pdfpref['pdf_margin_left']				= '25';
		$pdfpref['pdf_margin_right']			= '15';
		$pdfpref['pdf_margin_top']				= '15';
		$pdfpref['pdf_font_family']				= 'helvetica';
		$pdfpref['pdf_font_size']				= '8';
		$pdfpref['pdf_font_size_sitename']		= '14';
		$pdfpref['pdf_font_size_page_url']		= '8';
		$pdfpref['pdf_font_size_page_number']	= '8';
		$pdfpref['pdf_show_logo']				= true;
		$pdfpref['pdf_show_sitename']			= false;
		$pdfpref['pdf_show_page_url']			= true;
		$pdfpref['pdf_show_page_number']		= true;
		$pdfpref['pdf_error_reporting']			= true;
		return $pdfpref;
	}


	/**
	 *	Set up the e107 PDF prefs - if can't be loaded from the DB, force some sensible defaults
	 */
	//get preferences from db
	function getPDFPrefs()
	{
		$this->pdfPref = e107::pref('pdf');         // retrieve pref array.
		if (count($this->pdfPref) == 0)
		{
			$this->pdfPref = $this->getDefaultPDFPrefs();
		}

		return $this->pdfPref;
	}


	/**
	 *	Convert e107-encoded text to body text
	 *	@param string $text
	 *	@return string with various entities replaced with their equivalent characters
	 */
	function toPDF($text)
	{
		$search = array('&#39;', '&#039;', '&#036;', '&quot;');
		$replace = array("'", "'", '$', '"');
		$text = str_replace($search, $replace, $text);
		return $text;
	}



	/**
	 *	Convert e107-encoded text to title text
	 *	@param string $text
	 *	@return string with various characters replaced with '-'		TODO: Why?
	 */
	function toPDFTitle($text)
	{
		$search = array(":", "*", "?", '"', '<', '>', '|');
		$replace = array('-', '-', '-', '-', '-', '-', '-');
		$text = str_replace($search, $replace, $text);
		return $text;
	}



	/**
	 *	The makePDF function does all the real parsing and composing
	 *	@param array $text needs to be an array containing the following:
	 *	$text = array($text, $creator, $author, $title, $subject, $keywords, $url[, $orientation]);
	 *	@return - none (the PDF file is output, all being well)
	*/
	function makePDF($text)
	{
		$tp = e107::getParser();

		// Make sure prefs up to date
		$this->getPDFPrefs();

		//define logo and source pageurl (before the parser!)
		if(is_readable(THEME.'images/logopdf.png'))
		{
			$logo = THEME.'images/logopdf.png';
		}
		else
		{
			$logo = e_IMAGE.'logo.png';
		}
		define('PDFLOGO', $logo);					//define logo to add in header
		if (substr($text[6], -1) == '?')
		{
			$text[6] = substr($text[6], 0, -1);
		}
		define('PDFPAGEURL', $text[6]);				//define page url to add in header

		//parse the data
		$text[3] = $this->toPDF($text[3]);					//replace some in the title
		$text[3] = $this->toPDFTitle($text[3]);			//replace some in the title
		foreach($text as $k=>$v)
		{
			$text[$k] = $tp->toHTML($v, TRUE, 'BODY');
		}




// In the code that follows, the commented out lines beginning $pdf-> are things which could potentially be set.

//set image scale factor
//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
//$pdf->setLanguageArray($l);



		//set some variables
		$this->SetMargins($this->pdfPref['pdf_margin_left'],$this->pdfPref['pdf_margin_top'],$this->pdfPref['pdf_margin_right']);
//$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		$this->SetAutoPageBreak(true,25);			// Force new page break at 25mm from bottom
		$this->SetPrintHeader(TRUE);

// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
//$pdf->setFooterData($tc=array(0,64,0), $lc=array(0,64,128));

// set default monospaced font
//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//start creating the pdf and adding the data
		$this->DefOrientation=(varset($text[7], 'P') == 'L' ? 'L' : 'P'); 	// Page orientation - P=portrait, L=landscape
//___ Deprecated		$this->AliasNbPages();						//calculate current page + number of pages
		$this->getAliasNbPages();						//calculate current page + number of pages
		$this->AddPage();							//start page

//$pdf->setFontSubsetting(true);

		$this->SetFont($this->pdfPref['pdf_font_family'],'',$this->pdfPref['pdf_font_size']);				//set font
		$this->SetHeaderFont(array($this->pdfPref['pdf_font_family'],'',$this->pdfPref['pdf_font_size']));
		$this->SetFooterFont(array($this->pdfPref['pdf_font_family'],'',$this->pdfPref['pdf_font_size']));

		$this->WriteHTML($text[0], true);			//write text		TODO: possibly other parameters
		$this->SetCreator($text[1]);				//name of creator
		$this->SetAuthor($text[2]);					//name of author
		$this->SetTitle($text[3]);					//title
		$this->SetSubject($text[4]);				//subject
		$this->SetKeywords($text[5]);				//space/comma separated
		$file = $text[3].'.pdf';					//name of the file
		$this->Output($file, $this->pdfPref['pdf_output']);					//Save PDF to file (D = output to download window)
		return;
	}



	/**
	 *	Add e107-specific header to each page.
	 *	Uses various prefs set in the admin page.
	 *	Overrides the tcpdf default header function
	 */
	function Header()
	{
		$pageWidth = $this->getPageWidth();		// Will be 210 for A4 portrait
		$ormargins = $this->getOriginalMargins();
		$headerfont = $this->getHeaderFont();
		$headerdata = $this->getHeaderData();

		$topMargin = $this->pdfPref['pdf_margin_top'];
		if($this->pdfPref['pdf_show_logo'])
		{
			$this->SetFont($this->pdfPref['pdf_font_family'],'',$this->pdfPref['pdf_font_size']);
			$this->Image(PDFLOGO, $this->GetX(), $topMargin);
			$imgx = $this->getImageRBX();
			$imgy = $this->getImageRBY();			// Coordinates of bottom right of logo

			$a=$this->GetStringWidth(SITENAME);
			$b=$this->GetStringWidth(PDFPAGEURL);
			$c = max($a, $b) + $this->rMargin;
			if(($imgx + $c) > $pageWidth)			// See if room for other text to right of logo
			{	// No room - move to underneath
				$this->SetX($this->lMargin);
				$this->SetY($imgy+2);
			}
			else
			{
				$m = 0;
				if($this->pdfPref['pdf_show_sitename'])
				{
					$m = 5;
				}
				if($this->pdfPref['pdf_show_page_url'])
				{
					$m += 5;
				}
				if($this->pdfPref['pdf_show_page_number'])
				{
					$m += 5;
				}
				$this->SetX($imgx);						// May not be needed
				$newY = max($topMargin, $imgy - $m);
				$this->SetY($newY);						//Room to right of logo - calculate space to line up bottom of text with bottom of logo
			}
		}
		else
		{
			$this->SetY($topMargin);
		}
		
		// Now print text - 'cursor' positioned in correct start position
		$cellwidth	= $pageWidth - $this->GetX()-$this->rMargin;
		$align		= 'R';
//		echo "imgx: {$imgx}   imgy: {$imgy}   cellwidth: {$cellwidth}   m: {$m} <br />";
		if($this->pdfPref['pdf_show_sitename'])
		{
			$this->SetFont($this->pdfPref['pdf_font_family'],'B',$this->pdfPref['pdf_font_size_sitename']);
			$this->Cell($cellwidth,5,SITENAME,0,1,$align);
		}
		if($this->pdfPref['pdf_show_page_url'])
		{
			$this->SetFont($this->pdfPref['pdf_font_family'],'I',$this->pdfPref['pdf_font_size_page_url']);
			$this->Cell($cellwidth,5,PDFPAGEURL,0,1,$align,'',PDFPAGEURL);
		}
		if($this->pdfPref['pdf_show_page_number'])
		{
			$this->SetFont($this->pdfPref['pdf_font_family'],'I',$this->pdfPref['pdf_font_size_page_number']);
			//$this->Cell($cellwidth,5,PDF_LAN_19.' '.$this->PageNo().'/{nb}',0,1,$align);		// {nb} is an alias for the total number of pages
			$this->Cell($cellwidth,5,PDF_LAN_19.' '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),0,1,$align);
		}

		$this->SetFont($this->pdfPref['pdf_font_family'],'',$this->pdfPref['pdf_font_size']);

		// Following cloned from tcpdf header function
		$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));		// 2.835 is number of pixels per mm
		if ($this->getRTL()) 
		{
			$this->SetX($ormargins['right']);
		} 
		else 
		{
			$this->SetX($ormargins['left']);
		}
		$this->Cell(0, 0, '', 'T', 0, 'C');			// This puts a line between header and text

		$this->SetTopMargin($this->GetY()+2);		// FIXME: Bodge to force main body text to start below header
	}



	/**
	 *	Override standard function to use our own font directory
	 */
	protected function _getfontpath() 
	{
		//return str_replace(e_HTTP, e_ROOT, e_PLUGIN_ABS.'pdf/fonts/');
		return e_PLUGIN.'pdf/tcpdf/fonts/';
	}



	/**
	 *	Called by tcpdf when it encounters a file reference - e.g. source file name in 'img' tag - so we can tweak the source path
	 *	(tcpdf modified to check for a class extension which adds this method)
	 *	@param string $fileName - name of file as it appears in the 'src' parameter
	 *	@param string $source - name of tag which provoked call (might be useful to affect encoding)
	 *	@return string - file name adjusted to suit tcpdf
	 */
	function filePathModify($fileName, $source = '')
	{
		// This handles the fact that local images use absolute links in web pages
		if (strpos($fileName, SITEURL) === 0)
		{
			$fileName = e_BASE.str_replace(SITEURL, '', $fileName);
			return $fileName;
		}

		// Leave off-site links unchanged
		if (strpos($fileName, 'http://') === 0) return $fileName;
		if (strpos($fileName, 'https://') === 0) return $fileName;
		if (strpos($fileName, 'ftp://') === 0) return $fileName;
		
		// Assumed to be a 'local' link here
		if (substr($fileName,0,1) == '/')
		{	// Its an absolute file reference
			return str_replace(e_HTTP,e_ROOT,$fileName);
		}
		else
		{	// Its a relative link
			return realpath($fileName);
		}
	}

}

?>
