var $jQ = jQuery.noConflict();

// Font Replacement
Cufon.replace('.category-title h1,.footer h4, .product-view .product-shop .product-name h1,.page-title h1, .page-title h2,.wired-home .subscribe strong', {
	hover: true
});

$jQ(document).ready(function() {
	// Featured Products
    $jQ('#featured').jcarousel();
	// FancyBox jQuery
	$jQ("a.group").fancybox({ 'zoomSpeedIn': 300, 'zoomSpeedOut': 300, 'overlayShow': true }); 	
	// Slider Homepage
	$jQ('#slider').cycle({
        fx: 'fade',
        speed: 2000,
		timeout: 5000,
        pager: '#controls',
		slideExpr: '.panel'
    });
	
	//Fix TopLink IMG
	$jQ("ul.links li a[title='My Wishlist']").addClass('top-link-wishlist');
});
