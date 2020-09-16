#  Módulo PrestaShop para Wide Pay
Módulo desenvolvido para integração entre o sistema PrestaShop e Wide Pay. Com o módulo é possível gerar cobrança para pagamento e liquidação automática pelo Wide Pay após o recebimento.

* **Versão atual:** 1.0.0
* **Versão PrestaShop Testada:** 1.7.6.7
* **Acesso Wide Pay**: [Abrir Link](https://www.widepay.com/acessar)
* **API Wide Pay**: [Abrir Link](https://widepay.github.io/api/index.html)
* **Módulos Wide Pay**: [Abrir Link](https://widepay.github.io/api/modulos.html)

# Instalação Plugin

1. Para a instalação do plugin realize o download pelo link: https://github.com/widepay/mod-prestashop
2. Após o download concluído, renomei o arquivo para: wide-pay.zip
3. Acesse o menu de módulos no PrestaShop, clique em "Enviar um módulo". Selecione o arquivo *wide-pay.zip*.
4. Clique em Ativar.

# Configuração do Plugin
Lembre-se que para esta etapa, o plugin deve estar instalado e ativado no PrestaShop.

A configuração do Plugin Wide Pay pode ser encontrada no menu: PrestaShop -> Module Manager -> Wide Pay -> Configurar.


|Campo|Obrigatório|Descrição|
|--- |--- |--- |
|Titulo|**Sim**|Nome que será exibido na tela de pagamento|]
|ID da Carteira Wide Pay |**Sim** |Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras|
|Token da Carteira Wide Pay|**Sim**|Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token|
|Tipo da Taxa de Variação|Não|Modifique o valor final do recebimento. Configure aqui um desconto ou acrescimo na venda.|
|Taxa de Variação|Não|O campo acima "Tipo de Taxa de Variação" será aplicado de acordo com este campo. Será adicionado um novo item na cobrança do Wide Pay. Esse item será possível verificar apenas na tela de pagamento do Wide Pay.|
|Acréscimo de Dias no Vencimento|Não|Número em dias para o vencimento do Boleto.|
|Configuração de Multa|Não|Configuração de multa após o vencimento. Valor em porcentagem|
|Configuração de Juros|Não|Configuração de juros após o vencimento. Valor em porcentagem|
|Forma de Recebimento|Não|Selecione entre Boleto, Cartão|
|Origem CPF/CNPJ|Não|Campo customizado para CPF/CNPJ|
|Status Aguardando Pagamento|Não|Status do sistema para aguardando pagamento|
|Status Pago|Não|Status do sistema para fatura paga|
|Status Não Pago|Não|Status do sistema para faturas não pagas|
