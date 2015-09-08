<div style="background-color:#eeeeee;height:50px;padding-left:20px;padding-top:10px">
	<img src="https://<?=COMPANY_URL?>/img/<?=COMPANY_URL?>.gif" alt="<?=COMPANY_URL?>">
</div>

<h4>Withdraw</h4>

<h5>Hi <?=$data['username']?>,</h5>

<p>You have requested to withdraw money from <?=COMPANY_URL?>.</p>
<p>Thank you, your request has been sent for clearance.</p>
<table>
<?php 
	if($data['WithdrawalMethod']=='bank'){
?>
		<tr>
			<td>Account name:</td>
			<td><?=$data['AccountName']?></td>
		</tr>
		<tr>
			<td>Full Name:</td>
			<td><?=$user['firstname']?> <?=$user['lastname']?></td>
		</tr>
		<tr>
			<td>Sort code: </td>
			<td><?=$data['SortCode']?></td>	
		</tr>
		<tr>
			<td>Account number:</td>
			<td><?=$data['AccountNumber']?></td>
		</tr>
		<tr>
			<td>Withdrawal Charges:</td>
			<td> 1</td>
		</tr>
<?php }?>
<?php 
	if($data['WithdrawalMethod']=='bankBuss'){
?>
		<tr>
			<td>Account name:</td>
			<td><?=$data['AccountNameBuss']?></td>
		</tr>
		<tr>
			<td>Sort code: </td>
			<td><?=$data['SortCodeBuss']?></td>	
		</tr>
		<tr>
			<td>Account number:</td>
			<td><?=$data['AccountNumberBuss']?></td>
		</tr>
		<tr>
			<td>Company number:</td>
			<td><?=$data['CompanyNumberBuss']?></td>
		</tr>
		<tr>
			<td>Company name:</td>
			<td><?=$data['CompanyNameBuss']?></td>
		</tr>
<?php }?>		<tr>
			<td>Reference:</td>
			<td><strong><?=$data['Reference']?></strong></td>
		</tr>
		<tr>
			<td>Amount:</td>
			<td><?=$data['Amount']?></td>
		</tr>
		<tr>
			<td>Currency:</td>
			<td><?=$data['Currency']?></td>
		</tr>		
</table>
<p><strong><u>Deposits/Withdrawals</u></strong></p>

<blockquote>
<u>CAD / USD / EUR</u>
<ul >
<li>    Transfers are only processed weekdays, barring bank holidays.</li>
<li>    It can take us up to 24 hours to verify and confirm your deposit request once received.</li>
<li>    It can take us up to 24 hours to verify, confirm and start the process for your withdrawal requests.</li>
</ul>
</blockquote>

<p>Thanks,<br>
<?=NOREPLY?></p>

<p>P.S. Please do not reply to this email. </p>
<p>This email was sent to you as you tried to withdraw <?=$currency?> from <?=COMPANY_URL?> with the email address. 
<p>We do not spam. </p>
