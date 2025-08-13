# WooCommerce Smart Post‑Purchase Redirect

Redirecione automaticamente clientes após pagamento aprovado no WooCommerce, com mensagem e tempo de espera configuráveis. Inclui criação automática de página “Pagamento aprovado” caso a URL informada não exista.

## Recursos
- Redireciona quando o pedido está `processing` ou `completed`
- Mensagem antes do redirecionamento (opcional)
- Tempo de espera em segundos
- Verificação via AJAX na página de “Obrigado” (sem reload)
- Criação de página-exemplo com layout pronto
- Nonce no AJAX e validação do order key
- Submenu no WooCommerce e link em Plugins → “Configurações”

## Instalação
1. Copie para `wp-content/plugins/wc-redirect-approvement` ou instale via ZIP
2. Ative em Plugins
3. Acesse WooCommerce → Redirecionamento Pós-Compra
4. Clique em “Criar página automaticamente” ou informe sua própria URL
5. Ajuste mensagem e tempo de espera

## Como funciona
- Na página “Obrigado”, o script verifica periodicamente o status do pedido via `admin-ajax.php`.
- Ao detectar `processing`/`completed`, exibe a mensagem (se houver), aguarda o tempo configurado e redireciona.
- Se a URL configurada for inválida/indisponível, ao salvar o plugin cria uma página-exemplo e preenche a opção automaticamente.

## Desenvolvimento
- PHP >= 7.4, WordPress >= 5.8
- Código organizado em classes em `includes/`
- Assets em `assets/`
- Auto-update via GitHub (plugin-update-checker) já integrado

## Roadmap
- Banner acessível (ARIA live) na thankyou page (substituir alert)
- Filtros para desenvolvedores (`wcsppr/redirect_url`, `wcsppr/wait_ms`, etc.)
- Opções por gateway

## Licença
GPLv2 or later. Veja `LICENSE` (ou a licença GPL no cabeçalho do plugin).


