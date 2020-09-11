<?php
/**
 * 2007-2015 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Wide_PayCobrancaModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_higt = false;
    public $ssl = true;

    public function SomenteNumero($a)
    {
        return preg_replace('/\D/', '', $a);
    }

    public function cpf_cnpj()
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "wide_pay` WHERE id_cliente = '" . (int)Context::getContext()->customer->id . "'";
        $row = Db::getInstance()->getRow($sql);
        if (isset($row['fiscal']) && !empty($row['fiscal'])) {
            return $row['fiscal'];
        } else {
            return '';
        }
    }

    public function CleanString($str)
    {
        $replaces = array(
            'S' => 'S', 's' => 's', 'Z' => 'Z', 'z' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        );

        return preg_replace('/[^0-9A-Za-z;,.\- ]/', '', strtoupper(strtr(trim($str), $replaces)));
    }

    public function gerar_cobranca()
    {
        $carrinho = Context::getContext()->cart;
        $total = $carrinho->getOrderTotal(true, 3);
        $frete = $carrinho->getOrderTotal(true, 5);
        $produtos_array = $carrinho->getProducts();
        $desconto = $carrinho->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $endereco_id = $carrinho->id_address_invoice;
        $endereco = new Address((int)($endereco_id));
        $estado = new State((int)($endereco->id_state));
        $cliente = new Customer($carrinho->id_customer);

        $tax = Configuration::get('WIDE_PAY_TAX_VARIATION');
        $tax_type = Configuration::get('WIDE_PAY_TAX_TYPE');


        //produtos
        $items = [];
        $i = 1;
        foreach ($produtos_array as $key => $value) {
            $items[$i]['descricao'] = $this->CleanString(utf8_encode((isset($value['product_name']) ? $value['product_name'] : $value['name'])));
            $items[$i]['valor'] = number_format($value['price_wt'], 2, '.', '');
            $items[$i]['quantidade'] = $value['quantity'];
            $i++;
        }
        if (isset($frete) && $frete > 0) {
            $items[$i]['descricao'] = 'Frete';
            $items[$i]['valor'] = number_format($frete, 2, '.', '');
            $items[$i]['quantidade'] = 1;
            $i++;
        }
        if (isset($desconto) && $desconto > 0) {
            $items[$i]['descricao'] = 'Desconto';
            $items[$i]['valor'] = number_format($desconto, 2, '.', '') * (-1);
            $items[$i]['quantidade'] = 1;
            $i++;
        }
        $variableTax = $this->getVariableTax($tax, $tax_type, $total);
        if (isset($variableTax)) {
            list($description, $total) = $variableTax;
            $items[$i]['descricao'] = $description;
            $items[$i]['valor'] = $total;
            $items[$i]['quantidade'] = 1;
        }


        $invoiceDuedate = new DateTime(date('Y-m-d'));
        $invoiceDuedate->modify('+' . intval(Configuration::get('WIDE_PAY_VALIDADE')) . ' day');
        $invoiceDuedate = $invoiceDuedate->format('Y-m-d');
        $tel = !empty($endereco->phone) ? $endereco->phone : $endereco->phone_mobile;
        $fiscal = preg_replace('/\D/', '', $this->cpf_cnpj());
        list($widepayCpf, $widepayCnpj, $widepayPessoa) = $this->getFiscal($fiscal);

        $widepayData = array(
            'forma' => $this->widepay_get_formatted_way(Configuration::get('WIDE_PAY_WAY')),
            'referencia' => $carrinho->id,
            'notificacao' => Context::getContext()->link->getModuleLink('wide_pay', 'ipn', array('ajax' => 'true'), true),
            'vencimento' => $invoiceDuedate,
            'cliente' => (preg_replace('/\s+/', ' ', $endereco->firstname . ' ' . $endereco->lastname)),
            'telefone' => preg_replace('/\D/', '', $tel),
            'email' => $cliente->email,
            'pessoa' => $widepayPessoa,
            'cpf' => $widepayCpf,
            'cnpj' => $widepayCnpj,
            'enviar'=> 'E-mail',
            'endereco' => array(
                'rua' => $endereco->address1,
                'complemento' => $endereco->address2,
                'cep' => preg_replace('/\D/', '', $endereco->postcode),
                'estado' => $estado->iso_code,
                'cidade' => $endereco->city
            ),
            'itens' => $items,
            'boleto' => array(
                'gerar' => 'Nao',
                'desconto' => 0,
                'multa' => doubleval(Configuration::get('WIDE_PAY_FINE')),
                'juros' => doubleval(Configuration::get('WIDE_PAY_INTEREST'))
            ));


        return $this->api(intval(Configuration::get('WIDE_PAY_WALLET_ID')), Configuration::get('WIDE_PAY_WALLET_TOKEN'), 'recebimentos/cobrancas/adicionar', $widepayData);

    }

    private function getVariableTax($tax, $taxType, $total)
    {
        //Formatação para calculo ou exibição na descrição
        $widepayTaxDouble = number_format((double)$tax, 2, '.', '');
        $widepayTaxReal = number_format((double)$tax, 2, ',', '');
        // ['Description', 'Value'] || Null

        if ($taxType == 1) {//Acrécimo em Porcentagem
            return array(
                'Referente a taxa adicional de ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2));
        } elseif ($taxType == 2) {//Acrécimo valor Fixo
            return array(
                'Referente a taxa adicional de R$' . $widepayTaxReal,
                ((double)$widepayTaxDouble));
        } elseif ($taxType == 3) {//Desconto em Porcentagem
            return array(
                'Item referente ao desconto: ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2) * (-1));
        } elseif ($taxType == 4) {//Desconto valor Fixo
            return array(
                'Item referente ao desconto: R$' . $widepayTaxReal,
                $widepayTaxDouble * (-1));
        }
        return null;
    }

    private function widepay_get_formatted_way($way)
    {
        $key_value = array(
            'cartao' => 'Cartão',
            'boleto' => 'Boleto',
            'boleto_cartao' => 'Cartão,Boleto',

        );
        return $key_value[$way];
    }

    private function getFiscal($cpf_cnpj)
    {
        $cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
        // [CPF, CNPJ, FISICA/JURIDICA]
        if (strlen($cpf_cnpj) == 11) {
            return array($cpf_cnpj, '', 'Física');
        } else {
            return array('', $cpf_cnpj, 'Jurídica');
        }
    }

    private function api($wallet, $token, $local, $params = array())
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.widepay.com/v1/' . trim($local, '/'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, trim($wallet) . ':' . trim($token));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('WP-API: SDK-PHP'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        $exec = curl_exec($curl);
        curl_close($curl);
        if ($exec) {
            $requisicao = json_decode($exec, true);
            if (!is_array($requisicao)) {
                $requisicao = array(
                    'sucesso' => false,
                    'erro' => 'Não foi possível tratar o retorno.'
                );
                if ($exec) {
                    $requisicao['retorno'] = $exec;
                }
            }
        } else {
            $requisicao = array(
                'sucesso' => false,
                'erro' => 'Sem comunicação com o servidor.'
            );
        }

        return (object)$requisicao;
    }

    public function postProcess()
    {

        $carrinho = Context::getContext()->cart;
        $response = $this->gerar_cobranca();

        if ($response->sucesso) {

            $link_boleto = $response->link;
            $transacao = $response->id;

            //tenta criar
            try {

                //vars
                $extraVars = array(
                    '{segunda_via}' => $link_boleto,
                    '{link_boleto}' => $link_boleto,
                );

                //cria o pedido
                $cliente = Context::getContext()->customer;
                $frete = $carrinho->getOrderTotal(true, 5);
                $total = $carrinho->getOrderTotal(true, Cart::BOTH);
                $this->module->validateOrder($carrinho->id, Configuration::get('WIDE_PAY_WAITING_PAYMENT'), $total, $this->module->displayName, null, $extraVars, null, false, $cliente->secure_key);

                //consulta o pedido criado
                $order = new Order($this->module->currentOrder);

                //cria um log para o pedido
                $message = "-----------------\nID Wide Pay: " . $transacao . "\nID Cobrança: " . str_pad($carrinho->id, 11, "0", STR_PAD_LEFT) . "\nStatus: Aguardando Pagamento\n-----------------";
                $msg = new Message();
                $message = strip_tags($message, '<br>');
                if (Validate::isCleanHtml($message)) {
                    $msg->message = $message;
                    $msg->id_order = intval($order->id);
                    $msg->private = 1;
                    $msg->add();
                }

                //cria o registro de banco de dados 
                Db::getInstance()->execute("INSERT INTO `" . _DB_PREFIX_ . "wide_pay_cobrancas` (`id_pedido`, `transacao`, `status`, `link_boleto`) VALUES ('" . $this->module->currentOrder . "', '" . $transacao . "', 'aguardando', '" . $link_boleto . "');");

                //url de confirmacao
                $confirmar = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . (int)($carrinho->id) . '&id_module=' . (int)($this->module->id) . '&id_order=' . $this->module->currentOrder . '&meio=boleto&transacao=' . $transacao . '&key=' . $cliente->secure_key;
                Tools::redirect($confirmar);
                exit;

            } catch (Exception $e) {
                $erro = 'Erro ao criar cobrança Wide Pay, ID: ' . $carrinho->id . '! - ' . $e->getMessage();
                PrestaShopLogger::addLog($erro, 2);
                $this->context->smarty->assign(array(
                    'cart_id' => Context::getContext()->cart->id,
                    'cliente' => Context::getContext()->customer,
                    'erro' => $erro,
                    'secure_key' => Context::getContext()->customer->secure_key,
                ));
                return $this->setTemplate('module:wide_pay/views/templates/front/error.tpl');
            }

        } else {
            $validacao = '';

            if ($response->erro) {
                $validacao = $response->erro . '<br>';
            }

            if (isset($response->validacao)) {
                foreach ($response->validacao as $item) {
                    $validacao .= '- ' . strtoupper($item['id']) . ': ' . $item['erro'] . '<br>';
                }
                $validacao = 'Erro Validação: ' . $validacao;
            }

            PrestaShopLogger::addLog($validacao, 2);
            PrestaShopLogger::addLog(print_r($response, true), 2);
            $this->context->smarty->assign(array(
                'cart_id' => Context::getContext()->cart->id,
                'cliente' => Context::getContext()->customer,
                'erro' => $validacao,
                'secure_key' => Context::getContext()->customer->secure_key,
            ));
            return $this->setTemplate('module:wide_pay/views/templates/front/error.tpl');
        }
    }
}
