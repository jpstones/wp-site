(function ($) {
    let maxScroll = 0;

    $(window).on("scroll", function () {
        const docHeight = $(document).height() - $(window).height();
        const scrollTop = $(window).scrollTop();
        const currentScroll = Math.round((scrollTop / docHeight) * 100);

        if (currentScroll > maxScroll) {
            maxScroll = currentScroll;
        }
    });

    $(window).on("beforeunload", function () {
        const postId = crData.postId;
        if (maxScroll > 0 && postId) {
            $.post(crData.ajaxUrl, {
                action: "cr_save_scroll_percentage",
                post_id: postId,
                percentage: maxScroll,
            });
        }
    });
})(jQuery);