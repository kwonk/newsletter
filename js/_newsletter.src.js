/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of newsletter, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2015 Benoit de Marne and contributors
 * benoit.de.marne@gmail.com
 * Many thanks to Association Dotclear
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

$(document).ready(function(){
	
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	dotclear.postsActionsHelper();

	$('#subscribers_list').submit(function(){
		var action=$(this).find('select[name="op"]').val();
		if(action=='remove'){
			return window.confirm(dotclear.msg.confirm_delete_subscribers);
		}
		return true;
	});

	$('#erasingnewsletter').submit(function() {
		return window.confirm(dotclear.msg.confirm_erasing_datas);
	});	

	$('#import').submit(function() {
		return window.confirm(dotclear.msg.confirm_import_backup);
	});	

	$('#letters_list').submit(function(){
		var action=$(this).find('select[name="action"]').val();
		if(action=='delete'){
			return window.confirm(dotclear.msg.confirm_delete_letters);
		}
		return true;
	});
	
	$filtersform=$('#filters-form');
	$filtersform.before('<p><a id="filter-control" class="form-control" style="display:inline">'+dotclear.msg.filter_subscribers_list+'</a></p>')
	if(dotclear.msg.show_filters=='false'){
		$filtersform.hide();
	}else{
		$('#filter-control').addClass('open').text(dotclear.msg.cancel_the_filter);
	}
	
	$('#filter-control').click(function(){
		if($(this).hasClass('open')){
			if(dotclear.msg.show_filters=='true'){
				return true;
			}else{
				$filtersform.hide();
				$(this).removeClass('open').text(dotclear.msg.filter_subscribers_list);
			}
		}else{
			$filtersform.show();
			$(this).addClass('open').text(dotclear.msg.cancel_the_filter);
		}
		return false;
	});

});
