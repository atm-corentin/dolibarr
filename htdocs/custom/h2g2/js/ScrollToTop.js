let scrollAmmount = 200;
let scrollSpeed = 600;
$(document).ready(function () {
    $('body').append('<a href="javascript:void(0);" id="scroll-to-top" class="scroll-btn" style="display: none;"><span style="color: var(--colortextbackhmenu)" class="fas fa-chevron-up"></span></a>');

    if (window.location.href.includes('list.php')) {
        $('body').append('<a href="javascript:void(0);" id="scroll-to-left" class="scroll-btn" style="display: none;"><span style="color: var(--colortextbackhmenu)" class="fas fa-chevron-left"></span></a>');
        $('body').append('<a href="javascript:void(0);" id="scroll-to-top-left" class="scroll-btn" style="display: none;"><span style="color: var(--colortextbackhmenu); transform:rotate(45deg);" class="fas fa-chevron-left"></span></a>');
        setupScrollHandlers('div.div-table-responsive', '#scroll-to-top', '#scroll-to-left', '#scroll-to-top-left');
    } else {
        setupScrollHandlers('#id-right', '#scroll-to-top');
    }

    /**
     * Sets up scroll handlers for a container.
     * Shows or hides "scroll-to-top" and optionally "scroll-to-left" buttons
     * based on the user's scroll position, and animates scrolling when clicked.
     *
     * @param {string} container - The scrollable container selector.
     * @param {string} scrollTopBtn - The "scroll-to-top" button selector.
     * @param {string} [scrollLeftBtn=null] - Optional "scroll-to-left" button selector.
     */
    function setupScrollHandlers(container, scrollTopBtn, scrollLeftBtn = null, scrollTopLeftBtn = null) {
        $(container).scroll(function () {
            const scrollTop = $(this).scrollTop();
            const scrollLeft = $(this).scrollLeft();

            if (scrollTopLeftBtn) {
                if (scrollTop > scrollAmmount && scrollLeft > scrollAmmount) $(scrollTopLeftBtn).fadeIn();
                else $(scrollTopLeftBtn).fadeOut();

                if ($(scrollTopLeftBtn).css('display') == 'none') { //top-left btn isn't display
                    if (scrollTop > scrollAmmount) $(scrollTopBtn).fadeIn();
                    else $(scrollTopBtn).fadeOut();

                    if (scrollLeftBtn) {
                        if (scrollLeft > scrollAmmount) $(scrollLeftBtn).fadeIn();
                        else $(scrollLeftBtn).fadeOut();
                    }
                }
            } else {
                if (scrollTop > scrollAmmount) $(scrollTopBtn).fadeIn();
                else $(scrollTopBtn).fadeOut();

                if (scrollLeftBtn) {
                    if (scrollLeft > scrollAmmount) $(scrollLeftBtn).fadeIn();
                    else $(scrollLeftBtn).fadeOut();
                }
            }
        });

        $(scrollTopBtn).click(() => {
            $(container).animate({scrollTop: 0}, scrollSpeed);
            $(scrollTopBtn).css('display', 'none');
            return false;
        });

        if (scrollLeftBtn) {
            $(scrollLeftBtn).click(() => {
                $(container).animate({scrollLeft: 0}, scrollSpeed);
                $(scrollLeftBtn).css('display', 'none');
                return false;
            });
        }

        if (scrollTopLeftBtn) {
            $(scrollTopLeftBtn).click(() => {
                $(container).animate({
                    scrollTop: 0,
                    scrollLeft: 0
                }, scrollSpeed);
                $(scrollTopLeftBtn).css('display', 'none');
                $(scrollTopBtn).css('display', 'none');
                $(scrollLeftBtn).css('display', 'none');
                return false;
            });
        }
    }
});
