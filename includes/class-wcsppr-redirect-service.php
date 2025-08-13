<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCSPPR_Redirect_Service {
	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'woocommerce_thankyou', array( $this, 'enqueue_thankyou_script' ), 10, 1 );
		add_action( 'wp_ajax_wcsppr_check_order_status', array( $this, 'ajax_check_order_status' ) );
		add_action( 'wp_ajax_nopriv_wcsppr_check_order_status', array( $this, 'ajax_check_order_status' ) );
	}

	public function enqueue_thankyou_script( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$redirect_url = esc_url( get_option( 'wc_redirect_url', 'https://seusite.com/obrigado' ) );
		$wait_time    = intval( get_option( 'wc_redirect_wait_time', 5 ) ) * 1000;
		$message      = get_option( 'wc_redirect_message', __( 'Seu pagamento foi aprovado! Redirecionando...', 'wcsppr' ) );
		$nonce        = wp_create_nonce( 'wc_redirect_check_order_status' );

		wp_enqueue_script( 'wcsppr-thankyou', WCSPPR_URL . 'assets/js/thankyou-redirect.js', array(), WCSPPR_VERSION, true );
		wp_add_inline_script( 'wcsppr-thankyou', 'window.WCSPPR = ' . wp_json_encode( array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'orderId'     => absint( $order_id ),
			'orderKey'    => $order->get_order_key(),
			'nonce'       => $nonce,
			'redirectUrl' => $redirect_url,
			'waitMs'      => $wait_time,
			'message'     => $message ? wp_kses_post( $message ) : '',
		) ) . ';', 'before' );
	}

	public function ajax_check_order_status() {
		check_ajax_referer( 'wc_redirect_check_order_status', 'nonce' );
		if ( isset( $_GET['order_id'], $_GET['key'] ) ) {
			$order_id     = absint( $_GET['order_id'] );
			$provided_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
			$order        = wc_get_order( $order_id );
			if ( $order && hash_equals( $order->get_order_key(), $provided_key ) ) {
				wp_send_json( array( 'status' => $order->get_status() ) );
			}
		}
		wp_send_json( array( 'status' => 'pending' ) );
	}
}


