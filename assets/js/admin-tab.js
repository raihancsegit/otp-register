(function ($) {
  $(document).ready(function () {
    $(".nav-tab").click(function () {
      var href = $(this).attr("href");
      $(".nav-tab").removeClass("nav-tab-active");
      $(this).addClass("nav-tab-active");
      $(".tab-content").hide();
      $(href).show();
      return false;
    });
  });
})(jQuery);
