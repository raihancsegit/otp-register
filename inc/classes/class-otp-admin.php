<?php 
/**
	 * class OTP Admin
	 * 
	 * @package otp register
	 *
 */
class OTP_Admin {
	/** 
	 * For easier overriding we declared the keys
	 * here as well as our tabs array which is populated
	 * when registering settings
	 */
	private $userinfo_settings_key = 'otp_userinfo_settings';
	private $emailTemplate_settings_key = 'otp_emailTemplate_settings';
	private $otp_settings_key = 'otp_option_settings';
	private $plugin_options_key = 'otp-admin-page';
	private $plugin_settings_tabs = array();

	/** 
	 * Fired during plugins_loaded (very very early),
	 * so don't miss-use this, only actions and filters,
	 * current ones speak for themselves.
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_userinfo_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_emailTemplate_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_otp_settings' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
	}

	/** 
	 * Loads both the Userinfo and emailTemplate settings from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_settings() {
		$this->userinfo_settings = (array) get_option( $this->userinfo_settings_key );
		$this->emailTemplate_settings = (array) get_option( $this->emailTemplate_settings_key );
		
		// Merge with defaults
		$this->userinfo_settings = array_merge( array(
			'userinfo_option' => 'Userinfo value'
		), $this->userinfo_settings );

		$this->emailTemplate_settings = array_merge( array(
			'otp_message' => 'Email Template value'
		), $this->emailTemplate_settings );

		
	}

	/**
	 * Registers the Userinfo settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_userinfo_settings() {
		$this->plugin_settings_tabs[$this->userinfo_settings_key] = 'Userinfo';

		register_setting( $this->userinfo_settings_key, $this->userinfo_settings_key );
		add_settings_section( 'section_userinfo', 'Userinfo Plugin Settings', array( &$this, 'section_userinfo_desc' ), $this->userinfo_settings_key );
		add_settings_field( 'userinfo_option', 'Select Users', array( &$this, 'field_userinfo_option' ), $this->userinfo_settings_key, 'section_userinfo' );
	}

	/** 
	 * Registers the emailTemplate settings and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_emailTemplate_settings() {
		$this->plugin_settings_tabs[$this->emailTemplate_settings_key] = 'Email Template';

		register_setting( $this->emailTemplate_settings_key, $this->emailTemplate_settings_key );
		add_settings_section( 'section_emailTemplate', 'Email Template Plugin Settings', array( &$this, 'section_emailTemplate_desc' ), $this->emailTemplate_settings_key );
		add_settings_field( 'otp_message', 'OTP Message', array( &$this, 'field_otp_message_option' ), $this->emailTemplate_settings_key, 'section_emailTemplate' );
		add_settings_field( 'thankyou_option', 'Thank You Message', array( &$this, 'field_thankyou_message' ), $this->emailTemplate_settings_key, 'section_emailTemplate' );
		
	}

	/** 
	 * Registers the emailTemplate settings and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_otp_settings() {
		$this->plugin_settings_tabs[$this->otp_settings_key] = 'OTP Setting';

		register_setting( $this->otp_settings_key, $this->otp_settings_key );
		add_settings_section( 'section_otp', 'OTP Settings', array( &$this, 'section_otp_desc' ), $this->otp_settings_key );
		add_settings_field( 'otp_enable', 'OTP Enable/Disable', array( &$this, 'field_otp_section_option' ), $this->otp_settings_key, 'section_otp' );
		
	}

	/**
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_userinfo_desc() { echo 'Userinfo section description goes here.'; }
	function section_emailTemplate_desc() { echo 'Email Template section description goes here.'; }
	function section_otp_desc() { echo 'OTP section description goes here.'; }

	/**
	 * Userinfo Option field callback, renders a
	 * text input, note the name and value.
	 */
	function field_userinfo_option() {
		?>
            <div class="main-setting-section-wdp">
                <?php
                      $users = get_users();
                      if ( ! empty( $users ) ) {
                     echo '<select name="otp_user_email" id="otp_user_email">';
                        echo '<option value="">Select User Email</option>';
                         foreach ( $users as $user ) {
                            echo '<option value="' . esc_attr( $user->user_email ) . '" uid="'.esc_attr($user->ID).'">' . esc_html( $user->user_email ) . '</option>';
                        }
                    echo '</select>';
                    } else {
                         echo '<p>No users found.</p>';
                    }
                 ?>
                <input type="submit" id="userInfoSubmit" value="SUBMIT" />
                <input type="hidden" name="OtpNonce" value="<?php echo wp_create_nonce("otp_user_nonce"); ?>">

                <div id="resultMainWrapper">
                    <div id="loadingSpinner" class="hidden"></div>
                    <div id="userDetailsContainer">Select user-email to view OTP details</div>
                </div>
            </div>
        <?php
	}

	/**
	 * Email Template Option field callback, same as above.
	 */
	function field_otp_message_option() {
		?>
        <input type="text" class="otpText" name="<?php echo $this->emailTemplate_settings_key; ?>[otp_message]"
            value="<?php echo esc_attr( $this->emailTemplate_settings['otp_message'] ); ?>" />
        <?php
            }
        function field_thankyou_message() {
                ?>
        <input type="text" class="otpText" name="<?php echo $this->emailTemplate_settings_key; ?>[thankyou_option]"
            value="<?php echo esc_attr( $this->emailTemplate_settings['thankyou_option'] ); ?>" />
        <?php
	}

	/**
	 * OTP Option field callback, same as above.
	 */

	function field_otp_section_option() {
		?>
        <input type="checkbox" id="otp_enable" name="<?php echo $this->otp_settings_key; ?>[otp_enable]" value="1" <?php
            checked(!empty(get_option('otp_option_settings')['otp_enable']),1)

            ?> />
        <?php
	}

	/**
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		//add_options_page( 'My Plugin Settings', 'My Settings', 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) );
        add_menu_page(
            __( 'OTP Admin', 'otp-register' ),
            __( 'OTP Admin', 'otp-register' ),
            'manage_options',
            'otp-admin-page',
            array( &$this,'init_register_otp' ),
            '',
            6
        );
	}

	/**
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function init_register_otp() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->userinfo_settings_key;
		?>
        <div class="wrap">
            <?php $this->plugin_options_tabs(); ?>
            <form method="post" action="options.php">
                <?php wp_nonce_field( 'update-options' ); ?>
                <?php settings_fields( $tab ); ?>
                <?php do_settings_sections( $tab ); ?>
                <?php 
                            if($tab != 'otp_userinfo_settings'){
                                submit_button();
                            }
                        ?>
            </form>
        </div>
    <?php
	}

	/**
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->userinfo_settings_key;

		//screen_icon();
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
	}
};