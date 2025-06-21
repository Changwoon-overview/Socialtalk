<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'MSSMS_Email_Point_Shortage' ) ) :

	class MSSMS_Email_Point_Shortage extends WC_Email {
		public $order;
		public function __construct() {
			$this->id = 'mssms_point_shortage';

			$this->enabled       = MSSMS_Manager::use_point_shortage_notification();
			$this->title         = __( '{site_title} | 포인트 충전 안내', 'mshop-sms-s2' );
			$this->description   = __( '문자 알림 포인트가 부족해서 문자알림이 전송되지 못했을때 관리자에게 전달되는 이메일입니다.', 'mshop-sms-s2' );
			$this->template_html = 'emails/point-shortage.php';
			$this->template_base = MSSMS()->template_path();
			$this->placeholders  = array (
				'{site_title}' => $this->get_blogname()
			);

			// Trigger
			add_action( 'mssms_send_point_shortage_notification', array ( $this, 'trigger' ) );

			// Call parent constructor
			parent::__construct();
		}
		public function get_default_subject() {
			return __( '{site_title} | 포인트 충전 안내', 'mshop-sms-s2' );
		}
		public function get_default_heading() {
			return __( '{site_title} | 포인트 충전 안내', 'mshop-sms-s2' );
		}

		public function get_recipient() {
			$admin_list = MSSMS_Manager::get_admin_list();

			return implode( ',', array_column( $admin_list, 'email' ) );
		}
		public function trigger( $auction_id ) {
			$this->setup_locale();

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}
		public function get_content_html() {
			return wc_get_template_html( $this->template_html, array (
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
			), '', MSSMS()->template_path() );
		}
		public function init_form_fields() {
			$this->form_fields = array (
				'enabled'   => array (
					'title'             => __( 'Enable/Disable', 'woocommerce' ),
					'type'              => 'checkbox',
					'custom_attributes' => array ( 'readonly' => 'readonly' ),
					'label'             => __( 'Enable this email notification', 'woocommerce' ),
					'default'           => MSSMS_Manager::use_point_shortage_notification() ? 'yes' : 'no',
				),
				'recipient' => array (
					'title'             => __( 'Recipient(s)', 'woocommerce' ),
					'type'              => 'text',
					'custom_attributes' => array ( 'readonly' => 'readonly' ),
					'description'       => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder'       => '',
					'default'           => self::get_recipient(),
					'desc_tip'          => true,
				),
				'subject'   => array (
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'   => array (
					'title'       => __( 'Email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				)
			);
		}
	}

endif;

return new MSSMS_Email_Point_Shortage();
