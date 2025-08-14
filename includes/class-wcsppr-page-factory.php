<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCSPPR_Page_Factory {
	/**
	 * Garantir que exista uma página de "Pagamento aprovado" com visual pré-formatado.
	 * Retorna a URL (permalink) criada.
	 */
    public static function ensure_sample_page( string $desired_slug = '' ): string {
        $postarr = array(
            'post_title'   => __( 'Pagamento aprovado', 'wcsppr' ),
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => self::build_sample_content(),
        );
        if ( ! empty( $desired_slug ) ) {
            $postarr['post_name'] = sanitize_title( $desired_slug );
        }
        $page_id = wp_insert_post( $postarr );
		if ( is_wp_error( $page_id ) || empty( $page_id ) ) {
			return '';
		}
		$permalink = get_permalink( $page_id );
		return $permalink ? (string) $permalink : '';
	}

	/**
	 * Constrói o HTML do conteúdo da página baseando-se no layout do JSON e imagem fornecidos.
	 */
	private static function build_sample_content(): string {
		$image_url = self::import_check_image();
		$members_url = home_url( '/minha-conta/' );
		$heading = esc_html__( 'Parabéns seu pagamento foi aprovado!', 'wcsppr' );
		$sub1 = esc_html__( 'Sua compra foi realizada com sucesso!', 'wcsppr' );
		$sub2 = esc_html__( 'Verifique o acesso no seu e-mail, procure pelo email com o título: “seus dados de acesso”', 'wcsppr' );
		$ps = esc_html__( 'Ps: verifique também na sua caixa de promoções e spam do seu email.', 'wcsppr' );
		$contact = esc_html__( 'ou entre em contato contato@seudominio.com', 'wcsppr' );
		$btn = esc_html__( 'Entrar na área de membros', 'wcsppr' );

		$imgHtml = $image_url ? '<img src="' . esc_url( $image_url ) . '" alt="ok" style="width:80px;height:80px;display:block;margin:0 auto 24px;border-radius:50%;" />' : '';

		$html = '<div style="max-width:980px;margin:40px auto;padding:40px 20px;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.06);text-align:center;">
			' . $imgHtml . '
			<h1 style="color:#13C039;font-family:Montserrat,Arial,sans-serif;font-weight:800;font-size:44px;line-height:1.2;margin:0 0 16px;">' . $heading . '</h1>
			<p style="margin:10px 0 18px;font-size:18px;color:#111;">' . $sub1 . '</p>
			<p style="margin:10px 0 18px;font-size:16px;color:#111;">' . $sub2 . '</p>
			<p style="margin:18px 0;font-size:16px;color:#FF0202;">' . $ps . '</p>
			<p style="margin:18px 0;font-size:16px;color:#111;">' . $contact . '</p>
			<p><a href="' . esc_url( $members_url ) . '" style="display:inline-block;background:#49C86E;color:#fff;text-decoration:none;padding:14px 24px;border-radius:8px;font-weight:700;">' . $btn . '</a></p>
		</div>';
		return $html;
	}

	/**
	 * Importa a imagem de check do plugin para a mídia e retorna a URL.
	 */
	private static function import_check_image(): string {
		$cached_id = (int) get_option( 'wcsppr_check_img_id', 0 );
		if ( $cached_id ) {
			$url = wp_get_attachment_url( $cached_id );
			if ( $url ) {
				return $url;
			}
		}
		$filename = dirname( __DIR__ ) . '/verificar (1).png';
		if ( ! file_exists( $filename ) ) {
			return '';
		}
		$contents = file_get_contents( $filename );
		if ( ! $contents ) {
			return '';
		}
		$upload = wp_upload_bits( 'wcsppr-check.png', null, $contents );
		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return '';
		}
		$attachment = array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'wcsppr-check',
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			return '';
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		update_option( 'wcsppr_check_img_id', (int) $attach_id );
		$url = wp_get_attachment_url( $attach_id );
		return $url ? (string) $url : '';
	}
}


