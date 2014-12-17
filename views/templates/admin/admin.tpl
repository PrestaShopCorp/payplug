{*
* 2013 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PayPlug SAS
*  @copyright 2013 PayPlug SAS
*  @license http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<link rel="stylesheet" type="text/css" href="{$this_path|escape:'htmlall'}css/admin.css">
<div id="payplug_admin">
	<img src="{$this_path|escape:'htmlall'}img/logoPayPlug.png" alt="logoPayPlug">
	<h2>{l s='The simplest online payment solution' mod='payplug'}</h2>
	<p>{l s='PayPlug provides small e-merchants all the benefits of a full online payment solution.' mod='payplug'}</p>

	<ul>
		<li>
			{l s='Accept all Visa and MasterCard credit and debit cards' mod='payplug'}
		</li>
		<li>
			{l s='Customize your payment page with your own colors and design' mod='payplug'}
		</li>
		<li>
			{l s='Avoid fraud by using Verified by Visa and Mastercard Secure Code' mod='payplug'}
		</li>
		<li>
			{l s='Automatic order update and email confirmation' mod='payplug'}
		</li>
		<li>
			{l s='Web interface to manage and export transaction history' mod='payplug'}
		</li>
		<li>
			{l s='Funds available on your bank account within 2 to 5 business days' mod='payplug'}
		</li>
		<li>
			{l s='Pay-as-you-go: transaction fees range from 1.5&#37; to 2.5&#37; without fees for wire transfer and refunds.' mod='payplug'}
		</li>
	</ul>

	<h2>{l s='How to activate PayPlug' mod='payplug'}</h2>

	<h3>{l s='Step 1 : Open a PayPlug account' mod='payplug'}</h3>
	<p>
		{l s='You can sign up for free in under a minute' mod='payplug'} <a href="https://www.payplug.fr/inscription" class="button btn btn-default">{l s='SIGN UP' mod='payplug'}</a>
	</p>

	<h3>{l s='Step 2 : Set up the module' mod='payplug'}</h3>
	<p>{l s='To activate payments on your website, you must set up the module with the login information you created when you signed up to PayPlug.' mod='payplug'}</p>

	{if !isset($debugMode) || $debugMode == ''}
		<div class="notif alert information">
			{l s='The module is not yet set up: you are not accepting any payments.' mod='payplug'}
		</div>
	{else if $debugMode == true}
		<div class="notif alert alert-warning warn">
			{l s='The module is correctly set up in TEST mode.' mod='payplug'}
			<span class="underline">{l s='Note that payments you collect are not real.' mod='payplug'}</span>
		</div>
	{else}
		<div class="notif alert alert-warning warn">
			{l s='The module is correctly set up in LIVE mode. You are collecting real payments.' mod='payplug'}
		</div>
	{/if}

	{if isset($errors) && $errors|count}
		{foreach from=$errors item=error}
			<div class="notif alert alert-danger error">
				{$error|escape:'quotes':'UTF-8'}
			</div>
		{/foreach}
	{/if}

	<h4>{l s='Option 1: Set up the module in TEST mode' mod='payplug'}</h4>
	<p>
		{l s='In order to fully test the solution on your website without processing actual payments, you can use the TEST mode. Note that to come out of the TEST mode and accept real payments, you will need to return to this page and set up the module in LIVE mode.' mod='payplug'}
	</p>

	<a href="#" class="button btn btn-default debugSettings">
		{if isset($debugMode) && $debugMode == true}
			{l s='Re-set up in TEST mode' mod='payplug'}
		{else}
			{l s='Set up in TEST mode' mod='payplug'}
		{/if}
	</a>

	<form class="form-horizontal" id="debugSettings" method="POST" action="{$this_link|escape:'htmlall'}">
		<p>
			{l s='Not yet signed up? Enter an email and password on' mod='payplug'}
			<a target="_blank" href="http://www.payplug.fr/inscription">www.payplug.fr/inscription</a>
			{l s=' and return here to set up your module.' mod='payplug'}
		</p>
		<div class="form-group">
			<label for="payplug_email" class="control-label col-lg-3">{l s='Email' mod='payplug'}</label>
			<div class="margin-form col-lg-5">
				<input type="text" id="payplug_email" name="payplug_email">
			</div>
		</div>
		<div class="form-group">
			<label for="payplug_password" class="control-label col-lg-3">{l s='Password' mod='payplug'}</label>
			<div class="margin-form col-lg-5">
				<input type="password" id="payplug_password" name="payplug_password">
			</div>
		</div>
		<div class="form-group">
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<button name="debugButton" class="button btn btn-default">
					{l s='SET UP' mod='payplug'}
				</button>
			</div>
		</div>
	</form>

	<h4>{l s='Option 2: Set up the module in LIVE mode' mod='payplug'}</h4>
	<p>
		{l s='The LIVE mode is the production mode that enables you to accept real payments.' mod='payplug'}
	</p>

	<a href="#" class="button btn btn-default liveSettings">
		{if isset($debugMode) && $debugMode != 1}
			{l s='Re-set up in LIVE mode' mod='payplug'}
		{else}
			{l s='Set up in LIVE mode' mod='payplug'}
		{/if}
	</a>

	<form class="form-horizontal" id="liveSettings" method="POST" action="{$this_link|escape:'htmlall'}">
		<div class="form-group">
			<label for="payplug_email" class="control-label col-lg-3">{l s='Email' mod='payplug'}</label>
			<div class="margin-form col-lg-5">
				<input type="text" id="payplug_email" name="payplug_email">
			</div>
		</div>
		<div class="form-group">
			<label for="payplug_password" class="control-label col-lg-3">{l s='Password' mod='payplug'}</label>
			<div class="margin-form col-lg-5">
				<input type="password" id="payplug_password" name="payplug_password">
			</div>
		</div>
		<div class="form-group">
			<div class="margin-form col-lg-9 col-lg-offset-3">
				<button name="liveButton" class="button btn btn-default">
					{l s='SET UP' mod='payplug'}
				</button>
			</div>
		</div>
		<p>
			{l s='You still have questions? Take a look at our' mod='payplug'} <a target="_blank" href="http://support.payplug.fr">{l s='Support Center' mod='payplug'}</a> {l s='to contact our sales team.' mod='payplug'}
		</p>
	</form>


	<p>
		{if !isset($errorMode) || $errorMode == false}
			{l s='Debug mode not activated.' mod='payplug'}
		{else}
			{l s='Debug mode activated.' mod='payplug'}
		{/if}
		<a href="{$this_link}&error_mode">{if !isset($errorMode) || $errorMode == false}{l s='Activate' mod='payplug'}{else}{l s='Desactivate' mod='payplug'}{/if}</a>
	</p>
	{literal}
		<script type="text/javascript">
			$(function(){

				$('.debugSettings').click(function(){
					$('#debugSettings').slideToggle('slow');
					return false;
				});

				$('.liveSettings').click(function(){
					$('#liveSettings').slideToggle('slow');
					return false;
				});

			});
		</script>
	{/literal}
</div>