<?php


namespace models;

require_once "includes/include.php";
require_once "TCPDF/tcpdf.php";

class slipprint
{
	/**
	 * TODO: break this up - especially the output call SB
	 * Formats a PDF and writes the output to file
	 * @param array $slipIds
	 */
	public function writePDF($slipIds) {
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Faclair Excerpting System');
		$pdf->SetTitle('Faclair Slips');
// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// set auto page breaks
		$pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);
// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
// set default font subsetting mode
		$pdf->setFontSubsetting(true);
// Set fonts
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
		$pdf->AddFont('times', '', 'times.php');
		$pdf->AddFont('dejavusans', '', 'dejavusans.php');
		$pdf->SetFont('times', '', 14, '', true);
		$pdf->AddPage();
		$i= 0;
		foreach ($slipIds as $slipId) {
			$i++;
			$slipInfo = collection::getSlipInfoBySlipId($slipId)[0];
			$headword = $slipInfo["lemma"];
			$filename = $slipInfo["filename"];
			$filenameElems = explode('_', $filename);
			$textNum = $filenameElems[0];
			$checkmark = "";
			if ($slipInfo["starred"]) {
				$checkmark = $pdf->unhtmlentities("&#x2713;");
			}
			$checked = 'Ch <span style="font-family:dejavusans;">' . $checkmark . '</span>';
			$id = $slipInfo["auto_id"];
			$fileHandler = new xmlfilehandler($filename);
			$context = $fileHandler->getContext($slipInfo["id"], $slipInfo["preContextScope"], $slipInfo["postContextScope"]);
			$citation = $context["pre"]["output"]
				. ' <span style="background-color: #CCCCCC">' . $context["word"] . '</span> '
				. $context["post"]["output"];
			$translation = $slipInfo["translation"];
			$date = $slipInfo["date_of_lang"];
			$reference = $date . ' <em>' . $slipInfo["title"] . '</em> ' . $slipInfo["page"];

			$html = <<<EOD
			<table>
				<tr>
					<td>{$headword}</td>			<td style="text-align: center;">Text {$textNum}</td>				<td style="text-align: right;">{$checked}</td>
				</tr>
				<tr>
					<td></td>                 <td></td>                       <td style="text-align: right;">{$id}</td>
				</tr>
				<tr><td colspan="3"><br></td></tr>
				<tr>
					<td colspan="3">{$citation}</td>
				</tr>
				<tr>
					<td colspan="3" style="color:green;font-style:italic;">{$translation}</td>
				</tr>
				<tr><td colspan="3"><br></td></tr>
				<tr>
		                <td colspan="3" style="text-align: center;">{$reference}</td>
				</tr>
				<tr>
					<td>{$date}</td>          <td></td>                     <td></td>
				</tr>
			</table>
EOD;

			$width = $pdf->pixelsToUnits(500);  //5 inches
			$height = $pdf->pixelsToUnits(300); //3 inches

			// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
			$pdf->MultiCell($width, $height, $html, 1, 'L', 0, 1, '', '', true, 0, true, true, $height);
			if (!($i & 1) && $i != count($slipIds)) {  //2 slips per page
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
			} else {
				$pdf->Ln(16);
			}
		}
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
		$pdf->Output('slips.pdf', 'I');
	}
}