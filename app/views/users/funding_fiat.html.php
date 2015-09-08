<style>
.Address_success{background-color: #9FFF9F;font-weight:bold}
</style>
<?php echo $this->_render('element', 'funding_fiat_header');?>
<div class="row">
	<div class="col-md-6">
		<div class="panel panel-info" >
			<div class="panel-heading">
			<h2 class="panel-title">Deposit <?=$currency?> </h2>
			</div>
		</div>
		<form action="/users/deposit/" method="post" class="form">
		<table class="table table-condensed table-bordered table-hover" style="margin-top:-20px">
			<tr style="background-color:#CFFDB9">
				<td colspan="2">Send payment to</td>
			</tr>
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
			<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Quote this reference number in your deposit">
				<td width="50%">Reference:</td>
				<?php $Reference = substr($details['username'],0,10).rand(10000,99999);?>
				<td><?=$Reference?></td>
			</tr>
			<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Amount should be between 1 and 10000">
				<td>Amount:</td>
				<td><input type="text" value="" class="form-control" placeholder="1.0" min="1" max="10000" name="AmountFiat" id="AmountFiat" maxlength="5"></td>
			</tr>
			<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Select a currency">
				<td>Currency:</td>
				<td><select name="Currency" id="Currency" class="form-control" >
						<option value="<?=$currency?>"><?=$currency?></option>
				</select></td>
			</tr>
			<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Once verified and processed your funds will be mailed.">
				<td colspan="2" style="text-align:center ">
				<input type="hidden" name="Reference" id="Reference" value="<?=$Reference?>">
					<input type="submit" value="Send email to admin for approval" class="btn btn-primary" onclick="return CheckDeposit();">
				</td>
			</tr>
		</table>
		</form>

	</div>
	<div class="col-md-6">
		<div class="panel panel-info" >
			<div class="panel-heading">
			<h2 class="panel-title">Withdraw <?=$currency?> </h2>
			</div>
		</div>
			<form action="/users/withdraw/" method="post" class="form">		
			<table class="table table-condensed table-bordered table-hover" style="margin-top:-20px">
				<tr style="background-color:#CFFDB9">
					<td>Balance</td>
					<td style="text-align:right " colspan="2"><?=$details['balance.'.$currency]?> <?=$currency?></td>									</tr>			
				<tr style="background-color: #FDDBAC">
				<?php 
				$Amount = 0;$AmountUSD = 0;$AmountEUR = 0; $AmountCAD = 0;
				foreach($transactions as $transaction){
					if($transaction['Currency']==$currency){
						$Amount = $Amount + $transaction['Amount'];
					}
				}
				?>
					<td>Withdrawal</td>
					<td style="text-align:right " colspan="2"><?=$Amount?> <?=$currency?></td>					
				</tr>			
				<tr style="background-color:#CFFDB9">
					<td>Net Balance</td>
					<td style="text-align:right " colspan="2"><?=$details['balance.'.$currency]-$Amount?> <?=$currency?></td>					
				</tr>							
				<tr>
					<td>Withdrawal Methods:</td>
					<td colspan="2">
						<select name="WithdrawalMethod" id="WithdrawalMethod" onChange="PaymentMethod(this.value);" class="form-control">
							<option value=""> -- Select -- </option>
							<option value="bank">Bank - Personal</option>
							<option value="bankBuss">Bank - Business</option>											
						</select>
					</td>
				</tr>
				<tr>
				<td colspan="5">
					<div id="WithdrawalBank" style="display:none">
				<table class="table table-condensed table-bordered table-hover">								
					<tr>
						<td>Account name:</td>
						<td><input type="text" name="AccountName" id="AccountName" placeholder="Verified bank account name" value="<?=$details['bank']['bankname']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Sort code: </td>
						<td><input type="text" name="SortCode" id="SortCode" placeholder="01-01-10" value="<?=$details['bank']['sortcode']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Account number:</td>
						<td><input type="text" name="AccountNumber" id="AccountNumber" placeholder="12345678" value="<?=$details['bank']['accountnumber']?>" class="form-control"></td>
					</tr>
					</table>
					</div>
					<div id="WithdrawalBankBuss" style="display:none">
				<table class="table table-condensed table-bordered table-hover">								
					<tr>
						<td>Account name:</td>
						<td><input type="text" name="AccountNameBuss" id="AccountNameBuss" placeholder="Verified bank account name" value="<?=$details['bankBuss']['bankname']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Sort code: </td>
						<td><input type="text" name="SortCodeBuss" id="SortCodeBuss" placeholder="01-01-10" value="<?=$details['bankBuss']['sortcode']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Company name:</td>
						<td><input type="text" name="CompanyNameBuss" id="CompanyNameBuss" placeholder="12345678" value="<?=$details['bankBuss']['companyname']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Company number:</td>
						<td><input type="text" name="CompanyNumberBuss" id="CompanyNumberBuss" placeholder="12345678" value="<?=$details['bankBuss']['companynumber']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Account number:</td>
						<td><input type="text" name="AccountNumberBuss" id="AccountNumberBuss" placeholder="12345678" value="<?=$details['bankBuss']['accountnumber']?>" class="form-control"></td>
					</tr>
					</table>
					</div>									
					<div id="WithdrawalPost"  style="display:none">
					<table class="table table-condensed table-bordered table-hover">
					<tr>
						<td>Name:</td>
						<td><input type="text" name="PostalName" id="PostalName" placeholder="Name" value="<?=$details['postal']['Name']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Address:</td>
						<td><input type="text" name="PostalAddress" id="PostalAddress" placeholder="Name" value="<?=$details['postal']['Address']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Street:</td>
						<td><input type="text" name="PostalStreet" id="PostalStreet" placeholder="Street" value="<?=$details['postal']['Street']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>City:</td>
						<td><input type="text" name="PostalCity" id="PostalCity" placeholder="City" value="<?=$details['postal']['City']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Postal / Zip code:</td>
						<td><input type="text" name="PostalZip" id="PostalZip" placeholder="Zip" value="<?=$details['postal']['Zip']?>" class="form-control"></td>
					</tr>
					<tr>
						<td>Country:</td>
						<td><input type="text" name="PostalCountry" id="PostalCountry" placeholder="Country" value="<?=$details['postal']['Country']?>" class="form-control"></td>
					</tr>
					
					</table>
					</div>
			</td>
				<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Quote this reference number in your withdrawal">
					<td >Reference:</td>
					<?php $Reference = substr($details['username'],0,10).rand(10000,99999);?>
					<td colspan="2"><?=$Reference?></td>
				</tr>
				<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Amount should be between 6 and 10000">
					<td>Amount:</td>
					<td colspan="2"><input type="text" value="" placeholder="5.0" min="5" max="10000" name="WithdrawAmountFiat" id="WithdrawAmountFiat" maxlength="5" class="form-control"><br>
				</td>
				</tr>
				<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Select a currency">
					<td>Currency:</td>
					<td colspan="2"><select name="WithdrawCurrency" id="WithdrawCurrency" class="form-control">
							<option value="<?=$currency?>"><?=$currency?></option>																		
					</select></td>
				</tr>
				<tr  class=" tooltip-x" rel="tooltip-x" data-placement="top" title="Once your email is approved, you will receive the funds in your bank account">
					<td colspan="3" style="text-align:center ">
					<input type="hidden" name="WithdrawReference" id="WithdrawReference" value="<?=$Reference?>" class="form-control">
					<input type="submit" value="Send email to admin for approval" class="btn btn-primary" onclick="return CheckWithdrawal();" >
					</td>
				</tr>
			</table>
		</form>
	</div>
</div>
