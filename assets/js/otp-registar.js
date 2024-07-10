jQuery(document).ready(function () {
  jQuery(document).on("click", "#manual_verified", function (e) {
    e.preventDefault();
    var selectedUserID = jQuery(this).attr("data-id");
    jQuery.ajax({
      url: _otp_registrar.ajaxurl,
      type: "post",
      data: {
        action: "otp_registration_user_verified_by_manually",
        user_id: selectedUserID,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          jQuery("#userDetailsContainer").html(
            "Manually verification process has been performed successful"
          );
        } else {
          jQuery("#userDetailsContainer").html(
            "Manually verification process has been performed failed"
          );
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        jQuery("#userDetailsContainer").html(
          "<p>An error occurred while fetching user details.</p>"
        );
      },
    });
  });

  jQuery("#otp_user_email").select2();
  // Listen for the user selection change
  var loadingSpinner = jQuery("#loadingSpinner");
  jQuery("#userInfoSubmit").click(function (e) {
    e.preventDefault();
    var submitBTN = jQuery(this);
    var selectedEmail = jQuery("#otp_user_email").val();
    var selectedUserID = jQuery("option:selected", this).attr("uid");
    var OtpNonce = jQuery('input[name="OtpNonce"]').val();
    const userDetailsContainer = jQuery("#userDetailsContainer");

    if (selectedEmail === "") {
      alert("Select user from drop-down");
      return;
    }

    submitBTN.val("Finding...").prop("disabled", true);
    loadingSpinner.removeClass("hidden");
    // Clear existing result
    userDetailsContainer.empty();
    // Make Ajax request to get user details
    jQuery.ajax({
      url: _otp_registrar.ajaxurl,
      type: "post",
      data: {
        action: "otp_registration_get_user_details",
        user_email: selectedEmail,
        user_id: selectedUserID,
        OtpNonce,
      },
      dataType: "json",
      success: function (response) {
        // Hide loading spinner and show data container
        loadingSpinner.addClass("hidden");
        submitBTN.val("SUBMIT").prop("disabled", false);
        if (response.success) {
          setTimeout(function () {
            var user = response.data;
            var is_otp_verified =
              user.is_otp_verified === "1"
                ? "<span style='color:green'>Verified</span>"
                : "<span style='color:red'>Unverified</span><div class='manual_btn'><a href='#' class='btn_verify' id='manual_verified' data-id='" +
                  user.id +
                  "'>Manually Verify</a></div>";
            var userDetails = "<ul>";
            userDetails += "<li>Display Name: " + user.display_name + "</li>";
            userDetails += "<li>User Login: " + user.user_login + "</li>";
            userDetails += "<li>Email: " + user.user_email + "</li>";
            userDetails += "<li>OTP: " + user.otp + "</li>";
            userDetails += "<li>Is OTP Veryfied?: " + is_otp_verified + "</li>";
            // Add more user details as needed
            userDetails += "</ul>";
            jQuery("#userDetailsContainer").html(userDetails);
          }, 100); // Simulated delay of 1 seconds
        } else {
          jQuery("#userDetailsContainer").html("<p>" + response.data + "</p>");
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        loadingSpinner.addClass("hidden");
        submitBTN.val("SUBMIT").prop("disabled", false);
        jQuery("#userDetailsContainer").html(
          "<p>An error occurred while fetching user details.</p>"
        );
      },
    });
  });

  jQuery("form#otpVerify").submit(function (e) {
    e.preventDefault();
    var otp_code = jQuery('input[name="otp_code"]').val();
    var otp_user_id = jQuery('input[name="otp_user_id"]').val();
    var nonce = jQuery('input[name="nonce"]').val();

    if (otp_user_id == -1) {
      alert("Invalid User");
      return;
    }
    jQuery.ajax({
      type: "POST",
      dataType: "json",
      url: _otp_registrar.ajaxurl,
      data: { action: "verify_user_otp", otp_code, otp_user_id, nonce },
      success: function (response) {
        console.log(response);
      },
    });
  });

  var attempts = 0;
  jQuery("#otp_registarar_resend_otp_button").on("click", function (e) {
    e.preventDefault();
    var user_id = jQuery(this).data("user_id");
    var nonce = jQuery('input[name="resend_nonce"]').val();
    attempts++;
    if (attempts === 3) {
      jQuery("#otp_registarar_resend_otp_button").prop("disabled", true);
    }
    jQuery.ajax({
      url: _otp_registrar.ajaxurl,
      type: "POST",
      data: {
        action: "otp_registarar_resend_otp",
        user_id: user_id,
        nonce,
      },
      success: function (response) {
        // console.log(response);
        alert("OTP Resent successfully");
      },
      error: function () {
        alert("Error resending OTP.");
      },
    });
  });

});
