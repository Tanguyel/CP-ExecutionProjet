$(document).ready(function () {

    //$('.quick-course-info a').addClass('formule');
    //$('.quick-course-info form').addClass('formule');
    if ($('[name="course_formula"]').val() === 'paid' ) {
        $('.formule.free_price').addClass( 'hidden' );
        $('.quick-course-info .enrollment-button.formule.free').addClass('hidden');
    } else {
        $('.formule.paid_price').addClass( 'hidden' );
        $('.quick-course-info .enrollment-button.formule.paid').addClass('hidden');
    }
    
    $( '[name="course_formula"]' ).on( 'change', function () {
        if ( $(this).val()==='paid' ) {
            $('.formule.paid_price').removeClass('hidden');
            $('.formule.free_price').addClass('hidden');
            $('.quick-course-info .enrollment-button.formule.paid').removeClass('hidden');
            $('.quick-course-info .enrollment-button.formule.free').addClass('hidden');
        } else if ( $( this ).val()==='free' ) {
            $('.formule.free_price').removeClass( 'hidden' );
            $('.formule.paid_price').addClass( 'hidden' );
            $('.quick-course-info .enrollment-button.formule.paid').addClass('hidden');
            $('.quick-course-info .enrollment-button.formule.free').removeClass('hidden');

        }
    } );
});

