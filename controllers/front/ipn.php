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

class Wide_PayIpnModuleFrontController extends ModuleFrontController
{

    public $display_column_left = false;
    public $display_column_higt = false;
    public $display_header = false;
    public $display_header_javascript = false;
    public $display_footer = false;
    public $ssl = true;

    public function postProcess()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["notificacao"])) {
            $notificacao = $this->api(intval(Configuration::get('WIDE_PAY_WALLET_ID')), trim(Configuration::get('WIDE_PAY_WALLET_TOKEN')), 'recebimentos/cobrancas/notificacao', array(
                'id' => $_POST["notificacao"] // ID da notificação recebido do Wide Pay via POST
            ));
            if ($notificacao->sucesso) {
                $order_id = (int)$notificacao->cobranca['referencia'];
                $transactionID = $notificacao->cobranca['id'];
                $status = $notificacao->cobranca['status'];
                if ($status == 'Baixado' || $status == 'Recebido' || $status == 'Recebido manualmente') {
                    $pedido = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "orders` WHERE module = 'wide_pay' AND id_cart = '" . (int)$order_id . "' AND current_state = '" . Configuration::get('WIDE_PAY_WAITING_PAYMENT') . "'");
                    $order = new Order($pedido['id_order']);

                    if ($order) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState(Configuration::get('WIDE_PAY_PAYED'), $order);
                        $history->addWithemail(true, null);
                        echo 'NOTIFICAÇÃO RECEBIDA';
                    }
                }


            } else {
                echo $notificacao->erro; // Erro
                exit();
            }
        }
        exit();

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
}
