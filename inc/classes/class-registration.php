<?php
require_once plugin_dir_path(__FILE__) . './email/class-send-mail.php';

class Registration {
    private $user;
    private $otp;
    private $otpMeta = 'register_otp';

    public function __construct()
    {
        $this->createOTP();
    }

    public function afterUserRegister($user_id)
    {
        $this->user = get_userdata($user_id);

        // update user meta as otp
        $this->updateMeta($this->otpMeta, $this->otp);

        // update user is_otp_verified column
        $this->updateUserOTPColumn($user_id);

        // prepare email body
        $body = $this->prepareMailTemplate();
        
        // filters
        $from_email = apply_filters( 'otp_registrar_email_from', get_option('admin_email') );
        $subject = apply_filters( 'otp_registrar_email_subject', 'One time password for registration' );

        // send email
        // todo: create common email template
        (new SendMail())
            ->to($this->user->user_email)
            ->from($from_email)
            ->subject($subject)
            ->body($body)
            ->send();

        //todo: create a hook that will save the mail failed/success log
    }

    private function updateUserOTPColumn($user_id)
    {
        global $wpdb;   
        $wpdb->query( 
            $wpdb->prepare( "
                UPDATE ". $wpdb->users ." 
                SET is_otp_verified = %d 
                WHERE ID = %d",
                0, 
                $user_id
            ) 
        );
    }

    private function createOTP()
    {
        $this->otp = rand(222222, 999999);
    }

    private function updateMeta($key, $meta)
    {
        update_user_meta($this->user->id, $key, $meta);
    }

    private function prepareMailTemplate()
    {
        ob_start();
        require_once plugin_dir_path(__FILE__) . '../../email-template/custom-email-template.php';
        $body = ob_get_contents();
        ob_clean();

        // Filter
        $body = apply_filters( 'otp_registrar_email_body', $body, $this->user,  $this->otp );

        // Replace placeholders with actual values
        $body = str_replace('{site_name}', get_bloginfo('name'), $body);
        $body = str_replace('{recipient_name}', $this->user->display_name, $body);
        $body = str_replace('{otp_code}', $this->otp, $body);

        return $body;
    }
}