<?php
namespace app\controllers;
use lithium\storage\Session;
use app\models\Users;
use app\models\Details;
use app\models\Transactions;
use app\models\Parameters;
use app\models\Reasons;
use app\models\File;
use app\models\Settings;
use app\models\Trades;
use app\models\Orders;
use app\models\Pages;
use app\models\Requests;
use app\models\Logins;
use app\controllers\ExController;
use app\extensions\action\Functions;
use lithium\data\Connections;
use app\extensions\action\Pagination;
use lithium\util\String;
use MongoID;
use MongoDate;
use \lithium\template\View;
use \Swift_MailTransport;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_Attachment;

class AdminController extends \lithium\action\Controller {

	public function index() {
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}
		if($this->request->data){
					$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
					$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		$mongodb = Connections::get('default')->connection;

		$UserRegistrations = Users::connection()->connection->command(array(
			'aggregate' => 'users',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'created' => '$created',
				)),
				array( '$match' => array( 'created'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ) ),
				array('$group' => array( '_id' => array(
						'year'=>array('$year' => '$created'),
						'month'=>array('$month' => '$created'),						
						'day'=>array('$dayOfMonth' => '$created'),												
				),
						'count' => array('$sum' => 1), 
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
					'_id.month'=>-1,
					'_id.day'=>-1,					
//					'_id.hour'=>-1,					
				)),
			)
		));
		$TotalUserRegistrations = Users::connection()->connection->command(array(
			'aggregate' => 'users',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'created' => '$created',
				)),
				array('$group' => array( '_id' => array(
						'year'=>array('$year' => '$created'),
				),
						'count' => array('$sum' => 1), 
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
				)),
			)
		));

		$TotalOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action'=>'$Action',					
					'Amount'=>'$Amount',
					'Completed'=>'$Completed',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'DateTime' => '$DateTime',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),
				)),
				array( '$match' => array( 'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ) ),
				array('$group' => array( '_id' => array(
					'Action'=>'$Action',
					'Completed'=>'$Completed',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'year'=>array('$year' => '$DateTime'),
					'month'=>array('$month' => '$DateTime'),						
					'day'=>array('$dayOfMonth' => '$DateTime'),											
					),
					'Amount' => array('$sum' => '$Amount'), 
					'TotalAmount' => array('$sum' => '$TotalAmount'), 
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
					'_id.month'=>-1,
					'_id.day'=>-1,										
				)),
			)
		));

		$YearTotalOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action'=>'$Action',					
					'Amount'=>'$Amount',
					'Completed'=>'$Completed',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'DateTime' => '$DateTime',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),
				)),
				array('$group' => array( '_id' => array(
					'Action'=>'$Action',
					'Completed'=>'$Completed',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'year'=>array('$year' => '$DateTime'),
					),
					'Amount' => array('$sum' => '$Amount'), 
					'TotalAmount' => array('$sum' => '$TotalAmount'), 
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
				)),
			)
		));


		$newYear = array();
		for($i=0;$i<=5;$i++){
			$date = gmdate('Y',mktime(0,0,0,1,1,2013)+$i*60*60*24*365);
			$newYear[$date] = array();
		}

	
		$new = array();
		
  $days = ($EndDate->sec - $StartDate->sec)/(60*60*24);
		for($i=0;$i<=$days;$i++){
			$date = gmdate('Y-m-d',($EndDate->sec)-$i*60*60*24);
			$new[$date] = array();
		}

			foreach($TotalUserRegistrations['result'] as $UR){
				$URdate = date_create($UR['_id']['year']."-01-01");			
				$urDate = date_format($URdate,"Y");
					$newYear[$urDate] = array(
						'Register'=> $UR['count']
					);
			}

 
			foreach($UserRegistrations['result'] as $UR){
				$URdate = date_create($UR['_id']['year']."-".$UR['_id']['month']."-".$UR['_id']['day']);			
				$urDate = date_format($URdate,"Y-m-d");
					$new[$urDate] = array(
						'Register'=> $UR['count']
					);
			}
			foreach ($YearTotalOrders['result'] as $TO){
				$TOdate = date_create($TO['_id']['year']."-01-01");			
				$toDate = date_format($TOdate,"Y");				

						$newYear[$toDate][$TO['_id']['Action']][$TO['_id']['FirstCurrency'].'/'.$TO['_id']['SecondCurrency']][$TO['_id']['Completed']] = array(
										'FirstCurrency' => $TO['_id']['FirstCurrency'],										
										'Amount' => $TO['Amount'],
										'TotalAmount' => $TO['TotalAmount'],										
						);

			}

			foreach ($TotalOrders['result'] as $TO){
				$TOdate = date_create($TO['_id']['year']."-".$TO['_id']['month']."-".$TO['_id']['day']);			
				$toDate = date_format($TOdate,"Y-m-d");				

						$new[$toDate][$TO['_id']['Action']][$TO['_id']['FirstCurrency'].'/'.$TO['_id']['SecondCurrency']][$TO['_id']['Completed']] = array(
										'FirstCurrency' => $TO['_id']['FirstCurrency'],										
										'Amount' => $TO['Amount'],
										'TotalAmount' => $TO['TotalAmount'],										
						);

			}
			$trades = Trades::find('all');

			$title = "Admin";
			$keywords = "Admin, Index";
			$description = "Administer the site";

	return compact('new','newYear','StartDate','EndDate','title','keywords','description','trades');
	}
	
	public function Approval() {
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}
		if($this->request->data){
			$UserApproval = $this->request->data['UserApproval']	;
			$EmailSearch = $this->request->data['EmailSearch']	;	
			$UserSearch = $this->request->data['UserSearch']	;							
			$usernames = array();		
			if($EmailSearch!="" || $UserSearch!="" ){
				$user = Users::find('all',array(
					'conditions'=>array(
						'username'=>array('$regex'=>$UserSearch),
						'email'=>array('$regex'=>$EmailSearch),					
					)
				));
				foreach($user as $u){
					array_push($usernames,$u['username']);
				}
			}else{
					$user = Users::find('all',array('limit'=>1000));
					foreach($user as $u){
						array_push($usernames,$u['username']);
					}
			}
			switch ($UserApproval) {
				case "All":
					$details = Details::find('all',array(
						'conditions'=>array('username'=>array('$in'=>$usernames))
					));
	//			print_r($usernames);				
					break;
				case "VEmail":
					$details = Details::find('all',array(
						'conditions'=>array(
						'email.verified'=>'Yes',
						'username'=>array('$in'=>$usernames)					
						)
					));
	
					break;
				case "VPhone":
					$details = Details::find('all',array(
						'conditions'=>array(
						'phone.verified'=>'Yes',
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "VBank":
					$details = Details::find('all',array(
						'conditions'=>array(
						'bank.verified'=>'Yes',
						'username'=>array('$in'=>$usernames)
						)
					));
				
					break;
				case "VGovernment":
					$details = Details::find('all',array(
						'conditions'=>array(
						'government.verified'=>'Yes',
						'username'=>array('$in'=>$usernames)
						)
					));
				
					break;
				case "VUtility":
					$details = Details::find('all',array(
						'conditions'=>array(
						'utility.verified'=>'Yes',
						'username'=>array('$in'=>$usernames)
						)
					));			
					break;
					
				case "NVEmail":
					$details = Details::find('all',array(
						'conditions'=>array(
						'email.verify'=>'Yes',
						'username'=>array('$in'=>$usernames))
					));
	
					break;
				case "NVPhone":
					$details = Details::find('all',array(
						'conditions'=>array(
						'phone.verified'=>array('$exists'=>false),
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "NVBank":
					$details = Details::find('all',array(
						'conditions'=>array(
						'bank.verified'=>array('$exists'=>false),
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "NVGovernment":
					$details = Details::find('all',array(
						'conditions'=>array(
						'government.verified'=>array('$exists'=>false),
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "NVUtility":
					$details = Details::find('all',array(
						'conditions'=>array(
						'utility.verified'=>array('$exists'=>false),
						'username'=>array('$in'=>$usernames)
						)
					));
				
					break;
					
				case "WVEmail":
					$details = Details::find('all',array(
						'conditions'=>array(
						'email.verified'=>array('$exists'=>false),
						'username'=>array('$in'=>$usernames)
						)
					));
				
					break;
				case "WVPhone":
					$details = Details::find('all',array(
						'conditions'=>array(
						'phone.verified'=>'No',
						'phone.error'=>0,
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "WVBank":
					$details = Details::find('all',array(
						'conditions'=>array(
						'bank.verified'=>'No',
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "WVGovernment":
					$details = Details::find('all',array(
						'conditions'=>array(
						'government.verified'=>'No',
						'government.error'=>0,
						'username'=>array('$in'=>$usernames)
						)
					));
	
					break;
				case "WVUtility":
					$details = Details::find('all',array(
						'conditions'=>array(
						'utility.verified'=>'No',
						'utility.error'=>0,
						'username'=>array('$in'=>$usernames)
						)
					));
					break;
			}
			}else{
				$details = Details::find('all',array(
				'conditions'=>array(
					'$or'=>array(
						array('utility.verified'=>'No'),
						array('government.verified'=>'No'),
						array('bank.verified'=>'No'),
						array('utility.verified'=>''),
						array('government.verified'=>''),
						array('bank.verified'=>'')	
					)
					
				)
				));
			}
//		print_r(count($details));
$title = "Admin Approval";
$keywords = "Admin, Approval";
$description = "Admin panel for approval";
			$settings = Settings::find('first');

		return compact('UserApproval','details','title','keywords','description','settings');
		
	}
	
	public function __init(){
		$user = Session::read('member');
		$id = $user['_id'];
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$id))
		);
		if(str_replace("@","",strstr($user['email'],"@"))==COMPANY_URL 
			&& $details['email.verified']=="Yes"
			&& $details['TOTP.Validate'] == 1
			&& $details['TOTP.Login'] == 1
			&& ( 
				 MAIL_1==$user['email'] 
			|| MAIL_2==$user['email'] 
			|| MAIL_3==$user['email'] 	
			|| MAIL_4==$user['email'] 	
				 )
		){
			return true;
		}else{
			return false;
		}
	}
	
	public function Approve($media=null,$id=null,$response=null){
			if($this->__init()==false){$this->redirect('ex::dashboard');	}
			if($response!=""){
				if($response=="Approve"){
					$data = array(
						$media.".verified" => "Yes"
					);
				}elseif($response=="Reject"){
					$data = array(
						$media.".verified" => "No"
					);
				}
				$details = Details::find('first',array(
					'conditions'=>array(
						'_id'=>$id
					)
				))->save($data);
			}
			$details = Details::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));

		$image_utility = File::find('first',array(
			'conditions'=>array('details_'.$media.'_id'=>(string)$details['_id'])
		));
		if($image_utility['filename']!=""){
				$imagename_utility = $image_utility['_id'].'_'.$image_utility['filename'];
				$path = LITHIUM_APP_PATH . '/webroot/documents/'.$imagename_utility;
				
				file_put_contents($path, $image_utility->file->getBytes());
		}
			$this->_render['layout'] = 'image';
$title = "Approve";
$keywords = "Approve, documents";
$description = "Admin Approve documents ";

			return compact('imagename_utility','media','id','title','keywords','description','settings');
	}
	
	public function transactions(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}

		$Fiattransactions = Transactions::find('all',array(
			'conditions'=>array(
				'Currency'=>array('$nin'=>array('BTC','XGC')),
				'Approved'=>'No',
				'Added'=>true
			),
			'order'=>array('DateTime'=>-1)
		));
		
		$Details = array();$i=0;
		foreach ($Fiattransactions as $ft){
		/////////////////////////////////////////////////////////////////////////////////////////////				
		  // Verified Bank from details
			$bankverified = Details::find('first',array(
				'conditions'=>array('username'=>$ft['username'])
			));
			$Details[$i]['BankVerified'] = $bankverified['bank.verified'];
			$Details[$i]['UtilityVerified'] = $bankverified['utility.verified'];			
			$Details[$i]['GovtVerified'] = $bankverified['government.verified'];			
		/////////////////////////////////////////////////////////////////////////////////////////////			
		/////////////////////////////////////////////////////////////////////////////////////////////			
			//Summary of all deposits / withdrawals for a user
		$mongodb = Connections::get('default')->connection;
		$UserFundsDeposits = Users::connection()->connection->command(array(
			'aggregate' => 'transactions',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'username' => '$username',
					'AmountApproved' => '$AmountApproved',					
					'Currency' => '$Currency',					
					'Approved'=>'$Approved',
					'Added'=>'$Added'
				)),
				array('$match'=>array(
					'username'=>$ft['username'],					
					'Currency'=>array('$nin'=>array('BTC','XGC')),
					'Approved'=>'Yes',
					'Added'=>(boolean)true
					)),
				array('$group' => array( '_id' => array(
					'username' => '$username',
					'Currency' => '$Currency',					
				),
						'TotalDeposit' => array('$sum' => '$AmountApproved'), 
				)),
			)
		));
		$UserFundsWithdrawals = Users::connection()->connection->command(array(
			'aggregate' => 'transactions',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'username' => '$username',
					'AmountApproved' => '$AmountApproved',					
					'Currency' => '$Currency',					
					'Approved'=>'$Approved',
					'Added'=>'$Added'
				)),
				array('$match'=>array(
					'username'=>$ft['username'],					
					'Currency'=>array('$nin'=>array('BTC','XGC')),
					'Approved'=>'Yes',
					'Added'=>(boolean)false
					)),
				array('$group' => array( '_id' => array(
					'username' => '$username',
					'Currency' => '$Currency',					
				),
						'TotalDeposit' => array('$sum' => '$AmountApproved'), 
				)),
			)
		));


/////////////////////////////////////////////////////////////////////////////////////////////
		foreach($UserFundsDeposits['result'] as $uf){
			if($uf['_id']['Currency']=='USD'){
				$Details[$i]['Funds']['USD'] = $uf['TotalDeposit'];										
			}
			if($uf['_id']['Currency']=='CAD'){
				$Details[$i]['Funds']['CAD'] = $uf['TotalDeposit'];										
			}
			if($uf['_id']['Currency']=='EUR'){
				$Details[$i]['Funds']['EUR'] = $uf['TotalDeposit'];					
			}
			if($uf['_id']['Currency']=='GBP'){
				$Details[$i]['Funds']['GBP'] = $uf['TotalDeposit'];					
			}
		}
		foreach($UserFundsWithdrawals['result'] as $uf){
			if($uf['_id']['Currency']=='USD'){
				$Details[$i]['FundsOut']['USD'] = $uf['TotalDeposit'];										
			}
			if($uf['_id']['Currency']=='CAD'){
				$Details[$i]['FundsOut']['CAD'] = $uf['TotalDeposit'];										
			}
			if($uf['_id']['Currency']=='EUR'){
				$Details[$i]['FundsOut']['EUR'] = $uf['TotalDeposit'];					
			}
			if($uf['_id']['Currency']=='GBP'){
				$Details[$i]['FundsOut']['GBP'] = $uf['TotalDeposit'];					
			}
		}
		
			$Previoustransactions = Transactions::find('all',array(
				'conditions'=>array(
					'Currency'=>array('$ne'=>'BTC'),
					'username'=>$ft['username']
				),
				'order'=>array('DateTime'=>-1),
				'limit'=>3
			));
			
			$Details[$i]['DateTime'] = $ft['DateTime'];	
			$Details[$i]['username'] = $ft['username'];				
			$Details[$i]['Reference'] = $ft['Reference'];	
			$Details[$i]['Amount'] = $ft['Amount'];	
			$Details[$i]['Currency'] = $ft['Currency'];	
			$Details[$i]['Added'] = $ft['Added'];													
			$Details[$i]['Approved'] = $ft['Approved'];										
			$Details[$i]['_id'] = $ft['_id'];													
			$j = 0;
			foreach($Previoustransactions as $pt){
				$Details[$i]['Previous'][$j]['Approved']	=		$pt['Approved'];
				$Details[$i]['Previous'][$j]['Added']	=		$pt['Added'];				
				$Details[$i]['Previous'][$j]['Amount']	=		$pt['Amount'];				
				$Details[$i]['Previous'][$j]['Currency']	=		$pt['Currency'];				
				$Details[$i]['Previous'][$j]['DateTime']	=		$pt['DateTime'];				
				$j++;
			}
			$i++;
		}
		$reasons = Reasons::find('all',array(
			'conditions'=>array('type'=>'Deposit'),
			'order'=>array('code'=>1)
		));		
		$title = "Transactions";
$keywords = "Transactions";
$description = "Admin panel for transactions";


		return compact('Details','reasons','title','keywords','description');
	}
	public function withdrawals(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}
		$Fiattransactions = Transactions::find('all',array(
			'conditions'=>array(
				'Currency'=>array('$ne'=>'BTC'),
				'Approved'=>'No',
				'Added'=>false
			),
			'order'=>array('DateTime'=>-1)
		));
		$Details = array();$i=0;
		foreach($Fiattransactions as $ft){
			$details = Details::find('first',array(
				'conditions'=>array('username'=>$ft['username'])
			));
			$Previoustransactions = Transactions::find('all',array(
				'conditions'=>array(
					'Currency'=>array('$ne'=>'BTC'),
					'username'=>$ft['username']
				),
				'order'=>array('DateTime'=>-1),
				'limit'=>3
			));
			
			$Details[$i]['GBP'] = $details['balance.GBP'];
			$Details[$i]['EUR'] = $details['balance.EUR'];			
			$Details[$i]['USD'] = $details['balance.USD'];			
			$Details[$i]['CAD'] = $details['balance.CAD'];						
			$Details[$i]['username'] = $details['username'];						
			$Details[$i]['TranDate'] = $ft['DateTime'];						
			$Details[$i]['Reference'] = $ft['Reference'];									
			$Details[$i]['Amount'] = $ft['Amount'];									
			$Details[$i]['Currency'] = $ft['Currency'];									
			$Details[$i]['Added'] = (string)$ft['Added'];												
			$Details[$i]['Approved'] = $ft['Approved'];									
			$Details[$i]['WithdrawalMethod'] = $ft['WithdrawalMethod'];
			$Details[$i]['WithdrawalCharges'] = $ft['WithdrawalCharges'];
			$Details[$i]['_id'] = $ft['_id'];							
			$j = 0;
			foreach($Previoustransactions as $pt){
				$Details[$i]['Previous'][$j]['Approved']	=		$pt['Approved'];
				$Details[$i]['Previous'][$j]['Amount']	=		$pt['Amount'];				
				$Details[$i]['Previous'][$j]['Currency']	=		$pt['Currency'];				
				$Details[$i]['Previous'][$j]['DateTime']	=		$pt['DateTime'];				
				$j++;
			}
			$i++;

		}
		$reasons = Reasons::find('all',array(
			'conditions'=>array('type'=>'Withdrawal'),
			'order'=>array('code'=>1)
		));
		$title = "Withdrawals";
$keywords = "Withdrawals, admin";
$description = "Admin panel for withdrawal";


		return compact('Fiattransactions','Details','reasons','title','keywords','description');
	}
	
	
	public function approvetransaction(){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}
	if($this->request->data){
		$Amount = $this->request->data['Amount'];
		$id = $this->request->data['id'];	
		$Currency = $this->request->data['Currency'];			

		$Authuser = Session::read('member');
		$AuthBy = $Authuser['username'];

		$data = array(
			'AmountApproved' => (float)$Amount,
			'Approved' => 'Yes',
			'ApprovedBy'=> $AuthBy,
			
		);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			))->save($data);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));
		$databank = array(
			'bank.verified' => 'Yes'
		);
		$details = Details::find('all',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		))->save($databank);

		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		));

		$OriginalAmount = $details['balance.'.$Currency];
		$dataDetails = array(
					'balance.'.$Currency => (float)$OriginalAmount + (float)$Amount,
		);

		$detailsAdd = Details::find('all',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		))->save($dataDetails);
		$user = Users::find('first',array(
			'conditions'=>array('_id'=>	new MongoID ($details['user_id']))
		));

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('Transactions','details','user'),
			array(
				'controller' => 'admin',
				'template'=>'approvetransaction',
				'type' => 'mail',
				'layout' => false
			)
		);	

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Deposit Approved ".COMPANY_URL);
		$message->setFrom(array(NOREPLY => 'Deposit Approved '.COMPANY_URL));
		$message->setTo($user['email']);
		$message->addBcc(MAIL_1);
		$message->addBcc(MAIL_2);			
		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		
		$mailer->send($message);
		

	}
		$this->redirect('Admin::transactions');	
	}
	public function deletetransaction($id=null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Transactions = Transactions::remove('all',array(
		'conditions'=>array(
			'_id'=>new MongoID ($id)
		)
	));
		$this->redirect('Admin::transactions');	

	}	

	public function rejecttransaction($id=null,$reason=null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Authuser = Session::read('member');
		$AuthBy = $Authuser['username'];

		$reason = Reasons::find('first',array(
			'conditions'=>array('code'=>$reason),
		));

		$data = array(
			'Reason'=>$reason['reason'],
			'Approved' => 'Rejected',
			'ApprovedBy' => $AuthBy,
		);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			))->save($data);

		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));
		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		));
			$user = Users::find('first',array(
			'conditions'=>array('_id'=>	new MongoID ($details['user_id']))
		));

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('Transactions','details','user'),
			array(
				'controller' => 'admin',
				'template'=>'rejecttransaction',
				'type' => 'mail',
				'layout' => false
			)
		);	

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Deposit Rejected ".COMPANY_URL.": ".$reason['reason']);
		$message->setFrom(array(NOREPLY => 'Deposit Rejected '.COMPANY_URL.": ".$reason['reason']));
		$message->setTo($user['email']);
		$message->addBcc(MAIL_1);
		$message->addBcc(MAIL_2);			
		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		
		$mailer->send($message);
		$this->redirect('Admin::transactions');	
	}

	public function sendemailtransaction($id=null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Authuser = Session::read('member');
		$AuthBy = $Authuser['username'];

		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));
		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		));
		$user = Users::find('first',array(
			'conditions'=>array('_id'=>	new MongoID ($details['user_id']))
		));

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('Transactions','details','user'),
			array(
				'controller' => 'admin',
				'template'=>'sendemailtransaction',
				'type' => 'mail',
				'layout' => false
			)
		);	

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Deposit Approved ".COMPANY_URL.": Deposit funds now!");
		$message->setFrom(array(NOREPLY => 'Deposit Approved '.COMPANY_URL.": Deposit funds now!"));
		$message->setTo($user['email']);
		$message->addBcc(MAIL_1);
		$message->addBcc(MAIL_2);			
		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		
		$mailer->send($message);
		$this->redirect('Admin::transactions');	
	}
	
	public function approvewithdrawal(){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}
	if($this->request->data){
		$Amount = $this->request->data['Amount'];
		$WithdrawalCharges = $this->request->data['WithdrawalCharges'];
		$FinalWithdrawalCharges = $this->request->data['FinalWithdrawalCharges'];		
		$WithdrawalMethod = $this->request->data['WithdrawalMethod'];
		$id = $this->request->data['id'];	
		$Currency = $this->request->data['Currency'];			

		$Authuser = Session::read('member');
		$AuthBy = $Authuser['username'];

		$data = array(
			'AmountApproved' => (float)$Amount,
			'Approved' => 'Yes',
			'WithdrawalCharges' => $WithdrawalCharges,
			'WithdrawalChargesFinal' => $FinalWithdrawalCharges,			
			'WithdrawalMethod' => $WithdrawalMethod,			
			'ApprovedBy'=> $AuthBy,
			
		);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			))->save($data);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));
		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		));

		$OriginalAmount = $details['balance.'.$Currency];
		$dataDetails = array(
					'balance.'.$Currency => (float)$OriginalAmount - (float)$Amount,
		);

		$detailsAdd = Details::find('all',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		))->save($dataDetails);
		$user = Users::find('first',array(
			'conditions'=>array('_id'=>	new MongoID ($details['user_id']))
		));

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('Transactions','details','user'),
			array(
				'controller' => 'admin',
				'template'=>'approvewithdrawal',
				'type' => 'mail',
				'layout' => false
			)
		);	

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Withdrawal Approved ".COMPANY_URL);
		$message->setFrom(array(NOREPLY => 'Withdrawal Approved '.COMPANY_URL));
		$message->setTo($user['email']);
		$message->addBcc(MAIL_1);
		$message->addBcc(MAIL_2);			
		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		
		$mailer->send($message);

	}
		$this->redirect('Admin::withdrawals');	
	}

	public function deletewithdrawal($id=null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Transactions = Transactions::remove('all',array(
		'conditions'=>array(
			'_id'=>new MongoID ($id)
		)
	));
		$this->redirect('Admin::withdrawals');	
	}	

	public function rejectwithdrawal($id=null,$reason=null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Authuser = Session::read('member');
		$AuthBy = $Authuser['username'];

		$reason = Reasons::find('first',array(
			'conditions'=>array('code'=>$reason),
		));

		$data = array(
			'Reason'=>$reason['reason'],
			'Approved' => 'Rejected',
			'ApprovedBy' => $AuthBy,
		);
		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			))->save($data);

		$Transactions = Transactions::find('first',array(
				'conditions'=>array(
					'_id'=>$id
				)
			));
		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$Transactions['username']
			)
		));
			$user = Users::find('first',array(
			'conditions'=>array('_id'=>	new MongoID ($details['user_id']))
		));

		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('Transactions','details','user'),
			array(
				'controller' => 'admin',
				'template'=>'rejectwithdrawal',
				'type' => 'mail',
				'layout' => false
			)
		);	

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Withdrawal Rejected ".COMPANY_URL.": ".$reason['reason']);
		$message->setFrom(array(NOREPLY => 'Withdrawal Rejected '.COMPANY_URL.": ".$reason['reason']));
		$message->setTo($user['email']);
		$message->addBcc(MAIL_1);
		$message->addBcc(MAIL_2);			
		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		
		$mailer->send($message);
		$this->redirect('Admin::withdrawals');	
	}
	public function user($page=1,$pagelength=20){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
			
		$mongodb = Connections::get('default')->connection;
	  $pagination = new Pagination($mongodb, '/Admin/user/{{PAGE}}/');
		if($this->request->data){
			$itemsPerPage = $this->request->data['pagelength'];
		}else{
			$itemsPerPage = $pagelength;
		}
		$currentPage    = $page;
		$pagination->setQuery(array(
			'#collection'	=>  'details',
			'#find'		=>  array(),
			'#sort'		=>  array(
				'balance.BTC'	=>  -1
			),
		), $currentPage, $itemsPerPage);
		$details = $pagination->Paginate();
		$Details = array();$i = 0;
		foreach($details['dataset'] as $dt){
			$user = Users::find('first',array(
				'conditions'=>array('username'=>$dt['username'])
			));
			
		$mongodb = Connections::get('default')->connection;
		$YourOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'user_id' => '$user_id',					
					'username' => '$username',
					'Amount'=>'$Amount',
					'PerPrice'=>'$PerPrice',
					'Completed'=>'$Completed',
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),					
				)),
				array('$match'=>array(
					'Completed'=>'N',
					'username'=>$dt['username']
					)),
				array('$group' => array( '_id' => array(
						'Action'=>'$Action',				
						'FirstCurrency'=>'$FirstCurrency',
						'SecondCurrency'=>'$SecondCurrency',						
						),
					'Amount' => array('$sum' => '$Amount'),  
					'TotalAmount' => array('$sum' => '$TotalAmount'), 										
					'No' => array('$sum'=>1)					
				)),
				array('$sort'=>array(
					'_id.Action'=>1,
				))
			)
		));

		$trades = Trades::find('all',array(
			'fields'=>array('trade')
		));

		foreach($YourOrders['result'] as $YO){
			foreach($trades as $trade){
				$FC = substr($trade['trade'],0,3);
				$SC = substr($trade['trade'],4,3);
				$CGroup = $FC.'-'.$SC;
				if($YO['_id']['Action']=='Buy' && $YO['_id']['FirstCurrency'] == $FC && $YO['_id']['SecondCurrency']==$SC){
					$Details[$i]['Buy'][$CGroup]['Amount'] = $YO['Amount'];
					$Details[$i]['Buy'][$CGroup]['TotalAmount'] = $YO['TotalAmount'];
				}				
				if($YO['_id']['Action']=='Sell' && $YO['_id']['FirstCurrency'] == $FC && $YO['_id']['SecondCurrency']==$SC){
					$Details[$i]['Sell'][$CGroup]['Amount'] = $YO['Amount'];
					$Details[$i]['Sell'][$CGroup]['TotalAmount'] = $YO['TotalAmount'];
				}
			}
		}

			foreach($trades as $trade){
				$FC = substr($trade['trade'],0,3);
				$SC = substr($trade['trade'],4,3);
				$Details[$i][$FC] = $dt['balance'][$FC];
				$Details[$i][$SC] = $dt['balance'][$SC];				
			}
			$Details[$i]['username'] = $user['username'];							
			$Details[$i]['firstname'] = $user['firstname'];							
			$Details[$i]['lastname'] = $user['lastname'];										
			$Details[$i]['email'] = $user['email'];													
			$Details[$i]['ip'] = $user['ip'];													
			$Details[$i]['created'] = $user['created'];													

			$i++;
		}

		$page_links = $pagination->getPageLinks();

		$title = "User";
		$TotalUsers = Users::count();
		$title = "Users";
$keywords = "Admin, Users";
$description = "Admin panel for users";


		return compact('title','users','page_links','TotalUsers','Details','title','keywords','description','pagelength');
		
	}
	
	public function bitcoin(){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$title = "Bitcoins";
		if($this->request->data){
			$transactions = Transactions::find('all',array(
				'conditions' => array('TransactionHash'=>$this->request->data['transactionhash'])
			));
		}
		$title = "Bitcoin transaction";
$keywords = "Bitcoin, Admin";
$description = "Admin panel for bitcoin transaction";


		return compact('title','transactions','title','keywords','description');
	}
	public function reverse($txhash = null, $username = null, $amount = null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$Transactions = Transactions::remove('all',array(
			'conditions'=>array(
				'TransactionHash'=>$txhash
			)
		));

		$details = Details::find('first',array(
			'conditions'=>array(
				'username'=>$username
			)
		));

		$OriginalAmount = $details['balance.BTC'];
		$dataDetails = array(
					'balance.BTC' => (float)$OriginalAmount - (float)$amount,
		);

		$detailsAdd = Details::find('all',array(
			'conditions'=>array(
				'username'=>$username
			)
		))->save($dataDetails);
		
		$this->redirect('Admin::bitcoin');	
	}
	
	public function detail($username=null){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$transactions = Transactions::find('all',array(
				'conditions'=>array(
					'username'=>$username,
					'Currency'=>'BTC'					
				),
			'order' => array('DateTime'=>'DESC')				
			));
		$transactionsXGC = Transactions::find('all',array(
				'conditions'=>array(
					'username'=>$username,
					'Currency'=>'XGC'					
				),
			'order' => array('DateTime'=>'DESC')				
			));
		$Fiattransactions = Transactions::find('all',array(
			'conditions'=>array(
			'username'=>$username,
			'Currency'=>array('$nin'=>array('BTC','XGC'))
			),
			'order'=>array('DateTime'=>-1)
		));
			
		$details = Details::find('all',array(
			'conditions'=>array(
				'username'=>$username
			)
		));
		$userdetail = Details::find('first',array(
			'conditions'=>array(
				'username'=>$username
			)
		));
		$id = $userdetail['user_id'];
			$user = Users::find('all',array(
			'conditions'=>array(
			'username'=>$username
			)
		));
		
		$logins = Logins::find('first',array(
			'conditions'=>array(
			'username'=>$username
			),
			'order' => array('DateTime'=>-1)		
		));
		$loginCount = Logins::find('count',array(
			'conditions'=>array(
			'username'=>$username
			)
		));
		$UserOrders = Orders::find('all',array(
			'conditions'=>array(
				'username'=>$username,
				'Completed'=>'N',
				),
			'order' => array('DateTime'=>-1)
		));
		$UserCompleteOrders = Orders::find('all',array(
			'conditions'=>array(
				'username'=>$username,
				'Completed'=>'Y',
				),
			'order' => array('DateTime'=>-1)
		));
		$title = "Detail user";
$keywords = "Admin, Detail user";
$description = "Admin Panel for user";

$trades = Trades::find('all');
$ex = new ExController();
		$YourOrders = array();
		foreach($trades as $t){
			$YourOrders['Buy'] = $ex->YourOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$YourOrders['Sell'] = $ex->YourOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
			$YourCompleteOrders['Buy'] = $ex->YourCompleteOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$YourCompleteOrders['Sell'] = $ex->YourCompleteOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
		}
		$Commissions = $ex->TotalCommissions($id);
		$CompletedCommissions = $ex->CompletedTotalCommissions($id);		
		$RequestFriends = $ex->RequestFriend($id);
		$UsersRegistered = Details::count();
		$functions = new Functions();
		$OnlineUsers = 	$functions->OnlineUsers();
		foreach($trades as $t){
			$TotalOrders['Buy'] = $ex->TotalOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$TotalOrders['Sell'] = $ex->TotalOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
			$TotalCompleteOrders['Buy'] = $ex->TotalCompleteOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$TotalCompleteOrders['Sell'] = $ex->TotalCompleteOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));						
		}
		
			return compact('title','transactions','transactionsXGC','details','user','UserOrders','Fiattransactions','UserCompleteOrders','title','keywords','description','logins','loginCount','YourOrders','YourCompleteOrders','Commissions','CompletedCommissions','TotalOrders','TotalCompleteOrders');
	}
	public function bankapprove($username = null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$data = array(
			'bank.verified' => 'Yes'
		);
		$details = Details::find('all',array(
			'conditions'=>array(
				'username'=>$username
			)
		))->save($data);
		
		$this->redirect('Admin::user');	
	}
	public function bankBussapprove($username = null){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		$data = array(
			'bankBuss.verified' => 'Yes'
		);
		$details = Details::find('all',array(
			'conditions'=>array(
				'username'=>$username
			)
		))->save($data);
		
		$this->redirect('Admin::user');	
	}
	
	public function commission(){
	if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
	
		$mongodb = Connections::get('default')->connection;
		$Commissions = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action'=>'$Action',					
					'Amount'=>'$Amount',
					'Completed'=>'$Completed',					
					'CommissionAmount'=>'$Commission.Amount',
					'CommissionCurrency'=>'$Commission.Currency',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'DateTime' => '$DateTime',					
					'username' => '$username',
				)),
				array( '$match' => array( 
					'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ),
					'Completed'=>'Y',
					'username'=>array('$nin'=>array('comUserA','comUserB','comUserC','comUserD'))
					 )),
				array('$group' => array( '_id' => array(
					'CommissionCurrency'=>'$CommissionCurrency',					
					'year'=>array('$year' => '$DateTime'),
					'month'=>array('$month' => '$DateTime'),						
					'day'=>array('$dayOfMonth' => '$DateTime'),	
					),
					'CommissionAmount' => array('$sum' => '$CommissionAmount'), 
					'Transactions' => array('$sum'=>1),
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
					'_id.month'=>-1,
					'_id.day'=>-1,										
				)),
				array('$limit'=>30)
			)
		));
		$new = array();
  	$days = ($EndDate->sec - $StartDate->sec)/(60*60*24);
		for($i=0;$i<=$days+1;$i++){
			$date = gmdate('Y-m-d',($EndDate->sec)-$i*60*60*24);
			$new[$date] = array();
		}
		foreach($Commissions['result'] as $UR){
			$URdate = date_create($UR['_id']['year']."-".$UR['_id']['month']."-".$UR['_id']['day']);			
			$urDate = date_format($URdate,"Y-m-d");
				$new[$urDate]['Transactions'] = $UR['Transactions'];

				if($UR['_id']['CommissionCurrency']=='BTC'){
					$new[$urDate]['BTC'] = $UR['CommissionAmount'];
				}
				if($UR['_id']['CommissionCurrency']=='XGC'){
					$new[$urDate]['XGC'] = $UR['CommissionAmount'];
				}
				if($UR['_id']['CommissionCurrency']=='GBP'){
					$new[$urDate]['GBP'] = $UR['CommissionAmount'];				
				}
				if($UR['_id']['CommissionCurrency']=='EUR'){
					$new[$urDate]['EUR'] = $UR['CommissionAmount'];				
				}
				if($UR['_id']['CommissionCurrency']=='USD'){
					$new[$urDate]['USD'] = $UR['CommissionAmount'];				
				}
				if($UR['_id']['CommissionCurrency']=='CAD'){
					$new[$urDate]['CAD'] = $UR['CommissionAmount'];				
				}
		}
		
$title = "Commission";
$keywords = "Admin Commission";
$description = "Admin panel for commission";


	return compact(	'new','StartDate','EndDate','title','keywords','description')	;
	}
	public function bitcointransaction(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		
		$transactions = Transactions::find('all',array(
			'conditions'=>array(
				'Currency'=>'BTC',
				'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ,			
				),
			'order'=>array('DateTime'=>-1)
		));
$title = "Bitcoin Transactions";
$keywords = "Bitcoin Transactions";
$description = "Admin panel for bitcoin transactions";
		

		return compact(	'transactions','StartDate','EndDate','title','keywords','description')	;
		
	}
	public function litecointransaction(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		
		$transactions = Transactions::find('all',array(
			'conditions'=>array(
				'Currency'=>'XGC',
				'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ,			
				),
			'order'=>array('DateTime'=>-1)
		));
$title = "Litecoin Transactions";
$keywords = "Litecoin Transactions";
$description = "Admin panel for Litecoin transactions";
		

		return compact(	'transactions','StartDate','EndDate','title','keywords','description')	;
		
	}
	
	public function orders(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		$Commission = Parameters::find('first');
		$Orders = Orders::find('all',array(
			'conditions'=>array(
				'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ,			
				),
			'order'=>array('DateTime'=>-1)
		));
$title = "Orders";
$keywords = "Orders";
$description = "Admin panel for Orders";
		

		return compact(	'Orders','StartDate','EndDate','title','keywords','description','Commission')	;
	
	}
	public function api(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}		
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*2)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		$mongodb = Connections::get('default')->connection;

		$Requests = Requests::connection()->connection->command(array(
			'aggregate' => 'requests',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'DateTime' => '$DateTime',
					'API' => '$API',
					'username' => '$username',					
					'IP' => '$IP',					
					'nounce' => '$nounce',					
				)),
				array( '$match' => array( 'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ) ),
				array('$group' => array( '_id' => array(
						'year'=>array('$year' => '$DateTime'),
						'month'=>array('$month' => '$DateTime'),						
						'day'=>array('$dayOfMonth' => '$DateTime'),												
						'username'=>'$username',
						'IP'=>'$IP',
						'API'=>'$API'
				),
						'count' => array('$sum' => 1), 
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
					'_id.month'=>-1,
					'_id.day'=>-1,
					'_id.username'=>1,
					'_id.API'=>1
				)),
			)
		));
		$new = array();
		
  	$days = ($EndDate->sec - $StartDate->sec)/(60*60*24);
		for($i=0;$i<=$days;$i++){
			$date = gmdate('Y-m-d',($EndDate->sec)-$i*60*60*24);
			$new[$date] = array();
		}

		foreach($Requests['result'] as $rq){
				$RQdate = date_create($rq['_id']['year']."-".$rq['_id']['month']."-".$rq['_id']['day']);			
				$RQDate = date_format($RQdate,"Y-m-d");
					$new[$RQDate][$rq['_id']['username']][$rq['_id']['API']][$rq['_id']['IP']] = array(
						'Request'=>$rq['count'],
					);
		}
		return compact('new')		;		
	}
	public function play(){
		$details = Details::find('all',array(
			'conditions'=>array(
				'username'=>array('$regex'=>'comUser'),
			)
		));
		$trades = Trades::find('all');
		return compact('details','trades');
	}
	
	public function complete(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		$Orders = Orders::find('all',array(
			'conditions'=>array(
				'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ,			
				'Completed'=>'Y',
				'Action'=>'Buy'
				),
			'order'=>array('Transact.DateTime'=>-1)
		));
	$i = 0;
	$FinalOrders = array();
		foreach($Orders as $Order){
			$UserOrder = Orders::find('first',array(
				'conditions'=>array(
					'_id' => $Order['Transact']['id']
					),
				'order'=>array('DateTime'=>-1)
			));
			$FinalOrders[$i]['Buy']['_id'] = $Order['_id'];
			$FinalOrders[$i]['Buy']['username'] = $Order['username'];
			$FinalOrders[$i]['DateTime'] = $Order['Transact']['DateTime'];
			$FinalOrders[$i]['Buy']['Amount'] = number_format($Order['Amount'],6);			
			$FinalOrders[$i]['Buy']['PerPrice'] = $Order['PerPrice'];						
			$FinalOrders[$i]['Buy']['pair'] = $Order['FirstCurrency'].'/'.$Order['SecondCurrency'];									
			$FinalOrders[$i]['Buy']['Commission'] = number_format($Order['Commission']['Amount'],6).':'.$Order['Commission']['Currency'];												

			$FinalOrders[$i]['Sell']['_id'] = $UserOrder['_id'];
			$FinalOrders[$i]['Sell']['username'] = $UserOrder['username'];
			$FinalOrders[$i]['Sell']['Amount'] = number_format($UserOrder['Amount'],6);			
			$FinalOrders[$i]['Sell']['PerPrice'] = $UserOrder['PerPrice'];									
			$FinalOrders[$i]['Sell']['pair'] = $UserOrder['FirstCurrency'].'/'.$UserOrder['SecondCurrency'];												
			$FinalOrders[$i]['Sell']['Commission'] = number_format($UserOrder['Commission']['Amount'],6).':'.$UserOrder['Commission']['Currency'];															
			$i++;
		}
	
	return compact('FinalOrders');
	}
	
	public function down(){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}
		$data = array(
		'server' => (boolean)false
		);
		Parameters::find('all')->save($data);
		return compact('$data');
	}
	public function activate($id=null){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}	
		$data = array(
			'active' => 'Yes'
		);
		Details::find('all',array('conditions'=>array(
			'_id'=>$id
		)))->save($data);
		$this->redirect('Admin::approval');	
	}
	public function deactivate($id=null){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}	
		$data = array(
			'active' => 'No'
		);
		Details::find('all',array('conditions'=>array(
			'_id'=>$id
		)))->save($data);
		$this->redirect('Admin::approval');	
	}
	
	public function map(){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}	
		$mongodb = Connections::get('default')->connection;
		$IPDetails = Details::connection()->connection->command(array(
			'aggregate' => 'details',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'ip' => '$lastconnected.IP',
					'iso' => '$lastconnected.ISO',					
				)),
				array('$group' => array( '_id' => array(
						'iso'=> '$iso',
				),
						'count' => array('$sum' => 1), 
				)),
			)
		));
		$details = Details::find('all',array(
			'conditions'=>array('lastconnected.loc'=>array('$exists'=>true)),
			'fields'=>array('lastconnected.loc','lastconnected.ISO','balance.BTC','balance.XGC','balance.USD','balance.EUR','balance.GBP'),
			'sort'=>array('lastconnected.ISO'=>'ASC')
		));
		$balance = array();
		$coun = "{";
		foreach($IPDetails['result'] as $IP){
				$BTC = 0; $XGC = 0; $GBP = 0; $USD = 0; $EUR = 0;
				foreach($details as $dd){
					if($IP['_id']['iso']==$dd['lastconnected']['ISO']){
						$BTC = $BTC + $dd['balance']['BTC'];
						$XGC = $XGC + $dd['balance']['XGC'];
						$GBP = $GBP + $dd['balance']['GBP'];
						$USD = $USD + $dd['balance']['USD'];
						$EUR = $EUR + $dd['balance']['EUR'];																								
					}
				$balance[$IP['_id']['iso']] = " BTC: ".number_format($BTC,3)." XGC: ".number_format($XGC,3)." GBP: ".number_format($GBP,3)." USD: ".number_format($USD,3)." EUR: ".number_format($EUR,3);
				}

			if($IP["_id"]["iso"]!=""){
				$coun = $coun . '"'.$IP['_id']['iso'].'":"Users: '. $IP["count"].$balance[$IP["_id"]["iso"]].'",';
			}
		}
		$coun = substr($coun,0,-1);
		$coun = $coun . '}';

		return compact('IPDetails','details','coun');
	}
	
	public function hard(){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}
	
		if($this->request->data){
			$Withdrawal = Parameters::find('all')->save($this->request->data);
		}

		$Withdrawal = Parameters::find('first');
		return compact('Withdrawal');	
	}
	
	public function pages(){
		if($this->__init()==false){			$this->redirect('ex::dashboard');	}	
	$pages = Pages::find('all',array('order'=>array('_id'=>1)));
	if($this->request->data){
		Pages::find('all',array(
			'conditions'=>array('_id'=>$this->request->data['_id']))
		)->save($this->request->data);
	}
	return compact('pages');	
	}
	public function pageadd(){
		if($this->request->data){
			Pages::create()->save($this->request->data);
		}
		$this->redirect('admin::pages');		
	}
	public function greencointransaction(){
		if($this->__init()==false){$this->redirect('ex::dashboard');	}	
		if($this->request->data){
			$StartDate = new MongoDate(strtotime($this->request->data['StartDate']));
			$EndDate = new MongoDate(strtotime($this->request->data['EndDate']));			
		}else{
			$StartDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))-60*60*24*30)));
			$EndDate = new MongoDate(strtotime(gmdate('Y-m-d H:i:s',mktime(0,0,0,gmdate('m',time()),gmdate('d',time()),gmdate('Y',time()))+60*60*24*1)));
		}
		
		$transactions = Transactions::find('all',array(
			'conditions'=>array(
				'Currency'=>'XGC',
				'DateTime'=> array( '$gte' => $StartDate, '$lte' => $EndDate ) ,			
				),
			'order'=>array('DateTime'=>-1)
		));
$title = "Greencoin Transactions";
$keywords = "Greencoin Transactions";
$description = "Admin panel for Litecoin transactions";
		

		return compact(	'transactions','StartDate','EndDate','title','keywords','description')	;
		
	}
	public function company(){
	if($this->__init()==false){			$this->redirect('ex::dashboard');	}	
	$details = Details::find('all',array(
		'conditions' => array(
			'company'=>array('$exists'=>true),
			'company.verified'=>'No')
	));
	
		return compact(	'details')	;
	}
	
	public function companyverify($id=null){
	$data = array(
		"company.verified"=>'Yes'
	);
	Details::find('all',array(
			'conditions'=>array('_id'=>$id)
	))->save($data);
	$this->redirect('Admin::company');
	}
	
	public function trades(){}
	
	public function RemoveCompletedOrder($ID){
	if($this->__init()==false){			$this->redirect('ex::dashboard');	}	

	$Orders = Orders::find('first', array(
			'conditions' => array('_id' => new MongoID($ID))
	));

/*	print_r($Orders['_id']);
	print_r("<br>");
	print_r($Orders['Transact']['id']);
	print_r("<br>");
*/	
		if($Orders['Completed']=='Y')		{
			$details = Details::find('first', array(
				'conditions' => array('user_id'=>(string)$Orders['user_id'])
			));
			if($Orders['Action']=='Buy'){
				$balanceFirst = 'balance.'.$Orders['FirstCurrency'];
				$balanceSecond = 'balance.'.$Orders['SecondCurrency'];
				$data = array(
					$balanceSecond => (float)($details[$balanceSecond] + $Orders['PerPrice']*$Orders['Amount'])
				);
/*				print_r($Orders['username']);
				print_r($details[$balanceSecond]);
				print_r($data);
				*/
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 'username'=>$Orders['username']
						)
				))->save($data);
				
				$fromUser = Details::find('first', array(
					'conditions' => array('user_id'=>(string)$Orders['Transact']['user_id'])
				));
				$data = array(
					$balanceFirst => (float)($fromUser[$balanceFirst] + (float)$Orders['Amount'])
				);
/*				print_r($Orders['Transact']['username']);
				print_r($fromUser[$balanceFirst]);
				print_r($data);
		*/		
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['Transact']['user_id'], 'username'=>$Orders['Transact']['username']
						)
				))->save($data);
				
			}
			if($Orders['Action']=='Sell'){
				$balanceFirst = 'balance.'.$Orders['FirstCurrency'];
				$balanceSecond = 'balance.'.$Orders['SecondCurrency'];
				$data = array(
					$balanceFirst => (float)($details[$balanceFirst] + (float)$Orders['Amount'])
				);
/*				print_r($Orders['username']);
				print_r($details[$balanceFirst]);
				print_r($data);
		*/		
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 
						'username'=>$Orders['username']
						)
				))->save($data);
				
				$fromUser = Details::find('first', array(
					'conditions' => array('user_id'=>(string)$Orders['Transact']['user_id'])
				));
				$data = array(
					$balanceSecond => (float)($fromUser[$balanceSecond] + $Orders['PerPrice']*$Orders['Amount'])
				);
				
/*				print_r($Orders['Transact']['username']);
				print_r($fromUser[$balanceSecond]);
				print_r($data);
		*/		
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['Transact']['user_id'], 'username'=>$Orders['Transact']['username']
						)
				))->save($data);
				
			}
			
			$Remove = Orders::remove(array('_id'=>$Orders['_id']));
			$Remove = Orders::remove(array('_id'=>$Orders['Transact']['id']));
			
				$data = array(
				'page.refresh' => true
				);
				Details::find('all')->save($data);
			
		}
		$this->redirect(array('controller'=>'Admin','action'=>"orders"));		
	}

}
?>