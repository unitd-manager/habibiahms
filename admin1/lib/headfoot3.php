<?php 
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$fn    = Zend_Registry::get('fn');
		$cpCfg = Zend_Registry::get('cpCfg');

		$clinicname   = '';
		$drspecialist = '';
		$phone 		  = '';
		$address 	  = '';
        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $pvRec = $fn->getRecordRowByID('patient_visit', 'patient_visit_id', $patient_visit_id);
        $empRec = $fn->getRecordRowByID('employee', 'employee_id', $pvRec['employee_id']);

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

		$height  = 35;
        $siteRec = $fn->getRecordRowByID('site', 'site_id', $fn->getSessionParam('cp_site_id'));
        if($siteRec['site_id'] == 1){
	        $clinicname   = $cpCfg['cp.clinicName'] ;
	        $drspecialist = $cpCfg['cp.doctorSpecialistPDF'];
	        $phone  	  = $cpCfg['cp.prescriptionAddressPDF'].'<br/></span><span style="font-size:12px;">'.$cpCfg['cp.phonePDF'];
        }
        else if($siteRec['site_id'] == 2){
	        $clinicname   = $cpCfg['cp.clinicName2'];
	        $drspecialist = $cpCfg['cp.doctorSpecialistPDF2'];
	        $phone  	  = $cpCfg['cp.addressPdf1'].'<br/></span><span style="font-size:12px;">'.$cpCfg['cp.footerCellPdf'];
        }
        else if($siteRec['site_id'] == 3){
	        $clinicname   = 'HABIBIA CLINIC';
	        //$drspecialist = 'Child Specialist' . ' (Morning 7:30 AM to 9:00 AM | Evening 5:00 PM to 6:00 PM )' ;  
	        $drspecialist = $cpCfg['cp.doctorSpecialistPDF3'];
	        //$phone  =   'Eppodum Vendran / Phone : 0461 - 2373296';
	        $phone   	  = $cpCfg['cp.prescriptionAddressPDF2'].'</span><span style="font-size:12px;"> | '.$cpCfg['cp.phonepdf2'];
        	$height       = 32;
        }
        else{
	        $clinicname   = 'HABIBIA CLINIC';
	        $drspecialist = 'Child Specialist' . ' (Morning 7.00 AM to 9:00 AM)';  
	        $phone        = 'Mobile : 9655897448 / 6385859549';
	    }
    
    //$cpCfg['cp.doctorNamePDF']

        if($pvRec['employee_id'] == 85){
			$header='
			<table border="0" width="100%" style="border-bottom:2px solid #000000;">
	            <tr>
	                <td width="100%" align="center"><br/>
	                    <span style="font-size:18px;"><b>Dr. KANNAN</b></span><span style="font-size:12px;"> M.B.B.S., D.D.,</span><br/>
	                    <span style="font-size:12px;">Skin Specialist<br/></span>
	                    <span style="font-size:12px;">Cell : 96558 97448<br/></span>
	                    <span style="font-size:12px;">For Appointment Call: 0461-2373296<br/></span>
	                </td>
	            </tr>
	        </table>
			';        	
        } else {
			$header='
			<table border="0" width="100%" style="border-bottom:2px solid #000000;">
	            <tr>
	                <td width="100%" align="center"><br/>
	                    <span style="font-size:18px;"><b>'.$clinicname .'</b></span><br/>
	                    <span style="font-size:16px;">'.$empRec['first_name'] .'<br/></span>
	                    <span style="font-size:12px;">'.$drspecialist .'<br/></span>
	                    <span style="font-size:10px;">'.$phone .'</span><br/>
	                </td>
	                <!--<td width="50%" align="center"><br/>
	                    <span>'.$siteRec['address1']. ' Phone : 99999 99999' . $siteRec['address2'] . '<br/>' .$siteRec['address_state']. 'Email : info@test.com ' . $siteRec['address_town'].'<br/>'.
	                    $siteRec['phone'].'</span>
	                </td>-->
	            </tr>
	        </table>
			';
		}

		$this->writeHTML($header, true, false, false, false, '');
		$this->SetTopMargin($height);
	}

	public function Footer() {
		$fn = Zend_Registry::get('fn');
		$cpCfg = Zend_Registry::get('cpCfg');
		$clinicname = '';
		$address = '';
		$phone = '';
        $patient_visit_id = $fn->getReqParam('patient_visit_id');
        $pvRec = $fn->getRecordRowByID('patient_visit', 'patient_visit_id', $patient_visit_id);

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

        if($pvRec['employee_id'] == 85){
	      	$footer='
	      	<table border="0" width="100%" style="border-top:1px solid #000000;">
		        <tr>
		            <td align="center">Habibia Hospital, Kurukkuchalai.</td>
		        </tr>
			</table>';
        } else {
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
		}
		$this->writeHTML($footer, true, false, false, false, '');
		//$this->SetFooterMargin(10);
    }
}
?>