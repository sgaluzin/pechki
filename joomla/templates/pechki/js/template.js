/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       3.2
 */

(function($)
{
	$(document).ready(function()
	{
		$('*[rel=tooltip]').tooltip()

		// Turn radios into btn-group
		$('.radio.btn-group label').addClass('btn');
		$(".btn-group label:not(.active)").click(function()
		{
			var label = $(this);
			var input = $('#' + label.attr('for'));

			if (!input.prop('checked')) {
				label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
				if (input.val() == '') {
					label.addClass('active btn-primary');
				} else if (input.val() == 0) {
					label.addClass('active btn-danger');
				} else {
					label.addClass('active btn-success');
				}
				input.prop('checked', true);
			}
		});
		$(".btn-group input[checked=checked]").each(function()
		{
			if ($(this).val() == '') {
				$("label[for=" + $(this).attr('id') + "]").addClass('active btn-primary');
			} else if ($(this).val() == 0) {
				$("label[for=" + $(this).attr('id') + "]").addClass('active btn-danger');
			} else {
				$("label[for=" + $(this).attr('id') + "]").addClass('active btn-success');
			}
		});
        $('#comjshop')

        var siteUrl = document.location.origin+'/joomla';
        var productBigImg = $('.product .main-image img');
        var productSmallImgs = $('.product .image-list img');
        var catProductSmallImgs = $('.jshop_list_product .photo img');
        if (productBigImg.length>0){
            $("<img />").attr('src', productBigImg.attr('src'))
                .bind('error',  function(ev){
                    productBigImg.attr('src', siteUrl+ '/templates/pechki/img/default-image-big.png');
                });
        }
        if (productSmallImgs.length>0) {
            productSmallImgs.each(function(i, el){
                $("<img />").attr('src', $(el).attr('src'))
                    .bind('error',{'self' : el}, function(ev){
                        var self = ev.data.self;
                        $(self).attr('src', siteUrl+'/templates/pechki/img/default-image-small.png');
                    });
            });
        }
        if (catProductSmallImgs.length>0) {
            catProductSmallImgs.each(function(i, el){
                $("<img />").attr('src', $(el).attr('src'))
                    .bind('error',{'self' : el}, function(ev){
                        var self = ev.data.self;
                        $(self).attr('src', siteUrl+'/templates/pechki/img/default-image-small.png');
                    });
            });
        }

        $('.review-header').on('click', function(){
            $('.review-body').toggle();
        });

	})
})(jQuery);