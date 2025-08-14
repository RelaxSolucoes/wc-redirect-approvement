<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCSPPR_Admin_Settings {
	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_create_page' ) );
	}
	public function maybe_create_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['wcsppr_create_page'] ) ) {
			return;
		}
		check_admin_referer( 'wcsppr_create_page' );

		// Já existe URL configurada? Não recria
		if ( ! empty( get_option( 'wc_redirect_url', '' ) ) ) {
			return;
		}

		$page_id = wp_insert_post( array(
			'post_title'   => __( 'Pagamento aprovado', 'wcsppr' ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '<h2>' . esc_html__( 'Parabéns, seu pagamento foi aprovado!', 'wcsppr' ) . '</h2><p>' . esc_html__( 'Você receberá um e-mail com os dados de acesso. Verifique também a caixa de promoções e spam.', 'wcsppr' ) . '</p>',
		) );

		if ( ! is_wp_error( $page_id ) && $page_id ) {
			$permalink = get_permalink( $page_id );
			if ( $permalink ) {
				update_option( 'wc_redirect_url', esc_url_raw( $permalink ) );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wc-redirect-settings' ) );
		exit;
	}

	public function add_settings_page() {
		if ( class_exists( 'WooCommerce' ) ) {
			add_submenu_page(
				'woocommerce',
				__( 'Pós-Compra: Redirecionamento', 'wcsppr' ),
				__( 'Redirecionamento Pós-Compra', 'wcsppr' ),
				'manage_options',
				'wc-redirect-settings',
				array( $this, 'render_settings_page' )
			);
		} else {
			add_options_page(
				__( 'Redirecionamento Pós-Compra', 'wcsppr' ),
				__( 'Redirecionamento Pós-Compra', 'wcsppr' ),
				'manage_options',
				'wc-redirect-settings',
				array( $this, 'render_settings_page' )
			);
		}
	}

	public function register_settings() {
		register_setting( 'wc_redirect_settings_group', 'wc_redirect_url', array( 'sanitize_callback' => array( $this, 'sanitize_redirect_url' ) ) );
		register_setting( 'wc_redirect_settings_group', 'wc_redirect_wait_time', array( 'sanitize_callback' => array( $this, 'sanitize_wait_time' ) ) );
		register_setting( 'wc_redirect_settings_group', 'wc_redirect_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Aviso: se URL não existir, criaremos uma página exemplo
		add_settings_error(
			'wc_redirect_settings_group',
			'wcsppr_info_create_page',
			__( 'Se a página informada não existir, criaremos uma página de exemplo automaticamente. Você poderá editá-la depois.', 'wcsppr' ),
			'info'
		);

		add_settings_section( 'wc_redirect_main_section', __( 'Configurações principais', 'wcsppr' ), null, 'wc-redirect-settings' );

		add_settings_field( 'wc_redirect_url', __( 'URL de Redirecionamento', 'wcsppr' ), function () {
			$value = get_option( 'wc_redirect_url', '' );
			echo '<input type="url" name="wc_redirect_url" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="https://exemplo.com/obrigado">';
		}, 'wc-redirect-settings', 'wc_redirect_main_section' );

		add_settings_field( 'wc_redirect_wait_time', __( 'Tempo de Espera (segundos)', 'wcsppr' ), function () {
			$value = get_option( 'wc_redirect_wait_time', 5 );
			echo '<input type="number" name="wc_redirect_wait_time" value="' . esc_attr( $value ) . '" class="small-text" min="0"> ' . esc_html__( 'segundos', 'wcsppr' );
		}, 'wc-redirect-settings', 'wc_redirect_main_section' );

		add_settings_field( 'wc_redirect_message', __( 'Mensagem antes do Redirecionamento', 'wcsppr' ), function () {
			$value = get_option( 'wc_redirect_message', __( 'Seu pagamento foi aprovado! Redirecionando...', 'wcsppr' ) );
			echo '<input type="text" name="wc_redirect_message" value="' . esc_attr( $value ) . '" class="regular-text">';
		}, 'wc-redirect-settings', 'wc_redirect_main_section' );
	}

	public function sanitize_wait_time( $value ) {
		return max( 0, intval( $value ) );
	}

	public function sanitize_redirect_url( $value ) {
		$raw = trim( (string) $value );
		// Campo vazio: cria a página exemplo durante o fluxo de sanitização
		if ( $raw === '' ) {
			$created = class_exists( 'WCSPPR_Page_Factory' ) ? WCSPPR_Page_Factory::ensure_sample_page() : '';
			if ( $created ) {
				add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_created', __( 'Criamos uma página de exemplo e definimos como destino.', 'wcsppr' ), 'updated' );
				return esc_url_raw( $created );
			}
			return '';
		}
		// Aceita caminhos relativos como "/obrigado" ou "obrigado" e converte para URL absoluta
		if ( '/' === substr( $raw, 0, 1 ) || ! preg_match( '#^https?://#i', $raw ) ) {
			$raw = home_url( '/' . ltrim( $raw, '/' ) );
		}
		$value = esc_url_raw( $raw );
		// Se for uma URL válida, verifica se já há página correspondente; se não houver, cria com o slug informado
		if ( ! empty( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$post_id = url_to_postid( $value );
			if ( ! $post_id ) {
				// Fallback por slug/slug final
				$path = parse_url( $value, PHP_URL_PATH );
				$path = is_string( $path ) ? trim( $path, '/' ) : '';
				$segments = $path ? array_values( array_filter( explode( '/', $path ) ) ) : array();
				$slug = $segments ? end( $segments ) : '';
				if ( $slug ) {
					$existing = get_page_by_path( $slug );
					if ( $existing instanceof \WP_Post ) {
						return $value;
					}
				}
				$created = class_exists( 'WCSPPR_Page_Factory' ) && method_exists( 'WCSPPR_Page_Factory', 'ensure_page_for_url' )
					? WCSPPR_Page_Factory::ensure_page_for_url( $value )
					: '';
				if ( $created ) {
					add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_created_for_input', __( 'A página informada não existia. Criamos automaticamente e definimos como destino.', 'wcsppr' ), 'updated' );
					return esc_url_raw( $created );
				}
			}
			return $value;
		}
		// Caso contrário, cria a página com o slug derivado da URL desejada
		$created = class_exists( 'WCSPPR_Page_Factory' ) && method_exists( 'WCSPPR_Page_Factory', 'ensure_page_for_url' )
			? WCSPPR_Page_Factory::ensure_page_for_url( $raw )
			: ( class_exists( 'WCSPPR_Page_Factory' ) ? WCSPPR_Page_Factory::ensure_sample_page() : '' );
		if ( $created ) {
			add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_created', __( 'A URL informada não existia. Criamos uma página de exemplo e definimos como destino.', 'wcsppr' ), 'updated' );
			return esc_url_raw( $created );
		}
		add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_invalid', __( 'Não foi possível validar a URL informada. Ajuste-a ou deixe em branco para criarmos a página de exemplo.', 'wcsppr' ), 'error' );
		return get_option( 'wc_redirect_url', '' );
	}

	public function render_settings_page() {
		wp_enqueue_style( 'wcsppr-admin', WCSPPR_URL . 'assets/css/admin.css', array(), WCSPPR_VERSION );
		// Se o campo foi enviado vazio, cria a página exemplo e preenche automaticamente
		if ( isset( $_POST['wc_redirect_url'] ) ) {
			$raw_post = trim( (string) wp_unslash( $_POST['wc_redirect_url'] ) );
			if ( $raw_post === '' ) {
				$created = class_exists( 'WCSPPR_Page_Factory' ) && method_exists( 'WCSPPR_Page_Factory', 'ensure_sample_page' )
					? WCSPPR_Page_Factory::ensure_sample_page()
					: '';
				if ( $created ) {
					update_option( 'wc_redirect_url', esc_url_raw( $created ) );
					add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_created', __( 'Criamos uma página de exemplo e definimos como destino.', 'wcsppr' ), 'updated' );
				}
			}
			// Para valor preenchido: se a página não existir, cria com o slug informado e já define como destino
			if ( $raw_post !== '' ) {
				$normalized = ( '/' === substr( $raw_post, 0, 1 ) || ! preg_match( '#^https?://#i', $raw_post ) )
					? home_url( '/' . ltrim( $raw_post, '/' ) )
					: $raw_post;
				$normalized = esc_url_raw( $normalized );
				$post_id = url_to_postid( $normalized );
				if ( ! $post_id ) {
					$created = class_exists( 'WCSPPR_Page_Factory' ) && method_exists( 'WCSPPR_Page_Factory', 'ensure_page_for_url' )
						? WCSPPR_Page_Factory::ensure_page_for_url( $normalized )
						: '';
					if ( $created ) {
						update_option( 'wc_redirect_url', esc_url_raw( $created ) );
						add_settings_error( 'wc_redirect_settings_group', 'wcsppr_url_created_for_input', __( 'A página informada não existia. Criamos automaticamente e definimos como destino.', 'wcsppr' ), 'updated' );
					}
				}
			}
		}
		?>
		<div class="wrap wcsppr-settings wpwevo-panel" style="max-width: none;">
			<h1>⚙️ <?php echo esc_html__( 'Redirecionamento Pós-Compra - Configurações', 'wcsppr' ); ?></h1>
			<?php settings_errors( 'wc_redirect_settings_group' ); ?>
			<?php if ( empty( get_option( 'wc_redirect_url', '' ) ) ) : ?>
				<div class="wpwevo-cta-box">
					<div class="wpwevo-cta-content">
						<h3 class="wpwevo-cta-title">📄 <?php echo esc_html__( 'Ainda não tem a página de “Pagamento aprovado”?', 'wcsppr' ); ?></h3>
						<p class="wpwevo-cta-description">💡 <?php echo esc_html__( 'Crie automaticamente com uma mensagem padrão e já defina como destino do redirecionamento.', 'wcsppr' ); ?></p>
					</div>
					<a class="wpwevo-cta-button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-redirect-settings&wcsppr_create_page=1' ), 'wcsppr_create_page' ) ); ?>">
						<?php echo esc_html__( 'Criar página automaticamente', 'wcsppr' ); ?>
					</a>
				</div>
			<?php endif; ?>
			<div class="wcsppr-card-outer">
				<div class="wcsppr-card-inner">
					<div style="display:flex; align-items:center; margin-bottom:15px;">
						<div style="background:#a8edea; color:#2d3748; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; margin-right:15px;">🔁</div>
						<h3 style="margin:0; color:#2d3748; font-size:18px;"><?php echo esc_html__( 'Configurações principais', 'wcsppr' ); ?></h3>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( 'wc_redirect_settings_group' ); ?>
						<div class="wcsppr-field">
							<label for="wc_redirect_url"><?php echo esc_html__( 'URL de Redirecionamento', 'wcsppr' ); ?></label>
							<input type="url" id="wc_redirect_url" name="wc_redirect_url" value="<?php echo esc_attr( get_option( 'wc_redirect_url', '' ) ); ?>" placeholder="https://exemplo.com/obrigado" />
						</div>
						<div class="wcsppr-field">
							<label for="wc_redirect_wait_time"><?php echo esc_html__( 'Tempo de Espera (segundos)', 'wcsppr' ); ?></label>
							<input type="number" id="wc_redirect_wait_time" name="wc_redirect_wait_time" min="0" value="<?php echo esc_attr( get_option( 'wc_redirect_wait_time', 5 ) ); ?>" />
						</div>
						<div class="wcsppr-field">
							<label for="wc_redirect_message"><?php echo esc_html__( 'Mensagem antes do Redirecionamento', 'wcsppr' ); ?></label>
							<input type="text" id="wc_redirect_message" name="wc_redirect_message" value="<?php echo esc_attr( get_option( 'wc_redirect_message', __( 'Seu pagamento foi aprovado! Redirecionando...', 'wcsppr' ) ) ); ?>" />
						</div>
			<div class="wcsppr-actions">
							<?php submit_button( __( 'Salvar Configurações', 'wcsppr' ), 'primary', 'submit', false ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	private function url_exists( string $url ): bool {
		$response = wp_remote_head( $url, array( 'timeout' => 5, 'redirection' => 1 ) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 400;
	}
}


