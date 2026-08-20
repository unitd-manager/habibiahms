<?php
include_once(CP_LIBRARY_PATH.'lib_php/tcpdf-extra/headfoot.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF_Local extends MYPDF{
	//Page header
	public function Header() {
		$cpCfg = Zend_Registry::get('cpCfg');
		$fn    = Zend_Registry::get('fn');
		$db    = Zend_Registry::get('db');

		$site_id = $fn->getSessionParam('cp_site_id');

		if (count($this->pages) == 1 ) {

			$images = '<img src="images/HMS Logo.png" width="92px" height="90px"/>';
			$images = '<img src="images/logo-print.jpg" height="92px"/>';

			$sqlSite = "
	        SELECT site_id
	              ,title
	              ,address1
	              ,address2
	              ,address_state
	              ,address_town
	              ,phone
	              ,admin_email
	        FROM site
	        WHERE site_id = '{$site_id}'
	        ";
	        $resultSite = $db->sql_query($sqlSite);
	        $rowSite    = $db->sql_fetchrow($resultSite);

	        $addressLine1  = '';
	        $addressLine2  = '';
	        $addressLine3  = '';
	        $addressLine4  = '';
	        $addressLine5  = '';
	        $addressLine6  = '';
	        if($rowSite['title'] != '') {
	        	$addressLine1 = $rowSite['address1'];
	        	$addressLine2 = $rowSite['address2'];
	        	$addressLine3 = $rowSite['address_state'];
	        	$addressLine4 = $rowSite['address_town'];
	        	$addressLine5 = $rowSite['phone'];
	        	$addressLine6 = $rowSite['admin_email'];
	        }

			$header='
			<table border="0" width="100%">
				<tr>
					<td width="18%" rowspan="2" style="border-bottom:2px solid black">'.$images.'</td>
					<td width="82%" align="left"><font style="font-size:14px;font-weight:bold;">RICH MAPS SDN BHD</font>
					</td>
				</tr>
				<tr>
					<td width="82%" align="left" style="border-bottom:2px solid black"><font style="font-size:12px;">'.$addressLine1 . ' ' . $addressLine2 .'<br/>
						'.$addressLine3 . ' ' . $addressLine4 .'<br/>
						'.$addressLine5.'<br/>
						'.$addressLine6.'</font>
					</td>
				</tr>
			</table>
			';

			$this->writeHTML($header, true, false, false, false, '');
			$this->SetTopMargin(33);
		} else {
			$this->SetTopMargin(6);
		}
	}

	public function Footer() {
		$this->SetFont('Courier','',9);
		$cpCfg = Zend_Registry::get('cpCfg');

      	// Page number
      	//$this->Cell(0, 10, '(This is computer generated document, and does not require a signature) Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'R');

      	$footer='
      	<table border="0" width="100%">
	        <tr>
	            <td align="center">
	            </td>
	        </tr>
			<tr>
				<td width="78%">(This is computer generated document, and does not require a signature)</td>
				<td width="22%" align="right">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</td>
			</tr>
		</table>';
		$this->writeHTML($footer, true, false, false, false, '');
    }
}
?>