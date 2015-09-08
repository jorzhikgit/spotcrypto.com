<div style="background-color:#eeeeee;height:50px;padding-left:20px;padding-top:10px">
	<img src="https://<?=COMPANY_URL?>/img/<?=COMPANY_URL?>.gif" alt="<?=COMPANY_URL?>">
</div>
<h4>Hi <?=$user['firstname']?>,</h4>

<p>You have requested to deposit money to <?=COMPANY_URL?>.</p>
<p><strong>Make SURE you deposit to your verified account. Money attempted to be sent to any other account will result in the transaction being blocked and investigated.</strong></p>
<p style="color:red ">Please make SURE you copy/paste and print the boxed information, or write it clearly and INCLUDE either with your deposit.</p>
<blockquote>
<h4>Send Payment to:</h4>
<table cellspacing="5" cellpadding="5" width="50%">
			<tr>
				<td>Account Name:</td>
				<td><strong>Sabrina Investments Inc.</strong><br>
			<tr>
				<td>Bank:</td>
				<td>CAIXA BANK</td>
			</tr>
			<tr>
				<td>Address:</td>
				<td>Avenida Jaime 1, nr 60,<br>
        03750 Pedreguer, Spain, ES<br></td>
			</tr>
			<tr>
				<td>Account No/IBAN:</td>
				<td>ES49 2100 4608 1222 0010 9432</td>
			</tr>
			<tr>
				<td>SWIFT/BIC:</td>
				<td>CAIXESBBXXX</td>
			</tr>
</table>
</blockquote>

<table cellspacing="5" cellpadding="5" width="50%" style="border:1px solid black ">
		<tr>
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
<p><strong><u>Time Delays</u></strong></p>

<blockquote>
<u>CAD / USD / EUR</u>
<ul >
<li>Transfers are only processed weekdays, barring bank holidays.</li>
<li>It can take us up to 24 hours to verify and confirm your deposit request once received.</li>
</ul>
</blockquote>

<p>Thanks,<br>
<?=NOREPLY?></p>

<p>P.S. Please do not reply to this email. </p>
<p>This email was sent to you as you tried to register on <?=COMPANY_URL?> with the email address. 
If you did not register, then you can delete this email.</p>
<p>We do not spam. </p>