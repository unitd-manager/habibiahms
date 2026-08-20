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

		//$images = '<img src="images/HMS Logo.png" width="62px" height="60px"/>';
		$images = '<img src="images/HMS Logo.png"  width="60px" height="55px"/>';

		/*
		$header='
		<table border="0" width="100%" style="border-bottom:2px solid #000000;">
            <tr>
                <td width="10%">'.$images.'</td>
                <td width="90%" align="center"><br/>
                    <span style="font-size:22px;"><b>RICH MAPS SDN BHD</b></span><br/>
                    <span>NO. 20, 1ST FLOOR, JALAN 34/154, TAMAN BUKIT ANGGERIK,
                      56000 CHERAS, KUALA LUMPUR, 
                    TEL: 03-9101 1153 FAX: 03-9102 6616</span>
                </td>
            </tr>
        </table>
		';
		*/
        $siteRec = $fn->getRecordRowByID('site', 'site_id', $fn->getSessionParam('cp_site_id'));
        if($siteRec['site_id'] == 1){
	        $clinicname = $cpCfg['cp.clinicName'] ;
	        $drspecialist = $cpCfg['cp.doctorSpecialistPDF'];
	        $phone  =  $cpCfg['cp.phonePDF'];
        }
        else if($siteRec['site_id'] == 2){
	        $clinicname = $cpCfg['cp.clinicName2'] ;
	        $drspecialist = $cpCfg['cp.doctorSpecialistPDF2'] ; 
	        $phone  =   $cpCfg['cp.addressPdf1'] . ' |' .$cpCfg['cp.footerCellPdf'];
         }
         else{
	        $clinicname = 'HABIBIA CLINIC' ;
	        $drspecialist = 'Child Specialist' . ' (Morning 8:00 AM to 9:00 AM | 12:00 PM to 1:00 PM)' ;  
	        $phone  =  $phone  =   'Eppodum Vendran / Mobile : 9655897448 / 6385859549';
	    }

		$header='
		<table border="0" width="100%" style="border-bottom:2px solid #000000;">
            <tr>
                <td width="100%" align="center"><br/>
                    <span style="font-size:18px;"><b>PURPLE DIAGNOSTICS</b></span><br/>
                    <span style="font-size:10px;">'.$phone .'<br/></span>
                </td>
                <!--<td width="50%" align="center"><br/>
                    <span>'.$siteRec['address1']. ' Phone : 99999 99999' . $siteRec['address2'] . '<br/>' .$siteRec['address_state']. 'Email : info@test.com ' . $siteRec['address_town'].'<br/>'.
                    $siteRec['phone'].'</span>
                </td>-->
            </tr>
        </table>
		';

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin(22);

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
      	//$this->Cell(0, 10, '(This is computer generated document, and does not require a signature) Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'R');

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