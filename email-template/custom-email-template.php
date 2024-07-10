<!DOCTYPE html>
<html>

<head>
    <title>Email Template</title>
</head>

<body style="font-family: Arial, sans-serif;">

    <div style="background-color: #7F54B2; padding: 20px;">
        <!-- Rest of your email template content here -->
        <p style="color:#fff">
            Welcome to {site_name},
        </p>

    </div>

    <div style="background-color: #fff; padding: 20px;">
        <p>
            Dear {recipient_name},
        </p>
        <p>
            <?php 
            $otp_message = get_option( 'otp_emailTemplate_settings' )['otp_message'];
            if (isset($otp_message)) {
                echo $otp_message;?>:{otp_code} <?php
            }else {
                echo "your register OTP as following";?>:{otp_code} <?php
            }
            ?>
        </p>
        <p>
            <?php 
            $thankYou = get_option( 'otp_emailTemplate_settings' )['thankyou_option'];
            if (isset($thankYou)) {
                echo $thankYou;
            }else {
                echo "Thank you for your attention to this matter.";
            }
            ?>
        </p>
    </div>

</body>

</html>