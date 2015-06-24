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

var actions;
var currentRetrieve;
var currentAction=0;
var nbActions=0;
var retrieves;
var processid="#process";
var requestid="#request";
var cancel=false;

$("input#cancel").hide();

function processNext(data) {
	var action = actions[currentAction];
	var redo=false;
	if (data instanceof String) {
		$("#"+action.line_id).html('<img src="images/check-off.png" alt="KO" /> '+data);
	} else if ($(data).find('rsp').attr('status') == 'ok') {
		if ($(data).find('redo').length > 0)
			redo=true;
		$("#"+action.line_id).html('<img src="images/check-on.png" alt="OK" />');
	} else {
		$("#"+action.line_id).html('<img src="images/check-off.png" alt="KO" /> '+$(data).find('message').text());
	}
	if (!redo)
		currentAction++;
	doProcess();

}

function doProcess() {
	if ((currentAction < actions.length) && !cancel) {
		var action = actions[currentAction];
		$("#"+action.line_id).html('<img src="index.php?pf=newsletter/progress.gif" alt="'+dotclear.msg.please_wait+'" />');
		action.params.xd_check=dotclear.nonce;
		$.post("services.php",action.params,processNext);
	} else {
		doRetrieve();
	}
}

function addLine(divid,id,desc,status_desc) {
	var td_id='action_'+nbActions;
	var line = '<tr class="removable"><td>'+id+'</td><td>'+desc+'</td><td id="'+td_id+'">'+status_desc+'</td>';
	$(divid).append(line);
	nbActions++;
	return td_id;
}

function doRetrieve() {
	if ((currentRetrieve < retrieves.length) && !cancel) {
		var retrieve = retrieves[currentRetrieve];
		var params = retrieve.request;
		$("#"+retrieve.line_id).html('<img src="index.php?pf=newsletter/progress.gif" alt="'+dotclear.msg.please_wait+'" />');
		$.get("services.php",retrieve.request,retrieve.callback);
	} else {
		// That's all folks !
		$("input,select").attr("disabled",false);
		$("input#cancel").hide();
	}

	currentRetrieve++;

}

actions=[];
retrieves=[];
