<?php
/**
 * Plugin Name: WooCommerce Smart Post‑Purchase Redirect
 * Description: Redireciona automaticamente o cliente após a aprovação do pagamento, com opções de tempo de espera e mensagem.
 * Version: 1.0.0
 * Author: Relax Soluções
 * Author URI: https://relaxsolucoes.online/
 * Text Domain: wcsppr
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/relaxsolucoes/wc-redirect-approvement
 */

// Impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
if (!defined('WCSPPR_VERSION')) {
    define('WCSPPR_VERSION', '1.0.0');
}
if (!defined('WCSPPR_URL')) {
    define('WCSPPR_URL', plugin_dir_url(__FILE__));
}

// I18n
add_action('init', function() {
    load_plugin_textdomain('wcsppr', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Verifica dependência do WooCommerce
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce Smart Post‑Purchase Redirect requer WooCommerce ativo.', 'wcsppr') . '</p></div>';
        });
    }
});

// Carrega classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcsppr-redirect-service.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcsppr-page-factory.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-wcsppr-admin-settings.php';

// Inicializa
add_action('plugins_loaded', function() {
    WCSPPR_Redirect_Service::get_instance();
    WCSPPR_Admin_Settings::get_instance();
});

// ===== AUTO-UPDATE VIA GITHUB (plugin-update-checker) =====
add_action('init', function() {
    // Carrega a lib somente se ainda não tiver sido carregada por outro plugin
    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        $local_lib = plugin_dir_path(__FILE__) . 'lib/plugin-update-checker/plugin-update-checker.php';
        if (file_exists($local_lib)) {
            require_once $local_lib;
        }
    }

    if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/relaxsolucoes/wc-redirect-approvement',
            __FILE__,
            'wc-redirect-approvement'
        );
        if (method_exists($updateChecker, 'setBranch')) {
            $updateChecker->setBranch('main');
        }
    }
});
// ===== FIM AUTO-UPDATE =====

// Link rápido para Configurações na lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_url = admin_url('admin.php?page=wc-redirect-settings');
    array_unshift($links, '<a href="' . esc_url($settings_url) . '">' . esc_html__('Configurações', 'wcsppr') . '</a>');
    return $links;
});

// (Removido) criação automática na ativação. Agora criamos ao salvar nas configurações ou via botão.

// Sanitizador legado (mantido por compatibilidade, não mais usado diretamente)
function wc_redirect_sanitize_wait_time($value) {
    return max(0, intval($value));
}
