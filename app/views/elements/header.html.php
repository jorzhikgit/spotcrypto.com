<?php
use app\models\Trades;
use lithium\storage\Session;
use app\extensions\action\Functions;
?>
<?php $user = Session::read('member'); ?>
<div class="navbar-header">
	<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
		<span class="sr-only">Toggle navigation</span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
	</button>
	<a class="navbar-brand" href="/"><img src="/img/logo.png" alt="SiiCrypto.com" title="SII Crypto"></a>
</div> <!-- navbar-header-->
<div class="navbar-collapse collapse">
	<?php 
			if(strtolower($this->_request->controller)=='ex'){ ?>
	
	<?php }else{?>
	
<?php }?>				
	<ul class="nav navbar-nav navbar-right">
		<?php if($user!=""){ ?>
			<li ><a href='#' class='dropdown-toggle' data-toggle='dropdown' >
			<?=$user['username']?> <i class='glyphicon glyphicon-chevron-down'></i>&nbsp;&nbsp;&nbsp;
			</a>
			<ul class="dropdown-menu">
				<li><a href="/users/settings"><i class="fa fa-gears"></i> Settings</a></li>			
				<li><a href="/ex/dashboard"><i class="fa fa-dashboard"></i> Dashboard</a></li>
				<li class="divider"></li>				
<?php 
$trades = Trades::find('all');
$currencies = array();
$VirtualCurr = array(); $FiatCurr = array();
foreach($trades as $tr){
	$first_curr = substr($tr['trade'],0,3);
	array_push($currencies,$first_curr);
	$second_curr = substr($tr['trade'],4,3);
	array_push($currencies,$second_curr);

		if($tr['FirstType']=='Virtual'){
			array_push($VirtualCurr,$first_curr);
			}else{
			array_push($VirtualCurr,$first_curr);
		}
		if($tr['SecondType']=='Virtual'){
			array_push($VirtualCurr,$second_curr);
			}else{
			array_push($FiatCurr,$second_curr);
		}
}	//for

	$currencies = array_unique($currencies);
	$VirtualCurr = array_unique($VirtualCurr);
	$FiatCurr = array_unique($FiatCurr);
	foreach($VirtualCurr as $currency){
		echo '<li><a href="/users/funding/'.$currency.'"><i class="fa fa-exchange"></i> Funding '.$currency.'</a></li>';
	}
	foreach($FiatCurr as $currency){
		echo '<li><a href="/users/funding_fiat/'.$currency.'"><i class="fa fa-exchange"></i> Funding '.$currency.'</a></li>';
	}

?>
				<li class="divider"></li>								
				<li><a href="/users/transactions"><i class="fa fa-tasks"></i> Transactions</a></li>							
				<li class="divider"></li>
				<li><a href="/print/"><i class="fa fa-print"></i> Print / Cold Storage</a></li>											
				<li class="divider"></li>												
				<li><a href="/logout"><i class="fa fa-power-off"></i> Logout</a></li>
			</ul>
			<?php }else{?>
			<li><a href="/login">Login</a></li>
			<li><a href="/users/signup">Register&nbsp;&nbsp;&nbsp;&nbsp;</a></li>
			<?php }?>				
		</ul>
</div> <!-- navbar-collapse -->
