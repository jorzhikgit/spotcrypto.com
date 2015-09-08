<?php
namespace app\extensions\action;


class Identification extends \lithium\action\Controller {

	public function set($address=null)
	{
	if ( $address == "" ){return;}
	$secret = SIICRYPTO_SECRET;
			$opts = array(
			  'http'=> array(
					'method'=> "GET",
					'user_agent'=> "MozillaXYZ/1.0"));
			$context = stream_context_create($opts);
			$json = file_get_contents('http://hitarth.org/verify/addaddress/'.$secret.'/'.$address, false, $context);
			$jdec = json_decode($json);
			return (array)$jdec;
	}


}
?>