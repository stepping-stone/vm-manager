<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * and
 *
 * Copyright (C) 2013-2015 stepping stone GmbH
 * Switzerland
 * http://www.stepping-stone.ch
 * support@stepping-stone.ch
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *  Tiziano Müller <tiziano.mueller@stepping-stone.ch>
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

/**
 * VmTemplateController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.6
 */

class VmTemplateController extends Controller
{
	public function beforeAction($action) {
		$retval = parent::beforeAction($action);
		if ($retval) {
			$this->activesubmenu = 'vm';
		}
		return $retval;
	}

	protected function createMenu() {
		parent::createMenu();
		$action = '';
		if (!is_null($this->action)) {
			$action = $this->action->id;
		}
		if ('update' == $action) {
			$this->submenu['vm']['items']['vmtemplate']['items'][] = array(
				'label' => Yii::t('menu', 'Update'),
				'itemOptions' => array('title' => Yii::t('menu', 'Virtual Machine Template Update Tooltip')),
				'active' => true,
			);

		}
		if ('index' == $action) {
			$this->submenu['links'] = array(
				'label' => Yii::t('menu', 'Links'),
				'static' => true,
				'items' => array(
					array(
                                                'label' => Yii::t('menu', 'Download virt-viewer'),
                                                'url' => 'http://wiki.stoney-cloud.org/index.php/stoney_cloud:_virt-viewer',
                                                'itemOptions' => array('title' => Yii::t('menu', 'virt-view Tooltip')),
                                                'linkOptions' => array('target' => '_blank'),
					)
				)
			);
		}
		$this->activesubmenu = 'vm';
	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index', 'view', 'create', 'update', 'delete', 'finish', 'finishDynamic',
					'getDefaults', 'getVmInfo', 'getVmTemplates', 'refreshVMs', 'getNodeGui',
					'saveVm', 'startVm', 'shutdownVm', 'rebootVm', 'destroyVm', 'migrateVm', 'toogleBoot',
					'getCheckCopyGui', 'checkCopy', 'getDynData', 'getStaticPoolGui', 'getDynamicPoolGui', 'restoreVm', 'waitForRestoreAction', 'getRestoreAction', 'startRestoreAction', 'cancelRestoreAction', 'handleRestoreAction',
					'setMachineMode', 'getPersistentCreationData',
					'setRelationship'),
		        'users'=>array('@'),
				'expression'=>'Yii::app()->user->isAdmin'
			),
			array('deny',  // deny all users
	    	    'users'=>array('*'),
			),
		);
	}
	public function actionIndex() {
		$persistentpools = array();
		$ldappools = CLdapRecord::model('LdapVmPool')->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'persistent')));
		foreach ($ldappools as $pool) {
			$persistentpools[$pool->dn] = array();
			$persistentpools[$pool->dn]['name'] = $pool->sstDisplayName; //array('name' => $pool->sstDisplayName);
			$persistentpools[$pool->dn]['nodes'] = array();
			foreach($pool->nodes as $poolnode) {
				$node = LdapNode::model()->findByAttributes(array('attr'=>(array('sstNode' => $poolnode->ou))));
				if (!is_null($node)) {
					$nodetype = $node->getType('VM-Node');
					if (!is_null($nodetype) && 'maintenance' != $nodetype->sstNodeState) {
						$persistentpools[$pool->dn]['nodes'][$node->sstNode] = $node->sstNode;
					}
				}
			}
		}
		$this->render('index', array('persistentpools' => $persistentpools, 'copyaction' => isset($_GET['copyaction']) ? $_GET['copyaction'] : null));
	}

	public function actionView() {
	}

	public function actionCreate() {
		$model = new VmTemplateForm('create');
		$hasError = false;

		$this->performAjaxValidation($model);

		if(isset($_POST['VmTemplateForm'])) {
			$model->attributes = $_POST['VmTemplateForm'];
			$parts = explode('°', $_POST['VmTemplateForm']['path']);
			
			$os = LdapVmOperatingSystem::model();
			$os->ou = 'operating system';
			$os->sstOperatingSystem = $model->os;
			$os->sstOperatingSystemType = $model->ostype;
			$os->sstOperatingSystemVersion = $model->osversion;
				
/*
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			if (0 == count($subnets)) {
				$model->addError('dn', Yii::t('vmtemplate', 'No Subnet found! <a href="{anker}">Please create one.</a>', array('{anker}' => '/subnet/create.html')));
				$hasError = true;
			}
			else {
				$subnet = $subnets[0];
				$ranges = $subnet->ranges;
				if (0 == count($ranges)) {
					$model->addError('dn', Yii::t('vmtemplate', 'No Range in Subnet ({subnet}) found! <a href="{anker}">Please create one.</a>',
						array(
							'{subnet}' => $subnet->cn . '/' . $subnet->dhcpNetMask,
							'{anker}' => $this->createUrl('subnet/createRange', array('dn' => $subnet->dn))
						)
					));
					$hasError = true;
				}
				else {
					$range = $ranges[0];
					if (is_null($range->getFreeIp())) {
						$model->addError('dn', Yii::t('vmtemplate', 'No free IP in Range {range} (Subnet {subnet}) found!',
							array(
								'{range}' => $range->cn,
								'{subnet}' => $subnet->cn . '/' . $subnet->dhcpNetMask,
							)
						));
						$hasError = true;
					}
				}
			}
*/
			$pool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$model->vmpool)));
			$range = null;
			foreach($pool->ranges as $poolrange) {
				$range = CLdapRecord::model('LdapDhcpRange')->findByAttributes(array('attr'=>array('cn'=>$poolrange->ou), 'depth'=>true));
				if (!is_null($range) && !is_null($range->getFreeIp())) {
					break;
				}
			}
			if (is_null($range) || is_null($range->getFreeIp())) {
				$model->addError('dn', Yii::t('vmtemplate', 'No free IP in Ranges of VmPool {pool} found!',
					array(
						'{pool}' => $pool->sstDisplayName,
					)
				));
				$hasError = true;
			}

			if (!$hasError) {
				//$range = $subnets[0]->ranges[0];

				$result = CLdapRecord::model('LdapVmFromProfile')->findByDn($_POST['VmTemplateForm']['basis']);
				$result->setOverwrite(true);
				$result->sstVirtualMachineType = 'template';
				$result->sstVirtualMachineSubType = 'VM-Template';
				$result->sstClockOffset = $model->sstClockOffset;
				$result->sstMemory = $model->sstMemory;
				$result->sstOSArchitecture = $parts[2];
				$result->sstVCPU = $model->sstVCPU;
				$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
				$result->description = $model->description;
				if ('TBD_GUI' == $result->sstOnCrash) {
					$result->sstOnCrash = $result->sstOnCrashDefault;
				}
				if ('TBD_GUI' == $result->sstOnPowerOff) {
					$result->sstOnPowerOff = $result->sstOnPowerOffDefault;
				}
				if ('TBD_GUI' == $result->sstOnReboot) {
					$result->sstOnReboot = $result->sstOnRebootDefault;
				}
				if ('TBD_GUI' == $result->sstDisplayName) {
					$result->sstDisplayName = $model->name;
					$result->sstNetworkHostname = $model->hostname;
					$result->sstNetworkDomainName = $model->domainname;
				}
				if ('TBD_GUI' == $result->sstNode) {
					$result->sstNode = $model->node;
				}

				$vmpool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$model->vmpool)));
				if ('TBD_GUI' == $result->sstVirtualMachinePool) {
					$result->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
				}

				$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array('sstNode' => $model->node)));
				$node = $nodes[0];
				
				// 'save' devices before
				$rdevices = $result->devices;
				/* Create a copy to be sure that we will write a new record */
				$templatevm = new LdapVmFromTemplate();
				/* Don't change the labeledURI; must refer to a default Profile */
				$templatevm->attributes = $result->attributes;
				/* Delete all objectclasses and let the LdapVMFromProfile set them */
				$templatevm->removeAttribute(array('objectClass', 'member'));
				$templatevm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

				$templatevm->sstVirtualMachineMode = 'first installation';
				$templatevm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($node->sstNode);
				$templatevm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
				$templatevm->save();
				$os->setBranchDn($templatevm->dn);
				$os->save();
				
				// Workaround to get Node
				$templatevm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($templatevm->getDn());

				$settings = new LdapVmConfigurationSettings();
				$settings->setBranchDn($templatevm->dn);
				$settings->ou = "settings";
				$settings->save();
				
				$devices = new LdapVmDevice();
				//echo '<pre>' . print_r($result->devices, true) . '</pre>';
				$devices->attributes = $rdevices->attributes;
				$devices->setBranchDn($templatevm->dn);
				//echo '<pre>' . print_r($devices, true) . '</pre>';
				$devices->save();
				
				foreach($rdevices->disks as $rdisk) {
					$disk = new LdapVmDeviceDisk();
					//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
					$disk->setOverwrite(true);
					$disk->attributes = $rdisk->attributes;
					if ('disk' == $disk->sstDevice) {
						if ('vda' == $disk->sstDisk) {
							$disk->sstVolumeCapacity = $model->sstVolumeCapacity;
						}
						$storagepool = $vmpool->getStoragePool();
						$templatesdir = substr($storagepool->sstStoragePoolURI, 7);
						$names = CPhpLibvirt::getInstance()->createVolumeFile($templatesdir, $storagepool->sstStoragePool, $node->getLibvirtUri(), $disk->sstVolumeCapacity);
						if (false !== $names) {
							$disk->sstVolumeName = $names['VolumeName'];
							$sstSourceFile = $names['SourceFile'];
							
							if ('network' === $rdisk->sstType) {
								$disk->sstSourceName = str_replace(Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][0], 
										Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][1], $sstSourceFile);
								$disk->sstType = 'network';
								$disk->sstSourceProtocol = 'gluster';
								$disk->sstSourceHostName = Yii::app()->params['virtualization']['disk']['sstSourceHostName']['vm-templates'];
								$disk->sstSourcePort     = Yii::app()->params['virtualization']['disk']['sstSourcePort']['vm-templates'];
								$disk->sstSourceFile     = '';
							}
							else if ('file' === $rdisk->sstType) {
								$disk->sstSourceFile = $sstSourceFile;
							}
							else {
								$hasError = true;
								$model->addError('dn', Yii::t('vmtemplate', 'sstType={type} not implemented!', array('{type}' => $rdisk->sstType)));
								$templatevm->delete();
								break;
							}
						}
						else {
							$hasError = true;
							$model->addError('dn', Yii::t('vmtemplate', 'Unable to create Volume file!'));
							$templatevm->delete();
							break;
						}
					}
					$disk->setBranchDn($devices->dn);
					$disk->save();
				}
				if (!$hasError) {
					$firstMac = null;
					foreach($rdevices->interfaces as $rinterface) {
						$interface = new LdapVmDeviceInterface();
						$interface->attributes = $rinterface->attributes;
						$interface->setOverwrite(true);
						$interface->sstMacAddress = CPhpLibvirt::getInstance()->generateMacAddress();
						if (is_null($firstMac)) {
							$firstMac = $interface->sstMacAddress;
						}
						$interface->setBranchDn($devices->dn);
						$interface->save();
					}

					$dhcpvm = new LdapDhcpVm();
					$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
					$dhcpvm->cn = $result->sstVirtualMachine;
					$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
					$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
					$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

					$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
					$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
					$dhcpvm->dhcpStatements = 'option host-name "' . $templatevm->sstDisplayName . '"';
					$dhcpvm->save();

					// Workaround to get Node
					$templatevm = CLdapRecord::model('LdapVm')->findByDn($templatevm->getDn());

					$ret = CPhpLibvirt::getInstance()->defineVm($templatevm->getStartParams());
						
					$this->redirect(array('index'));
				}
			}
		}
		if (!isset($_POST['VmTemplateForm']) || $hasError) {
			if (!$hasError) {
				$config = LdapConfigurationHostname::model()->findAll(array('filterName' => 'all'));
				$model->hostname = $config[0]->getNextHostname();
				$model->domainname = $config[0]->sstNetworkDomainName;
				$model->name = $model->hostname . '.' . $model->domainname;
			}
							
			$vmpools = array();
			$criteria = array('attr'=>array('sstVirtualMachinePoolType'=>'template'));
			//$criteria = array('filter'=>'(|(sstVirtualMachinePoolType=template)(sstVirtualMachinePoolType=static)(sstVirtualMachinePoolType=dynamic))');
			$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);
/*
			$nodes = array();
			$criteria = array('attr'=>array());
			$nodes = CLdapRecord::model('LdapNode')->findAll($criteria);
*/
			$profiles = array();
			$subtree = CLdapRecord::model('LdapSubTree');
			$subtree->setBranchDn('ou=virtual machine profiles,ou=virtualization,ou=services');
			$profiles = $subtree->findSubTree(array());
			//echo '<pre>' . print_r($profiles, true) . '</pre>';

			$this->render('create',array(
				'model' => $model,
				'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
				'nodes' => array(), //$this->createDropdownFromLdapRecords($nodes, 'sstNode', 'sstNode'),
				'profiles' => $this->getProfilesFromSubTree($profiles),
				'defaults' => null,
			));
		}
	}

	public function actionUpdate() {
		$model = new VmTemplateForm('update');

		if(isset($_GET['dn'])) {
			$model->dn = $_GET['dn'];
		}
		else {
			throw new CHttpException(404,'The requested page does not exist.');
		}

		$this->performAjaxValidation($model);

		if(isset($_POST['VmTemplateForm'])) {
			$model->attributes = $_POST['VmTemplateForm'];

			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_POST['VmTemplateForm']['dn']);
			$result->setOverwrite(true);
			$result->sstClockOffset = $model->sstClockOffset;
			$result->sstMemory = $model->sstMemory;
			$result->sstVCPU = $model->sstVCPU;
			$result->description = $model->description;
			$result->sstDisplayName = $model->name;
			//$result->sstNode = $model->node;
			$result->save();

			$rdevices = $result->devices;
			foreach($rdevices->disks as $rdisk) {
				if ('disk' === $rdisk->sstDevice && 'vda' === $rdisk->sstDisk) {
					$rdisk->setOverwrite(true);
					$rdisk->sstVolumeCapacity = $model->sstVolumeCapacity;
					$rdisk->save();
				}
			}

			if ($model->useStaticIP) {
				$dhcpvm = $result->dhcp;
				$dhcpvm->dhcpStatements = 'fixed-address ' . $model->staticIP;
				if (!$dhcpvm->subnet->inRange($model->staticIP)) {
					$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
					foreach ($subnets as $subnet) {
						if ($subnet->inRange($model->staticIP)) {
							$dhcpvm->save();
							$dhcpvm->move('ou=virtual machines,' . $subnet->dn);
							break;
						}
					}
				}
				else {
					$dhcpvm->save();
				}
			}

			// reload because of the node bug
			$vm = CLdapRecord::model('LdapVm')->findByDn($_POST['VmTemplateForm']['dn']);
			$data = $vm->getStartParams();
			$data['name'] = $data['sstName'];
			CPhpLibvirt::getInstance()->redefineVm($data);
				
			$this->redirect(array('index'));
		}
		else {
			$vmpools = array();
			$criteria = array('filter'=>'(|(sstVirtualMachinePoolType=template)(sstVirtualMachinePoolType=dynamic))');
			$vmpools = CLdapRecord::model('LdapVmPool')->findAll($criteria);

			$nodes = array();
			$nodes = CLdapRecord::model('LdapNode')->findAll(array('attr'=>array()));

			$vm = CLdapRecord::model('LdapVmFromTemplate')->findbyDn($_GET['dn']);
			$defaults = $vm->defaults;
			//$subnet = $vm->dhcp->subnet;
			$allRanges = array('' => '');
			$subnets = CLdapRecord::model('LdapDhcpSubnet')->findAll(array('attr'=>array()));
			foreach($subnets as $subnet) {
				$ranges = array();
				foreach($subnet->ranges as $range) {
					if ($range->sstNetworkType == 'persistent') {
						$ranges[$range->cn] = $range->getRangeAsString();
					}
				}
				$allRanges[$subnet->cn . '/' . $subnet->dhcpNetMask] = $ranges;
			}

			$model->dn = $vm->dn;
			$model->node = $vm->sstNode;
			$model->ip = $vm->dhcp->dhcpStatements['fixed-address'];
			$model->name = $vm->sstDisplayName;
			$model->description = $vm->description;
			//echo '<pre>' . print_r($profile, true) . '</pre>';
			//echo '<pre>' . print_r($defaults, true) . '</pre>';
			$model->sstClockOffset = $vm->sstClockOffset;
			$model->sstMemory = $vm->sstMemory;
			$model->sstVCPU = $vm->sstVCPU;
			$result = $vm->devices->getDiskByName('vda');
			if (isset($result->sstVolumeCapacity)) {
				$model->sstVolumeCapacity = $result->sstVolumeCapacity;
				$defaults->setVolumeCapacityMin($result->sstVolumeCapacity, true);
			}
			else {
				$model->sstVolumeCapacity = $defaults->VolumeCapacityMin;
			}

			$os = $vm->operatingsystem;
			if (!is_null($os)) {
				$model->os = $os->sstOperatingSystem;
				$model->ostype = $os->sstOperatingSystemType;
				$model->osversion = $os->sstOperatingSystemVersion;
			}
			else {
				$model->os = 'OS?';
				$model->ostype = 'Type?';
				$model->osversion = 'Version?';
			}
				
			$this->render('update',array(
				'model' => $model,
				'vmpools' => $this->createDropdownFromLdapRecords($vmpools, 'sstVirtualMachinePool', 'sstDisplayName'),
				'nodes' => $this->createDropdownFromLdapRecords($nodes, 'sstNode', 'sstNode'),
				'profiles' => null,
				'ranges' => $allRanges,
				'defaults' => $defaults,
			));
		}
	}

	public function actionDelete() {
		$this->disableWebLogRoutes();

		if ('del' != Yii::app()->getRequest()->getPost('oper')) {
			Yii::log('actionDelete: invalid oper value', 'error', 'vmTemplateController');
			return;
		}

		$dn = urldecode(Yii::app()->getRequest()->getPost('dn'));
		$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($dn);

		if (is_null($vm)) {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => "Vm '{$dn}' not found!"));
			return;
		}

		if ($vm->isActive()) {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => "Vm '{$vm->sstDisplayName}' is running!"));
			return;
		}

		$libvirt = CPhpLibvirt::getInstance();
		$message = '';
		$disks = $vm->devices->getDisksByDevice('disk');
		foreach($disks as $disk) {
			$diskpath = $disk->sstSourceFile;

			// if the disk is attached via gfapi, reconstruct the local filesystem path
			// using the configuration in vm_config.php
			if ($disk->sstType == 'network') {
				$diskpath = str_replace(
					Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][1],
					Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][0],
					$disk->sstSourceName);
			}

			if (!$libvirt->deleteVolumeFile($diskpath)) {
				$message .= "Unable to delete Volume File '{$diskpath}' for Vm '{$vm->sstDisplayName}'!<br/>";
			}
		}

		// TODO: CWI remove also sstActiveGoldenImage from VmPool if this VM is that golden image

		// delete IP
		if (!is_null($vm->network)) {
			foreach($vm->network as $network) {
				$network->delete();
			}
		}

		$libvirt->undefineVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));

		// delete VM
		$vm->delete(true);
		if ('' != $message) {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => $message));
		}
	}

	public function actionFinish() {
		$this->disableWebLogRoutes();
		//echo '<pre>' . print_r($_POST, true) . '</pre>';
		if (isset($_POST['dn'])) {
			$finishForm = $_POST['FinishForm'];
			if (!isset($finishForm['pool']) || '' == $finishForm['pool']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select a pool!')));
				Yii::app()->end();
			}
			if (!isset($finishForm['node']) || '' == $finishForm['node']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select a node!')));
				Yii::app()->end();
			}
			if (!isset($finishForm['reseller']) || '' == $finishForm['reseller']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select a reseller!')));
				Yii::app()->end();
			}
			if (!isset($finishForm['customer']) || '' == $finishForm['customer']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select a customer!')));
				Yii::app()->end();
			}
			if (!isset($finishForm['stack']) || '' == $finishForm['stack']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select a software stack!')));
				Yii::app()->end();
			}
			if (!isset($finishForm['env']) || '' == $finishForm['env']) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Please select an environment!')));
				Yii::app()->end();
			}

			$vmpool = LdapVmPool::model()->findByDn($finishForm['pool']);
			if (is_null($vmpool)) {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Pool not found!')));
				Yii::app()->end();
			}
			$storagepool = $vmpool->getStoragePool();
			if (is_null($storagepool)) {
				$this->sendJsonAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No storagepool found for selected vmpool!')));
				Yii::app()->end();
			}
			$usedNode = LdapNode::model()->findByAttributes(array('attr'=>(array('sstNode' => $finishForm['node']))));
			if (is_null($usedNode)) {
				$this->sendJsonAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'Node not found!')));
				Yii::app()->end();
			}
				
			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_POST['dn']);

			$os = null;
			if (!is_null($result->operatingsystem)) {
				$os = LdapVmOperatingSystem::model();
				$os->ou = 'operating system';
				$os->sstOperatingSystem = $result->operatingsystem->sstOperatingSystem;
				$os->sstOperatingSystemType = $result->operatingsystem->sstOperatingSystemType;
				$os->sstOperatingSystemVersion = $result->operatingsystem->sstOperatingSystemVersion;
			}			
			$vmstack = null;
			$stack = LdapConfigurationSoftwareStack::model()->findByDn($finishForm['stack']);
			if (!is_null($stack)) {
				$vmstack = LdapVmSoftwareStack::model();
				$vmstack->ou = 'software stack';
				$vmstack->sstDisplayName = $stack->sstDisplayName;
				$vmstack->description = $stack->description;
				$vmstack->labeledURI = 'ldap:///' . $finishForm['stack'];
				$vmstack->sstEnvironmentName = $finishForm['env'];
				$vmstack->sstBusinessLogicRoleName = $stack->sstBusinessLogicRoleName;
			}
			else {
				$this->sendJsonAnswer(array('error' => 2, 'message' => Yii::t('vmtemplate', 'Software stack not found!')));
				Yii::app()->end();
			}

			// 'save' devices before
			$rdevices = $result->devices;
			/* Create a copy to be sure that we will write a new record */
			$vm = new LdapVm();
			/* Don't change the labeledURI; must refer to a default Profile */
			$vm->attributes = $result->attributes;
			$vm->setOverwrite(true);
			$vm->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
				
			if (isset($finishForm['displayname']) && '' != $finishForm['displayname']) {
				$vm->sstDisplayName = $finishForm['displayname'];
			}
			if (isset($finishForm['hostname']) && '' != $finishForm['hostname']) {
				$vm->sstNetworkHostname = $finishForm['hostname'];
			}
			if (isset($finishForm['domainname']) && '' != $finishForm['domainname']) {
				$vm->sstNetworkDomainName = $finishForm['domainname'];
			}
			$vm->sstVirtualMachineType = 'persistent';
			$vm->sstVirtualMachineSubType = $finishForm['subtype'];
			$vm->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
			$vm->sstNode = $usedNode->sstNode;
			/* Delete all objectclasses and let LdapVm set them */
			$vm->removeAttribute(array('objectClass', 'member'));
			$vm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

			$vm->sstBelongsToCustomerUID = $finishForm['customer']; // Yii::app()->user->customerUID;
			$vm->sstBelongsToResellerUID = $finishForm['reseller']; // Yii::app()->user->resellerUID;
			$vm->sstOsBootDevice = 'hd';
			$vm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($vm->sstNode);
			$vm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
			$vm->save();
			if (!is_null($os)) {
				$os->setBranchDn($vm->dn);
				$os->save();
			}
			if (!is_null($vmstack)) {
				$vmstack->setBranchDn($vm->dn);
				$vmstack->save();
			}				
			$settings = new LdapVmConfigurationSettings();
			$settings->setBranchDn($vm->dn);
			$settings->ou = "settings";
			$settings->save();
				
			$devices = new LdapVmDevice();
			$devices->setOverwrite(true);
			$devices->attributes = $rdevices->attributes;
			$devices->setBranchDn($vm->dn);
			$devices->save();

			$copydata = array();
			foreach($rdevices->disks as $rdisk) {
				$disk = new LdapVmDeviceDisk();
				//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
				$disk->setOverwrite(true);
				$disk->attributes = $rdisk->attributes;
				if ('disk' == $disk->sstDevice) {
					$templatesdir = substr($storagepool->sstStoragePoolURI, 7);
					$diskpath = $disk->sstSourceFile;
					if ('network' === $rdisk->sstType) {
						$diskpath = str_replace(Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][1],
									Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-templates'][0], $rdisk->sstSourceName);
					}
					$names = CPhpLibvirt::getInstance()->createBackingStoreVolumeFile($templatesdir, $storagepool->sstStoragePool, $diskpath, $usedNode->getLibvirtUri(), $disk->sstVolumeCapacity);
					if (false !== $names) {
						$disk->sstVolumeName = $names['VolumeName'];
						$sstSourceFile = $names['SourceFile'];
							
						if ('network' === $rdisk->sstType) {
							$disk->sstSourceName = str_replace(Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-persistent'][0],
									Yii::app()->params['virtualization']['disk']['sstSourceName']['vm-persistent'][1], $sstSourceFile);
							$disk->sstType = 'network';
							$disk->sstSourceProtocol = 'gluster';
							$disk->sstSourceHostName = Yii::app()->params['virtualization']['disk']['sstSourceHostName']['vm-persistent'];
							$disk->sstSourcePort     = Yii::app()->params['virtualization']['disk']['sstSourcePort']['vm-persistent'];
							$disk->sstSourceFile     = '';
						}
						else if ('file' === $rdisk->sstType) {
							$disk->sstSourceFile = $sstSourceFile;
						}
						else {
							$hasError = true;
							$vm->delete(true);
							$this->sendAjaxAnswer(array('error' => 1, 'message' => 'sstType=' . $rdisk->sstType . ' not implemented!'));
							break;
						}
					}
					else {
						$hasError = true;
						$vm->delete(true);
						$this->sendAjaxAnswer(array('error' => 1, 'message' => 'Unable to create backingstore volume!'));
						break;
					}
				}
				else if ('network' === $disk->sstType) {
					$hasError = true;
					$vm->delete(true);
					$this->sendAjaxAnswer(array('error' => 1, 'message' => 'sstType=network not implemented!'));
					break;
				}
				
				$disk->setBranchDn($devices->dn);
				$disk->save();
			}
			if (!$hasError) {
				// Don't set $result->setOverwrite(true); because sstThinProvisioningVirtualMachine is a multiple attribute
				$result->sstThinProvisioningVirtualMachine = $vm->sstVirtualMachine;
				$result->update();
				
				$firstMac = null;
				foreach($rdevices->interfaces as $rinterface) {
					$interface = new LdapVmDeviceInterface();
					$interface->attributes = $rinterface->attributes;
					$interface->setOverwrite(true);
					$interface->sstMacAddress = CPhpLibvirt::getInstance()->generateMacAddress();
					if (is_null($firstMac)) {
						$firstMac = $interface->sstMacAddress;
					}
					$interface->setBranchDn($devices->dn);
					$interface->save();
				}
	
				$range = $vmpool->getRange();
				if (is_null($range)) {
					$vm->delete(true);
					$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No range found for VMPool!')));
					return;
				}
				$dhcpvm = new LdapDhcpVm();
				$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
				$dhcpvm->cn = $vm->sstVirtualMachine;
				$dhcpvm->sstBelongsToCustomerUID = $finishForm['customer']; //Yii::app()->user->customerUID;
				$dhcpvm->sstBelongsToResellerUID = $finishForm['reseller']; //Yii::app()->user->resellerUID;
				$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;
	
				$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
				$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
				$dhcpvm->dhcpStatements = 'option host-name "' . $vm->sstDisplayName . '"';
				$dhcpvm->save();
	
				$server = CLdapServer::getInstance();
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
				$data['ou'] = array('groups');
				$data['description'] = array('This is the assigned groups subtree.');
				$data['sstBelongsToCustomerUID'] = array($finishForm['customer']); // Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array($finishForm['reseller']); // Yii::app()->user->resellerUID);
				$dn = 'ou=groups,' . $vm->dn;
				$server->add($dn, $data);
	
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
				$data['ou'] = array('people');
				$data['description'] = array('This is the assigned people subtree.');
				$data['sstBelongsToCustomerUID'] = array($finishForm['customer']); // Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array($finishForm['reseller']); // Yii::app()->user->resellerUID);
				$dn = 'ou=people,' . $vm->dn;
				$server->add($dn, $data);
				
				// Workaround to get Node
				$vm = CLdapRecord::model('LdapVm')->findByDn($vm->getDn());

				if (!CPhpLibvirt::getInstance()->startVmWithBlockJob($vm->getStartParams())) {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'Error starting block job!')));
					Yii::app()->exit(1);
				}
			}
		}
		//$this->redirect(array('index', 'copyaction' => $copydata['pid']));
		$this->sendJsonAnswer(array('error' => 0, 'message' => 'OK')); //'url' => $this->createUrl('index')));//, array('copyaction' => $copydata['pid']))));
	}

	public function actionFinishDynamic() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn']) && isset($_GET['pool'])) {
			$result = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			$result->setOverwrite(true);
			$result->sstVirtualMachine = CPhpLibvirt::getInstance()->generateUUID();
			//$pools = CLdapRecord::model('LdapVmPool')->findAll( array('attr'=>array()));
			//$result->sstVirtualMachinePool = $pools[0]->sstVirtualMachinePool;

			if ('undefined' == $_GET['pool']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a pool!'));
				return;
			}
			$vmpool = CLdapRecord::model('LdapVmPool')->findByDn($_GET['pool']);
			$storagepool = $vmpool->getStoragePool();
			if (is_null($storagepool)) {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => 'No storagepool found for selected vmpool!'));
				return;
			}
			$poolNodes = $vmpool->nodes;
			$usedNode = null;
			foreach($poolNodes as $poolNode) {
				$node = LdapNode::model()->findByAttributes(array('attr'=>(array('sstNode' => $poolNode->ou))));
				if (!is_null($node)) {
					$nodetype = $node->getType('VM-Node');
					if (!is_null($nodetype) && 'maintenance' != $nodetype->sstNodeState) {
						$usedNode = $node;
						break;
					}
				}
			}
			if (is_null($usedNode)) {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No active node found for selected vmpool!')));
				return;
			}
				
			// 'save' devices before
			$rdevices = $result->devices;
			/* Create a copy to be sure that we will write a new record */
			$vm = new LdapVm();
			/* Don't change the labeledURI; must refer to a default Profile */
			$vm->attributes = $result->attributes;

			$vm->setOverwrite(true);
			$vm->sstVirtualMachineType = 'dynamic';
			if (isset($_GET['sysprep']) && 'true' == $_GET['sysprep']) {
				$vm->sstVirtualMachineSubType = 'System-Preparation';
			}
			else {
				$vm->sstVirtualMachineSubType = 'Golden-Image';
			}
			if (isset($_GET['name']) && '' != $_GET['name']) {
				$vm->sstDisplayName = $_GET['name'];
			}
			$vm->sstVirtualMachinePool = $vmpool->sstVirtualMachinePool;
			$vm->sstNode = $usedNode->sstNode;
			/* Delete all objectclasses and let the LdapVM set them */
			$vm->removeAttribute(array('objectClass', 'member'));
			$vm->setBranchDn('ou=virtual machines,ou=virtualization,ou=services');

			$vm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
			$vm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
			$vm->sstOsBootDevice = 'hd';
			$vm->sstSpicePort = CPhpLibvirt::getInstance()->nextSpicePort($vm->sstNode);
			$vm->sstSpicePassword = CPhpLibvirt::getInstance()->generateSpicePassword();
			$vm->save();

			$settings = new LdapVmPoolConfigurationSettings();
			$settings->setBranchDn($vm->dn);
			$settings->ou = "settings";
			$settings->save();
				
			$devices = new LdapVmDevice();
			$devices->setOverwrite(true);
			$devices->attributes = $rdevices->attributes;
			$devices->setBranchDn($vm->dn);
			$devices->save();

			$copydata = array();
			foreach($rdevices->disks as $rdisk) {
				$disk = new LdapVmDeviceDisk();
				//$rdisk->removeAttributesByObjectClass('sstVirtualizationVirtualMachineDiskDefaults');
				$disk->setOverwrite(true);
				$disk->attributes = $rdisk->attributes;
				if ('disk' == $disk->sstDevice) {
					$persistentdir = substr($storagepool->sstStoragePoolURI, 7);
					$copydata = CPhpLibvirt::getInstance()->copyVolumeFile($persistentdir, $disk);
					$copydata['Dn'] = $vm->getDn();
					$disk->sstVolumeName = $copydata['VolumeName'];
					$disk->sstSourceFile = $copydata['SourceFile'];
					$_SESSION['copyVolumeFile'] = $copydata;
				}
				$disk->setBranchDn($devices->dn);
				$disk->save();
			}
			$firstMac = null;
			foreach($rdevices->interfaces as $rinterface) {
				$interface = new LdapVmDeviceInterface();
				$interface->attributes = $rinterface->attributes;
				$interface->setOverwrite(true);
				$interface->sstMacAddress = CPhpLibvirt::getInstance()->generateMacAddress();
				if (is_null($firstMac)) {
					$firstMac = $interface->sstMacAddress;
				}
				$interface->setBranchDn($devices->dn);
				$interface->save();
			}

			if ('System-Preparation' === $vm->sstVirtualMachineSubType) {
				/* Not necessary for a golden image */
				$range = $vmpool->getRange();
				if (is_null($range)) {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => Yii::t('vmtemplate', 'No range found for VMPool!')));
					return;
				}
				$dhcpvm = new LdapDhcpVm();
				$dhcpvm->setBranchDn('ou=virtual machines,' . $range->subnet->dn);
				$dhcpvm->cn = $result->sstVirtualMachine;
				$dhcpvm->sstBelongsToCustomerUID = Yii::app()->user->customerUID;
				$dhcpvm->sstBelongsToResellerUID = Yii::app()->user->resellerUID;
				$dhcpvm->sstBelongsToPersonUID = Yii::app()->user->UID;

				$dhcpvm->dhcpHWAddress = 'ethernet ' . $firstMac;
				$dhcpvm->dhcpStatements = 'fixed-address ' . $range->getFreeIp();
				$dhcpvm->save();

				$server = CLdapServer::getInstance();
				$data = array();
				$data['objectClass'] = array('top', 'organizationalUnit', 'sstRelationship');
				$data['ou'] = array('people');
				$data['description'] = array('This is the assigned people subtree.');
				$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
				$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
				$dn = 'ou=people,' . $vm->dn;
				$server->add($dn, $data);
			}
		}
		//$this->redirect(array('index', 'copyaction' => $copydata['pid']));
		$this->sendAjaxAnswer(array('error' => 0, 'url' => $this->createUrl('index', array('copyaction' => $copydata['pid']))));
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vmtemplate-form')
		{
			$this->disableWebLogRoutes();
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionGetDefaults() {
		$defaults = array();
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$defaults['path'] = $_GET['p'];
			if ('sstVirtualMachine=default' == substr($_GET['dn'], 0, strlen('sstVirtualMachine=default'))) {
				$defaults['name'] = '';
				$defaults['description'] = '';
				$result = CLdapRecord::model('LdapVmDefaults')->findByDn($_GET['dn']);
				$defaults['memorydefault'] = $result->sstMemoryDefault;
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpudefault'] = $result->sstVCPUDefault;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockdefault'] = $result->sstClockOffsetDefault;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				//echo '<pre>' . print_r($result->device->disks, true) . '</pre>';
				//$result = CLdapRecord::model('LdapVmDeviceDisk')->findByDn('sstDisk=hdb,ou=devices,' . $_GET['dn']);
				$result = $result->devices->getDiskByName('vda');
				//echo '<pre>' . print_r($result, true) . '</pre>';
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacityDefault;
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
				//echo '<pre>' . print_r($defaults, true) . '</pre>';
			}
			else {
				$parts = explode('°', $_GET['p']);
				$defaults['name'] = $parts[1];
				$profile = LdapVmFromProfile::model()->findByDn($_GET['dn']);
				$defaults['description'] = $profile->description;
				$defaults['memorydefault'] = $profile->sstMemory;
				$defaults['cpudefault'] = $profile->sstVCPU;
				$defaults['clockdefault'] = $profile->sstClockOffset;

				$result = $profile->devices->getDiskByName('vda');
				$defaults['volumecapacitydefault'] = $result->sstVolumeCapacity;

				$result = CLdapRecord::model('LdapVmDefaults')->findByDn(substr($profile->labeledURI, 8));
				$defaults['memorymin'] = $result->sstMemoryMin;
				$defaults['memorymax'] = $result->sstMemoryMax;
				$defaults['memorystep'] = $result->sstMemoryStep;
				$defaults['cpuvalues'] = $result->sstVCPUValues;
				$defaults['clockvalues'] = $result->sstClockOffsetValues;

				$result = $result->devices->getDiskByName('vda');
				$defaults['volumecapacitymin'] = $result->sstVolumeCapacityMin;
				$defaults['volumecapacitymax'] = $result->sstVolumeCapacityMax;
				$defaults['volumecapacitystep'] = $result->sstVolumeCapacityStep;
				
				$os = $profile->operatingsystem;
				$defaults['os'] = $os->sstOperatingSystem;
				$defaults['ostype'] = $os->sstOperatingSystemType;
				$defaults['osversion'] = $os->sstOperatingSystemVersion;
				$defaults['osall'] = $os->getCompleteName();
			}
		}
		$s = CJSON::encode($defaults);
		header('Content-Type: application/json');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVMTemplates() {
		$this->disableWebLogRoutes();
		$page = $_GET['page'];

		// get how many rows we want to have into the grid - rowNum parameter in the grid
		$limit = $_GET['rows'];

		// get index row - i.e. user click to sort. At first time sortname parameter -
		// after that the index from colModel
		$sidx = $_GET['sidx'];

		// sorting order - at first time sortorder
		$sord = $_GET['sord'];

		$criteria = array('attr'=>array());
		$criteria['attr']['sstVirtualMachineType'] = 'template';
		if (isset($_GET['sstDisplayName'])) {
			$criteria['attr']['sstDisplayName'] = '*' . $_GET['sstDisplayName'] . '*';
		}
		if (isset($_GET['sstNode'])) {
			$criteria['attr']['sstNode'] = '*' . $_GET['sstNode'] . '*';
		}
		if ($sidx != '')
		{
			$criteria['sort'] = $sidx . '.' . $sord;
		}
		$vms = CLdapRecord::model('LdapVmFromTemplate')->findAll($criteria);
		$count = count($vms);

		// calculate the total pages for the query
		if( $count > 0 && $limit > 0)
		{
			$total_pages = ceil($count/$limit);
		}
		else
		{
			$total_pages = 0;
		}

		// if for some reasons the requested page is greater than the total
		// set the requested page to total page
		if ($page > $total_pages)
		{
			$page = $total_pages;
		}

		// calculate the starting position of the rows
		$start = $limit * $page - $limit;

		// if for some reasons start position is negative set it to 0
		// typical case is that the user type 0 for the requested page
		if($start < 0)
		{
			$start = 0;
		}

		$criteria['limit'] = $limit;
		$criteria['offset'] = $start;

		$vms = CLdapRecord::model('LdapVmFromTemplate')->findAll($criteria);

		// we should set the appropriate header information. Do not forget this.
		//header("Content-type: text/xml;charset=utf-8");

		$s = "<?xml version='1.0' encoding='utf-8'?>";
		$s .=  '<rows>';
		$s .= '<page>' . $page . '</page>';
		$s .= '<total>' . $total_pages . '</total>';
		$s .= '<records>' . $count . '</records>';

		$i = 1 + $start;
		foreach ($vms as $vm) {
			//	'colNames'=>array('No.', 'DN', 'UUID', 'Spice', 'Boot', 'Name', 'Displayname', 'Status', 'Run Action', 'Memory', 'CPU', 'Node', 'Action'),

			$s .= '<row id="' . $i . '">';
			$s .= '<cell>'. $i ."</cell>\n";
			$s .= '<cell>'.$vm->dn ."</cell>\n";
			$s .= '<cell>'. $vm->sstVirtualMachine ."</cell>\n";
			$s .= '<cell><![CDATA['. $vm->getSpiceUri() . "]]></cell>\n";
			$s .= '<cell>'. $vm->sstOsBootDevice ."</cell>\n";
			$s .= '<cell>'. $vm->sstDisplayName ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>unknown</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= "<cell>---</cell>\n";
			$s .= '<cell>'. $vm->sstNode ."</cell>\n";
			$s .= "<cell></cell>\n";
			$s .= "</row>\n";
			$i++;
		}
		$s .= '</rows>';

		header('Content-Type: application/xml');
		header('Content-Length: ' . strlen($s));
		echo $s;
	}

	public function actionGetVmInfo() {
		$this->disableWebLogRoutes();
		$dn = $_GET['dn'];
		$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($dn);
		$rowid  = $_GET['rowid'];

		$ipText = '';
		$networks = $vm->network;
		if (!is_null($networks)) {
			$ips = array();
			//echo '<pre>Network: ' . print_r($network, true) . '</pre>';
			foreach($networks as $network) {
				$idx = 0;
				if (false !== strpos($network->cn, '_')) {
					$idx = substr($network->cn, strpos($network->cn, '_') + 1);
				}
				$ips[$idx] = $network->dhcpstatements['fixed-address'];
			}
			ksort($ips);
			foreach($ips as $idx => $ip) {
				if ('' !== $ipText) {
					$ipText .= '<br/>';
				}
				$ipText .= ('0' == $idx && 1 === count($ips) ? '' : $idx . ': ') . $ip;
			}
		}
		if ('' === $ipText) {
			$ipText = '???';
		}
		$memory = $this->getHumanSize($vm->sstMemory);
		$loading = $this->getImageBase() . '/loading.gif';

		$machinemodeUrl = $this->createUrl('setMachineMode');

		$criteria = array('attr'=>array('sstIsActive' => 'TRUE'));
		$reseller = LdapReseller::model()->findAll($criteria);
		$reseller = $this->createDropdownFromLdapRecords($reseller, 'uid', 'o');
		$resellerselect = CHtml::dropDownList('reseller', $vm->sstBelongsToResellerUID, $reseller, array('id' => 'reseller' . $rowid, 'rowid' => $rowid, 'prompt' => ''));
		$criteria['attr']['sstBelongsToResellerUID'] = $vm->sstBelongsToResellerUID;
		$customer = LdapCustomer::model()->findAll($criteria);
		$customer = $this->createDropdownFromLdapRecords($customer, 'uid', 'o');
		$customerselect = CHtml::dropDownList('customer', $vm->sstBelongsToCustomerUID, $customer, array('id' => 'customer' . $rowid, 'rowid' => $rowid, 'style' => 'float: left;', 'prompt' => ''));
		$criteria['attr']['sstBelongsToCustomerUID'] = $vm->sstBelongsToCustomerUID;
		$person = LdapPerson::model()->findAll($criteria);
		$person = $this->createDropdownFromLdapRecords($person, 'uid', 'name');
		$personselect = CHtml::dropDownList('person', $vm->sstBelongsToPersonUID, $person, array('id' => 'person' . $rowid, 'rowid' => $rowid, 'style' => 'float: left;', 'prompt' => ''));
		
		/*
		 * with cpu graph
      <td style="text-align: right"><b>Memory:</b></td>
      <td>$memory</td>
      <td rowspan="3" style="text-align: right"><b>CPU:</b></td>
      <td rowspan="3" style="height: 53px;" id="cpu2_$rowid"><img src="{$loading}" alt="" /></td>

		 */
		
		$baseurl = Yii::app()->baseUrl;
		$imagesurl = $baseurl . '/images';
		
		echo <<<EOS
    <table style="margin-bottom: 0px; font-size: 90%; width: auto;"><tbody>
    <tr>
		<td style="text-align: right; vertical-align: top;"><b>Type:</b></td>
		<td style="vertical-align: top;">{$vm->sstVirtualMachineType}, {$vm->sstVirtualMachineSubType}</td>
		<td style="text-align: right; vertical-align: top;"><b>VM:</b></td>
		<td style="vertical-align: top;" colspan="2">{$vm->sstDisplayName}</td>
		<td style="vertical-align: top;" colspan="2">{$vm->sstVirtualMachine}</td>
		</tr>
    <tr>
		<td style="text-align: right; vertical-align: top;"><b>Memory:</b></td>
		<td style="vertical-align: top;">$memory</td>
		<td style="text-align: right;vertical-align: top;"><b>VM Pool:</b></td>
		<td style="vertical-align: top;" colspan="2">{$vm->vmpool->sstDisplayName}</td>
		<td style="vertical-align: top;" colspan="2">{$vm->sstVirtualMachinePool}</td>
		</tr>
    <tr>
		<td style="text-align: right; vertical-align: top;"><b>CPUs:</b></td>
		<td style="vertical-align: top;">{$vm->sstVCPU}</td>
EOS;
		if (!is_null($vm->sstThinProvisioningVirtualMachine)) {
			echo <<< EOS
		<td style="text-align: right; vertical-align: top;"><b>VM's streaming:</b></td>
		<td style="vertical-align: top;" colspan="2">
EOS;
			$vmnames = array();
			foreach($vm->sstThinProvisioningVirtualMachine as $vmuuid) {
				$streamingvm = LdapVm::model()->findByAttributes(array('attr' => array('sstVirtualMachine' => $vmuuid)));
				if (!is_null($streamingvm)) {
					$vmnames[] = $streamingvm->sstDisplayName;
				}
			}
			echo implode(', ', $vmnames) . '</td>';
		}
		else {
			echo '<td colspan="3">&nbsp;</td>';
		}
		echo <<< EOS
      <td style="text-align: right; vertical-align: top;">
EOS;
		echo CHtml::hiddenField('dn', $dn, array('id' => 'dn_' . $vm->sstVirtualMachine));
		echo '<b>' . CHtml::label(Yii::t('vmtemplate', 'MachineMode'), 'machinemode') . ':</b></td><td style="vertical-align: top;">';
		$machinemode = $vm->sstVirtualMachineMode;
		if (is_null($machinemode)) {
			$machinemode = 'do not use';
		}
		echo CHtml::dropDownList('machinemode', $machinemode, array(
			'maintenance mode' => Yii::t('vmtemplate', 'maintenance mode'),
			'first installation' => Yii::t('vmtemplate', 'first installation'),
			'ready for use' => Yii::t('vmtemplate', 'ready for use')),
			array('id' => 'machinemode_' . $vm->sstVirtualMachine)
		);
		echo '<div id="message_' . $vm->sstVirtualMachine . '" class="flash-error" style="margin-top: 10px; display: none;"></div>';
		echo <<< EOS
<script type="text/javascript">
	$("#machinemode_{$vm->sstVirtualMachine}").change(function(event) {
		event.stopImmediatePropagation();
		$.post('{$machinemodeUrl}', $("#machinemode_{$vm->sstVirtualMachine}, #dn_{$vm->sstVirtualMachine}").serialize(), function(data){
			if (data.err) {
				$("#message_{$vm->sstVirtualMachine}").text(data.msg).show();
			}
		}, "json");
	});
</script>
EOS;
		echo <<< EOS
      </td>
    </tr>
    <tr>
EOS;
		if ('Golden-Image' !== $vm->sstVirtualMachineSubType) {
			echo <<< EOS
		<td style="text-align: right;vertical-align: top;" rowspan="2"><b>IP Adress:</b></td>
		<td style="vertical-align: top;" rowspan="2">$ipText</td>
EOS;
			if (!is_null($vm->operatingsystem)) {
				echo <<< EOS
		<td style="text-align: right;vertical-align: top;"><b>OS:</b></td>
		<td colspan="2" style="vertical-align: top;">{$vm->operatingsystem->getCompleteName()}</td>
EOS;
			}
			else {
				echo '		<td colspan="3">&nbsp;</td>';
			}

			echo <<< EOS
		<td rowspan="3" colspan="2" style="vertical-align: top; padding-bottom: 0;">
			<form id="assign{$rowid}" rowid="{$rowid}">
				<table style="width: auto; margin-left: 10px; margin-bottom: 0;">
				<tr>
					<td style="text-align: right; vertical-align: top; padding-top: 0;"><b>Reseller:</b></td>
					<td style="vertical-align: top; padding-top: 0;">$resellerselect</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top;"><b>Customer:</b></td>
					<td style="vertical-align: top;"><img id="customer{$rowid}loading" src="{$imagesurl}/loading.gif" style="float: left; display: none;" />$customerselect</td>
				</tr>
				<tr>
					<td style="text-align: right; vertical-align: top;"><b>Person:</b></td>
					<td style="vertical-align: top;"><img id="person{$rowid}loading" src="{$imagesurl}/loading.gif" style="float: left; display: none;" />$personselect</td>
				</tr>
				<tr>
					<td></td>
					<td>
					<input id="assign{$rowid}save" rowid="{$rowid}" type="submit" value="Save" style="display: none;"/>
					<div id="assign{$rowid}message" style="display: none;"></div>
					</td>
				</tr>
				</table>
			</form>
		</td>
EOS;
			echo '    </tr><tr>';
			if (!is_null($vm->softwarestack)) {
				echo <<< EOS
		<td style="text-align: right;vertical-align: top;"><b>SW Stack:</b></td>
		<td style="vertical-align: top;">{$vm->softwarestack->sstDisplayName}
EOS;
				if (!is_null($vm->softwarestack->environment)) {
					echo '<br/>' . $vm->softwarestack->environment->sstDisplayName;
				}
				echo '		</td>';
			}
		}
		echo '</tr><tr><td colspan="4">&nbsp;</td></tr></tbody></table>';
		if (!is_null($vm->backup)) {
			echo <<< EOS
	<br />
	<h3>Backups</h3>
	<table style="margin-bottom: 0px; width: 100%;"><tbody>
    <tr>
		<th style="text-align: center; width: 16px;">&nbsp;</th>
		<th style="text-align: center; width: 120px;"><b>Date</b></th>
		<th style="text-align: center; width: 120px;"><b>State</b></th>
		<th style="text-align: center;"><b>Message</b></th>
		<th style="text-align: center; width: 60px;"><b>Action</b></th>
	</tr>
			
EOS;
			$formatter = new CDateFormatter(CLocale::getInstance(Yii::t('app', 'locale')));
			$restoring = false;
			foreach($vm->backup->backups as $backup) {
				if (0 === strpos($backup->sstProvisioningMode, 'unretain') || 0 === strpos($backup->sstProvisioningMode, 'restor')) {
					$restoring = true;
					break;
				}
			}
			foreach($vm->backup->backups as $backup) {
				echo '<tr><td>';
				if ('finished' === $backup->sstProvisioningMode) {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_finished.png" />';
				}
				else if (0 != $backup->sstProvisioningReturnValue) {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_error.png" />';
				}
				else {
					echo '<img alt="" src="' . Yii::app()->baseUrl . '/images/backup_running.png" />';
				}
				echo '</td>';
				$date = $formatter->formatDateTime(substr($backup->ou, 0, strlen($backup->ou)-1));
				echo '<td style="white-space: nowrap;">' . $date . '</td><td style="text-align: center;">' . $backup->sstProvisioningMode . '</td><td>';
				if (0 != $backup->sstProvisioningReturnValue) {
					echo $backup->sstProvisioningReturnValue . ' (';	
				
					switch($backup->sstProvisioningReturnValue) {
						case  1: echo Yii::t('backup', 'UNDEFINED_ERROR'); break;
						case  2: echo Yii::t('backup', 'MISSING_PARAMETER_IN_CONFIG_FILE'); break;
						case  3: echo Yii::t('backup', 'CONFIGURED_RAM_DISK_IS_NOT_VALID'); break;
						case  4: echo Yii::t('backup', 'NOT_ENOUGH_SPACE_ON_RAM_DISK'); break;
						case  5: echo Yii::t('backup', 'CANNOT_SAVE_MACHINE_STATE'); break;
						case  6: echo Yii::t('backup', 'CANNOT_WRITE_TO_BACKUP_LOCATION'); break;
						case  7: echo Yii::t('backup', 'CANNOT_COPY_FILE_TO_BACKUP_LOCATION'); break;
						case  8: echo Yii::t('backup', 'CANNOT_COPY_IMAGE_TO_BACKUP_LOCATION'); break;
						case  9: echo Yii::t('backup', 'CANNOT_COPY_XML_TO_BACKUP_LOCATION'); break;
						case 10: echo Yii::t('backup', 'CANNOT_COPY_BACKEND_FILE_TO_BACKUP_LOCATION'); break;
						case 11: echo Yii::t('backup', 'CANNOT_MERGE_DISK_IMAGES'); break;
						case 12: echo Yii::t('backup', 'CANNOT_REMOVE_OLD_DISK_IMAGE'); break;
						case 13: echo Yii::t('backup', 'CANNOT_REMOVE_FILE'); break;
						case 15: echo Yii::t('backup', 'CANNOT_CREATE_EMPTY_DISK_IMAGE'); break;
						case 16: echo Yii::t('backup', 'CANNOT_RENAME_DISK_IMAGE'); break;
						case 17: echo Yii::t('backup', 'CANNOT_CONNECT_TO_BACKEND'); break;
						case 18: echo Yii::t('backup', 'WRONG_STATE_INFORMATION'); break;
						case 19: echo Yii::t('backup', 'CANNOT_SET_DISK_IMAGE_OWNERSHIP'); break;
						case 20: echo Yii::t('backup', 'CANNOT_SET_DISK_IMAGE_PERMISSION'); break;
						case 21: echo Yii::t('backup', 'CANNOT_RESTORE_MACHINE'); break;
						case 22: echo Yii::t('backup', 'CANNOT_LOCK_MACHINE'); break;
						case 23: echo Yii::t('backup', 'CANNOT_FIND_MACHINE'); break;
						case 24: echo Yii::t('backup', 'CANNOT_COPY_STATE_FILE_TO_RETAIN'); break;
						case 25: echo Yii::t('backup', 'RETAIN_ROOT_DIRECTORY_DOES_NOT_EXIST'); break;
						case 26: echo Yii::t('backup', 'BACKUP_ROOT_DIRECTORY_DOES_NOT_EXIST'); break;
						case 27: echo Yii::t('backup', 'CANNOT_CREATE_DIRECTORY'); break;
						case 28: echo Yii::t('backup', 'CANNOT_SAVE_XML'); break;
						case 29: echo Yii::t('backup', 'CANNOT_SAVE_BACKEND_ENTRY'); break;
						case 30: echo Yii::t('backup', 'CANNOT_SET_DIRECTORY_OWNERSHIP'); break;
						case 31: echo Yii::t('backup', 'CANNOT_SET_DIRECTORY_PERMISSION'); break;
						case 32: echo Yii::t('backup', 'CANNOT_FIND_CONFIGURATION_ENTRY'); break;
						case 33: echo Yii::t('backup', 'BACKEND_XML_UNCONSISTENCY'); break;
						case 34: echo Yii::t('backup', 'CANNOT_CREATE_TARBALL'); break;
						case 35: echo Yii::t('backup', 'UNSUPPORTED_FILE_TRANSFER_PROTOCOL'); break;
						case 36: echo Yii::t('backup', 'UNKNOWN_BACKEND_TYPE'); break;
						case 37: echo Yii::t('backup', 'MISSING_NECESSARY_FILES'); break;
						case 38: echo Yii::t('backup', 'CORRUPT_DISK_IMAGE_FOUND'); break;
						case 39: echo Yii::t('backup', 'UNSUPPORTED_CONFIGURATION_PARAMETER'); break;
						case 40: echo Yii::t('backup', 'CANNOT_MOVE_DISK_IMAGE_TO_ORIGINAL_LOCATION'); break;
						case 41: echo Yii::t('backup', 'CANNOT_DEFINE_MACHINE'); break;
						case 42: echo Yii::t('backup', 'CANNOT_START_MACHINE'); break;
						case 43: echo Yii::t('backup', 'CANNOT_WORK_ON_UNDEFINED_OBJECT'); break;
						case 44: echo Yii::t('backup', 'CANNOT_READ_STATE_FILE'); break;
						case 45: echo Yii::t('backup', 'CANNOT_READ_XML_FILE'); break;
						case 46: echo Yii::t('backup', 'NOT_ALL_FILES_DELETED_FROM_RETAIN_LOCATION'); break;
						default: echo Yii::t('backup', 'UNKNOWN_ERROR'); break;
					}
					echo ')';
				}
				else {
					echo '&nbsp;';
				}
				echo '</td><td>';
				if ('finished' === $backup->sstProvisioningMode && !$restoring) {
					echo '<img class="action" title="restore VM backup" alt="restore" src="' . Yii::app()->baseUrl . '/images/vm_restore.png" backupDn="' . $backup->Dn . '" style="cursor: pointer;">';
				}
				else {
					echo '&nbsp;';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo <<< EOS
<script type="text/javascript">
	$("#reseller{$rowid}").change(resellerChange);
	$("#customer{$rowid}").change(customerChange);
	$("#person{$rowid}").change(personChange);
	$("#assign{$rowid}save").click(assignSave);
</script>
EOS;
	}

	public function actionGetCheckCopyGui() {
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmtemplate', 'Check Volume Copy') . '</span></div>';
?>
		<div style="text-align: center;" ><img id="running" src="<?php echo Yii::app()->baseUrl; ?>/images/loading.gif" alt="" /><br/></div>
		<div id="errorAssignment" class="ui-state-error ui-corner-all" style="display: block; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>
			<span id="errorMsg">
			<?=Yii::t('vmtemplate', 'Copy of VM Template volume to VM volume still running!'); ?></span></p>
		</div>
		<div id="infoAssignment" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoMsg"></span></p>
		</div>
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionGetNodeGui() {
		$this->disableWebLogRoutes();
		$narray = array();
		$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
		$vmpool = $vm->vmpool;
		foreach ($vmpool->nodes as $poolnode) {
			$node = LdapNode::model()->findByAttributes(array('attr'=>array('sstNode' => $poolnode->ou)));
			if (!is_null($node) && $node->sstNode != $vm->sstNode && $node->isType('VM-Node') && 'maintenance' !== $node->getType('VM-Node')->sstNodeState) {
				$narray[$node->dn] = array('name' => $node->sstNode);
			}
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vmtemplate', 'Migrate VM Template "{name}"', array('{name}' => $vm->sstDisplayName)) . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'nodeSelection',
			'values' => $narray,
			'multiselect' => false,
			'size' => 7,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'Nodes'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
		$showbutton = '';
		$showerror = 'display: none;';
		$errormsg = '';
		if (0 == count($narray)) {
			$showbutton = 'display: none;';
			$showerror = '';
			$errormsg = Yii::t('vmtemplate', 'No node found to migrate to');
		}
?>
		<br/>
		<button id="migrateNode" style="margin-top: 10px; float: left; <?php echo $showbutton?>"></button>
		<div id="errorNode" class="ui-state-error ui-corner-all" style="<?php echo $showerror;?>  width: 160px; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorNodeMsg" style="display: block;"><?php echo $errormsg;?></span></p>
		</div>
		<div id="infoNode" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoNodeMsg"></span></p>
		</div>
		<br style="clear: both;"/><br/><br/><br/>&nbsp;
<?php
		$dual = ob_get_contents();
		ob_end_clean();
		echo $dual;
	}

	public function actionGetStaticPoolGui() {
		$this->disableWebLogRoutes();
		$parray = array();
		$pools = CLdapRecord::model('LdapVmPool')->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'persistent')));
		foreach ($pools as $pool) {
			$parray[$pool->dn] = array('name' => $pool->sstDisplayName);
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Create persistent VM') . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'staticpoolSelection',
			'values' => $parray,
			'multiselect' => false,
			'size' => 4,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'persistent VM Pools'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
		
		//$name = isset($_GET['name']) ? $_GET['name'] : '';
		$config = LdapConfigurationHostname::model()->findAll(array('filterName' => 'all'));
		$hostname = $config[0]->getNextHostname();
		 
		$name = $hostname . '.' . $config[0]->sstNetworkDomainName;
?>
		<div style="padding-top: 10px; clear: both;">
			<label for="displayname">Name </label><input type="text" id="displayname" name="displayname" disabled="disabled" value="<?php echo $name; ?>"/>
			<input type="hidden" id="hostname" name="hostname" value="<?php echo $hostname; ?>" />
			<input type="hidden" id="domainname" name="domainname" value="<?php echo $config[0]->sstNetworkDomainName; ?>" />
		</div>
		<br/>
		<div id="radiosubtype" style="">
			<label>Type </label>
			<input type="radio" id="radiosubtype1" name="radiosubtype" value="Server" checked="checked" /><label for="radiosubtype1">Server</label>
			<input type="radio" id="radiosubtype2" name="radiosubtype" value="Desktop" /><label for="radiosubtype2">Desktop</label>
		</div>
		<button id="selectStaticButton" style="margin-top: 10px; float: left;"></button>
		<div id="errorSelectStatic" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectStaticMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectStatic" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em; float: right;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectStaticMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionGetDynamicPoolGui() {
		$this->disableWebLogRoutes();
		$parray = array();
		$pools = CLdapRecord::model('LdapVmPool')->findAll(array('attr'=>array('sstVirtualMachinePoolType'=>'dynamic')));
		foreach ($pools as $pool) {
			$parray[$pool->dn] = array('name' => $pool->sstDisplayName);
		}
		ob_start();
		echo '<div class="ui-widget-header ui-corner-all" style="padding: 0.4em 1em; margin-bottom: 0.7em;"><span class="">' . Yii::t('vm', 'Create dynamic VM') . '</span></div>';
		$dual = $this->createWidget('ext.zii.CJqSingleselect', array(
			'id' => 'dynamicpoolSelection',
			'values' => $parray,
			'multiselect' => false,
			'size' => 4,
			'options' => array(
				'sorted' => true,
				'header' => Yii::t('vm', 'dynamic VM Pools'),
			),
			'theme' => 'osbd',
			'themeUrl' => $this->cssBase . '/jquery',
			'cssFile' => 'singleselect.css',
		));
		$dual->run();
?>
		<br/>
		<div style="padding-top: 10px; clear: both;">
			<label for="displayname">Name </label><input type="text" id="displayname" name="displayname" value="<?php echo (isset($_GET['name']) ? $_GET['name'] : ''); ?>"/>
		</div>
		<br/>
		<div style="">
			<input type="checkbox" id="radiosysprep" name="radiosysprep" /><label for="radiosysprep">Sys Prep</label>
		</div>
		<button id="selectDynamicButton" style="margin-top: 10px;"></button>
		<div id="errorSelectDynamic" class="ui-state-error ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span><span id="errorSelectDynamicMsg" style="display: block;"></span></p>
		</div>
		<div id="infoSelectDynamic" class="ui-state-highlight ui-corner-all" style="display: none; margin-top: 10px; margin-left: 20px; padding: 0pt 0.7em;">
			<p style="margin: 0.3em 0pt ; "><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><span id="infoSelectDynamicMsg"></span></p>
		</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	public function actionCheckCopy() {
		$this->disableWebLogRoutes();
		if (CPhpLibvirt::getInstance()->checkPid($_GET['pid'])) {
			$json = array('err' => true, 'msg' => Yii::t('vmtemplate', 'Still copying!'));
		}
		else {
			if (isset($_SESSION['copyVolumeFile'])) {
				chmod($_SESSION['copyVolumeFile']['SourceFile'], 0660);

				$vm = CLdapRecord::model('LdapVm')->findByDn($_SESSION['copyVolumeFile']['Dn']);
				if (!is_null($vm)) {
					$retval = CPhpLibvirt::getInstance()->defineVm($vm->getStartParams());
					if (false !== $retval) {
						$json = array('err' => false, 'msg' => Yii::t('vmtemplate', 'Finished!'));
					}
					else {
						$json = array('err' => true, 'msg' => 'CPhpLibvirt defineVm failed (' . CPhpLibvirt::getInstance()->getLastError() . ')!');
					}
				}
				else {
					$json = array('err' => true, 'msg' => 'Copied Vm not found!');
				}
				unset($_SESSION['copyVolumeFile']);
			}
			else {
				$json = array('err' => false, 'msg' => Yii::t('vmtemplate', 'No copy action found in session!'));
			}
		}
		$this->sendJsonAnswer($json);
	}

	public function actionGetDynData() {
		$this->disableWebLogRoutes();
		$pool = $_GET['pool'];
		$vmpool = CLdapRecord::model('LdapVmPool')->findByAttributes(array('attr'=>array('sstVirtualMachinePool'=>$pool)));
		$retval = array();
		$retval['nodes'] = array();
		foreach($vmpool->nodes as $node) {
			$retval['nodes'][$node->ou] = $node->ou;
		}

		$this->sendJsonAnswer($retval);
	}


	/* Private functions */
	private static $_levels = array('ou', 'sstOSArchitectureValues', 'sstLanguageValues');

	private function getProfilesFromSubTree($result, $i=0) {
//		if (!isset(self::$_levels[$i])) {
//			return null;
//		}
//		$attr = self::$_levels[$i];
//		$retval = array();
//		echo '<br/>' . substr('-----', 0, $i) . $i . ': ' . $result->dn . '<br/>';
//		$children = $result->children;
//		if (!is_array($children)) {
//			$children = array($children);
//		}
//		foreach($children as $child) {
//
//			echo '(' . $child->dn . ', ' . $child->$attr . ')';
//			if (isset($child->ou)) {
//				//echo substr('+++++', 0, $i+1) . ($i+1) . ': ' . $child->ou . '<br/>';
//				//$retval[$child->dn] = array('name' => $child->ou);
//				if (isset($child->children)) {
//					$retval[$child->dn]['children'] = $this->getProfilesFromSubTree($child, $i+1);
//				}
//			//}
//		}
//		return $retval;

		$retval = array();
		foreach($result->children as $child) {
			//echo $child->dn . '  (' . count($child->children) . ')<br/>';
			$retval[$child->ou] = array('dn' => $child->dn, 'children' => array());
			if (!is_null($child->children)) {
				$childs = $child->children;
				if (!is_array($childs)) {
					$childs = array($childs);
				}
				foreach($childs as $child2) {
					if ('default' == $child2->ou) continue;

					//echo $child2->dn . '<br/>';
					$retval[$child->ou]['children'][$child2->ou] = array('dn' => $child2->dn, 'children' => array());
					if (!is_null($child2->children)) {
						$childs2 = $child2->children;
						if (!is_array($childs2)) {
							$childs2 = array($childs2);
						}
						foreach($childs2 as $child3) {
							//echo $child3->dn . '<br/>';
							if ($child3->hasObjectClass('sstVirtualizationProfileArchitectureDefaults')) {
								$archs = $child3->sstOSArchitectureValues;
								if (!is_array($archs)) {
									$archs = array($archs);
								}
								foreach($archs as $arch) {
									//echo $arch . '<br/>';
									$retval[$child->ou]['children'][$child2->ou]['children'][$arch] = array('dn' => $child3->dn, 'children' => array());
									$childs3 = $child3->children;
									if (!is_array($childs3)) {
										$childs3 = array($childs3);
									}
									foreach($childs3 as $child4) {
										$langs = $child4->sstLanguageValues;
										if (!is_array($langs)) {
											$langs = array($langs);
										}
										foreach($langs as $lang) {
											//echo $lang . '<br/>';
											if (!is_null($child4->children)) {
												$dn = $child4->children[0]->dn;
											}
											else {
												$dn = $child4->dn;
											}
											$retval[$child->ou]['children'][$child2->ou]['children'][$arch]['children'][$lang] = array('dn' => $dn);
										}
									}
								}
							}
							else {
								$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou] = array('dn' => $child3->dn, 'children' => array());
								$childs3 = $child3->children;
								foreach($childs3 as $child4) {
									if (!is_null($child4->children)) {
										$dn = $child4->children[0]->dn;
									}
									else {
										$dn = $child4->dn;
									}
									$retval[$child->ou]['children'][$child2->ou]['children'][$child3->ou]['children'][$child4->ou] = array('dn' => $dn);
								}
							}
						}
					}
				}
			}
		}
		return $retval;
	}


	public function actionSaveVm() {
		if (isset($_POST['oper']) && '' != $_POST['oper']) {
			switch($_POST['oper']) {
				case 'edit':
					break;
				case 'del':

					break;
			}
		}
	}

	private $status = array('unknown', 'stopped', 'running', 'migrating', 'shutdown');

	public function actionRefreshVms() {
		$this->disableWebLogRoutes();
		$data = array();
		if (isset($_GET['time'])) {
			$session = Yii::app()->getSession();
			$session->add('vm_refreshtime', (int) $_GET['time']);
		}
		if (isset($_GET['dns'])) {
			$dns = explode(';', $_GET['dns']);
			foreach($dns as $dn) {
				//echo "DN: $dn";
				$vm = LdapVmFromTemplate::model()->findByDn($dn);
				//echo '<pre>' . print_r($vm, true) . '</pre>';
				if (!is_null($vm)) {
					$answer = array('name' => $vm->sstDisplayName, 'node' => $vm->sstNode, 'substatus' => '', 'machinemode' => '', 'statustxt' => '');
					//
					// check sstVirtualMachineMode
					if (isset($vm->sstVirtualMachineMode)) {
						$answer['machinemode'] = $vm->sstVirtualMachineMode;
					}
					else {
						$answer['machinemode'] = 'do not use';
						$vm->setOverwrite(true);
						$vm->sstVirtualMachineMode = 'do not use';
						$vm->save();
						
					}
					//
					// check for backup
					if (!is_null($vm->backup)) {
						foreach($vm->backup->backups as $backup) {
							if ('finished' !== $backup->sstProvisioningMode) {
								if (!isset($backup->sstProvisioningReturnValue) || 0 == $backup->sstProvisioningReturnValue) {
									$answer['substatus'] = 'backing up';
									break;
								}
							}
						}
					}
					
					//
					// check for migrating
					if (isset($vm->sstMigrationNode)) {
						$answer['substatus'] = 'migrating';
					}
					
					$libvirt = CPhpLibvirt::getInstance();
					if ($status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
						$state = '';
						switch($status['state']) {
							case CPhpLibvirt::$VIR_DOMAIN_NOSTATE:		$state = 'unknown'; break;
							case CPhpLibvirt::$VIR_DOMAIN_RUNNING:		$state = 'running'; break;
							case CPhpLibvirt::$VIR_DOMAIN_BLOCKED:		$state = 'blocked'; break;
							case CPhpLibvirt::$VIR_DOMAIN_PAUSED:		$state = 'paused'; break;
							case CPhpLibvirt::$VIR_DOMAIN_SHUTDOWN:		$state = 'shutdown'; break;
							case CPhpLibvirt::$VIR_DOMAIN_SHUTOFF:		$state = 'stopped'; break;
							case CPhpLibvirt::$VIR_DOMAIN_CRASHED:		$state = 'crashed'; break;
							case CPhpLibvirt::$VIR_DOMAIN_PMSUSPENDED:	$state = 'suspended'; break;
							default: $state = '?? ' . $status['state']; break;
						}
						$answer['status'] = $state;
						$answer['spice'] = $vm->getSpiceUri();
// 						if ($vm->hasActiveBackup()) {
// 							$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'backup', 'spice' => $vm->getSpiceUri()));
// 						}
// 						else {
						{
							if ($status['active']) {
								$memory = $this->getHumanSize($status['memory'] * 1024);
								$maxmemory = $this->getHumanSize($status['maxMem'] * 1024);
								//$data[$vm->sstVirtualMachine] = array('status' => ($status['active'] ? 'running' : 'stopped'), 'mem' => $memory . ' / ' . $maxmemory, 'node' => $vm->sstNode);
								$answer['mem'] = $memory . ' / ' . $maxmemory;
							}
							else {
								if (isset($vm->sstThinProvisioningVirtualMachine)) {
									$prov = $vm->sstThinProvisioningVirtualMachine;
									foreach($vm->sstThinProvisioningVirtualMachine as $uuid) {
										$othervm = LdapVm::model()->findByAttributes(array('attr' => array('sstVirtualMachine' => $uuid)));
	
										// Referenced VM was removed, unlink and continue
										if (!is_null($othervm)) {
											$finished = true;
											$disks = $vm->devices->getDisksByDevice('disk');
											foreach($disks as $disk) {
												$info = $libvirt->checkBlockJob($othervm->node->getLibvirtUri(), $uuid, $disk->sstDisk);
												if (true !== $info) {
													$finished = false;
													break;
												}
											}
											if ($finished) {
												unset($prov[array_search($uuid, $prov)]);
											}
										}
										else {
											unset($prov[array_search($uuid, $prov)]);
										}
									}
									if (0 == count($prov)) {
										$deldata = array('sstThinProvisioningVirtualMachine' => array());
										CLdapServer::getInstance()->modify_del($vm->getDn(), $deldata);
									}
									else {
										$vm->setOverwrite(true);
										$prov = array_values($prov);
										$vm->sstThinProvisioningVirtualMachine = $prov;
										$vm->update();
	
										$answer['substatus'] = 'streaming';
									}
								}
// 								else {
// 									$data[$vm->sstVirtualMachine] = array_merge($answer, array('status' => 'stopped', 'spice' => $vm->getSpiceUri()));
// 								}
							}
						}
						$answer['_t_status'] = Yii::t('vmstatus', $answer['status']);
						$answer['_t_substatus'] = Yii::t('vmstatus', $answer['substatus']);
						$answer['_t_machinemode'] = Yii::t('vmstatus', $answer['machinemode']);
						$data[$vm->sstVirtualMachine] = $answer;
					}
					else {
						$data = array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt getVmStatus failed!');
					}
				}
				else {
					$data = array('error' => 1, 'message' => 'CPhpLibvirt Vm \'' . $dn . '\' not found!');
				}
			}
		}
		$this->sendJsonAnswer($data);
	}

	public function actionStartVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			//$devices = $vm->devices[0];
			//echo '$devices <pre>' . print_r($devices, true) . '</pre>';
			//$disks = $devices->disks;
			//echo '<pre>' . print_r($devices->disks, true) . '</pre>';
			//$interfaces = $devices->interfaces;
			//echo '<pre>' . print_r($devices->interfaces, true) . '</pre>';
			if (!is_null($vm)) {
				$params = array();
				$params['sstName'] = $vm->sstVirtualMachine;
				$params['sstUuid'] = $vm->sstVirtualMachine;
				$params['sstClockOffset'] = $vm->sstClockOffset;
				$params['sstClockTimer'] = $vm->sstClockTimer;
				$params['sstMemory'] = $vm->sstMemory;
				//$params['sstNode'] = $vm->sstNode;
				$params['libvirt'] = $vm->node->getLibvirtUri();
				$params['sstOnCrash'] = $vm->sstOnCrash;
				$params['sstOnPowerOff'] = $vm->sstOnPowerOff;
				$params['sstOnReboot'] = $vm->sstOnReboot;
				$params['sstOSArchitecture'] = $vm->sstOSArchitecture;
				$params['sstOSBootDevice'] = $vm->sstOSBootDevice;
				$params['sstOSMachine'] = $vm->sstOSMachine;
				$params['sstOSType'] = $vm->sstOSType;
				$params['sstType'] = $vm->sstType;
				$params['sstVCPU'] = $vm->sstVCPU;
				$params['sstFeature'] = $vm->sstFeature;
				$params['devices'] = array();
				$params['devices']['usb'] = ($vm->settings->isUsbAllowed() ? 'yes' : 'no');
				$params['devices']['sound'] = $vm->settings->isSoundAllowed();
				$params['devices']['sstEmulator'] = $vm->devices->sstEmulator;
				$params['devices']['sstMemBalloon'] = $vm->devices->sstMemBalloon;
				$params['devices']['graphics'] = array();
				$params['devices']['graphics']['spiceport'] = $vm->sstSpicePort;
				$params['devices']['graphics']['spicepassword'] = $vm->sstSpicePassword;
				$params['devices']['graphics']['spicelistenaddress'] = $vm->node->getVLanIP('pub');
				$params['devices']['graphics']['spiceacceleration'] = isset(Yii::app()->params['virtualization']['disableSpiceAcceleration'])
					&& Yii::app()->params['virtualization']['disableSpiceAcceleration'];
				$params['devices']['disks'] = array();
				foreach($vm->devices->disks as $disk) {
					$params['devices']['disks'][$disk->sstDisk] = array();
					$params['devices']['disks'][$disk->sstDisk]['sstDevice'] = $disk->sstDevice;
					$params['devices']['disks'][$disk->sstDisk]['sstDisk'] = $disk->sstDisk;
					$params['devices']['disks'][$disk->sstDisk]['sstSourceFile'] = $disk->sstSourceFile;
					$params['devices']['disks'][$disk->sstDisk]['sstTargetBus'] = $disk->sstTargetBus;
					$params['devices']['disks'][$disk->sstDisk]['sstType'] = $disk->sstType;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverName'] = $disk->sstDriverName;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverType'] = $disk->sstDriverType;
					$params['devices']['disks'][$disk->sstDisk]['sstReadonly'] = $disk->sstReadonly;
					$params['devices']['disks'][$disk->sstDisk]['sstDriverCache'] = $disk->sstDriverCache;
				}
				$params['devices']['interfaces'] = array();
				foreach($vm->devices->interfaces as $interface) {
					$params['devices']['interfaces'][$interface->sstInterface] = array();
					$params['devices']['interfaces'][$interface->sstInterface]['sstInterface'] = $interface->sstInterface;
					$params['devices']['interfaces'][$interface->sstInterface]['sstMacAddress'] = $interface->sstMacAddress;
					$params['devices']['interfaces'][$interface->sstInterface]['sstModelType'] = $interface->sstModelType;
					$params['devices']['interfaces'][$interface->sstInterface]['sstSourceBridge'] = $interface->sstSourceBridge;
					$params['devices']['interfaces'][$interface->sstInterface]['sstType'] = $interface->sstType;
				}
				//echo '<pre>' . print_r($params, true) . '</pre>';
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->startVm($params)) {
					$vm->setOverwrite(true);
					$vm->sstStatus = 'running';
					$vm->save();
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt startVm failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionRebootVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->rebootVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt rebootVm failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionShutdownVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->shutdownVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$vm->setOverwrite(true);
					$vm->sstStatus = 'shutdown';
					$vm->save();
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt shutdownVm failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionDestroyVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($libvirt->destroyVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$vm->setOverwrite(true);
					$vm->sstStatus = 'shutdown';
					$vm->save();
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt destroyVm failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionMigrateVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if ('undefined' == $_GET['newnode']) {
				$this->sendAjaxAnswer(array('error' => 2, 'message' => 'Please select a node!'));
				return;
			}

			$newnode = CLdapRecord::model('LdapNode')->findByDn($_GET['newnode']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				if ($status = $libvirt->getVmStatus(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine))) {
					$move = false;
					$spiceport = $libvirt->nextSpicePort($newnode->sstNode);
					if ($status['active']) {
						$vm->setOverwrite(true);
						$vm->sstMigrationNode = $newnode->sstNode;
						$vm->sstMigrationSpicePort = $spiceport;
						$vm->save();
						if ($libvirt->migrateVm(array(
								'libvirt' => $vm->node->getLibvirtUri(), 
								'newlibvirt' => $newnode->getLibvirtUri(), 
								'name' => $vm->sstVirtualMachine, 
								'spiceport' => $spiceport,
								'newlisten' => $newnode->getVLanIP('pub')))) {
							$vm->sstNode = $newnode->sstNode;
							$vm->sstSpicePort = $spiceport;
							$vm->save();
							$entries = array('sstMigrationNode' => array(), 'sstMigrationSpicePort' => array());
							CLdapServer::getInstance()->modify_del($vm->dn, $entries);
								
							$this->sendAjaxAnswer(array('error' => 0, 'message' => Yii::t('vm', 'Migration finished'), 'refresh' => 1));
						}
						else {
							$this->sendAjaxAnswer(array('error' => 1, 'message' => 'CPhpLibvirt migrateVm failed (' . $libvirt->getLastError() . ')!'));
						}
					}
					else {
						$libvirt->undefineVm(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine));
						$vm->setOverwrite(true);
						$vm->sstNode = $newnode->sstNode;
						$vm->sstSpicePort = $spiceport;
						$vm->save();
						
						$vm = CLdapRecord::model('LdapVm')->findByDn($_GET['dn']);
						$libvirt->defineVm($vm->getStartParams());
						$this->sendAjaxAnswer(array('error' => 0, 'message' => Yii::t('vm', 'Migration finished'), 'refresh' => 1));
					}
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt unable to check status of VM!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt VM with dn=\'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}

	public function actionToogleBoot() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$vm = CLdapRecord::model('LdapVmFromTemplate')->findByDn($_GET['dn']);
			if (!is_null($vm)) {
				$libvirt = CPhpLibvirt::getInstance();
				//$dev = array($_GET['dev']);
				//$dev[1] = 'hd' === $_GET['dev'] ? 'cdrom' : 'hd';
				$dev = array('hd' === $vm->sstOsBootDevice ? 'cdrom' : 'hd', $vm->sstOsBootDevice);
				if ($libvirt->changeVmBootDevice(array('libvirt' => $vm->node->getLibvirtUri(), 'name' => $vm->sstVirtualMachine, 'device1' => $dev[0], 'device2' => $dev[1]))) {
					$vm->setOverwrite(true);
					$vm->sstOsBootDevice = $dev[0];
					$vm->save();
					$this->sendAjaxAnswer(array('error' => 0));
				}
				else {
					$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): CPhpLibvirt changeVmBootDevice failed!'));
				}
			}
			else {
				$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Vm \'' . $_GET['dn'] . '\' not found!'));
			}
		}
		else {
			$this->sendAjaxAnswer(array('error' => 1, 'message' => __FILE__ . '(' . __LINE__ . '): Parameter dn not found!'));
		}
	}
	
	public function actionRestoreVm() {
		$this->disableWebLogRoutes();
		if (isset($_GET['dn'])) {
			$backup = CLdapRecord::model('LdapVmSingleBackup')->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$backup->setOverwrite(true);
				$backup->sstProvisioningMode = 'unretainSmallFiles';
				$backup->sstProvisioningState = '0';
				$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
				$json = array('err' => false, 'msg' => Yii::t('vm', 'Restore Vm started!'));
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		else {
			$json = array('err' => true, 'msg' => Yii::t('vm', 'Parameter dn not found!'));
		}
		$this->sendJsonAnswer($json);
	}
	
	public function actionWaitForRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('waitForRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				Yii::log('waitForRestoreAction: ' . $backup->sstProvisioningMode . ', ' . $backup->sstProvisioningReturnValue, 'profile', 'vmController');
				if ('unretainedSmallFiles' === $backup->sstProvisioningMode) {
					if (0 == $backup->sstProvisioningReturnValue) {
						$vm = $backup->vm;
						$vmpool = $vm->vmpool;
						$backupconf = $vmpool->getConfigurationBackup();
						$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
						$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
							$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;
						Yii::log('waitForRestoreAction: ' . $ldiffile, 'profile', 'vmController');
						if (file_exists($ldiffile)) {
							$json = array('err' => false, 'msg' => Yii::t('vm', 'Should restore of Vm start?'));
						}
						else {
							$json = array('err' => true, 'msg' => Yii::t('vm', 'Error finding LDIF file'));
						}
					}
					else {
						$json = array('err' => true, 'msg' => Yii::t('vm', 'Error unretaining files'));
					}
				}
				else {
					$json = array('err' => true, 'msg' => Yii::t('vm', 'Waiting for data'), 'refresh' => true);
				}
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
	}
	
	public function actionStartRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('startRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$vm = $backup->vm;
				$vmpool = $vm->vmpool;
				$backupconf = $vmpool->getConfigurationBackup();
				$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
				$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;
				$ldiftofile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif';
				if (copy($ldiffile, $ldiftofile)) {
					$backup->setOverwrite(true);
					$backup->sstProvisioningMode = 'unretainLargeFiles';
					$backup->sstProvisioningState = '0';
					$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
						
					$json = array('err' => false, 'msg' => Yii::t('vm', 'Restore started'));
				}
				else {
					$json = array('err' => true, 'msg' => Yii::t('vm', 'Error copying LDIF file'));
				}
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
	}
					
	public function actionCancelRestoreAction() {
		$this->disableWebLogRoutes();
		Yii::log('cancelRestoreAction: ' . $_GET['dn'], 'profile', 'vmController');
		if (isset($_GET['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_GET['dn']);
			if (!is_null($backup)) {
				$backup->setOverwrite(true);
				$backup->sstProvisioningMode = 'finished';
				$backup->sstProvisioningState = '0';
				$backup->save(true, array('sstProvisioningMode', 'sstProvisioningState'));
		
				$json = array('err' => false, 'msg' => Yii::t('vm', 'Canceled'));
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
		$this->sendJsonAnswer($json);
		}
	
	public function actionGetRestoreAction() {
		Yii::log('getRestoreAction: ' . $_POST['dn'], 'profile', 'vmController');
		if (isset($_POST['dn'])) {
			$backup = LdapVmSingleBackup::model()->findByDn($_POST['dn']);
			if (!is_null($backup)) {
				$vm = LdapVm::model()->findByDn(CLdapRecord::getParentDn(CLdapRecord::getParentDn($backup->getDn())));
				$backupconf = $vm->backup;
				if (!isset($backupconf->sstBackupRetainDirectory)) {
					$backupconf = $vm->vmpool->backup;
					if (is_null($backupconf) || !isset($backupconf->sstBackupRetainDirectory)) {
						$backupconf = LdapConfigurationBackup::model()->findByDn('ou=backup,ou=configuration,ou=virtualization,ou=services');
					}
				}
				$dir = 'vm-' . ('persistent' === $vm->sstVirtualMachineType ? 'persistent' : ('template' === $vm->sstVirtualMachineType ? 'templates' : '???'));
				$ldiffile = substr($backupconf->sstBackupRetainDirectory, 7) . '/' . $dir . '/' . $vm->vmpool->storagepools[0]->ou . '/' . $vm->sstVirtualMachine . '/' . $backup->ou . '/' .
						$vm->sstVirtualMachine . '.ldif.' .  $backup->ou;

		echo $dir . '<br/>';
		echo $ldiffile . '<br/>';
			}
			else {
				$json = array('err' => true, 'msg' => Yii::t('vm', 'Backup with dn=\'{dn}\' not found!', array('{dn}' => $_GET['dn'])));
			}
		}
?>
<form action="#">
	Test1: <input type="text" size="12" />
</form>
<?php
	}
	
	public function actionHandleRestoreAction() {
	}

	public function actionSetMachineMode() {
		Yii::log('setMachineMode: ' . $_POST['dn'], 'profile', 'vmTemplateController');
		if (isset($_POST['dn'])) {
			$template = LdapVmFromTemplate::model()->findByDn($_POST['dn']);
			$template->setOverwrite(true);
			$template->sstVirtualMachineMode = $_POST['machinemode'];
			try {
				$template->save(false);
				$json = array('err' => false, 'msg' => '');
			}
			catch(CLdapException $e) {
				$json = array('err' => true, 'msg' => $e->getMessage());
			}
		}
		$this->sendJsonAnswer($json);
	}
	
	public function actionGetPersistentCreationData($dn) {
		$json = array('stacks' => array(), 'resellers' => array());
		$template = LdapVmFromProfile::model()->findByDn($dn);
		$os = $template->operatingsystem;
		if (!is_null($os)) {
			$envs = LdapConfigurationSoftwareStackEnvironment::model()->findAll(array('attr' => array()));
			$stacks = LdapConfigurationSoftwareStack::model()->findAll(array('attr' => array()));
			$json['length'] = count($stacks);
			foreach($stacks as $stack) {
				$data = array();
				$data['name'] = $stack->sstDisplayName;
				$data['env'] = array();
				foreach($stack->sstEnvironmentName as $name) {
					$key = '??';
					$envname = '??';
					foreach($envs as $env) {
						if ($name == $env->sstEnvironmentName) {
							$key = $env->uid;
							$envname = $env->sstDisplayName;
							break;
						}
					}
					$data['env'][$key] = $envname;
				}
				$json['stacks'][$stack->getDn()] = $data;
			}
		}
		else {
			$json['stacks'] = null;
		}
		$config = LdapConfigurationHostname::model()->findAll(array('filterName' => 'all'));
		$json['hostname'] = $config[0]->getNextHostname();
		$json['fullname'] = $json['hostname'] . '.' . $config[0]->sstNetworkDomainName;
		$json['domainname'] = $config[0]->sstNetworkDomainName;
		$resellers = LdapReseller::model()->findAll(array('filterName' => 'all'));
		foreach($resellers as $reseller) {
			$data = array();
			$data['name'] = $reseller->o;
			$data['customers'] = array();
			foreach($reseller->customer as $customer) {
				$data['customers'][$customer->uid] = $customer->o;
			}
			$json['resellers'][$reseller->uid] = $data;
		}
		$json['reseller'] = Yii::app()->user->getResellerUid();
		$json['customer'] = Yii::app()->user->getCustomerUid();
		$this->sendJsonAnswer($json);
	}

	public function actionSetRelationship($dn, $reseller, $customer, $person=null) {
		$this->disableWebLogRoutes();
		$data = array('error' => 0, 'message' => 'saved');
		$attrs = array();
		//$vm = LdapVmFromTemplate::model()->findByDn($dn);
		$criteria = array('attr' => array(
			'dn' => $dn,
			'objectClass' => 'sstRelationship'
		));
		$vm = LdapVmFromTemplate::model()->findByAttributes($criteria);
		if (is_null($vm)) {
			$vm = LdapVmFromTemplate::model()->findByDn($dn);
			if (!is_null($vm)) {
				//$vm->removeAttributesByObjectClass('sstRelationship');
				// just to also save objectClasses
				$vm->addObjectClass('sstRelationship');
			}
		}
		if (!is_null($vm)) {
			$vm->setOverwrite(true);
			$vm->sstBelongsToResellerUID = $reseller;
			$attrs[] = 'sstBelongsToResellerUID';
			$vm->sstBelongsToCustomerUID = $customer;
			$attrs[] = 'sstBelongsToCustomerUID';
			if (!is_null($person) && '' !== $person) {
				$vm->sstBelongsToPersonUID = $person;
			}
			else {
				$vm->sstBelongsToPersonUID = array();
			}
			$attrs[] = 'sstBelongsToPersonUID';
			try {
				$vm->save(false, $attrs);
			}
			catch(CLdapException $e) {
				$data['error'] = 1;
				$data['message'] = $e->getMessage();
			}
			$vmnet = $vm->dhcp;
			if (!is_null($vmnet)) {
// 				echo '<pre>' . print_r($vmnet, true) . '</pre>';
// 				Yii::app()->end();
				$attrs = array();
				$vmnet->setOverwrite(true);
				$vmnet->sstBelongsToResellerUID = $reseller;
				$attrs[] = 'sstBelongsToResellerUID';
				$vmnet->sstBelongsToCustomerUID = $customer;
				$attrs[] = 'sstBelongsToCustomerUID';
				if (!is_null($person) && '' !== $person) {
					$vmnet->sstBelongsToPersonUID = $person;
				}
				else {
					$vmnet->sstBelongsToPersonUID = array();
				}
				$attrs[] = 'sstBelongsToPersonUID';
				try {
					$vmnet->save(false, $attrs);
				}
				catch(CLdapException $e) {
					$data['error'] = 1;
					$data['message'] = $e->getMessage();
				}
			}
			else {
				$data['error'] = 1;
				$data['message'] = 'VM Network not found!';
			}
		}
		else {
			$data['error'] = 1;
			$data['message'] = 'VM not found!';
		}
		$this->sendJsonAnswer($data);
	}
}
