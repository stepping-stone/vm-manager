<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * and
 *
 * Copyright (C) 2013 stepping stone GmbH
 * Switzerland
 * http://www.stepping-stone.ch
 * support@stepping-stone.ch
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * http://www.osor.eu/eupl
 *
 * Unless required by applicable law or agreed to in
 * writing, software distributed under the Licence is
 * distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied.
 * See the Licence for the specific language governing
 * permissions and limitations under the Licence.
 *
 *
 */

$this->breadcrumbs=array(
	'VMTemplates'=>array('index'),
	'Manage',
);
$this->title = Yii::t('vmtemplate', 'Manage VMTemplates');
//$this->helpurl = Yii::t('help', 'manageVMTemplates');

$gridid = 'vmtemplates';
$baseurl = Yii::app()->baseUrl;
$imagesurl = $baseurl . '/images';
$detailurl = $this->createUrl('vmTemplate/view');
$updateurl = $this->createUrl('vmTemplate/update');
$deleteurl = $this->createUrl('vmTemplate/delete');
$finishurl = $this->createUrl('vmTemplate/finish');
$finishdynurl = $this->createUrl('vmTemplate/finishDynamic');
$nodeurl = $this->createUrl('node/view');
$restoreurl = $this->createUrl('vm/restoreVm');
$waitforrestoreactionurl = $this->createUrl('vmTemplate/waitForRestoreAction');
$getrestoreactionurl = $this->createUrl('vmTemplate/getRestoreAction');
$startrestoreactionurl = $this->createUrl('vmTemplate/startRestoreAction');
$cancelrestoreactionurl = $this->createUrl('vmTemplate/cancelRestoreAction');
$getcustomersurl = $this->createUrl('user/getCustomers');
$getpeopleurl = $this->createUrl('user/getPeople');
$setrelationshipurl = $this->createUrl('vmTemplate/setRelationship');

$actrefreshtime = Yii::app()->getSession()->get('vm_refreshtime', 10000);

Yii::app()->clientScript->registerScript('refresh', <<<EOS
var refreshTimeout = {$actrefreshtime};
var buttonState = [];
function refreshVmButtons(id, buttons) {
	$.each(buttons, function (key, active) {
		if (!(id in buttonState)) {
			buttonState[id] = [];
			if (!(key in buttonState[id])) {
				buttonState[id][key] = false;
			}
		}
		if (active) {
			if (!buttonState[id][key]) {
				$('#' + key + '_' + id).css({cursor: 'pointer'}).removeClass('notallowed');
				//$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '.png');
				switch(key) {
					case 'vm_start': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); startVm(id);}); break;
					case 'vm_reboot': break;
					case 'vm_shutdown': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); shutdownVm(id);}); break;
					case 'vm_destroy': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); destroyVm(id);}); break;
					case 'vm_migrate': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); selectNode(id);}); break;
					case 'vm_edit': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); editVm(id);}); break;
					case 'vm_del': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); deleteRow(id);}); break;
					case 'vm_toogle': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); toogleBoot(id);}); break;
					case 'vm_login': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); loginVm(id);}); break;
					case 'vmtemplate_finish': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); selectStaticPool(id);}); break;
					case 'vmtemplate_finishdyn': $('#' + key + '_' + id).click(function(event) {event.stopPropagation(); selectDynamicPool(id);}); break;
				}
			}
		}
		else {
			$('#' + key + '_' + id).css({cursor: 'auto'}).addClass('notallowed');
			//$('#' + key + '_' + id).attr('src', '{$imagesurl}/' + key + '_n.png');
			$('#' + key + '_' + id).unbind('click');
			if (key == 'vm_edit') {
				$('#{$gridid}_grid').setRowData(id, {'displayname' : row['name']});
			}
		}
		buttonState[id][key] = active;
	});
}
var row;
var cputimes = [];
var cputimes_max = [22, 100];
var vmcount = 0;
var timeoutid = -1;
var refreshDns = new Array();
function refreshVms()
{
	clearTimeout(timeoutid);
	var dns = '';
	var ids = $('#{$gridid}_grid').getDataIDs();
	vmcount = ids.length;
	for(var i=0;i < ids.length;i++)
	{
		var id = ids[i];
		row = $('#{$gridid}_grid').getRowData(id);
		if ('' != dns) dns += ';';
		dns += row['dn'];
		refreshDns.push(row['dn']);
	}

	refreshNextVm();
}

function refreshNextVm()
{
	dn = refreshDns.shift();
	$.ajax({
		url: "{$this->createUrl('vmTemplate/refreshVms')}",
		data: {dns:  dn, time: refreshTimeout},
		success: function(data){
			if (data['error'] != undefined) {
				if (1 == data['error']) {
					alert(data['message']);
					return;
				}
			}
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var id = ids[i];
				var row = $('#{$gridid}_grid').getRowData(id);
				if (data[row['uuid']] == undefined) continue;
				var status = data[row['uuid']]['status'];
				switch(status)
				{
					case 'unknown':
						buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'red';
						break;
					case 'running':
						switch(data[row['uuid']]['substatus']) {
							case 'backing up':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false,  'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false}; break;
							//case 'streaming':	buttons = {'vm_start': false, 'vm_shutdown': true, 'vm_destroy': true, 'vm_migrate': true,  'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': true, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false}; break;
							case 'migrating':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false}; break;
							default: 			buttons = {'vm_start': false, 'vm_shutdown': true, 'vm_destroy': true, 'vm_migrate': true,  'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': true, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false}; break;
						}
						if ('first installation' == data[row['uuid']]['machinemode'] ||
							'maintenance' == data[row['uuid']]['machinemode']  ||
							'do not use' == data[row['uuid']]['machinemode'] ) {
							buttons.vm_start = buttons.vm_edit = buttons.vm_de = buttons.vm_login = buttons.vmtemplate_finish = buttons.vmtemplate_finishdyn = false;
						}
						state = 'green';
						break;
//					case 'migrating':
//						buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
//						state = 'yellow';
//						break;
					case 'stopped':
						switch(data[row['uuid']]['substatus']) {
							case 'backing up':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false}; break;
							case 'streaming':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true}; break;
							case 'migrating':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': true, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true}; break;
							case 'preparing':	buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true}; break;
							default: 			buttons = {'vm_start': true, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': true, 'vm_edit': true, 'vm_del': true, 'vm_toogle': true, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true}; break;
						}
						if ('ready for use' == data[row['uuid']]['machinemode'] ) {
							buttons.vm_start = buttons.vm_shutdown = buttons.vm_destroy = buttons.vm_migrate = buttons.vm_edit = buttons.vm_del = buttons.vm_toogle = buttons.vm_login = false;
						}
						else if ('first installation' == data[row['uuid']]['machinemode']  ||
							'maintenance' == data[row['uuid']]['machinemode']  ||
							'do not use' == data[row['uuid']]['machinemode'] ) {
							buttons.vm_shutdown = buttons.vm_destroy = buttons.vm_login = buttons.vmtemplate_finish = buttons.vmtemplate_finishdyn = false;
						}
						state = 'red';
						break;
					case 'shutdown':
						buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': true, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
						state = 'red';
						break;
// 					case 'preparing':
// 						buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': true, 'vmtemplate_finishdyn': true};
// 						state = 'red';
// 						break;
// 					case 'backup':
// 						buttons = {'vm_start': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_toogle': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
// 						state = 'red';
// 						break;
				}

				status = data[row['uuid']]['_t_status']
				if ('' != data[row['uuid']]['machinemode']) {
					status += ', ' + data[row['uuid']]['_t_machinemode'];
				}
				if ('' != data[row['uuid']]['substatus']) {
					status += ', ' + data[row['uuid']]['_t_substatus'];
				}
				
				refreshVmButtons(id, buttons);
				if (buttons['vm_edit'])
				{
					displayname = '<a href="${updateurl}?dn=' + row['dn'] + '">' + row['name'] + '</a>';
				}
				else
				{
					displayname = row['name'];
				}
				stateimg = 'vm_status_' + state;
				node = '<a href="${nodeurl}?node=' + data[row['uuid']]['node'] + '">' + data[row['uuid']]['node'] + '</a>';

				spice = data[row['uuid']]['spice'];
				$('#{$gridid}_grid').setCell(id, 'spice', spice);
				if ('green' == state) {
					mem = data[row['uuid']]['mem'];
					cpu = data[row['uuid']]['cpu'];
					cputext = '<div id="cpu_' + id + '" style="float: left; height:16px;"></div><div style="float: right; background-color: #E7DFDA; width: 3em;">' + cpu + '%</div>';

					if (cputimes[row['uuid']] == undefined) {
						cputimes[row['uuid']] = [0];
					}
	                		cputimes[row['uuid']].push(cpu);
                			if (cputimes[row['uuid']].length > cputimes_max[0])
                    				cputimes[row['uuid']].splice(0,1);

					if (cputimes[row['uuid'] + 2] == undefined) {
						cputimes[row['uuid'] + 2] = [0];
					}
	                		cputimes[row['uuid'] + 2].push(cpu);
                			if (cputimes[row['uuid'] + 2].length > cputimes_max[1])
                    				cputimes[row['uuid'] + 2].splice(0,1);
				}
				else {
					mem = '---';
					cpu = 0;
					cputext = '---';
				}
				$('#{$gridid}_grid').setCell(id, 'status', status, {'padding-left': '20px', background: 'url({$imagesurl}/' + stateimg + '.png) no-repeat 3px 3px transparent'});

				var change = {};

				change['displayname'] = displayname;
				change['mem'] = mem;
				change['node'] = node;
				$('#{$gridid}_grid').setRowData(id, change);

				//$('#spice' + i).attr('href', spice);

//				$('#{$gridid}_grid').setCell(id, 'cpu', cputext, {'font-weight': 'normal'});
//				$('#cpu_' + id).sparkline(cputimes[row['uuid']], { lineColor: '#000000',
//			                fillColor: '', //#E7DFDA',
//			                spotColor: '#000000',
//			                chartRangeMin: 0,
//			                chartRangeMax: 100,
//	        		        //minSpotColor: '#000000',
//			                //maxSpotColor: '#000000',
//	        		        normalRangeMin: 0,
//					normalRangeMax: 50,
//					normalRangeColor: '#68C760',
//			                spotRadius: 2,
//	        		        lineWidth: 1,
//				 });
//				$('#cpu2_' + id).sparkline(cputimes[row['uuid'] + 2], { lineColor: '#000000',
//			                fillColor: '', //#E7DFDA',
//	        		        spotColor: '#000000',
//			                chartRangeMin: 0,
//	        		        chartRangeMax: 100,
//			                width : '150px',
//					height : '50px',
//	        		        //minSpotColor: '#000000',
//			                //maxSpotColor: '#000000',
//	        		        normalRangeMin: 0,
//					normalRangeMax: 50,
//					normalRangeColor: '#68C760',
//			                spotRadius: 2,
//	        		        lineWidth: 1,
//				 });
			}
			vmcount--;
			if (0 == vmcount) {
				//timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
			if (0 < refreshDns.length) {
				timeoutid = setTimeout(refreshNextVm, 100);
			}
			else {
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
		},
		error:  function(req, status, error) {
			vmcount--;
			if (0 == vmcount) {
				//timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
			//window.alert(status);
			if (0 < refreshDns.length) {
				timeoutid = setTimeout(refreshNextVm, 100);
			}
			else {
				timeoutid = setTimeout(refreshVms, refreshTimeout);
			}
		},
		datatype: "json"
	});
	//}
}
function startVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	//$('#{$gridid}_grid').setRowData(id, {'displayname' : row['name'], 'act' : ''});
	$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/startVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'starting', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
			clearTimeout(timeoutid);
			refreshDns.push(row['dn']);
			refreshNextVm();
  		}
	});
}
function shutdownVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/shutdownVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 != err) {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'stopping', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function rebootVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'rebooting', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/rebootVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'rebooting', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}
function destroyVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false, 'vm_edit': false, 'vm_del': false, 'vm_login': false, 'vmtemplate_finish': false, 'vmtemplate_finishdyn': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$.ajax({
		url: "{$baseurl}/vmTemplate/destroyVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'],
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'destroying', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
			clearTimeout(timeoutid);
			refreshDns.push(row['dn']);
			refreshNextVm();
  		}
	});
}

function migrateVm(id, newnodedn)
{
	$('#migrateNode').attr('disabled', 'disabled');
	$('#migrateNode').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorNode').css('display', 'none');
	$('#infoNode').css('display', 'block');
	$('#infoNodeMsg').html('Migration started');
	$.ajax({
		url: "{$baseurl}/vmTemplate/migrateVm",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'] + '&newnode=' + newnodedn,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 != err) {
				$('#infoNode').css('display', 'none');
				$('#errorNode').css('display', 'block');
				$('#errorNodeMsg').html($(xml).find('message').text());
				if (2 == err) {
					$('#migrateNode').removeAttr('disabled');
					$('#migrateNode').removeClass('ui-state-disabled');
				}
			}
			else {
				$('#errorNode').css('display', 'none');
				$('#infoNode').css('display', 'block');
				$('#infoNodeMsg').html($(xml).find('message').text());
				var refresh = $(xml).find('refresh');
				refresh = refresh.text();
				if (1 == refresh) {
					$('#{$gridid}_grid').trigger("reloadGrid");
				}
			}
			$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/vm_status_unknown.png) no-repeat 3px 3px transparent'});
  		}
	});
}

function editVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = '{$updateurl}?dn=' + row['dn'];
}
function loginVm(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	location.href = row['spice'];
}
function deleteRow(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#{$gridid}_grid').delGridRow(id, {'delData': {'dn': row['dn']}, 'afterSubmit': function(response, postdata){
		var xml = response.response;
		var err = $(xml).find('error');
		err = err.text();
		if (0 != err) {
			return new Array(false, $(xml).find('message').text());
		}
		else {
			return new Array(true, '');
		}
	}});
}

function toogleBoot(id) //, device)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$.ajax({
		url: "{$baseurl}/vmTemplate/toogleBoot",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'], // + '&dev=' + device,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 == err) {
			}
			else {
				alert($(xml).find('message').text());
			}
			$('#{$gridid}_grid').trigger("reloadGrid");
  		}
	});
}
function restoreVm(evt)
{
	clearTimeout(timeoutid);
	timeoutid = -1;
	$.ajax({
		url: "{$restoreurl}",
		data: 'dn=' + evt.data.backupDn,
		success: function(data) {
			if (data['err']) {
				$("#vmdialogtext").html(data['msg']);
				$("#vmdialog").dialog({
					title: 'Error restoring Vm',
					resizable: true,
					modal: true,
					buttons: {
						Cancel: function() {
							$(this).dialog('close');
						}
					}
				});
			}
			else {
				$("#vmdialogtext").html(data['msg']);
				$("#vmdialog").dialog({
					title: 'Restore Vm',
					resizable: true,
					minWidth: 330,
					modal: false,
					open: function( event, ui ) {
						$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: true}); //.attr('disabled', 'disabled');
					},
					buttons: {
						OK: function() {
							$.ajax({
								url: "{$startrestoreactionurl}",
								data: 'dn=' + waitForRestoreActionDn,
								success: function(data) {
									timeoutid = -1;
									$('#{$gridid}_grid').trigger('reloadGrid');
								},
								dataType: 'json'
							});
							$(this).dialog('close');
						},
						Cancel: function() {
							$.ajax({
								url: "{$cancelrestoreactionurl}",
								data: 'dn=' + waitForRestoreActionDn,
								success: function(data) {
									timeoutid = -1;
									$('#{$gridid}_grid').trigger('reloadGrid');
								},
								dataType: 'json'
							});
						$(this).dialog('close');
						}
					}
				});
//				alert("NOW");
				waitForRestoreActionDn = evt.data.backupDn;
				timeoutid = setTimeout(waitForRestoreAction, 3000);
			}
			
			$('#{$gridid}_grid').trigger('reloadGrid');
		},
		dataType: 'json'
	});
}
var waitForRestoreActionDn = null;
function waitForRestoreAction()
{
	$.ajax({
		url: "{$waitforrestoreactionurl}",
		data: 'dn=' + waitForRestoreActionDn,
		success: function(data) {
			if (data['err']) {
				$("#vmdialogtext").html(data['msg']);
				if (undefined != data['refresh'] && data['refresh']) {
					timeoutid = setTimeout(waitForRestoreAction, 5000);
				}
				else {
					$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: false});
				}
			}
			else {
				$("#vmdialogtext").html(data['msg']);
				clearTimeout(timeoutid);
				$("#vmdialog ~ .ui-dialog-buttonpane button").button({disabled: false});
				timeoutid = -1;
//				timeoutid = setTimeout(getRestoreAction, 100);
			}
		},
		dataType: 'json'
	});
}
function getRestoreAction()
{
	$("#vmdialogtext").load("{$getrestoreactionurl}", {'dn': waitForRestoreActionDn}, function(response, status, xhr) {
		var a = 12;
		if (status == "error") {
		}
		else {
			//timeoutid = setTimeout(refreshVms, 1000);
		}
	});
}
EOS
, CClientScript::POS_END);

$nodeGuiUrl = $this->createUrl('vmTemplate/getNodeGui');
$migratetxt = Yii::t('vm', 'Migrate');

Yii::app()->clientScript->registerScript('selectNode', <<<EOS
function selectNode(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#startnode').fancybox({
		'modal'			: false,
		'href'			: '{$nodeGuiUrl}?dn=' + row['dn'],
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 300,
		//'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#nodeSelection_singleselect').singleselect({
				'sorted':true,
				'header':'Nodes',
			});
			$('#migrateNode').button({icons: {primary: "ui-icon-disk"}, label: '{$migratetxt}'})
			.click(function() {
				var selected = $('#nodeSelection_singleselect').singleselect("values");
				migrateVm(id, selected[0]);
				//$.fancybox.close();
			});
		},
		'onClosed'	: function() {
			$('#selectNode').hide();
		}
	});
	$('#startnode').trigger('click');
}
EOS
, CClientScript::POS_END);

$selectStaticGuiUrl = $this->createUrl('vmTemplate/getStaticPoolGui');
$selectStaticTxt = Yii::t('vmtemplate', 'Create persistent VM');
$selectStaticOkButtonTxt = Yii::t('vmtemplate', 'Create');
$selectStaticCancelButtonTxt = Yii::t('vmtemplate', 'Cancel');

$getPersistentCreationDataUrl = $this->createUrl('vmTemplate/getPersistentCreationData');

Yii::app()->clientScript->registerScript('finish', <<<EOS
//function finish(id, pooldn, name, subtype, domainname, hostname)
function finish(id, formdata)
{
	$('#selectStaticOkButton').attr('disabled', 'disabled');
	$('#selectStaticOkButton').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorSelectStatic').css('display', 'none');
	$('#infoSelectStatic').css('display', 'block');
	$('#infoSelectStaticMsg').html('<img src="{$imagesurl}/loading.gif" alt=""/>{$selectStaticTxt}');
	$.ajax({
		type: "POST",
		url: "{$baseurl}/vmTemplate/finish",
		cache: false,
		dataType: 'json',
		//data: 'dn=' + row['dn'] + '&pool=' + pooldn + '&name=' + name + '&subtype=' + subtype + '&domainname=' + domainname + '&hostname=' + hostname,
		data: 'dn=' + row['dn'] + '&' + formdata,
		success: function(data) {
			if (0 != data.error) {
				$('#infoSelectStatic').css('display', 'none');
				$('#errorSelectStatic').css('display', 'block');
				$('#errorSelectStaticMsg').html(data.message);
				if (2 == data.error) {
					$('#selectStaticOkButton').removeAttr('disabled');
					$('#selectStaticOkButton').removeClass('ui-state-disabled');
				}
			}
			else {
				message = data.message;
				if ('' != message) {
					$('#errorSelectStatic').css('display', 'none');
					$('#infoSelectStatic').css('display', 'block');
					$('#infoSelectStaticMsg').html(message);
				}
				else {
					var url = data.url;
					window.location.replace(url);
				}
			}
  		}
	});
}

function selectStaticPool(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$("#finishdialog").dialog({
		resizable: true,
		width: 'auto',
		height: 'auto',
		modal: true,
		open: function(event, ui) {
			$('#selectStaticOkButton').attr('disabled', 'disabled');
			$('#selectStaticOkButton').addClass('ui-state-disabled');
			$.get('{$getPersistentCreationDataUrl}', {'dn': row['dn']}, function(data) {
				$("#finishId").val(id);
				$("#finishPool").val("");
				$("#finishNode").val("").empty();
				$("#finishStack").val("");
				$("#finishEnv").val("");
				$("#displayname").val(data.fullname);
				$("#finishDisplayname").val(data.fullname);
				$("#finishHostname").val(data.hostname);
				$("#finishDomainname").val(data.domainname);
				// softwarestacks defined as 'global'
				softwarestacks = data.stacks;
				if (null != softwarestacks) {
					$("#finishStack").empty().prop('disabled', false).append($('<option value=""></option>'));
					$.each(softwarestacks, function(key, val) {
						$("#finishStack").append($('<option value="' + key + '">' + val.name + '</option>'));
					});
					$("#finishEnv").empty().prop('disabled', false);
				}
				else {
					$("#finishStack").empty().prop('disabled', true);
					$("#finishEnv").empty().prop('disabled', true);
				}			
				// resellers defined as 'global'
				resellers = data.resellers;
				if (null != resellers) {
					$("#finishReseller").empty().prop('disabled', false).append($('<option value=""></option>'));
					$.each(resellers, function(key, val) {
						$("#finishReseller").append($('<option value="' + key + '">' + val.name + '</option>'));
					});
					$("#finishReseller").val(data.reseller);
					$("#finishCustomer").empty().prop('disabled', false).append($('<option value=""></option>'));
					$.each(resellers[data.reseller].customers, function(key, val) {
						$("#finishCustomer").append($('<option value="' + key + '">' + val + '</option>'));
					});
					$("#finishCustomer").val(data.customer);
				}
				else {
					$("#finishReseller").empty().prop('disabled', true);
					$("#finishCustomer").empty().prop('disabled', true);
				}			
				$('#selectStaticOkButton').removeAttr('disabled');
				$('#selectStaticOkButton').removeClass('ui-state-disabled');
			}, 'json');
		},
		buttons:  [
			{
				text: '{$selectStaticOkButtonTxt}',
				id: 'selectStaticOkButton',
				click: function() {
					finish($("#finishId").val(), $("#finishForm").serialize());
				}
			},
			{
				text: '{$selectStaticCancelButtonTxt}',
				click: function() {
					$(this).dialog('close');
				}
			}
		]
	});
}
EOS
, CClientScript::POS_END);

$selectDynamicGuiUrl = $this->createUrl('vmTemplate/getDynamicPoolGui');
$selectDynamicTxt = Yii::t('vm', 'Create dynamic VM');

Yii::app()->clientScript->registerScript('finishdyn', <<<EOS
function finishDyn(id, pooldn, name, sysprep)
{
	$('#selectDynamicButton').attr('disabled', 'disabled');
	$('#selectDynamicButton').addClass('ui-state-disabled');

	var row = $('#{$gridid}_grid').getRowData(id);
	buttons = {'vm_start': false, 'vm_restart': false, 'vm_shutdown': false, 'vm_destroy': false, 'vm_migrate': false};
	refreshVmButtons(id, buttons);
	$('#{$gridid}_grid').setCell(id, 'status', 'migrating', {'padding-left': '20px', background: 'url({$imagesurl}/loading.gif) no-repeat 3px 3px transparent'});
	$('#errorSelectDynamic').css('display', 'none');
	$('#infoSelectDynamic').css('display', 'block');
	$('#infoSelectDynamicMsg').html('<img src="{$imagesurl}/loading.gif" alt=""/> Create dynamic VM');
	$.ajax({
		url: "{$baseurl}/vmTemplate/finishDynamic",
		cache: false,
		dataType: 'xml',
		data: 'dn=' + row['dn'] + '&pool=' + pooldn + '&name=' + name + '&sysprep=' + sysprep,
		success: function(xml){
			var err = $(xml).find('error');
			err = err.text();
			if (0 != err) {
				$('#infoSelectDynamic').css('display', 'none');
				$('#errorSelectDynamic').css('display', 'block');
				$('#errorSelectDynamicMsg').html($(xml).find('message').text());
				if (2 == err) {
					$('#selectDynamicButton').removeAttr('disabled');
					$('#selectDynamicButton').removeClass('ui-state-disabled');
				}
			}
			else {
				var message = $(xml).find('message');
				message = message.text();
				if ('' != message) {
					$('#errorSelectDynamic').css('display', 'none');
					$('#infoSelectDynamic').css('display', 'block');
					$('#infoSelectDynamicMsg').html(message);
				}
				else {
					var url = $(xml).find('url');
					url = url.text();
					window.location.replace(url);
				}
			}
  		}
	});
}

function selectDynamicPool(id)
{
	var row = $('#{$gridid}_grid').getRowData(id);
	$('#selectDynamicPool').fancybox({
		'modal'			: false,
		'href'			: '{$selectDynamicGuiUrl}?name=' + row['name'],
		'type'			: 'inline',
		'autoDimensions': true,
		//'width'		: 340,
		//'height'		: 320,
		'scrolling'		: 'no',
		'onComplete'	: function() {
			$('#dynamicpoolSelection_singleselect').singleselect({
				'sorted':true,
				'header':'Pools',
			});
			//$( "#radiosysprep" ).buttonset();

			$('#selectDynamicButton').button({icons: {primary: "ui-icon-disk"}, label: '{$selectDynamicTxt}'})
			.click(function() {
				var selected = $('#dynamicpoolSelection_singleselect').singleselect("values");
				finishDyn(id, selected[0], $('#displayname').val(), $('#radiosysprep').attr('checked'));
				//$.fancybox.close();
			});
		},
		'onClosed'	: function() {
			$('#selectDynamicPool').hide();
		}
	});
	$('#selectDynamicPool').trigger('click');
}
EOS
, CClientScript::POS_END);
//Yii::app()->clientScript->registerScriptFile($baseurl . '/js/jquery.sparkline.min.js');

$refreshtimes = array(5 => 5000, 10 => 10000, 20 => 20000, 60 => 60000);
$refreshoptions = '';
foreach($refreshtimes as $key => $value) {
	$refreshoptions .= '<option value="' . $value . '" ' . ($actrefreshtime == $value ? 'selected="selected"' : '') . '>' .$key . '</option>';
}
$this->widget('ext.zii.CJqGrid', array(
	'extend'=>array(
		'id' => $gridid,
		'locale'=>'en',
		'pager'=>array(
			'Standard'=>array('edit'=>false, 'add' => false, 'del' => false, 'search' => false),
		),
		'filter' => array(
			'stringResult' => false,'searchOnEnter' => false
		),
	),
     'options'=>array(
		'pager'=>$gridid . '_pager',
		'url'=>$baseurl . '/vmTemplate/getVmTemplates',
		'datatype'=>'xml',
		'mtype'=>'GET',
		'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Boot', 'OrigName', 'Name', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),
		'colModel'=>array(
			array('name'=>'no','index'=>'no','width'=>'30','align'=>'right', 'sortable' => false, 'search' =>  false, 'editable'=>false),
			array('name'=>'dn','index'=>'dn','hidden'=>true,'editable'=>false),
			array('name'=>'uuid','index'=>'uuid','hidden'=>true,'editable'=>false),
			array('name'=>'spice','index'=>'spice','hidden'=>true,'editable'=>false),
			array('name'=>'boot','index'=>'boot','hidden'=>true,'editable'=>false),
			array('name'=>'name','index'=>'name','hidden'=>true,'editable'=>false),
			array('name'=>'displayname','index'=>'sstDisplayName','editable'=>false),
			array('name'=>'status','index'=>'status', 'sortable' =>false, 'search' =>false, 'editable'=>false),
			array('name'=>'statusact','index'=>'statusact','width' => 76, 'fixed' => true, 'sortable' =>false, 'search' =>false, 'editable'=>false),
			array('name'=>'mem','index'=>'mem','width' => '100', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false),
			array('name'=>'cpu','index'=>'cpu','width' => '104', 'fixed' => true, 'align'=>'center', 'sortable' =>false, 'search' => false, 'editable'=>false, 'hidden'=>true),
			array('name'=>'node','index'=>'sstNode','editable'=>false),
			array ('name' => 'act','index' => 'act','width' => 118, 'fixed' => true, 'sortable' => false, 'search' =>  false, 'editable'=>false)
			// 'width' => 124
		),
		//'toolbar' => array(true, "top"),
		//'caption'=>'VMs',
		'autowidth'=>true,
		'rowNum'=>10,
		'rowList'=> array(10,20,30),
		'height'=>300,
		'altRows'=>false,
		'editurl'=>$deleteurl,
		'subGrid' => true,
//		'subGridUrl' =>$baseurl . '/vmtemplate/getVmInfo',
      		'subGridRowExpanded' => 'js:' . <<<EOS
      	function(pID, id) {
      		$('#{$gridid}_grid_' + id).html('<img src="{$imagesurl}/loading.gif"/>');
      		var row = $('#{$gridid}_grid').getRowData(id);
			$.ajax({
				url: "{$baseurl}/vmTemplate/getVmInfo",
				cache: false,
				data: "dn=" + row['dn'] + "&rowid=" + id,
				success: function(html){
					$('#{$gridid}_grid_' + id).html(html);
					$("img[alt=restore]").each(function() {
						$(this).click({backupDn: $(this).attr("backupDn")}, restoreVm);
					});
				}
			});
      	}
EOS
,
		'gridComplete' =>  'js:' . <<<EOS
		function()
		{
			var ids = $('#{$gridid}_grid').getDataIDs();
			for(var i=0;i < ids.length;i++)
			{
				var row = $('#{$gridid}_grid').getRowData(ids[i]);
				if ('cdrom' == row['boot']) {
					bootdevice = 'hd';
				}
				else if ('hd' == row['boot']) {
					bootdevice = 'cdrom';
				}
				var statusact = '';
				statusact += '<img id="vm_start_' + ids[i] + '" src="{$imagesurl}/vm_start.png" alt="" title="start VM" class="action notallowed" />';
				statusact += '<img id="vm_shutdown_' + ids[i] + '" src="{$imagesurl}/vm_shutdown.png" alt="" title="shutdown VM" class="action notallowed" />';
				statusact += '<img id="vm_destroy_' + ids[i] + '" src="{$imagesurl}/vm_destroy.png" alt="" title="destroy VM" class="action notallowed" />';
				statusact += '<img id="vm_migrate_' + ids[i] + '" src="{$imagesurl}/vm_migrate.png" alt="" title="migrate VM" class="action notallowed" />';
				var act = '';
				//act += '<a href="${detailurl}?dn=' + row['dn'] + '"><img src="{$imagesurl}/vmtemplate_detail.png" alt="" title="view VM Template" class="action" /></a>';
				act += '<img id="vm_edit_' + ids[i] + '" src="{$imagesurl}/vm_edit.png" alt="" title="edit VM Template" class="action notallowed" />';
				act += '<img id="vm_del_' + ids[i] + '" src="{$imagesurl}/vm_del.png" alt="" title="delete VM Template" class="action notallowed" />';
				act += '<img id="vm_toogle_' + ids[i] + '" src="{$imagesurl}/vmtemplate_' + row['boot'] + '.png" alt="" title="toogle bootdevice to ' + bootdevice + '" class="action notallowed" />';
				act += '<img id="vm_login_' + ids[i] + '" src="{$imagesurl}/vm_login.png" alt="" title="use VM Template" class="action notallowed" />';
				act += '<img id="vmtemplate_finish_' + ids[i] + '" src="{$imagesurl}/vmtemplate_finish.png" alt="" title="VM Template =&gt; persistent VM" class="superaction notallowed"/>';
				act += '<img id="vmtemplate_finishdyn_' + ids[i] + '" src="{$imagesurl}/vmtemplate_finishdyn.png" alt="" title="VM Template =&gt; dynamic VM" class="superaction notallowed"/>';
				//act += '<img src="{$imagesurl}/vm_del.png" alt="" title="delete VM" class="action" onclick="deleteRow(\'' + ids[i] + '\');" />';
				var node = '<a href="${nodeurl}?node=' + row['node'] + '">' + row['node'] + '</a>';
				$('#{$gridid}_grid').setRowData(ids[i],{'displayname': row['name'], 'act': act, 'statusact': statusact, 'node': node});
			}
			if (-1 == timeoutid)
			{
				clearTimeout(timeoutid);
				timeoutid = setTimeout(refreshVms, 1000);
				$('#{$gridid}_pager_right').html('<table cellspacing="0" cellpadding="0" border="0" class="ui-pg-table" style="table-layout: auto; float: right;"><tbody><tr><td><input type="button" id="{$gridid}_refreshNow" value="Refresh"/></td><td><select id="{$gridid}_refresh" class="ui-pg-selbox">{$refreshoptions}</select></td></tr></tbody></table>');
				$('#{$gridid}_refreshNow').click(function() {
					clearTimeout(timeoutid);
					refreshVms();
				});
				$('#{$gridid}_refresh').change(function() {
					refreshTimeout = $(this).val();
				});
			}
			buttonState = [];
		}
EOS
,
		'onPaging' =>  'js:' . <<<EOS
		function()
		{
			buttonState = [];
		}
EOS
	),
	'theme' => 'osbd',
	'themeUrl' => $this->cssBase . '/jquery',
	'cssFile' => 'jquery-ui.custom.css',
));
?>
<div style="display: none;">
<a id="startcheck" href="#checkCopy">checkcopy</a>
<div id="checkCopy">
</div>
</div>
<?php
if (!is_null($copyaction)) {
	$checkCopyGuiUrl = $this->createUrl('vmTemplate/getCheckCopyGui');
	Yii::app()->clientScript->registerScript('checkCopy', <<<EOS
	$('#startcheck').fancybox({
		'modal'			: false,
		'href'			: '{$checkCopyGuiUrl}?pid={$copyaction}',
		'type'			: 'inline',
		'autoDimensions': false,
		'width'			: 450,
		'height'		: 120,
		'scrolling'		: 'no',
		'hideOnOverlayClick' : false,
		'onComplete'	: function() {
			clearTimeout(timeoutid);
			check();
		},
		'onClosed'		: function () {
			clearTimeout(timeoutid);
			timeoutid = setTimeout(refreshVms, 100);
		}
	});
	function check() {
		$.ajax({
			url: "{$baseurl}/vmTemplate/checkCopy",
			data: 'pid={$copyaction}',
			success: function(data) {
				if (!data['err']) {
					$('#running').css('display', 'none');
					$('#errorAssignment').css('display', 'none');
					$('#infoAssignment').css('display', 'block');
					$('#infoMsg').html(data['msg']);
				}
				else {
//					$('#infoAssignment').css('display', 'none');
//					$('#errorAssignment').css('display', 'block');
//					$('#errorMsg').html(data['msg']);
					timeoutid = setTimeout(check, 4000);
				}
				//$('#checkCopy').hide();
				//$.fancybox.close();
			},
			dataType: 'json'
		});
	}
	clearTimeout(timeoutid);
	dummy = setTimeout("$('#startcheck').trigger('click')", 250);
EOS
	, CClientScript::POS_END);
}

?>
<div style="display: none;">
	<a id="startnode" href="#selectNode">start node</a>
	<div id="selectNode">
	</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqSingleselect', array(
		'id' => 'nodeSelection',
		'values' => array(),
		'size' => 5,
		'multiselect' => false,
		'options' => array(
			'sorted' => true,
			'header' => Yii::t('vm', 'Nodes'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'singleselect.css',
	));
?>
<div id="vmdialog" title="" style="display: none;">
	<span class="ui-icon ui-icon-notice" style="float:left; margin:0 7px 0 0;"></span>
	<div id="vmdialogtext"></div>
</div>
<div id="finishdialog" title="<?php echo Yii::t('vm', 'Create persistent VM'); ?>" style="display: none;">
<form id="finishForm">
<?php 
	
	Yii::app()->clientScript->registerScript('finishdialog', <<<EOS
$("#finishPool").change(function() {
	var value = $(this).val();
	$("#finishNode").empty().append($('<option value=""></option>'));
	if ('' != value) {
	 	var nodes = persistentpools[value]['nodes'];
	 	$.each(nodes, function(key, val) {
			$("#finishNode").append($('<option value="' + key + '">' + val + '</option>'));
		});
 	}
});
$("#finishStack").change(function() {
	var value = $(this).val();
	$("#finishEnv").empty().append($('<option value=""></option>'));
	if ('' != value) {
 		var envs = softwarestacks[value]['env'];
		$.each(envs, function(key, val) {
			$("#finishEnv").append($('<option value="' + key + '">' + val + '</option>'));
		});
 	}
});
$("#finishReseller").change(function() {
	var value = $(this).val();
	$("#finishCustomer").empty().append($('<option value=""></option>'));
	if ('' != value) {
 		var customers = resellers[value]['customers'];
		$.each(customers, function(key, val) {
			$("#finishCustomer").append($('<option value="' + key + '">' + val + '</option>'));
		});
 	}
});
EOS
, CClientScript::POS_READY);
	
	$ppools = CJSON::encode($persistentpools);
	
	Yii::app()->clientScript->registerScript('finishdialog2', <<<EOS
	var persistentpools =  $.parseJSON('{$ppools}');
	var softwarestacks = null;
	var resellers = null;
EOS
, CClientScript::POS_END);
	
	$parray = array();
	foreach($persistentpools as $key => $pool) {
		$parray[$key] = $pool['name'];
	}
?>
		<?php echo CHtml::hiddenField('FinishForm[id]','', array('id' => 'finishId')); ?>
		<div>
			<label for="finishPool" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationPool'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[pool]', '', $parray, array('prompt' => '', 'id' => 'finishPool')); ?>
		</div>
		<br/>
		<div>
			<label for="finishNode" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationNode'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[node]', '', array(), array('prompt' => '', 'id' => 'finishNode')); ?>
		</div>
		<br/>
		<div>
			<label for="displayname" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationName'); ?> </label>
			<input type="text" id="displayname" name="displayname" disabled="disabled" value=""/>
			<input type="hidden" id="finishDisplayname" name="FinishForm[displayname]" value=""/>
			<input type="hidden" id="finishHostname" name="FinishForm[hostname]" value="" />
			<input type="hidden" id="finishDomainname" name="FinishForm[domainname]" value="" />
		</div>
		<br/>
		<div id="radiosubtype" style="">
			<label style="width: 180px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationType'); ?> </label>
			<input type="radio" id="radiosubtype1" name="FinishForm[subtype]" value="Server" checked="checked" /><label for="radiosubtype1">Server</label>
			<input type="radio" id="radiosubtype2" name="FinishForm[subtype]" value="Desktop" /><label for="radiosubtype2">Desktop</label>
		</div>
		<br/>
		<div>
			<label for="finishReseller" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationReseller'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[reseller]', '', array(), array('prompt' => '', 'id' => 'finishReseller')); ?>
		</div>
		<br/>
		<div>
			<label for="finishCustomer" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationCustomer'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[customer]', '', array(), array('prompt' => '', 'id' => 'finishCustomer')); ?>
		</div>
		<br/>
		<div>
			<label for="finishStack" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationSoftwareStack'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[stack]', '', array(), array('prompt' => '', 'id' => 'finishStack')); ?>
		</div>
		<br/>
		<div>
			<label for="finishEnv" style="width: 150px; float: left;"><?php echo Yii::t('vmtemplate', 'CreationEnvironment'); ?> </label>
			<?php echo CHtml::dropDownList('FinishForm[env]', '', array(), array('prompt' => '', 'id' => 'finishEnv')); ?>
		</div>
		<div id="errorSelectStatic" class="ui-state-error ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectStaticMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectStatic" class="ui-state-highlight ui-corner-all" style="display: none; width: 90%; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectStaticMsg"></span></p>
		</div>
</form>
</div>
<?php /* ?>
<div style="display: none;">
	<a id="startStaticPool" href="#selectStaticPool">start finish</a>
	<div id="selectStaticPool">
	</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqSingleselect', array(
		'id' => 'staticpoolSelection',
		'values' => array(),
		'size' => 5,
		'multiselect' => false,
		'options' => array(
			'sorted' => true,
			'header' => Yii::t('vm', 'persistent VM Pools'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'singleselect.css',
	));
?>
<?php */ ?>
<div style="display: none;">
	<a id="startDynamicPool" href="#selectDynamicPool">start finish dyn</a>
	<div id="selectDynamicPool">
	</div>
</div>
<?php
	$this->createwidget('ext.zii.CJqSingleselect', array(
		'id' => 'dynamicpoolSelection',
		'values' => array(),
		'size' => 5,
		'multiselect' => false,
		'options' => array(
			'sorted' => true,
			'header' => Yii::t('vm', 'dynamic VM Pools'),
		),
		'theme' => 'osbd',
		'themeUrl' => $this->cssBase . '/jquery',
		'cssFile' => 'singleselect.css',
	));
?>
<?php
$this->createWidget('ext.fancybox.EFancyBox');
?>
<script type="text/javascript">
function resellerChange(event) {
	var rowid = $(this).attr('rowid');
	$("#assign" + rowid + 'save').show();
	$("#assign" + rowid + 'message').html('').hide();
	var uid = $("#reseller" + rowid).val();
	$("#customer" + rowid).children().remove();
	$("#customer" + rowid + 'loading').show();
	$("#person" + rowid).children().remove().prop('disabled', true);
	$("#assign" + rowid + 'save').prop('disabled', true);
	if ('' != uid) {
		$.ajax({
			url: "<?php echo $getcustomersurl; ?>",
			cache: false,
			data: {uid: uid},
			success: function(data){
				if (undefined != data['error']) {
					if (1 == data['error']) {
						alert(data['message']);
						return;
					}
				}
				$("#customer" + rowid + 'loading').hide();
				$("#customer" + rowid).append('<option value=""></option>');
				for (i = 0; i < data.length; i++) {
					var option = $('<option value="' + data[i].uid + '">' + data[i].name + '</option>');
					$("#customer" + rowid).append(option);
				}
			},
			datatype: "json"
		});
	}
	else {
		$("#customer" + rowid + 'loading').hide();
	}
}
function customerChange(event) {
	var rowid = $(this).attr('rowid');
	$("#assign" + rowid + 'save').show();
	$("#assign" + rowid + 'message').html('').hide();
	var uid = $("#customer" + rowid).val();
	$("#person" + rowid).children().remove();
	$("#person" + rowid + 'loading').show();
	$("#assign" + rowid + 'save').prop('disabled', true);
	if ('' != uid) {
		$("#assign" + rowid + 'save').prop('disabled', false);
		$.ajax({
			url: "<?php echo $getpeopleurl; ?>",
			cache: false,
			data: {uid: uid},
			success: function(data){
				if (undefined != data['error']) {
					if (1 == data['error']) {
						alert(data['message']);
						return;
					}
				}
				$("#person" + rowid + 'loading').hide();
				$("#person" + rowid).append('<option value=""></option>');
				for (i = 0; i < data.length; i++) {
					var option = $('<option value="' + data[i].uid + '">' + data[i].name + '</option>');
					$("#person" + rowid).append(option);
				}
			},
			datatype: "json"
		});
	}
	else {
		$("#person" + rowid + 'loading').hide();
	}
}
function personChange(event) {
	var rowid = $(this).attr('rowid');
	$("#assign" + rowid + 'save').show();
	$("#assign" + rowid + 'message').html('').hide();
}
function assignSave(event) {
	event.preventDefault();
	event.stopPropagation();
	var rowid = $(this).attr('rowid');
	var row = $('#<?php echo $gridid; ?>_grid').getRowData(rowid);
	var params = 'dn=' + row['dn'] + '&' + $("#assign" + rowid).serialize();
	console.log(params);
	$.ajax({
		url: "<?php echo $setrelationshipurl; ?>",
		cache: false,
		data: params,
		success: function(data){
			if (undefined != data['error']) {
				if (1 == data['error']) {
					alert(data['message']);
					return;
				}
			}
			$("#assign" + rowid + 'message').html(data['message']).show();
			$("#assign" + rowid + 'save').hide();
		},
		datatype: "json"
	});
}
</script>