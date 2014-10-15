$(window).on('hashchange', function() {
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
});

$(document).ready(function(){
	if (typeof(DateInput) != 'undefined')
	{
		$('input.date_picker').date_input();
	}
	
	if (window.location.hash.indexOf('#!') != -1)
	{
		if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
		{
			$.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
		}
	}
	
	$.each($('span.label'), function (i, e) {
		if ($(e).find('input').length)
		{
			$(e).click(function () {
				$(this).find('input').click();
			});
			
			$(e).css('cursor', 'default').removeClass();
		}
	});
	
	// fix form bug...
    $("form[action='']").attr('action', window.location.href);
	
	/*左侧导航栏伸缩*/
	$('.side-nav > ul > li > a').click(function()
	{
		if ($(this).next().is(':visible'))
		{
			$(this).next().slideUp('normal');
		}else
		{
			$('.side-nav ul li').children('ul').slideUp('normal');
			$(this).next().slideDown('normal');
		}
	});

	/*信息box伸缩*/
	$('.aw-message-box h3').click(function(){
		if ($(this).parents('.aw-message-box').find('.aw-mod-body').is(':visible'))
		{
			$(this).parents('.aw-message-box').find('.aw-mod-body').slideUp();
		}else
		{
			$(this).parents('.aw-message-box').find('.aw-mod-body').slideDown();
		}
	});

	/*导航菜单收缩*/
	$('.aw-nav-menu li .aw-mod-head').click(function()
	{
		if ($(this).parents('li').find('.aw-mod-body').is(':visible'))
		{
			$(this).parents('li').find('.aw-mod-body').slideUp();
		}else
		{
			$(this).parents('li').find('.aw-mod-body').slideDown();
		}
	});
	
	// Check all checkboxes when the one in a table head is checked:
	$('.check-all').click(function() {
		var _this = this;
		
		$.each($(this).parents('table').find("input[type='checkbox']"), function (i, e) {
			if ($(e).is(':checked') != $(_this).is(':checked'))
			{
				$(e).click();
			}
		});
	});
});

$(window).scroll(function ()
{
    if ($('.aw-back-top').length)
    {
        if ($(window).scrollTop() > ($(window).height() / 2))
        {
            $('.aw-back-top').fadeIn();
        }
        else
        {
            $('.aw-back-top').fadeOut();
        }
    }

});