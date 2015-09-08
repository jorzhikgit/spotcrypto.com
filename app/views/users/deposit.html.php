<h4>Deposit</h4>

<h5>Hi <?=$user['firstname']?>,</h5>

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
<a href="/ex/dashboard" class="btn btn-primary">Dashboard</a>
<a href="/users/transactions" class="btn btn-primary">Transactions</a>