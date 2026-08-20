<?php 
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$fn = Zend_Registry::get('fn');
		$cpCfg = Zend_Registry::get('cpCfg');
		$clinicname = '';
		$drspecialist = '';
		$phone = '';

		$header='
		<table border="0" width="100%" style="border-bottom:2px solid #000000;">
            <tr>
                <td width="100%" align="center"><br/>
                    <span style="font-size:18px;">'.$cpCfg['cp.clinicNamePDF'] .'</span><br/>
                    <span style="font-size:16px;">'.$cpCfg['cp.prescriptionRegisterPDF'] .'<br/></span>
                </td>
            </tr>
        </table>
		';

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(25);

	}

	public function Footer() {
		$fn = Zend_Registry::get('fn');
		$cpCfg = Zend_Registry::get('cpCfg');
		$clinicname = '';
		$address = '';
		$phone = '';

        $siteRec = $fn->getRecordRowByID('site', 'site_id', $fn->getSessionParam('cp_site_id'));
        if($siteRec['site_id'] == 2){
	        $clinicname = $cpCfg['cp.clinicName'] ;
	        $phone  =   $cpCfg['cp.phonePDF'] . $cpCfg['cp.footerTimingPdf2'];
        }
        else if($siteRec['site_id'] == 1){
	        $clinicname = $cpCfg['cp.clinicName2'] ;
	        $phone  =   $cpCfg['cp.addressPdf1'] .' ' .$cpCfg['cp.footerTimingPdf'];
         }
         else{
	        $clinicname = $cpCfg['cp.clinicName'] ;
	        $phone  =   $cpCfg['cp.phonePDF'] . $cpCfg['cp.footerTimingPdf2'];
	    }

      	// Page number
      	//$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'R');

      	$footer='
      	<table border="0" width="100%" style="border-top:1px solid #000000;">
	        <tr>
	            <td align="center"><b>' . $clinicname .'</b>
	            '.
	            $phone 
	            .'</td>
	        </tr>
			<!--<tr>
				<td width="78%">(This is computer generated document, and does not require a signature)</td>
				<td width="22%" align="right">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</td>
			</tr>-->
		</table>';
		$this->writeHTML($footer, true, false, false, false, '');
		//$this->SetFooterMargin(10);
    }
}
?>