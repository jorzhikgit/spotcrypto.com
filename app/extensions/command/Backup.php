<?php
namespace app\extensions\command;
use \ZipArchive;
use \lithium\template\View;
use \Swift_MailTransport;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_Attachment;

class Backup extends \lithium\console\Command {
	public function run(){
			$files_to_zip = array(
			"/backup/SIICrypto/details.bson",
			"/backup/SIICrypto/details.metadata.json",
			"/backup/SIICrypto/logins.bson",
			"/backup/SIICrypto/logins.metadata.json",
			"/backup/SIICrypto/orders.bson",
			"/backup/SIICrypto/orders.metadata.json",
			"/backup/SIICrypto/pages.bson",
			"/backup/SIICrypto/pages.metadata.json",
			"/backup/SIICrypto/parameters.bson",
			"/backup/SIICrypto/parameters.metadata.json",
			"/backup/SIICrypto/requests.bson",
			"/backup/SIICrypto/requests.metadata.json",
			"/backup/SIICrypto/reasons.bson",
			"/backup/SIICrypto/reasons.metadata.json",
			"/backup/SIICrypto/system.indexes.bson",
			"/backup/SIICrypto/system.users.bson",
			"/backup/SIICrypto/system.users.metadata.json",
			"/backup/SIICrypto/settings.bson",
			"/backup/SIICrypto/settings.metadata.json",
			"/backup/SIICrypto/trades.bson",
			"/backup/SIICrypto/trades.metadata.json",
			"/backup/SIICrypto/transactions.bson",
			"/backup/SIICrypto/transactions.metadata.json",
			"/backup/SIICrypto/users.bson",
			"/backup/SIICrypto/users.metadata.json",
//			"/.bitcoin/wallet.dat",
			"/.greencoin/wallet.dat",			
		);
//if true, good; if false, zip creation failed
		$result = $this->create_zip($files_to_zip,BACKUP_DIR.'Backup.zip',true);

		$filename = BACKUP_DIR.'Backup.zip';

			$view  = new View(array(
				'loader' => 'File',
				'renderer' => 'File',
				'paths' => array(
					'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
				)
			));

			$body = $view->render(
				'template',
				compact('filename'),
				array(
					'controller' => 'admin',
					'template'=>'backup',
					'type' => 'mail',
					'layout' => false
				)
			);

			$transport = Swift_MailTransport::newInstance();
			$mailer = Swift_Mailer::newInstance($transport);
	
			$message = Swift_Message::newInstance();
			$message->setSubject("Data Backup: ".COMPANY_URL);
			$message->setFrom(array(SUPPORT => 'Data Backup: '.COMPANY_URL));
			$message->setTo("nilamdoc@gmail.com");
			$message->addBcc(MAIL_1);
			$message->addBcc(MAIL_2);			
			$message->addBcc(MAIL_3);		
			$message->attach(Swift_Attachment::fromPath($filename));
			$message->setBody($body,'text/html');
			
			$mailer->send($message);


	}

	function create_zip($files = array(),$destination = '',$overwrite = false) {
		//if the zip file already exists and overwrite is false, return false
		if(file_exists($destination) && !$overwrite) { return false; }
		//vars
		$valid_files = array();
		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {
				//make sure the file exists
				if(file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}
		//if we have good files...
		if(count($valid_files)) {
			//create the archive
			$zip = new ZipArchive();
			if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			//add the files
			foreach($valid_files as $file) {
				$zip->addFile($file,$file);
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
			
			//close the zip -- done!
			$zip->close();
			
			//check to make sure the file exists
			return file_exists($destination);
		}
		else
		{
			return false;
		}
	}
}
?>