<?
ini_set('display_errors', 1);
$url = 'http://habibiahms.cubosale.in/admin/index.php?_topRm=utils&module=hms_attendance&_spAction=createAttendanceForAbsent&showHTML=0';
$post_param = array();
//require_once "/home/cmsPilot/v3.0/library/Zend/Http/Client.php";
require_once "Zend/Http/Client.php";
$client = new Zend_Http_Client();
$client->setUri($url);
$client->setParameterPost($post_param);
$response = $client->request(Zend_Http_Client::POST);
//print_r($response);
