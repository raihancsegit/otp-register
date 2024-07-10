<?php
/**
 * Registration process
 * Admin menus options
 */

require_once plugin_dir_path(__FILE__) . '../classes/class-registration.php';
require_once plugin_dir_path(__FILE__) . '../classes/class-otp-admin.php';

// Admin Page.
new OTP_Admin();

// User Registration.
add_action( 'user_register', array( new Registration(), 'afterUserRegister' ) );

add_action( 'admin_enqueue_scripts', 'admin_otp_registrar_script_enqueuer' );
// registrar_script.
function admin_otp_registrar_script_enqueuer(){
    $valid_pages = array( "otp-admin-page" );
    $page = isset($_REQUEST[ 'page' ]) ? $_REQUEST[ 'page' ] : "";
    if(in_array( $page, $valid_pages )){
        wp_register_style( "otp_registrar_select2_style", WP_PLUGIN_URL.'/otp-registrar/assets/css/select2.min.css', array() );
        wp_register_style( "otp_style", WP_PLUGIN_URL.'/otp-registrar/assets/css/otp-style.css', array() );
        wp_register_script( "otp_registrar_select2_script", WP_PLUGIN_URL.'/otp-registrar/assets/js/select2.min.js', array( 'jquery' ) );
        wp_enqueue_style( 'otp_registrar_select2_style' );
        wp_enqueue_style( 'otp_style' );
        wp_enqueue_script( 'otp_registrar_select2_script' );
    }
}

add_action( 'init', 'otp_registrar_script_enqueuer' );
function otp_registrar_script_enqueuer() {
    $data_array = array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    );
    //todo: update plugin name(otp-registrar) as variable
    // this name can be changed in future
    wp_register_script( "otp_registrar_script", WP_PLUGIN_URL.'/otp-registrar/assets/js/otp-registar.js', array('jquery') );
    wp_register_script( "otp_registrar_admin_tab_script", WP_PLUGIN_URL.'/otp-registrar/assets/js/admin-tab.js', array('jquery', 'otp_registrar_script') );
    wp_localize_script( 'otp_registrar_script', '_otp_registrar', $data_array);        

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'otp_registrar_script' );
    wp_enqueue_script( 'otp_registrar_admin_tab_script' );
}


add_action( 'wp_ajax_otp_registration_get_user_details', 'get_user_details_callback' );
function get_user_details_callback() {
    if ( !wp_verify_nonce( $_REQUEST['OtpNonce'], "otp_user_nonce")) {
        wp_send_json_error('Protected');
        wp_die();
    }
    if (isset($_POST['user_email'])) {
        $user_email = sanitize_email($_POST['user_email']);
        $user = get_user_by('email', $user_email);
        $userotp = get_usermeta($user->ID,'register_otp',true);
        if ($user) {
            $user_details = array(
                'display_name'    => $user->display_name,
                'user_login'      => $user->user_login,
                'user_email'      => $user->user_email,
                'is_otp_verified' => $user->is_otp_verified,
                'id' => $user->ID,
            );

           $user_details += array('otp' => $userotp);
           
            wp_send_json_success($user_details);
        } else {
            wp_send_json_error('User not found.');
        }
    } else {
        wp_send_json_error('Invalid request.');
    }

    wp_die();
}

add_action( 'wp_ajax_otp_registration_user_verified_by_manually', 'user_verified_manual_callback' );
function user_verified_manual_callback(){
    // update users column data.
    global $wpdb;  
    $user_id = $_POST['user_id']; 
    $verified_manual = $wpdb->query( 
        $wpdb->prepare( "
            UPDATE ". $wpdb->users ." 
            SET is_otp_verified = %d 
            WHERE ID = %d",
            1, 
            $user_id
        ) 
    );
    wp_send_json_success($verified_manual);
    wp_die();
}


add_action( "wp_ajax_verify_user_otp", "verify_user_otp" );
add_action( "wp_ajax_nopriv_verify_user_otp", "verify_user_otp" );

function verify_user_otp() {
    if ( !wp_verify_nonce( $_REQUEST['nonce'], "otp_registrar_nonce")) {
        echo "0|This form is protected!!";
        die();
    }

    $otp_code = $_REQUEST['otp_code'];
    $otp_user_id = $_REQUEST['otp_user_id'];

    $user = get_userdata($otp_user_id);
    if ($user) {
        $redirectURL = get_home_url();

        if ( $user->is_otp_verified == "1" ) {
            echo "0|OTP is already verified";
            die();
        }

        $isValidOTP = checkOTPAndVerifyUser($user->id, $otp_code);
        if ( !$isValidOTP ) {
            echo "0|OTP is invalid";
            die();
        } else {
            do_action( 'otp_validation_success_before_authorize', $user);

            wp_set_current_user( $user->id, $user->user_login );
	        wp_set_auth_cookie( $user->id );

            // todo: need to change the name in future
            do_action( 'otp_validation_success_after_authorize', $user );
            $redirectURL = apply_filters( 'otp_validation_success_redirect_url', $redirectURL, $user );

            echo "1|Validation success, Redirecting...|". $redirectURL;
            die();
        }

    } else {
        echo "0|Invalid user";
        die();
    }

    echo "0|Something went wrong!!";
    die();
}

add_filter( 'authenticate', 'apply_wp_authenticate', 20, 3);
function apply_wp_authenticate ($user, $email, $password) {
    if ($user->is_otp_verified === "0") {
        return new WP_Error("otp_verified_error", __('Your account is not verified yet.'));
    }
    return $user;
}

add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );

add_action( "resetpass_form", "execute_on_resetpass_form_event" , 10, 2);
add_action( 'woocommerce_resetpassword_form', 'execute_on_resetpass_form_event' );
function execute_on_resetpass_form_event($user){
    echo '
        <p>
			<label for="otp_code">OTP Code *</label>
		</p>
        <div>
            <input type="text" id="otp_code" name="otp_code" class="input password-input" size="24">
        </div>
        <br class="clear">
    ';
    echo '<input type="hidden" name="resend_nonce" value="'.wp_create_nonce("otp_reset_nonce").'">';
    echo '<div style="margin-bottom:20px"><button class="button" id="otp_registarar_resend_otp_button" data-user_id="'.$user->ID.'">Resend OTP</button></div>';
    
}
add_action('wp_ajax_otp_registrar_resend_otp', 'resend_otp_ajax_callback');
add_action('wp_ajax_nopriv_otp_registrar_resend_otp', 'resend_otp_ajax_callback');
function resend_otp_ajax_callback() {
    
    if ( !wp_verify_nonce( $_REQUEST['resend_nonce'], "otp_reset_nonce")) {
        echo "0|This form is protected!!";
        die();
    }
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
   if ($user_id > 0) {
        $registration = new Registration();
        $registration->afterUserRegister($user_id);
        wp_send_json_success('OTP Resent successfully.');
        
    }else {
        wp_send_json_success('Invalid user ID.');
    }

    wp_die();  // Always end with this
}

add_action( "validate_password_reset", "validate_password_reset_event" , 10, 2);
function validate_password_reset_event($errors, $user){
    
    $plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';
    if (
        in_array( $plugin_path, wp_get_active_and_valid_plugins() )
        || in_array( $plugin_path, wp_get_active_network_plugins() )
    ) {
        if ( 
            (isset( $_POST['password_1'] ) && ! empty( $_POST['password_1'] ))
            && $_REQUEST['woocommerce-reset-password-nonce']
        ) {
            validateOTPInput($errors, $user);
        } else if ( isset( $_POST['pass1'] ) && ! empty( $_POST['pass1'] ) ) {
            //todo: register_otp should be constant
            validateOTPInput($errors, $user);
        }

    } else if ( isset( $_POST['pass1'] ) && ! empty( $_POST['pass1'] ) ) {
        //todo: register_otp should be constant
        validateOTPInput($errors, $user);
    }
}

function validateOTPInput($errors, $user) {
    $otp_code = wp_unslash( $_POST[ 'otp_code' ] );
    if ( isset( $_POST['otp_code'] ) && empty( $_POST['otp_code'] ) ) {
        $errors->add( 'otp_required', __( 'Error: OTP code is required.' ) );
        return;
    }

    if ($user->is_otp_verified == "1") {
        $errors->add( 'otp_already_verified', __( 'Error: OTP is already verified.' ) );
        return;
    }

    $isValidOTP = checkOTPAndVerifyUser($user->id, $otp_code);

    if (!$isValidOTP) {
        $errors->add( 'otp_invalid', __( 'Error: OTP code is invalid.' ) );
        return;
    }
}

function checkOTPAndVerifyUser($user_id, $otp_code) {
    $saved_otp = get_user_meta($user_id, 'register_otp', true);
    if ($saved_otp != $otp_code) {
        return false;
    } else {
        //todo: register_otp should be constant
        update_user_meta($user_id, 'register_otp', 'Verified at: ' . date('Y-m-d H:i:s'));

        // update users column data
        global $wpdb;   
        $wpdb->query( 
            $wpdb->prepare( "
                UPDATE ". $wpdb->users ." 
                SET is_otp_verified = %d 
                WHERE ID = %d",
                1, 
                $user_id
            ) 
        );

        return true;
    }
}