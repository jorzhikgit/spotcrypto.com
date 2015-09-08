<?php
use app\models\Parameters;
$Comm = Parameters::find('first');
?>
<?php $this->form->config(array( 'templates' => array('error' => '<p class="alert alert-danger">{:content}</p>'))); 
?>
<div class="row container-fluid">
	<div class="col-sm-6 col-md-5  col-md-offset-0 well" >
		<div class="panel panel-success">
			<div class="panel-heading">
				<h3 class="panel-title">Register</h3>
			</div>
		</div>
		<?=$this->form->create($Users,array('class'=>'form-group has-error')); ?>
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="FirstNameIcon"></i>
					</span>
		<?=$this->form->field('firstname', array('label'=>'','placeholder'=>'First Name', 'class'=>'form-control','onkeyup'=>'CheckFirstName(this.value);' )); ?>
				</div>
			</div>				
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="LastNameIcon"></i>
					</span>
		<?=$this->form->field('lastname', array('label'=>'','placeholder'=>'Last Name', 'class'=>'form-control','onkeyup'=>'CheckLastName(this.value);' )); ?>
				</div>
			</div>				
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="UserNameIcon"></i>
					</span>
		<?=$this->form->field('username', array('label'=>'','placeholder'=>'username', 'class'=>'form-control','onkeyup'=>'CheckUserName(this.value);' )); ?>
				</div>
		<p class="label label-danger">Only characters and numbers, NO SPACES</p>				
			</div>				
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="EmailIcon"></i>
					</span>

		<?=$this->form->field('email', array('label'=>'','placeholder'=>'name@youremail.com', 'class'=>'form-control','onkeyup'=>'CheckEmail(this.value);'  )); ?>
				</div>
			</div>				
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="PasswordIcon"></i>
					</span>
		<?=$this->form->field('password', array('type' => 'password', 'label'=>'','placeholder'=>'Password', 'class'=>'form-control','onkeyup'=>'CheckPassword(this.value);' )); ?>
				</div>
			</div>				
			<div class="form-group has-error">			
				<div class="input-group">
					<span class="input-group-addon">
						<i class="glyphicon glyphicon-asterisk" id="Password2Icon"></i>
					</span>
		<?=$this->form->field('password2', array('type' => 'password', 'label'=>'','placeholder'=>'same as above', 'class'=>'form-control','onkeyup'=>'CheckPassword(this.value);' )); ?>
				</div>
			</div>				
		<?php // echo $this->recaptcha->challenge();?>
		<?=$this->form->submit('Sign up' ,array('class'=>'btn btn-primary btn-block')); ?>
		<?=$this->form->end(); ?>
	</div>
	<div class="col-sm-9 col-sm-offset-3 col-md-6  col-md-offset-1 well" >
		<div class="panel panel-success">
			<div class="panel-heading">
				<h3 class="panel-title">Advantages</h3>
			</div>
		</div>
		<h3>Sabrina Investments Inc. SiiCrypto.com</h3>
		<ul>
			<li>Fees are <strong><?=$Comm['value']?></strong>% per transaction.</li>
    	<li>Crypto coins stored on Cold Storage, SSL and 256bit encryption.</li>
    <li>Two Factor Authentication(2FA) login and coin withdrawal, with optional (3FA) login.</li>
    <li>Exchange available to all internationally and nationally.</li>

		</ul>

<p>To become an SiiCrypto.com customer and use our platform and services, you only need the following;
<ul>
    <li>To trade with BTC/XGC - registered & verified email.</li>
    <li>To deposit fiat currency - registered & verified email.</li>
    <li>To withdraw fiat - verified proof of address.</li>

</ul>
</p>

<p>For further details on verification, deposits and withdrawals, please check.
<ul>
    <li><a href="/company/verification">Verification</a></li>

</ul>		
</p>
Any issues please contact us at <a href="mailto:support@siiCrypto.com">support@siiCrypto.com</a>
</p>
		</div>
	</div>

