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
<LINK rel="stylesheet" type="text/css" href="{$this_path|escape:'htmlall'}css/admin.css">
<div id="payplug_admin">
    <img src="{$this_path|escape:'htmlall'}img/logoPayPlug.png" alt="logoPayPlug" >
    <h1>{l s='Accepter la carte n\'a jamais été aussi simple' mod='payplug'}</h1>
    <h2>{l s='Avez-vous ouvert un compte PayPlug ?' mod='payplug'}</h2>
    <p>{l s='Vous pouvez créer un compte en moins d\'une minute en cliquant sur ce lien : ' mod='payplug'}<a href="https://www.payplug.fr/inscription?origin=PrestashopConfig" target="_blank">{l s='https://payplug.fr/inscription' mod='payplug'}</a>{l s='.' mod='payplug'}</p>
    <p>{l s='PayPlug propose les fonctionnalités suivantes :' mod='payplug'}</p>
    <ul>
        <li>{l s='Page de paiement personnalisée' mod='payplug'}</li>
        <li>{l s='Retour post-paiement et confirmation par email et IPN' mod='payplug'}</li>
        <li>{l s='Interface de suivi et de gestion des transactions' mod='payplug'}</li>
        <li>{l s='Possibilité de rembourser le payeur sans frais' mod='payplug'}</li>
        <li>{l s='Disponibilité des fonds sur le compte bancaire sous 2 à 5 jours ouvrés' mod='payplug'}</li>
        <li>{l s='Support dédié' mod='payplug'}</li>
        <li>{l s='Cartes acceptées : CB, Visa, MasterCard françaises et internationales' mod='payplug'}</li>
    </ul>
    <p>{l s='Pour toute question, vous pouvez contacter le support PayPlug depuis l\'espace Assistance : ' mod='payplug'}<a href="http://support.payplug.fr" target="_blank">{l s='http://support.payplug.fr' mod='payplug'}</a>{l s='.' mod='payplug'}</p>
    <h2>{l s='Connectez ce module à votre compte PayPlug' mod='payplug'}</h2>
    {if isset($moduleInstalled) && $moduleInstalled}
        <div class="conf">{l s='Votre module est correctement connecté !' mod='payplug'}</div>
        <p>
            {l s='Paramètres liés à votre compte PayPlug :' mod='payplug'}<br>
            - {l s='Montant minimum autorisé : ' mod='payplug'}{$minAmount|intval}&nbsp;€<br>
            - {l s='Montant maximum autorisé : ' mod='payplug'}{$maxAmount|intval}&nbsp;€<br>
            - {l s='Devises supportées : ' mod='payplug'}{$currencies|escape}<br>
        </p>
        {literal}
	        <script type="text/javascript">
	            function toggleDisplayForm() {
	                var display = document.getElementById('autoConfigForm').style.display;
	                display = (display == 'none') ? 'block' : 'none';
	                document.getElementById('autoConfigForm').style.display = display;
	            }
	        </script>
        {/literal}
        <p><button onclick="toggleDisplayForm();" class="updateButton">Mettre à jour les données de votre compte</button></p>
    {/if}
		{if not extension_loaded("openssl")}
            <br /><div class="error">Echec: librairie <strong>OpenSSL</strong> manquante. Nous vous invitons à demander à votre hébergeur d'installer la librairie <strong>OpenSSL</strong> sur votre serveur, puis à réessayer de configurer le module PayPlug sur Prestashop.</div><br />
		{elseif isset($noCurl)}
            <br /><div class="error">Echec: librairie <strong>Curl</strong> manquante. Nous vous invitons à demander à votre hébergeur d'installer la librairie <strong>Curl</strong> sur votre serveur, puis à réessayer de configurer le module PayPlug sur Prestashop.</div><br />
		{else}
        {if isset($errorCurl)}
            <div class="error">Il est actuellement impossible de se connecter à votre compte PayPlug. Vous pouvez nous contacter à <a href="mailto:support@payplug.fr">support@payplug.fr</a>, en indiquant le code d'erreur {$errorCurl|escape}.</div>
        {/if}
        {if isset($authorizationSuccess) && !$authorizationSuccess}
            <div class="error">Votre email ou votre mot de passe est incorrect.</div>
        {/if}
				<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall'}" id="autoConfigForm" {if not $displayForm}style="display: none;"{/if}>				
	        <p>{l s='Entrez vos identifiants PayPlug (email et mot de passe) ci-dessous :' mod='payplug'}</p>
	        <label for="email">{l s='Email :' mod='payplug'}</label>
	        <input type="text" name="email" id="email" value="{if isset($email)}{$email}{/if}"><br>
	        <label for="password">{l s='Mot de passe :' mod='payplug'}</label>
	        <input type="password" name="password" id="password"><br>
	        <input type="submit" value="{if isset($moduleInstalled) && $moduleInstalled}{l s='Mettre à jour les données de votre compte' mod='payplug'}{else}{l s='Connecter le module' mod='payplug'}{/if}" id="submitButton" />
				</form>
		{/if}
</div>
