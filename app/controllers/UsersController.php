<?php
namespace app\controllers;

use app\extensions\action\OAuth2;
use app\models\Users;
use app\models\Details;
use app\models\Transactions;
use app\models\Parameters;
use app\models\Settings;
use app\models\File;
use lithium\data\Connections;
use app\extensions\action\Coinprism;
use app\extensions\action\Greencoin;
use app\extensions\action\Identification;
use lithium\security\Auth;
use lithium\storage\Session;
use app\extensions\action\GoogleAuthenticator;
use lithium\util\String;
use MongoID;

use \lithium\template\View;
use \Swift_MailTransport;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_Attachment;

class UsersController extends \lithium\action\Controller {

	public function index(){
	}
	public function signup() {	
	
		if($this->request->data) {	
      $Users = Users::create($this->request->data);
      $saved = $Users->save();
			if($saved==true){
			$verification = sha1($Users->_id);

//			$bitcoin = new Bitcoin('http://'.BITCOIN_WALLET_SERVER.':'.BITCOIN_WALLET_PORT,BITCOIN_WALLET_USERNAME,BITCOIN_WALLET_PASSWORD);
//	$coinprism = new Coinprism(COINPRISM_USERNAME,COINPRISM_PASSWORD);
//	$response = $coinprism->create_address('NEWUSER',"NEWUSER");
//	print_r($response);

//			$bitcoinaddress = '';//$bitcoin->getaccountaddress($this->request->data['username']);

			
//			$oauth = new OAuth2();
//			$key_secret = $oauth->request_token();
			$ga = new GoogleAuthenticator();
			
			$data = array(
				'user_id'=>(string)$Users->_id,
				'username'=>(string)$Users->username,
				'email.verify' => $verification,
				'mobile.verified' => "No",				
				'mobile.number' => "",								
				'key'=>$ga->createSecret(64),
				'secret'=>$ga->createSecret(64),
				'Friend'=>array(),
				'EmailPasswordSecurity' => true,
//				'bitcoinaddress.0'=>$bitcoinaddress,
				'balance.BTC' => (float)0,
//				'balance.LTC' => (float)0,				
				'balance.XGC' => (float)0,								
				'balance.USD' => (float)0,				
				'balance.EUR' => (float)0,
				'balance.CAD' => (float)0,				
			);
			Details::create()->save($data);

			$email = $this->request->data['email'];
			$name = $this->request->data['firstname'];
			
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('email','verification','name'),
				array(
					'controller' => 'users',
					'template'=>'confirm',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("Verification of email from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Verification email '.COMPANY_URL));
			$message->setTo($Users->email);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			
			$mailer->send($message);
			$this->redirect('Users::email');	
			
			}
		}	
		return compact('saved','Users');		
	}
	public function email(){
		$user = Session::read('member');
		$id = $user['_id'];
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$id))
		);

		if(isset($details['email']['verified'])){
			$msg = "Your email is verified.";
		}else{
			$msg = "Your email is <strong>not</strong> verified. Please check your email to verify.";
			
		}
		$title = "Email verification";
		return compact('msg','title');
	}
	
	public function confirm($email=null,$verify=null){
		if($email == "" || $verify==""){
			if($this->request->data){
				if($this->request->data['email']=="" || $this->request->data['verified']==""){
					return $this->redirect('Users::email');
				}
				$email = $this->request->data['email'];
				$verify = $this->request->data['verified'];
			}else{return $this->redirect('Users::email');}
		}
		$finduser = Users::first(array(
			'conditions'=>array(
				'email' => $email,
			)
		));

		$id = (string) $finduser['_id'];
			if($id!=null){
				$data = array('email.verified'=>'Yes');
				Details::create();
				$details = Details::find('all',array(
					'conditions'=>array('user_id'=>$id,'email.verify'=>$verify)
				))->save($data);

				if(empty($details)==1){
					return $this->redirect('Users::email');
				}else{
					return $this->redirect('ex::dashboard');
				}
			}else{return $this->redirect('Users::email');}

	}
	
	public function mobile(){
		$title = "Mobile";
	
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];
		if ($this->request->data) {
			$data = array(
				"mobile.number" => $this->request->data['mobile'],
				"mobile.verified" =>	 "No",				
			);
			$details = Details::find('all',
				array('conditions'=>array('user_id'=> (string) $id))
			)->save($data);
		}
	return $this->redirect('Users::settings');
	}
	public function okpayverify(){
		$title = "OkPay";
		$user = Session::read('default');		
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];
		if ($this->request->data) {
			$details = Details::find('first',	array(
				'conditions'=>array(
					'user_id'=> (string) $id,
					'okpay.verify'=>$this->request->data['verify'])
				));
			if(count($details)>0){
				$data = array(
					"okpay.verified" =>	 "Yes",				
				);
			$details = Details::find('first',	array(
				'conditions'=>array(
					'user_id'=> (string) $id,
					'okpay.verify'=>$this->request->data['verify'])
				))->save($data);
			}
		}
	return $this->redirect('Users::settings');		
	}
	public function okpaysave(){
		$title = "OkPay";
		$verification = sha1($this->request->data['email']);
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];
		if ($this->request->data) {
			$data = array(
				"okpay.email" => $this->request->data['email'],
				"okpay.verified" =>	 "No",				
				"okpay.verify"=>$verification,
			);
			
			
			$details = Details::find('all',
				array('conditions'=>array('user_id'=> (string) $id))
			)->save($data);

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$email = $user['email'];
			$body = $view->render(
				'template',
				compact('data'),
				array(
					'controller' => 'users',
					'template'=>'okpay',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("Verify OKPAY email ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Verify OKPAY email '.COMPANY_URL));
			$message->setTo($email);
			$message->setBody($body,'text/html');
			$mailer->send($message);

		}
	return $this->redirect('Users::settings');
	}


	public function settings($option=null){
		
		$title = "User settings";
		$ga = new GoogleAuthenticator();
		
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$uploadOk = 1;
		$qrCodeUrl = $ga->getQRCodeGoogleUrl(COMPANY_URL."-".$details['username'], $details['secret']);							
		if ($this->request->data) {
			$imageFileType = pathinfo($this->request->data['file']['name'],PATHINFO_EXTENSION);
			
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
					$msg = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
					$uploadOk = 0;
					return $this->redirect('Users::settings',compact('msg'));		
			} 
	if($uploadOk=1){
			$option = $this->request->data['option'];
				$data = array(
					$option => $this->request->data['file'],
					$option.'.verified'=>'No',
				);
				$field = 'details_'.$option.'_id';
				$remove = File::remove('all',array(
					'conditions'=>array( $field => (string) $details->_id)
				));

				$fileData = array(
						'file' => $this->request->data['file'],
						'details_'.$option.'_id' => (string) $details->_id
				);
				
				$details = Details::find('first',
					array('conditions'=>array('user_id'=> (string) $id))
				)->save($data);
				$file = File::create();
				if ($file->save($fileData)) {
						$this->redirect('ex::dashboard');
				}
		}

		$TOTP = $details['TOTP.Validate'];
		$secret = $details['secret'];


		
				
	
	}
			$details = Details::find('first',
				array('conditions'=>array('user_id'=> (string) $id))
			);		
			
	$image_address = File::find('first',array(
			'conditions'=>array('details_address_id'=>(string)$details['_id'])
		));
		
		if($image_address['filename']!=""){
				$imagename_address = $image_address['_id'].'_'.$image_address['filename'];
					$path = LITHIUM_APP_PATH . '/webroot/documents/'.$imagename_address;
				file_put_contents($path, $image_address->file->getBytes());
		}

		$image_government = File::find('first',array(
			'conditions'=>array('details_government_id'=>(string)$details['_id'])
		));
		if($image_government['filename']!=""){
				$imagename_government = $image_government['_id'].'_'.$image_government['filename'];
				$path = LITHIUM_APP_PATH . '/webroot/documents/'.$imagename_government;
				file_put_contents($path, $image_government->file->getBytes());
		}		

		$image_bank = File::find('first',array(
			'conditions'=>array('details_bank_id'=>(string)$details['_id'])
		));
		if($image_bank['filename']!=""){
				$imagename_bank = $image_bank['_id'].'_'.$image_bank['filename'];
				$path = LITHIUM_APP_PATH . '/webroot/documents/'.$imagename_bank;
				file_put_contents($path, $image_bank->file->getBytes());
		}					
				$settings = Settings::find('first');
				
		return compact('details','user','title','qrCodeUrl','secret','option','imagename_address','imagename_government','imagename_bank','settings','msg');
	}
	
	
	
	public function ga(){
		$ga = new GoogleAuthenticator();
		
		$secret = $ga->createSecret(64);
		$secret = 'X6SWH7LNHWG3MBGNTWMJ53VPQ7IYWI6YSDYRZ6XYXWYS5KWZ4JFG7J6TM2P77AKX';
		echo "Secret is: ".$secret."\n\n";
		
		$qrCodeUrl = $ga->getQRCodeGoogleUrl("ABCD", $secret);
		echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n";
		
		
		$oneCode = $ga->getCode($secret);
		echo "Checking Code '$oneCode' and Secret '$secret':\n";
		
		$checkResult = $ga->verifyCode($secret, $oneCode, 2); // 2 = 2*30sec clock tolerance
		if ($checkResult) {
			echo 'OK';
		} else {
			echo 'FAILED';
		}
	}
	
	public function SendPassword($username=""){
		$users = Users::find('first',array(
					'conditions'=>array('username'=>$username)
				));
		$id = (string)$users['_id'];
		if($id==""){
			return $this->render(array('json' => array("Password"=>"Password Not sent","TOTP"=>"No")));
		}
		
		$ga = new GoogleAuthenticator();
		$secret = $ga->createSecret(64);
		$details = Details::find('first',array(
					'conditions'=>array('username'=>$username,'user_id'=>(string)$id)
		));
		if($details['oneCodeused']=='Yes' || $details['oneCodeused']==""){
			$oneCode = $ga->getCode($secret);	
			$data = array(
				'oneCode' => $oneCode,
				'oneCodeused' => 'No'
			);
			$details = Details::find('all',array(
						'conditions'=>array('username'=>$username,'user_id'=>(string)$id)
			))->save($data);
		}
		$details = Details::find('first',array(
					'conditions'=>array('username'=>$username,'user_id'=>(string)$id)
		));
		$oneCode = $details['oneCode'];
		$totp = "No";

		if($details['TOTP.Validate']==true && $details['TOTP.Login']==true){
			$totp = "Yes";
		}
		if($details['EmailPasswordSecurity']=="true" || $details['EmailPasswordSecurity']==null){
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$email = $users['email'];
				$body = $view->render(
					'template',
					compact('users','oneCode','username'),
					array(
						'controller' => 'users',
						'template'=>'onecode',
						'type' => 'mail',
						'layout' => false
					)
				);

				$transport = Swift_MailTransport::newInstance();
				$mailer = Swift_Mailer::newInstance($transport);
		
				$message = Swift_Message::newInstance();
				$message->setSubject("Sign in password for ".COMPANY_URL);
				$message->setFrom(array(NOREPLY => 'Sign in password from '.COMPANY_URL));
				$message->setTo($email);
				$message->setBody($body,'text/html');
				$mailer->send($message);
			}
			
			return $this->render(array('json' => array("Password"=>"Password sent to email","TOTP"=>$totp,"EmailPasswordSecurity"=>$details['EmailPasswordSecurity'])));
	}
	public function SaveTOTP(){
		$user = Session::read('default');
		if ($user==""){return $this->render(array('json' => false));}
		$id = $user['_id'];
	
		$login = $this->request->query['Login'];
		$withdrawal = $this->request->query['Withdrawal'];		
		$security = $this->request->query['Security'];		
		$ScannedCode = $this->request->query['ScannedCode'];		

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$ga = new GoogleAuthenticator();
		$checkResult = $ga->verifyCode($details['secret'], $ScannedCode, 2);

		if ($checkResult==1) {
			$data = array(
				'TOTP.Validate'=>(boolean)true,
				'TOTP.Login'=> (boolean)$login,				
				'TOTP.Withdrawal'=>(boolean)$withdrawal,				
				'TOTP.Security'=>(boolean)$security,				
			);
			$details = Details::find('first',
				array('conditions'=>array('user_id'=> (string) $id))
			)->save($data);
			return $this->render(array('json' => true));
		} else {
			return $this->render(array('json' => false));
		}
//		return $this->render(array('json' => false));
	}
	public function CheckTOTP(){
		$user = Session::read('default');
		if ($user==""){return $this->render(array('json' => false));}
		$id = $user['_id'];
		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);

		$CheckCode = $this->request->query['CheckCode'];		
		$ga = new GoogleAuthenticator();
		$checkResult = $ga->verifyCode($details['secret'], $CheckCode, 2);		
		if ($checkResult) {
			$data = array(
				'TOTP.Validate'=>false,
				'TOTP.Security'=>false,				
			);
			$details = Details::find('first',
				array('conditions'=>array('user_id'=> (string) $id))
			)->save($data);
			return $this->render(array('json' => true));
		}else{
			return $this->render(array('json' => false));
		}
	}
	public function DeleteTOTP(){

		return $this->render(array('json' => ""));
	}
	public function forgotpassword(){
		if($this->request->data){
			$msg = "Password reset link sent to your email address!";
			$user = Users::find('first',array(
				'conditions' => array(
					'email' => $this->request->data['email']
				),
				'fields' => array('_id')
			));
			$email = $user['email'];
//		print_r($user['_id']);
			$details = Details::find('first', array(
				'conditions' => array(
					'user_id' => (string)$user['_id']
				),
				'fields' => array('key')
			));
//					print_r($details['key']);exit;
		$key = $details['key'];
		if($key!=""){
		$email = $this->request->data['email'];
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('email','key'),
				array(
					'controller' => 'users',
					'template'=>'forgot',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("Password reset link from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Password reset email '.COMPANY_URL));
			$message->setTo($email);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			$mailer->send($message);
			}
		}
		
		return compact('msg');
	}

	public function addbank(){
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('Users::index');}		
		$user_id = $user['_id'];
		$details = Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			));		
		$title = "Add bank";
			
		return compact('details','title');
	}
	public function addbankBuss(){
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('Users::index');}		
		$user_id = $user['_id'];
		$details = Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			));		
		$title = "Add bank";
			
		return compact('details','title');
	}
	public function changepassword($key=null){
		if($key==null){	return $this->redirect('login');}
		return compact('key');
	}
		public function password(){
		if($this->request->data){

			$details = Details::find('first', array(
				'conditions' => array(
					'key' => $this->request->data['key'],
				),
				'fields' => array('user_id')
			));
			$msg = "Password Not Changed!";
//			print_r($details['user_id']);

			if($details['user_id']!=""){
				if($this->request->data['password'] == $this->request->data['password2']){
//					print_r($this->request->data['password']);
					
					$user = Users::find('first', array(
						'conditions' => array(
							'_id' => $details['user_id'],
						)
					));
//					print_r($user['password']);
						if($user['password']!=String::hash($this->request->data['password'])){
							print_r($details['user_id']);
							
							$data = array(
							'password' => String::hash($this->request->data['password']),
							);
//							print_r($data);
							
							$user = Users::find('all', array(
								'conditions' => array(
								'_id' => $details['user_id'],
								)
							))->save($data,array('validate' => false));
					//		print_r($user);
						
							if($user){
								$msg = "Password changed!";
							}
						}else{
								$msg = "Password same as old password!";
						}
					}else{
						$msg = "New password does not match!";
					}
			}
		}
		return compact('msg');
	}
	
	public function funding($currency=null){
				$currency = strtoupper($currency);
				$title = "Funding ".$currency;

		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$secret = $details['secret'];
		$userid = $details['user_id'];
		///////////////////// Change of code required when Virtual Currency added
		switch($currency){
			case "BTC":
			$currencyName = "Bitcoin";
			$my_address = BITCOIN_ADDRESS;			
			$callback_url = 'https://'.COMPANY_URL.'/users/receipt/?userid='.$userid.'&secret='.$secret;
			$root_url = 'https://blockchain.info/api/receive';
			$parameters = 'method=create&address=' . $my_address .'&shared=false&callback='. urlencode($callback_url);
			ini_set('allow_url_fopen',1);
			$response = file_get_contents($root_url . '?' . $parameters);
			$object = json_decode($response);
			$address = $object->input_address;
			if($address==""){
				$address = (string)$details['bitcoinaddress'][0];
			}
			break;

			case "XGC":
			$currencyName = "Greencoin";			
			$greencoin = new Greencoin('http://'.GREENCOIN_WALLET_SERVER.':'.GREENCOIN_WALLET_PORT,GREENCOIN_WALLET_USERNAME,GREENCOIN_WALLET_PASSWORD);
				
			if($details[$currency.'newaddress']=="" || $details[$currency.'newaddress']=="Yes"){
				$address = $greencoin->getnewaddress("SiiCrypto-".$user['username']);
			}else{
				if($details['greencoinaddress'][0]==""){
					$address = $greencoin->getnewaddress("SiiCrypto-".$user['username']);
				}else{
					$address = $details['greencoinaddress'][0];
				}
			}
				$identify = new Identification();
				$result = $identify->set($address);
			
			$data = array(
				'greencoinaddress.0' => $address,
				$currency.'newaddress'=>'No'
			);
			Details::find('all',array(
				'conditions'=>array('username'=>$user['username'])
			))->save($data);
			break;

		}
		// End of /////////////////// Change of code required when Virtual Currency added		


		$paytxfee = Parameters::find('first');
		$txfee = $paytxfee['paytxfee'];
		$transactions = Transactions::find('first',array(
				'conditions'=>array(
				'username'=>$user['username'],
				'Added'=>false,
				'Currency'=>strtoupper($currency),
				'Paid'=>'No'
				)
		));
			return compact('details','address','txfee','title','transactions','user','currency','currencyName')	;
	}
	public function funding_fiat($currency=null){
				$title = "Funding Fiat";

		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$transactions = Transactions::find('all',array(
				'conditions'=>array(
				'username'=>$user['username'],
				'Added'=>false,
				'Approved'=>'No'
				)
		));
		$settings = Settings::find('first');		
			return compact('details','title','transactions','user','settings','currency')	;
	}
	public function funding_ltc(){
				$title = "Funding LTC";

		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];
		
		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);

		
		$secret = $details['secret'];
		$userid = $details['user_id'];		
		$paytxfee = Parameters::find('first');
		$txfee = $paytxfee['payltctxfee'];
		$transactions = Transactions::find('first',array(
				'conditions'=>array(
				'username'=>$user['username'],
				'Added'=>false,
				'Paid'=>'No',
				'Currency'=>'LTC'
				)
		));
			return compact('details','address','txfee','title','transactions','user')	;
	}
	public function receipt(){
		$secret = $_GET['secret'];;
		$userid = $_GET['userid']; //invoice_id is past back to the callback URL
		$invoice = $_GET['invoice'];
		$transaction_hash = $_GET['transaction_hash'];
		$input_transaction_hash = $_GET['input_transaction_hash'];
		$input_address = $_GET['input_address'];
		$value_in_satoshi = $_GET['value'];
		$value_in_btc = $value_in_satoshi / 100000000;	
		$details = Details::find('first',
			array(
					'conditions'=>array(
						'user_id'=>$userid,
						'secret'=>$secret)
				));
				if(count($details)!=0){

				$Transactions = Transactions::find('first',array(
							'conditions'=>array('TransactionHash' => $transaction_hash)
						));
					if($Transactions['_id']==""){
						$tx = Transactions::create();
						$data = array(
							'DateTime' => new \MongoDate(),
							'TransactionHash' => $transaction_hash,
							'username' => $details['username'],
							'address'=>$input_address,							
							'Amount'=> (float)number_format($value_in_btc,8),
							'Currency'=> 'BTC',						
							'Added'=>true,
						);							
						$tx->save($data);
						$dataDetails = array(
							'balance.BTC' => (float)number_format((float)$details['balance.BTC'] + (float)$value_in_btc,8),
						);
						$details = Details::find('all',
							array(
							'conditions'=>array(
								'user_id'=>$userid,
								'secret'=>$secret
							)
						))->save($dataDetails);
					}
				}
// Send email to client for payment receipt, if invoice number is present. or not
			if($data['Amount']>0){
				$function = new Functions();
				$returnvalues = $function->twilio($data);	 // Testing if it works 
			}


			return $this->render(array('layout' => false));	
	}
	public function paymentconfirm($currency=null,$id = null){
		if ($id==""){return $this->redirect('/login');}
		$transaction = Transactions::find('first',array(
			'conditions'=>array(
				'verify.payment'=>$id,
				'Currency'=>$currency,
				'Paid'=>'No'
				)
		));
		$username = $transaction['username'];
		return compact('transaction','username','currency');

	}

	public function paymentverify($currency=null){
		if($currency==""){
				return compact('data','details','user');
		}
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];
		$email = $user['email'];
		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		
		if ($this->request->data) {
			$amount = $this->request->data['TransferAmount'];
			if($details['balance.'.$currency]<=$amount){return false;}			
			$fee = $this->request->data['txFee'];
			$address = $this->request->data['currencyaddress'];

			$tx = Transactions::create();
				$data = array(
					'DateTime' => new \MongoDate(),
					'username' => $details['username'],
					'address'=>$address,							
					'verify.payment' => sha1(openssl_random_pseudo_bytes(4,$cstrong)),
					'Paid' => 'No',
					'Amount'=> (float) -$amount,
					'Currency'=> $currency,					
					'txFee' => (float) -$fee,
					'Added'=>false,
				);							
				$tx->save($data);	
				
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('data','details','tx','currency'),
				array(
					'controller' => 'users',
					'template'=>'withdrawDigital',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject($currency." Withdrawal Approval from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => $currency.' Withdrawal Approval email '.COMPANY_URL));
			$message->setTo($email);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			
			$mailer->send($message);
				
		}	
		return compact('data','details','user','currency');
	}


	public function paymentadminconfirm($currency=null,$id = null){
		if ($id==""){return $this->redirect('/login');}
		$transaction = Transactions::find('first',array(
			'conditions'=>array(
				'verify.payment'=>$id,
				'Currency'=>$currency,
				'Paid'=>'No'
				)
		));
		$username = $transaction['username'];
		return compact('transaction','username','currency');

	}
	public function paymentadmin(){
		if ($this->request->data) {
			$verify = $this->request->data['verify'];
			$username = $this->request->data['username'];
			$password = $this->request->data['password'];
			$currency = $this->request->data['currency'];
			if($password==""){
				return $this->redirect(array('controller'=>'users','action'=>'paymentconfirm/'.$currency.'/'.$verify));
			}
			$transaction = Transactions::find('first',array(
				'conditions'=>array(
					'verify.payment'=>$verify,
					'username'=>$username,
					'Currency'=>$currency,
					'Paid'=>'No'
					)
			));

			$user = Users::find('first',array(
				'conditions' => array(
					'username' => $username,
					'password' => String::hash($password),
				)
			));
			$id = $user['_id'];
			$email = $user['email'];
		
			if($id==""){
				return $this->redirect(array('controller'=>'users','action'=>'paymentconfirm/'.$currency.'/'.$verify));
			}
			$transaction = Transactions::find('first',array(
				'conditions'=>array(
					'verify.payment'=>$verify,
					'username'=>$username,
					'Currency'=>$currency,
					'Paid'=>'No'
					)
			));
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$data = array(
				'username'=>$username,
				'verify'=>$verify,
				'Currency'=>$currency,
				'address'=>$transaction['address'],
				'Amount'=>$transaction['Amount'],
			);
			$body = $view->render(
				'template',
				compact('data'),
				array(
					'controller' => 'users',
					'template'=>'withdrawadmin',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject($currency." Admin Approval from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => $currency.' Admin Approval email '.COMPANY_URL));
			$message->setTo('admin@SiiCrypto.com');
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			
			$mailer->send($message);
		
		}
	}
	public function payment(){
			$title = "Payment";

		if ($this->request->data) {
			$verify = $this->request->data['verify'];
			$username = $this->request->data['username'];
			$password = $this->request->data['password'];
			$admin = $this->request->data['admin'];
			$totp = $this->request->data['totp'];
			$currency = $this->request->data['currency'];
			
			
			if($password==""){
				return $this->redirect(array('controller'=>'users','action'=>'paymentadminconfirm/'.$currency.'/'.$verify));
			}
			if($admin==""){
				return $this->redirect(array('controller'=>'users','action'=>'paymentadminconfirm/'.$currency.'/'.$verify));
			}
			
			$useradmin = Users::find('first',array(
				'conditions'=>array(
					'username'=>$admin,
					'password' => String::hash($password),
					)
			));
			$pos = strrpos($useradmin['email'], 'SiiCrypto.com');
			if ($pos === false) { // note: three equal signs
   return $this->redirect(array('controller'=>'users','action'=>'paymentadminconfirm/'.$currency.'/'.$verify));
			}
			$detailadmin = Details::find('first',array(
				'conditions'=>array(
					'username'=>$admin,
				)
			));

			$ga = new GoogleAuthenticator();
			if($totp==""){
				return $this->redirect(array('controller'=>'users','action'=>'paymentadminconfirm/'.$currency.'/'.$verify));
			}else{
				$checkResult = $ga->verifyCode($detailadmin['secret'], $totp, 2);		
				if ($checkResult!=1) {
					return $this->redirect(array('controller'=>'users','action'=>'paymentadminconfirm/'.$currency.'/'.$verify));
				}
			}


			
			$transaction = Transactions::find('first',array(
				'conditions'=>array(
					'verify.payment'=>$verify,
					'username'=>$username,
					'Currency'=>$currency,
					'Paid'=>'No'
					)
			));

			$user = Users::find('first',array(
				'conditions' => array(
					'username' => $username,
				)
			));
			$id = $user['_id'];
			$email = $user['email'];
		
			if($id==""){return $this->redirect('/login');}
			$details = Details::find('first',
				array('conditions'=>array('user_id'=> (string) $id))
			);
			$amount = abs($transaction['Amount']);

			if($details['balance.'.$currency]<=$amount){
				$txmessage = "Not Sent! Amount does not match!";
				return compact('txmessage');
			}			

		
			///////////////////Special for bitcoin as it uses blockchain!		
		
			if($currency=='BTC'){
				$guid=BITCOIN_GUID;
				$firstpassword=BITCOIN_FIRST;
				$secondpassword=BITCOIN_SECOND;
				$amount = abs($transaction['Amount']);
				if($details['balance.BTC']<=$amount){return false;}			
				
				$fee = $transaction['txFee'];
				$address = $transaction['address'];
				$satoshi = (float)$amount * 100000000;
				$fee_satoshi = (float)$fee * 100000000;
				$json_url = "http://blockchain.info/merchant/$guid/payment?password=$firstpassword&second_password=$secondpassword&to=$address&amount=$satoshi&fee=$fee_satoshi";
				$json_data = file_get_contents($json_url);
				$json_feed = json_decode($json_data);
				$txmessage = $json_feed->message;
				$txid = $json_feed->tx_hash;
				if($txid!=null){
					$data = array(
						'DateTime' => new \MongoDate(),
						'TransactionHash' => $txid,
						'Paid'=>'Yes',
						'Transfer'=>$txmessage,
						'Admin'=>$admin,
					);							
					$transaction = Transactions::find('first',array(
						'conditions'=>array(
							'verify.payment'=>$verify,
							'username'=>$username,
							'Paid'=>'No'
							)
					))->save($data);
				}
			}else{
//						print_r($currency);
//						print_r($address);
//						print_r($comment);
			
				$amount =  abs($transaction['Amount']);
				if($details['balance.'.$currency]<=$amount){return false;}		
				
				$fee = abs($transaction['txFee']);
				$address =  $transaction['address'];
				$satoshi = (float)$amount * 100000000;
				$fee_satoshi = (float)$fee * 100000000;

				
		///////////////////// Change of code required when Virtual Currency added				
				switch($currency){
					case "LTC":
						$coin = new Litecoin('http://'.LITECOIN_WALLET_SERVER.':'.LITECOIN_WALLET_PORT,LITECOIN_WALLET_USERNAME,LITECOIN_WALLET_PASSWORD);
					break;

					case "XGC":
						$coin = new Greencoin('http://'.GREENCOIN_WALLET_SERVER.':'.GREENCOIN_WALLET_PORT,GREENCOIN_WALLET_USERNAME,GREENCOIN_WALLET_PASSWORD);
							
					break;
				}
			}
		// End for /////////////////// Change of code required when Virtual Currency added
				
			$comment = "User: ".$details['username']."; Address: ".$address."; Amount:".$amount.";";
			
			if($currency=='LTC' || $currency=='XGC'){
				if((float)$details['balance.'.$currency]>=(float)$amount){
					$settxfee = $coin->settxfee($fee);
					$txid = $coin->sendfrom('NilamDoctor', $address, (float)$amount,(int)1,$comment);
				}
			}
			
			if($txid!=null){
				$data = array(
					'DateTime' => new \MongoDate(),
					'TransactionHash' => $txid,
					'Added'=>false,
					'Paid'=>'Yes',
					'Transfer'=>$comment,
					'Admin'=>$admin
				);							
				$transaction = Transactions::find('all',array(
					'conditions'=>array(
						'verify.payment'=>$verify,
						'username'=>$username,
						'Currency'=>$currency,
						'Paid'=>'No'
						)
				))->save($data);
		
		
				$transaction = Transactions::find('first',array(
					'conditions'=>array(
						'verify.payment'=>$verify,
						'username'=>$username,
						'Currency'=>$currency,					
						'Paid'=>'Yes'
					)
				));			
				$balance = (float)$details['balance.'.$currency] - (float)$amount;
				$balance = (float)($balance) + (float)$fee;
					$dataDetails = array(
						'balance.'.$currency => (float)$balance,
					);

					$details = Details::find('all',
						array(
							'conditions'=>array(
							'user_id'=> (string) $id
						)
					))->save($dataDetails);
					$view  = new View(array(
						'loader' => 'File',
						'renderer' => 'File',
						'paths' => array(
						'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
						)
					));
					$body = $view->render(
						'template',
						compact('transaction','details','txid','currency'),
						array(
							'controller' => 'users',
							'template'=>'withdrawSent',
							'type' => 'mail',
							'layout' => false
						)
					);

				$transport = Swift_MailTransport::newInstance();
				$mailer = Swift_Mailer::newInstance($transport);
		
				$message = Swift_Message::newInstance();
				$message->setSubject($currency." sent from ".COMPANY_URL);
				$message->setFrom(array(NOREPLY => $currency.' sent from '.COMPANY_URL));
				$message->setTo($email);
				$message->addBcc(MAIL_1);
				$message->addBcc(MAIL_2);			
				$message->addBcc(MAIL_3);		

				$message->setBody($body,'text/html');
				$txmessage = number_format($amount,8) . $currency ."  transfered to ".$address;
				$mailer->send($message);
			}
			
			$transactions = Transactions::find('first',array(
				'conditions'=>array(
				'username'=>$user['username'],
				'Added'=>false,
				'Currency'=>$currency,
				'Paid'=>'No'
				)
			));

			return compact('txmessage','txid','json_url','json_feed','title','currency','transactions');
		}
		
	}

	public function paymentltc(){
			$title = "Payment LTC";
			
		if ($this->request->data) {
			$verify = $this->request->data['verify'];
			$username = $this->request->data['username'];
			$password = $this->request->data['password'];
			
			$transaction = Transactions::find('first',array(
				'conditions'=>array(
					'verify.payment'=>$verify,
					'username'=>$username,
					'Paid'=>'No'
					)
			));

			$user = Users::find('first',array(
				'conditions' => array(
					'username' => $username,
					'password' => String::hash($password),
				)
			));
			$id = $user['_id'];
			$email = $user['email'];
		}

		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$amount =  abs($transaction['Amount']);

		if($details['balance.LTC']<=$amount){
			$txmessage = "Not Sent! Amount does not match!";
			return compact('txmessage');
		}			

		if($id==""){return $this->redirect('/login');}
		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		
		if ($this->request->data) {
			$amount =  abs($transaction['Amount']);
			if($details['balance.LTC']<=$amount){return false;}		

			$fee = abs($transaction['txFee']);
			$address =  $transaction['address'];
			$satoshi = (float)$amount * 100000000;
			$fee_satoshi = (float)$fee * 100000000;
			$litecoin = new Litecoin('http://'.LITECOIN_WALLET_SERVER.':'.LITECOIN_WALLET_PORT,LITECOIN_WALLET_USERNAME,LITECOIN_WALLET_PASSWORD);
 
				$comment = "User: ".$details['username']."; Address: ".$address."; Amount:".$amount.";";
				if((float)$details['balance.LTC']>=(float)$amount){
						$settxfee = $litecoin->settxfee($fee);
						$txid = $litecoin->sendfrom('NilamDoctor', $address, (float)$amount,(int)1,$comment);
					if($txid!=null){

						$data = array(
							'DateTime' => new \MongoDate(),
							'TransactionHash' => $txid,
							'Added'=>false,
							'Paid'=>'Yes',
							'Transfer'=>$comment,
						);							
						$transaction = Transactions::find('first',array(
							'conditions'=>array(
								'verify.payment'=>$verify,
								'username'=>$username,
								'Paid'=>'No'
								)
						))->save($data);
						$transaction = Transactions::find('first',array(
							'conditions'=>array(
								'verify.payment'=>$verify,
								'username'=>$username,
								'Paid'=>'Yes'
								)
						));			
						
						$txmessage = number_format($amount,8) . " LTC transfered to ".$address;

			$balance = (float)$details['balance.LTC'] - (float)$amount;
			$balance = (float)($balance) + (float)$fee;

						$dataDetails = array(
								'balance.LTC' => (float)number_format($balance,8),
							);
						$details = Details::find('all',
							array(
									'conditions'=>array(
										'user_id'=> (string) $id
									)
								))->save($dataDetails);
								
			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('transaction','details','txid'),
				array(
					'controller' => 'users',
					'template'=>'withdraw_ltc_sent',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("LTC sent from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'LTC sent from '.COMPANY_URL));
			$message->setTo($email);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			
			$mailer->send($message);
								
								
				}
			}
			return compact('txmessage','txid','json_url','json_feed','title');
		}
	}


	
	public function transactions(){
		$title = "Transactions";

		$user = Session::read('default');
		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		$transactions = Transactions::find('all',array(
			'conditions'=>array(
			'username'=>$details['username'],
			'Currency'=>'BTC'
			),
			'order'=>array('DateTime'=>-1)
		));
		$Fiattransactions = Transactions::find('all',array(
			'conditions'=>array(
			'username'=>$details['username'],
			'Currency'=>array('$ne'=>'BTC')
			),
			'order'=>array('DateTime'=>-1)
		));
		return compact('title','details','transactions','Fiattransactions');			
	}
	
	public function deposit(){
		$title = "Deposit";
	
		$user = Session::read('default');
		if ($user==""){return $this->redirect('/login');exit;}
		
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);
		if($this->request->data){
			$amountFiat = $this->request->data['AmountFiat'];
			$Currency = $this->request->data['Currency']; 
			$Reference = $this->request->data['Reference']; 		
			$DepositMethod = $this->request->data['DepositMethod'];
			$data = array(
					'DateTime' => new \MongoDate(),
					'username' => $details['username'],
					'Amount'=> (float)$amountFiat,
					'Currency'=> $Currency,					
					'Added'=>true,
					'Reference'=>$Reference,
					'DepositMethod'=>$DepositMethod,				
					'Approved'=>'No'
			);
			$tx = Transactions::create();
			$tx->save($data);

			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('details','data','user'),
				array(
					'controller' => 'users',
					'template'=>'deposit',
					'type' => 'mail',
					'layout' => false
				)
			);	
			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
			$message = Swift_Message::newInstance();
			$message->setSubject("Deposit to ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Deposit to '.COMPANY_URL));
			$message->setTo($user['email']);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		
			$message->setBody($body,'text/html');
			$mailer->send($message);
		}
			return compact('title','details','data','user');			
	}
	
	public function withdraw(){
		$title = "Withdraw";
	
		$user = Session::read('default');

		if ($user==""){		return $this->redirect('/login');}
		$id = $user['_id'];

		$details = Details::find('first',
			array('conditions'=>array('user_id'=> (string) $id))
		);

		if($this->request->data){
			$AccountName = $this->request->data['AccountName'];
			$SortCode = $this->request->data['SortCode'];
			$AccountNumber = $this->request->data['AccountNumber'];		
			$AccountNameBuss = $this->request->data['AccountNameBuss'];
			$SortCodeBuss = $this->request->data['SortCodeBuss'];
			$AccountNumberBuss = $this->request->data['AccountNumberv'];		
			$CompanyNameBuss = $this->request->data['CompanyNameBuss'];		
			$CompanyNumberBuss = $this->request->data['CompanyNumberBuss'];				
			$PostalName = $this->request->data['PostalName'];		
			$PostalStreet = $this->request->data['PostalStreet'];		
			$PostalCity = $this->request->data['PostalCity'];		
			$PostalAddress = $this->request->data['PostalAddress'];		
			$PostalZip = $this->request->data['PostalZip'];		
			$PostalCountry = $this->request->data['PostalCountry'];		
			$WithdrawalMethod = $this->request->data['WithdrawalMethod'];
			$WithdrawalCharges = $this->request->data['WithdrawalCharges'];		
			$amountFiat = $this->request->data['WithdrawAmountFiat'];
			$Currency = $this->request->data['WithdrawCurrency']; 
			$Reference = $this->request->data['WithdrawReference']; 		
			$okpayEmail = $this->request->data['okpay_email']; 				
			$data = array(
					'DateTime' => new \MongoDate(),
					'username' => $details['username'],
					'Amount'=> (float)$amountFiat,
					'Currency' => $Currency,					
					'Added'=>false,
					'Reference'=>$Reference,
					'AccountName'=>$AccountName,
					'SortCode'=>$SortCode,
					'AccountNumber'=>$AccountNumber,
					'AccountNameBuss'=>$AccountNameBuss,
					'SortCodeBuss'=>$SortCodeBuss,
					'AccountNumberBuss'=>$AccountNumberBuss,
					'CompanyNumberBuss'=>$CompanyNumberBuss,				
					'CompanyNameBuss'=>$CompanyNameBuss,								
					'WithdrawalMethod' => $WithdrawalMethod,
					'WithdrawalCharges' => $WithdrawalCharges,
					'okpayEmail' => $okpayEmail,				
					'Postal'=>array(
						'Name' => $PostalName,
						'Address' => $PostalAddress,					
						'Street' => $PostalStreet,					
						'City' => $PostalCity,					
						'Zip' => $PostalZip,					
						'Country' => $PostalCountry,					
					),
					'Approved'=>'No'
			);
			$tx = Transactions::create();
			$tx->save($data);

			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));
			$body = $view->render(
				'template',
				compact('details','data','user'),
				array(
					'controller' => 'users',
					'template'=>'withdraw',
					'type' => 'mail',
					'layout' => false
				)
			);	

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);

			$message = Swift_Message::newInstance();
			$message->setSubject("Withdraw from ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Withdraw from '.COMPANY_URL));
			$message->setTo($user['email']);
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		

			$message->setBody($body,'text/html');
			
			$mailer->send($message);
		}
		return compact('title','details','data','user');			
	
	}
	
	public function addbankdetails(){
		$user = Session::read('default');
		$user_id = $user['_id'];
		$data = array();
		if($this->request->data) {	
			$data['bank'] = $this->request->data;
			$data['bank']['id'] = new MongoID;
			$data['bank']['verified'] = 'No';
			Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			))->save($data);
		}
		return $this->redirect('Users::settings');
	}
	public function addbankBussdetails(){
		$user = Session::read('default');
		$user_id = $user['_id'];
		$data = array();
		if($this->request->data) {	
			$data['bankBuss'] = $this->request->data;
			$data['bankBuss']['id'] = new MongoID;
			$data['bankBuss']['verified'] = 'No';
			Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			))->save($data);
		}
		return $this->redirect('Users::settings');
	}

	public function deleteaccount(){}
	
	public function addpostal(){
		$user = Session::read('default');
		if ($user==""){		return $this->redirect('Users::index');}		
		$user_id = $user['_id'];
		$details = Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			));		
		$title = "Add Address";
			
		return compact('details','title');
	
	}
	public function addpostaldetails(){
		$user = Session::read('default');
		$user_id = $user['_id'];
		$data = array();
		if($this->request->data) {	
			$data['postal'] = $this->request->data;
			$data['postal']['id'] = new MongoID;
			$data['postal']['verified'] = 'No';
			Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			))->save($data);
		}
		return $this->redirect('Users::settings');
	
	}
	
	public function removetransaction($TransactionID,$ID,$url,$currency){
		$Transaction = Transactions::find('first', array(
			'conditions' => array('_id' => new MongoID($ID))
		));
			if(String::hash($Transaction['_id'])==$TransactionID){
				$Remove = Transactions::remove(array('_id'=>new MongoID ($ID)));
			}
		return $this->redirect('/Users/'.$url.'/'.$currency);
	}
	
	public function username($username=null){
		$usercount = Users::find('all',array(
			'conditions'=>array('username'=>$username)
		));
		if(count($usercount)==0){
			$Available = 'Yes';
		}else{
			$Available = 'No';
		}
			return $this->render(array('json' => array(
			'Available'=> $Available,
		)));
	}
	public function signupemail($email=null){
		$usercount = Users::find('all',array(
			'conditions'=>array('email'=>$email)
		));
		if(count($usercount)==0){
			$Available = 'Yes';
		}else{
			$Available = 'No';
		}
			return $this->render(array('json' => array(
			'Available'=> $Available,
		)));
	}
	
	public function addcompany(){
		$user = Session::read('default');
		$user_id = $user['_id'];
		if ($user==""){		return $this->redirect('/login');}
		
		if ($this->request->data) {
			$details = Details::find('first',
				array('conditions'=>array('user_id'=>$user_id))
			);
			if($details['company']['verified']=="Yes"){
				$data['company']['ShortName'] = $details['company']['ShortName'];
				$data['company']['verified'] = 'Yes';	
			}else{
				$data['company']['ShortName'] = $this->request->data['ShortName'];						
				$data['company']['verified'] = 'No';				
			}
			$data['company']['Name'] = $this->request->data['Name'];
			
			$data['company']['Address'] = $this->request->data['Address'];			
			$data['company']['Country'] = $this->request->data['Country'];			
			$data['company']['Registration'] = $this->request->data['Registration'];			
			$data['company']['GovernmentURL'] = $this->request->data['GovernmentURL'];			
			$data['company']['TotalShares'] = (int)$this->request->data['TotalShares'];			
			for($i=0;$i<10;$i++){
				$data['company']['share'][$i] = (int)$this->request->data['share'][$i];
				$data['company']['price'][$i] = (float)$this->request->data['price'][$i];
				$data['company']['sold'][$i] = (int)$this->request->data['sold'][$i];
			}
			$data['company']['id'] = new MongoID;

			Details::find('all',array(
				'conditions'=>array('user_id'=>$user_id)
			))->save($data);
		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));

		$email = $user['email'];
			$body = $view->render(
				'template',
				compact('data','user'),
				array(
					'controller' => 'users',
					'template'=>'addcompany',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("Company Registered with ".COMPANY_URL);
			$message->setFrom(array(NOREPLY => 'Company Registered with '.COMPANY_URL));
			$message->setTo($email);
			$message->setBody($body,'text/html');
			$mailer->send($message);
			
			
		}

		
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$user_id))
		);
		return compact('details');				
		
	}
	public function addcompanydetail(){
		$user = Session::read('default');
		$user_id = $user['_id'];
		if ($user==""){		return $this->redirect('/login');}

	}
	public function EmailPasswordSecurity($value=false){
		$user = Session::read('default');
		$user_id = $user['_id'];
		if ($user==""){		return $this->redirect('/login');}
		$data = array(
			'EmailPasswordSecurity' => $value
		);
		$conditions = array('user_id'=>$user_id);
		$details = Details::update($data,$conditions);
		
		return $this->render(array('json' => array("Updated"=>$value)));
	}
}
?>