<?php
/**
 * 2007-2020 PrestaShop
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
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wide_Pay extends PaymentModule
{
    private static $load_submit_values = false;
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'wide_pay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Wide Pay';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Wide Pay - Cartão e Boleto');
        $this->description = $this->l('As melhores soluções em recebimentos');
        $this->confirmUninstall = $this->l('Tem certeza em remover o módulo?');
        $this->limited_currencies = array('BRL');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('O curl é obrigatorio!');
            return false;
        }
        include(dirname(__FILE__) . '/sql/install.php');
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayPaymentReturn');
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (((bool)Tools::isSubmit('submitModule')) == true) {
            $errosValidation = $this->validateSubmit();
            if ($errosValidation) {
                $output .= $this->displayError($errosValidation);
                self::$load_submit_values = true;
            } else {
                $output .= $this->displayConfirmation($this->l('Dados do módulo atualizados com sucesso!'));
                $this->postProcess();
            }
        }
        $url_loja = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__;
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('url_loja', $url_loja);
        //$output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output . $this->renderForm();
    }

    private function validateSubmit()
    {
        $errors = array();
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);

            if ($key == 'WIDE_PAY_FINE' && ($this->stringToFloat($value) > 20))
                array_push($errors, $this->l('Valor informado para multa está acima do permitido'));

            if ($key == 'WIDE_PAY_INTEREST' && ($this->stringToFloat($value) > 20))
                array_push($errors, $this->l('Valor informado para juros está acima do permitido'));

        }

        return count($errors) ? $errors : false;
    }

    private function stringToFloat($value)
    {
        return (float)str_replace(',', '.', $value);
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    private function campos_extras()
    {
        //querys
        $campos[] = array('id' => '', 'campo' => 'Cliente informa manual');
        $clientes = Db::getInstance()->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "customer`");
        foreach ($clientes as $k => $v) {
            $input = _DB_PREFIX_ . 'customer.' . $v['Field'] . '';
            $campos[] = array('id' => $input, 'campo' => $input);
        }
        $enderecos = Db::getInstance()->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "address`");
        foreach ($enderecos as $k => $v) {
            $input = _DB_PREFIX_ . 'address.' . $v['Field'] . '';
            $campos[] = array('id' => $input, 'campo' => $input);
        }
        return $campos;
    }

    protected function getConfigForm()
    {
        $extras = $this->campos_extras();
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configurações'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'required' => true,
                        'desc' => $this->l('Nome que será exibido na tela de pagamento'),
                        'name' => 'WIDE_PAY_TITLE',
                        'label' => $this->l('Titulo'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'required' => true,
                        'desc' => $this->l('Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras'),
                        'name' => 'WIDE_PAY_WALLET_ID',
                        'label' => $this->l('ID da Carteira Wide Pay'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'required' => true,
                        'desc' => $this->l('Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token'),
                        'name' => 'WIDE_PAY_WALLET_TOKEN',
                        'label' => $this->l('Token da Carteira Wide Pay'),
                    ),
                    array(
                        'col' => 6,
                        'type' => 'select',
                        'required' => true,
                        'desc' => $this->l('Modifique o valor final do recebimento. Configure aqui um desconto ou acrescimo na venda.'),
                        'name' => 'WIDE_PAY_TAX_TYPE',
                        'label' => $this->l('Tipo da Taxa de Variação'),
                        'options' => array(
                            'query' => array(
                                array('id' => '0', 'name' => 'Sem alteração'),
                                array('id' => '1', 'name' => 'Acrécimo em %'),
                                array('id' => '2', 'name' => 'Acrécimo valor fixo em R$'),
                                array('id' => '3', 'name' => 'Desconto em %'),
                                array('id' => '4', 'name' => 'Desconto valor fixo em R$'),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' => 6,
                        'type' => 'text',
                        'required' => true,
                        'desc' => $this->l('O campo acima "Tipo de Taxa de Variação" será aplicado de acordo com este campo. Será adicionado um novo item na cobrança do Wide Pay. Esse item será possível verificar apenas na tela de pagamento do Wide Pay.'),
                        'name' => 'WIDE_PAY_TAX_VARIATION',
                        'label' => $this->l('Taxa de Variação'),
                    ),
                    array(
                        'col' => 2,
                        'type' => 'text',
                        'required' => true,
                        'default' => 5,
                        'desc' => $this->l('Prazo de validade em dias para o Boleto.'),
                        'name' => 'WIDE_PAY_VALIDADE',
                        'label' => $this->l('Acréscimo de Dias no Vencimento'),
                    ),
                    array(
                        'col' => 2,
                        'type' => 'text',
                        'required' => true,
                        'default' => 5,
                        'desc' => $this->l('Configuração de multa após o vencimento'),
                        'name' => 'WIDE_PAY_FINE',
                        'label' => $this->l('Configuração de Multa'),
                    ),
                    array(
                        'col' => 2,
                        'type' => 'text',
                        'required' => true,
                        'default' => 5,
                        'desc' => $this->l('Configuração de juros após o vencimento'),
                        'name' => 'WIDE_PAY_INTEREST',
                        'label' => $this->l('Configuração de Juros'),
                    ),
                    array(
                        'col' => 2,
                        'type' => 'select',
                        'required' => true,
                        'default' => 5,
                        'desc' => $this->l('Selecione uma opção.'),
                        'name' => 'WIDE_PAY_WAY',
                        'label' => $this->l('Forma de Recebimento'),
                        'options' => array(
                            'query' => array(
                                array('id' => 'boleto_cartao', 'name' => 'Boleto e Cartão'),
                                array('id' => 'boleto', 'name' => 'Boleto'),
                                array('id' => 'cartao', 'name' => 'Cartão'),
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'name' => 'WIDE_PAY_CPF_CNPJ',
                        'desc' => $this->l('Campo customizado para CPF/CNPJ'),
                        'label' => $this->l('Origem CPF/CNPJ'),
                        'options' => array(
                            'query' => $extras,
                            'id' => 'id',
                            'name' => 'campo'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'name' => 'WIDE_PAY_WAITING_PAYMENT',
//                        'desc' => $this->l(''),
                        'label' => $this->l('Status Aguardando Pagamento'),
                        'options' => array(
                            'query' => $this->GetStatusNomes(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'name' => 'WIDE_PAY_PAYED',
//                        'desc' => $this->l(''),
                        'label' => $this->l('Status Pago'),
                        'options' => array(
                            'query' => $this->GetStatusNomes(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'name' => 'WIDE_PAY_CANCELLED',
//                        'desc' => $this->l(''),
                        'label' => $this->l('Status Não Pago'),
                        'options' => array(
                            'query' => $this->GetStatusNomes(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Salvar'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        $inputs = array();
        $form = $this->getConfigForm();
        foreach ($form['form']['input'] as $v) {
            $chave = $v['name'];
            $inputs[$chave] = (self::$load_submit_values) ? Tools::getValue($chave) : Configuration::get($chave, '');
        }
        return $inputs;
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);

            if (in_array($key, array('WIDE_PAY_TAX_VARIATION', 'WIDE_PAY_INTEREST', 'WIDE_PAY_FINE')))
                $value = $this->stringToFloat($value);


            Configuration::updateValue($key, trim($value));
        }
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active)
            return;

        //verifica se e uma moeda aceita
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);
        if (in_array($currency->iso_code, $this->limited_currencies) == false) {
            return false;
        }

        $opcoes = array();

        $boleto = new PaymentOption();
        $boleto->setCallToActionText($this->trans(Configuration::get('WIDE_PAY_TITLE'), array(), 'Modules.Wide_Pay.Boleto'));
        $boleto->setAction($this->context->link->getModuleLink($this->name, 'fiscal', ['tipo' => 'boleto'], true));
        $opcoes[] = $boleto;

        return $opcoes;
    }

    public function GetStatusNomes()
    {
        global $cookie;
        return Db::getInstance()->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_state` AS a,`' . _DB_PREFIX_ . 'order_state_lang` AS b WHERE b.id_lang = "' . $cookie->id_lang . '" AND a.deleted = "0" AND a.id_order_state=b.id_order_state');
    }


    public function validar_fiscal($fiscal)
    {
        require_once(dirname(__FILE__) . '/include/class-valida-cpf-cnpj.php');
        $cpf_cnpj = new ValidaCPFCNPJ($fiscal);
        return $cpf_cnpj->valida();
    }

    public function hookdisplayAdminOrder($params)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "wide_pay_cobrancas` WHERE id_pedido = '" . (int)$params['id_order'] . "'";
        $boleto = Db::getInstance()->getRow($sql);
        $html = '';
        if (isset($boleto['link_boleto'])) {
            $html .= '<div class="panel">';
            $html .= 'C&oacute;digo da cobrança: ' . $boleto['transacao'] . ', <a href="' . $boleto['link_boleto'] . '" target="_blank"><b>clique aqui</b></a> para visualizar!';
            $html .= '</div>';
        }
        return $html;
    }

    public function hookdisplayOrderDetail($params)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "wide_pay_cobrancas` WHERE id_pedido = '" . (int)$params['order']->id . "'";
        $boleto = Db::getInstance()->getRow($sql);
        $html = '';
        if (isset($boleto['link_boleto'])) {
            $html .= '<div class="box">';
            $html .= 'C&oacute;digo da cobrança: ' . $boleto['transacao'] . ', <a href="' . $boleto['link_boleto'] . '" target="_blank"><b>clique aqui</b></a> para visualizar!';
            $html .= '</div>';
        }
        return $html;
    }

    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = new Order((int)$_GET['id_order']);

        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "wide_pay_cobrancas` WHERE id_pedido = '" . (int)$order->id . "'";
        $boleto = Db::getInstance()->getRow($sql);

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'boleto' => $boleto,
            'meio' => 'boleto',
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice(
                $params['order']->getOrdersTotalPaid(),
                new Currency($params['order']->id_currency),
                false
            ),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }
}
