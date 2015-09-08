<?php 
use app\models\Trades;
use app\models\Orders;
use lithium\data\Connections;
use lithium\storage\Session;

$howmany = 100;
$trades = Trades::find('all',array('limit'=>$howmany,'order'=>array('order'=>1)));
		$mongodb = Connections::get('default')->connection;
		$Rates = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( 
				'$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'PerPrice'=>'$PerPrice',					
					'Completed'=>'$Completed',					
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',	
					'TransactDateTime' => '$Transact.DateTime',
				)),
				array('$match'=>array(
					'Completed'=>'Y',					
					)),
				array('$group' => array( '_id' => array(
							'FirstCurrency'=>'$FirstCurrency',
							'SecondCurrency'=>'$SecondCurrency',	
							'year'=>array('$year' => '$TransactDateTime'),
							'month'=>array('$month' => '$TransactDateTime'),						
							'day'=>array('$dayOfMonth' => '$TransactDateTime'),												
						'hour'=>array('$hour' => '$TransactDateTime'),
						),
					'min' => array('$min' => '$PerPrice'), 
					'avg' => array('$avg' => '$PerPrice'), 					
					'max' => array('$max' => '$PerPrice'), 
					'last' => array('$last' => '$PerPrice'), 					
				)),
				array('$sort'=>array(
					'_id.year'=>-1,
					'_id.month'=>-1,
					'_id.day'=>-1,					
					'_id.hour'=>-1,					
				)),
				array('$limit'=>count($trades))
			)
		));
//print_r($Rates);
$sel_curr = $this->_request->params['args'][0];
if($this->_request->params['controller']!='api'){
	$currencies = array();
		foreach($trades as $trade){
			$first_currency = substr($trade['trade'],0,3);		
			$second_currency = substr($trade['trade'],4,3);		
			$avg = 0;
			$price = 0;
			foreach($Rates['result'] as $rate){
			 if($rate['_id']['FirstCurrency']==$first_currency && $rate['_id']['SecondCurrency']==$second_currency){
					$price = $rate['last'];
			 }
			}
?><li>
<a href="/ex/x/<?=strtolower(str_replace("/","_",$trade['trade']))?>" class="list-group-subitem">
	<i class="glyphicon glyphicon-caret-right"></i><?=$trade['trade']?> <span class="badge btn-success pull-right"><?=number_format($price,4)?></span>
</a>				
</li>
<?php
		}
}
?>
