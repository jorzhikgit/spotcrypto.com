<?php
use app\models\Parameters;
$Comm = Parameters::find('first');
?>
<h3>FAQ</h3>

<p><strong><u>Become a Customer</u></strong></p>

<blockquote>To become an SiiCrypto customer please click <a href="/users/signup">signup</a>. Registration implies you have read and agreed to our <a href="/company/termsofservice">Terms of Service.</a>
</blockquote>
<p><strong><u>Fees</u></strong></p>
<blockquote><ul>
<li>We charge <strong><?=$Comm['value']?></strong>% per transaction.</li>
<li>If you <strong>buy</strong> 1 Bitcoin our fee is <strong><?=$Comm['value']/100?></strong> Bitcoins.</li>
<li>If you <strong>sell</strong> $100 worth of Bitcoins our fee is <strong><?=$Comm['value']*100?></strong> cent.</li>
</ul>
</blockquote>
<p><strong><u>Deposits/Withdrawals</u></strong></p>
<blockquote>

<ul>
<span>For <strong>bank wire transfer</strong> deposits/withdrawals</span><br>

<span><strong>Limits</strong></span><br>
<ul>
<li><strong>Registered</strong> - $2,500 daily.</li>
<li><strong>Verified</strong> - 45,000 daily.</li>
<li><strong>Fully verified</strong> - $20,000 daily.</li>
</ul>
		
<li>All deposits and withdrawals need to be verified and cleared, please see relevant sections when you login.</li>
<li>VERY IMPORTANT: Please make sure to INCLUDE your CUSTOMER REFERENCE with your deposit, which you can find when you complete FUNDING on your account page, so that we can credit your account appropriately.</li>
<li>We cannot be held liable if you send us money with no reference and have not completed a deposit request via your account (though with recorded delivery we can attempt to return any such fiat or solve such matters).</li>
<li>We cannot be held liable if you send us fiat with no reference, no deposit request, and no recorded delivery, and will treat such activity as suspicious and report it to the relevant authorities.</li>
<u>Example Reference:</u><br>
Account name: <strong>silent bob</strong><br>
Reference number: <strong>15828481</strong><br>
Amount: <strong>$xxxx</strong><br>
</ul>  
<span>When we receive your funds we verify with your deposit request and credit your SiiCrypto.com account the amount.</span><br>
<br>
<p><strong>Deposits</strong></p>
<span>Once fiat amounts are received your account gets credited the same amount, just the same as doing a bank transfer, without the bank.</span><br>
<br>
<p><strong>Withdrawals</strong></p>
<ul>
<li>We charge customers the relevant fee that Royal Mail charges to cover the withdrawal amount respectively.</li>
<li>This charge is made to your SiiCrypto.com account.</li>
<li>If you do not have enough to cover the Royal Mail fee in your SiiCrypto.com account then your withdrawal will not be processed and you will be notified via email.</li>
<li>We store all fiat via safety deposit box services.</li>
</ul>
</blockquote>
<p><strong><u>Time Delays</u></strong></p>

<blockquote>
<ul >
<li>Transfers are only processed weekdays, barring bank holidays.</li>
<li>It can take us up to 24 hours to verify, confirm and start the process for your withdrawal requests.</li>
<li>It can then take Royal Mail 1-3 days to deliver your withdrawal (we always use 1st Class).</li>
</ul>
<u>Bitcoin</u>
<ul ><li>Bitcoin deposits and withdrawals are subject to the Bitcoin protocol.</li></ul>
</blockquote>

<p><strong><u>Security</u></strong></p>
<blockquote>

<ul >
<li>SiiCrypto employs two factor authentication (2FA) and time-based one-time password algorithm (TOTP), for login, withdrawals, deposits and settings.</li>

<li>We also require a level of identification for all customers as per our (link) verification page, and run random security checks on accounts. Any information found to be out of date may result in the account in question to be temporarily suspended until such information is suitably updated.</li>

<li>If you have any issues please contact SiiCrypto.com at <a href="mailto:support@SiiCrypto.com ">support@SiiCrypto.com</a></li>

</ul>
</blockquote>
