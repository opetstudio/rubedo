function contentListChangePage(page, prefix, query, url, singlePage,limit,displayType) {
	if (jQuery('#list-' + prefix + ' > #list-' + prefix + '-' + page).length == 0) {
		var request = jQuery.ajax({
			url : url + '/blocks/content-list/xhr-get-items',
			type : "POST",
			data : {
				'page' : page,
				'prefix' : prefix,
				'query-id' : query,
				'single-page' : singlePage,
				'limit' : limit,
				'displayType':displayType
			},
			dataType : "json"
		});

		request.done(function(data) {
			var newHtml = data.html;
			jQuery('#list-' + prefix).append(newHtml);
			var pagerHtml = data.pager;
			jQuery('#list-pager-' + prefix).append(pagerHtml);
			//alert(pagerHtml);
		});

		request.fail(function(jqXHR, textStatus) {
		});
	}
	jQuery('#list-' + prefix + ' > .active').hide();
	jQuery('#list-' + prefix + ' > .active').removeClass('active');
	jQuery('#list-' + prefix + ' > #list-' + prefix + '-' + page).show();
	jQuery('#list-' + prefix + ' > #list-' + prefix + '-' + page).addClass('active');
	
	jQuery('#list-pager-' + prefix + ' > .active').hide();
	jQuery('#list-pager-' + prefix + ' > .active').removeClass('active');
	jQuery('#list-pager-' + prefix + ' > #list-pager-' + prefix + '-' + page).show();
	jQuery('#list-pager-' + prefix + ' > #list-pager-' + prefix + '-' + page).addClass('active');
	return false;
}