$().ready(function()
{	
	pagination();
	$(".imgLiquid").imgLiquid();
	
	$('.checkbox').click(function(){
		$(this).toggleClass('selected');
	});
	
	$('#removeCommits').click(function() 
	{
		var removeItems = $('.checkbox.selected');
		if (removeItems.length == 0)
			return;
		
		var removeIds = [];
		removeItems.each(function(){
			removeIds.push($(this).data('commit-id'))
		});
		
		var url = '/remove?ids=' + JSON.stringify({ids:removeIds});
		$.ajax(url).done(function(data) {
			if (JSON.parse(data).result == 'success')
				removeItems.each(function(){
					$(this).parent().parent().hide();
				});
		});
	});
	
	$('#reloadRepo').click(function() {
		window.location = window.location.pathname + '?reload=1';
	});
	
	function pagination()
	{
		var pageCount = $('#pager-container').data('pagecount');
		var curPage = $('#pager-container').data('currentpage');
		if (pageCount > 0)
		{
			$('#pager-container').pagination({
				pages: pageCount,
				currentPage: curPage,
				hrefTextPrefix: '?page=',
				cssStyle: 'light-theme'
			});
		}
	}
	
});