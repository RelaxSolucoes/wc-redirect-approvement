=== WooCommerce Smart Post‑Purchase Redirect ===
Contributors: relaxsolucoes
Tags: woocommerce, checkout, redirect, thankyou, order, post-purchase
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redireciona automaticamente o cliente após a aprovação do pagamento, com tempo de espera e mensagem personalizados.

== Description ==

WooCommerce Smart Post‑Purchase Redirect permite redirecionar clientes após o pagamento aprovado (status "processing" ou "completed"), exibindo uma mensagem personalizada e aguardando um tempo configurável antes do redirecionamento.

Principais recursos:
- Define a URL de redirecionamento, mensagem e tempo de espera em segundos
- Verifica o status do pedido por AJAX na página de "Obrigado", sem recarregar
- Cria automaticamente uma página de "Pagamento aprovado" com layout base, caso a URL informada não exista
- Botão para criar a página exemplo diretamente na tela de configurações
- Segurança: nonce no AJAX e validação do order key

== Installation ==

1. Faça upload do diretório do plugin em `wp-content/plugins/` ou instale via zip
2. Ative o plugin em Plugins → Plugins instalados
3. Acesse WooCommerce → Redirecionamento Pós-Compra
4. Opcional: clique em "Criar página automaticamente" para gerar a página de "Pagamento aprovado" com exemplo de conteúdo
5. Configure a URL de redirecionamento, mensagem e tempo de espera e salve

== Frequently Asked Questions ==

= O redirecionamento ocorre sempre? =
Somente quando o pedido está com status `processing` ou `completed` na página de Obrigado do WooCommerce.

= E se eu informar uma URL inválida? =
Se a URL for inválida ou não existir, o plugin criará uma página de exemplo automaticamente e a definirá como destino. Você pode editar essa página depois.

= Posso desativar a mensagem (alert)? =
No momento é um alerta simples. Em versões futuras adicionaremos um aviso visual (banner) com ARIA. Você pode deixar o campo de mensagem vazio para não exibir.

== Screenshots ==
1. Tela de configurações no WooCommerce, com criação rápida de página

== Changelog ==

= 1.0.0 =
Inicial: redirecionamento pós-compra, AJAX com nonce e validação de order key, criação de página-exemplo, tela de configurações no WooCommerce e fallback em Configurações.

== Upgrade Notice ==

= 1.0.0 =
Primeiro lançamento público.


