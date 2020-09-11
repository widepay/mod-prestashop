<h4>{l s='Resultado de sua transação!' mod='wide_pay'}</h4>
<p>

	- {l s='Valor' mod='wide_pay'} : <span class="price"><strong>{$total}</strong></span>
	<br />- {l s='Referência' mod='wide_pay'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
    <br />- {l s='Status' mod='wide_pay'} : <span class="status"><strong>Aguardando Pagamento</strong></span>
    <br />- {l s='Forma de Pagamento' mod='wide_pay'} : <span class="venda"><strong>Boleto Banc&aacute;rio</strong></span>
    <br />- {l s='Transação' mod='wide_pay'} : <span class="venda"><strong>{$boleto['transacao']}</strong></span>

    <br><br><a class="btn btn-success" href="{$boleto['link_boleto']}" target="_blank"><i class="icon-print"></i> Imprimir Boleto de Pagamento</a>
    
	<br /><br />{l s='Enviamos um e-mail com detalhes de seu pedido.' mod='wide_pay'}
	<br /><br />{l s='Para qualquer duvida ou informação ' mod='wide_pay'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='clique aqui e entre em contato com nosso atendimento.' mod='wide_pay'}</a>
</p>

<hr />