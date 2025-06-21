<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MSSMS_Members')) {

    class MSSMS_Members
    {
        static $customer_id = 0;

        static $role_params = null;

        static $sms_rules   = null;

        static $alimtalk_rules = null;
        protected static function get_sms_rules()
        {
            if (is_null(self::$sms_rules)) {
                $rules = get_option('mssms_sms_created_customer', array());

                self::$sms_rules = array_filter($rules, function ($rule) {
                    return 'yes' == $rule['enable'];
                });
            }

            return self::$sms_rules;
        }
        protected static function get_alimtalk_rules()
        {
            if (is_null(self::$alimtalk_rules)) {
                $rules = get_option('mssms_alimtalk_created_customer', array());

                self::$alimtalk_rules = array_filter($rules, function ($rule) {
                    return 'yes' == $rule['enable'];
                });
            }
            return self::$alimtalk_rules;
        }
        public static function save_user_info($user_id, $new_customer_data, $params)
        {
            self::$customer_id = $user_id;
        }
        static function get_user_id()
        {
            if (0 == self::$customer_id) {
                self::$customer_id = get_current_user_id();
            }

            return self::$customer_id;
        }
        public static function register_post_actions($actions)
        {
            $actions['send_sms'] = __('문자발송', 'mshop-sms-s2');
            $actions['send_alimtalk'] = __('알림톡발송', 'mshop-sms-s2');

            return $actions;
        }
        public static function register_post_actions_settings($settings)
        {
            ob_start();
            include( 'admin/settings/html/sms-message-guide-for-register.php' );
            $guide = ob_get_clean();

            $settings['send_sms'] = array(
                'type' => 'Section',
                "hideSectionHeader" => true,
                'elements' => array(
                    array(
                        "id" => "phone_number",
                        "title" => "전화번호",
                        "className" => "fluid",
                        "placeholder" => "",
                        "type" => "Text",
                        'desc2' => __('<div class="desc2">휴대폰 번호를 입력하지 않으면 현재 로그인한 사용자에게 발송됩니다.</div>', 'mshop-sms-s2')
                    ),
                    array(
                        "id" => "title",
                        "title" => "문자제목",
                        "className" => "fluid",
                        "placeholder" => "고객에게 발송할 문자 제목을 입력해주세요.",
                        "type" => "Text"
                    ),
                    array(
                        "id" => "message",
                        "title" => "문자내용",
                        "className" => "",
                        "placeholder" => "고객에게 발송할 문자 내용을 입력해주세요.",
                        "type" => "TextArea"
                    ),
                    array (
                        "id"        => "mssms_sms_user_options_desc",
                        "className" => "fluid",
                        "type"      => "Label",
                        "readonly"  => "yes",
                        "desc2"     => $guide
                    ),
                )
            );

            $settings['send_alimtalk'] = array(
                'type' => 'Section',
                "hideSectionHeader" => true,
                'elements' => array(
                    array(
                        "id" => "phone_number",
                        "title" => "전화번호",
                        "className" => "fluid",
                        "placeholder" => "",
                        "type" => "Text",
                        'desc2' => __('<div class="desc2">휴대폰 번호를 입력하지 않으면 현재 로그인한 사용자에게 발송됩니다.</div>', 'mshop-sms-s2')
                    ),
                    array(
                        "id" => "template_code",
                        "title" => __("알림톡 템플릿", 'mshop-sms-s2'),
                        "className" => "fluid",
                        "type" => "Select",
                        "placeholder" => "알림톡 템플릿을 선택하세요.",
                        "options" => MSSMS_Settings_Alimtalk_Send::get_templates()
                    ),
                    array(
                        "id" => "resend_method",
                        "title" => __("문자 대체 발송", 'mshop-sms-s2'),
                        "className" => "fluid",
                        "type" => "Select",
                        "default" => "none",
                        "options" => array(
                            'none' => __("사용안함", 'mshop-sms-s2'),
                            'alimtalk' => __("알림톡 내용전달", 'mshop-sms-s2')
                        )
                    ),
                )
            );

            return $settings;
        }
        static function send_sms($response, $form, $action, $params)
        {
            if (self::get_user_id() > 0 && !empty($action['message'])) {
                if (!empty($action['phone_number'])) {
                    $phone_number = preg_replace('~\D~', '', $action['phone_number']);
                } else {
                    $phone_number = get_user_meta(self::get_user_id(), 'billing_phone', true);
                }

                if (!empty($phone_number)) {
                    $user = get_userdata(self::get_user_id());

                    $template_params = array(
                        '쇼핑몰명' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
                        '고객명' => $user->display_name,
                        '아이디' => $user->user_login
                    );

                    $recipients = array(
                        array(
                            'receiver' => $phone_number,
                            'template_params' => $template_params
                        )
                    );

                    $type = MSSMS_SMS::get_sms_type(stripslashes($action['message']), $template_params);

                    MSSMS_SMS::send_sms($type, $action['title'], stripslashes($action['message']), $recipients, get_option('mssms_rep_send_no'));
                }
            }

            return $response;
        }
        static function send_alimtalk($response, $form, $action, $params)
        {
            if (self::get_user_id() > 0 && !empty($action['template_code'])) {
                if (!empty($action['phone_number'])) {
                    $phone_number = preg_replace('~\D~', '', $action['phone_number']);
                } else {
                    $phone_number = get_user_meta(self::get_user_id(), 'billing_phone', true);
                }

                if (!empty($phone_number)) {
                    $recipients[] = $phone_number;

                    $resend_params = array(
                        'isResend' => 'false'
                    );

                    $user = get_userdata(self::get_user_id());

                    $template_code = $action['template_code'];
                    $template = MSSMS_Kakao::get_template($action['template_code']);


                    $profile = MSSMS_Kakao::get_profile($template['plus_id']);
                    if ('alimtalk' == $action['resend_method']) {
                        $resend_params = array(
                            'isResend' => 'true',
                            'resendSendNo' => $profile['resend_send_no']
                        );
                    }

                    $template_params = array(
                        '쇼핑몰명' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
                        '고객명' => $user->display_name,
                        '아이디' => $user->user_login
                    );

                    MSSMS_Kakao::send_alimtalk($template_code, $recipients, $template_params, $resend_params);
                }
            }

            return $response;
        }
        public static function send_created_customer_sms( $customer_id )
        {
            $rules = self::get_sms_rules();

            if (!empty($rules)) {
                $rule = reset($rules);

                $recipients[] = get_user_meta($customer_id, 'billing_phone', true);

                if (empty($recipients)) {
                    return;
                }

                $user = get_userdata($customer_id);
                $wc_customer = new WC_Customer( $customer_id );

                $template_params = array(
                    '쇼핑몰명' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
                    '고객명' => $user->display_name,
                    '아이디' => $user->user_login,
                    '회원등급' => mssms_get_user_role( $customer_id ),
                    '청구자명' => $wc_customer->get_billing_first_name() . $wc_customer->get_billing_last_name()
                );

                $recipients = array_map(function ($receiver) use ($template_params) {
                    return array(
                        'receiver'        => $receiver,
                        'template_params' => $template_params
                    );
                }, $recipients);

                $type = MSSMS_SMS::get_sms_type($rule['message'], $template_params);

                MSSMS_SMS::send_sms($type, '', $rule['message'], $recipients, '', MSSMS_Manager::get_request_date());
            }
        }
        public static function send_created_customer_alimtalk( $customer_id )
        {
            $rules = self::get_alimtalk_rules();

            if (!empty($rules)) {
                $rule         = reset($rules);

                $phone_number = get_user_meta($customer_id, 'billing_phone', true);


                if (!empty($phone_number)) {
                    $recipients[]  = $phone_number;

                    $resend_params = array(
                        'isResend' => 'false'
                    );

                    $user        = get_userdata( $customer_id );
                    $wc_customer = new WC_Customer( $customer_id );

                    $template = MSSMS_Kakao::get_template($rule['template_code']);

                    $profile = MSSMS_Kakao::get_profile($template['plus_id']);
                    if ('alimtalk' == $rule['resend_method']) {
                        $resend_params = array(
                            'isResend'     => 'true',
                            'resendSendNo' => $profile['resend_send_no']
                        );
                    }

                    $template_params = array(
                        '쇼핑몰명' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
                        '고객명'  => $user->display_name,
                        '아이디'  => $user->user_login,
                        '회원등급' => mssms_get_user_role( $customer_id ),
                        '청구자명' => $wc_customer->get_billing_first_name() . $wc_customer->get_billing_last_name()
                    );

                    MSSMS_Kakao::send_alimtalk($rule['template_code'], $recipients, $template_params, $resend_params);
                }
            }
        }
    }

}
