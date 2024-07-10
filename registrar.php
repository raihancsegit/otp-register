<?php
/*
Plugin Name: OTP Registration for Email & SMS
Description: Enable OTP on registration.
Version: 1.0
Author: sagormax
@package otp_verifier
*/

class Registrar {
    private $table_name;
    private $column_name = 'is_otp_verified';
    private $column_type = 'TINYINT(1)';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'users';

        register_activation_hook(__FILE__, array($this, 'activate'));
        // todo: having a major issue
        // if you active and de-active and active unverified users are made verified in default column
        // register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // require hooks
        require_once plugin_dir_path(__FILE__) . './inc/hooks/init.php';
    }

    public function activate() {
        global $wpdb;
        $column_exists = $wpdb->query("SHOW COLUMNS FROM {$this->table_name} LIKE '{$this->column_name}'");

        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN {$this->column_name} {$this->column_type} NOT NULL DEFAULT 1 AFTER `display_name`;");
        }
    }

    public function deactivate() {
        // Check if the column exists before rolling back the changes
        global $wpdb;
        $column_exists = $wpdb->query("SHOW COLUMNS FROM {$this->table_name} LIKE '{$this->column_name}'");

        if ($column_exists) {
            $wpdb->query("ALTER TABLE {$this->table_name} DROP COLUMN {$this->column_name};");
        }
    }
}

new Registrar();