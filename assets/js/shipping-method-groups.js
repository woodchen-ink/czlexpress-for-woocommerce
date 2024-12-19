jQuery(function($) {
    $('.shipping-method-group .toggle-sub-methods').on('click', function(e) {
        e.preventDefault();
        
        var $group = $(this).closest('.shipping-method-group');
        var $subMethods = $group.find('.sub-methods');
        
        $subMethods.slideToggle();
        $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });
}); 