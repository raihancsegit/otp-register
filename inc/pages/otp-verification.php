<?php
/**
 * Template Name: Otp Verification
 *
 * @package otp_verifier
 */

$otp_user_id = -1;

if ( isset( $_COOKIE['otp_user_id'] ) ) {
    $otp_user_id    = sanitize_text_field( wp_unslash( $_COOKIE['otp_user_id'] ) );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verify</title>
    <?php wp_head(); ?>
</head>
<body>
    <form name="otp-verification" id="otpVerify">
        <input type="number" name="otp_code">
        <input type="hidden" name="otp_user_id" value="<?php echo $otp_user_id; ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("otp_registrar_nonce"); ?>">
        <button type="submit">Verify</button>
    </form>
    <?php wp_footer(); ?>
</body>
</html>