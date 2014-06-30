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
<p><strong>
{if $state == 'waiting'}
    {l s='Votre paiement est actuellement en cours de traitement par PayPlug, et devrait être validé dans quelques secondes.' mod='payplug'}<br>
    {l s='Un email va vous être envoyé pour confirmer la transaction.' mod='payplug'}
{elseif $state == 'paid'}
    {l s='Votre paiement a été correctement effectué !' mod='payplug'}<br>
    {l s='Un email vous a été envoyé pour confirmer la transaction.' mod='payplug'}
{/if}

</strong></p>
<p>
{l s='Recapitulatif de votre commande :' mod='payplug'}<br>
<ul>
{if isset($reference)}
    <li>{l s='Reference : ' mod='payplug'}{$reference|escape}</li>
{/if}
    <li>{l s='Montant total : ' mod='payplug'}{$totalPaid|escape} €</li>
</ul>
</p>