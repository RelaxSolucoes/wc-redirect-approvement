<?php
// Se o arquivo for chamado diretamente, aborta.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Remove opções do plugin
delete_option('wc_redirect_url');
delete_option('wc_redirect_wait_time');
delete_option('wc_redirect_message');


