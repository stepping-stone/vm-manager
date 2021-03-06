<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 * and
 * Copyright (C) 2013 - 2015 stepping stone GmbH
 *                           Switzerland
 *                           http://www.stepping-stone.ch
 *                           support@stepping-stone.ch
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
 * LdapVm class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class LdapVm extends CLdapRecord {
	protected $_branchDn = 'ou=virtual machines,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'sstVirtualMachine=*');
	protected $_dnAttributes = array('sstVirtualMachine');
	protected $_objectClasses = array('sstVirtualizationVirtualMachine', 'sstRelationship', 'sstSpice', 'labeledURIObject', 'top');

	public function rules()
	{
		return array(
			array('', 'required'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('title', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'node' => array(self::HAS_ONE, 'sstNode', 'LdapNode', 'sstNode'),
			//'network' => array(self::HAS_MANY_DEPTH, 'sstVirtualMachine', 'LdapNetwork', 'cn'),
			'network' => array(self::HAS_MANY_DEPTH, 'sstVirtualMachine', 'LdapNetwork', 'cn', array('cn' => '"" . $model->sstVirtualMachine . "*"')),
			'devices' => array(self::HAS_ONE, 'dn', 'LdapVmDevice', '$model->getDn()', array('ou' => 'devices')),
			'defaults' => array(self::HAS_ONE_DN, 'dn', 'LdapVmDefaults', '$model->labeledURI', array()),
			'dhcp' => array(self::HAS_ONE_DEPTH, 'sstVirtualMachine', 'LdapDhcpVm', 'cn', array('objectclass' => 'dhcpHost')),
			'vmpool' => array(self::HAS_ONE, 'sstVirtualMachinePool', 'LdapVmPool', 'sstVirtualMachinePool'),
			'groups' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=groups,\' . $model->getDn()'),
			'people' => array(self::HAS_MANY, 'dn', 'LdapNameless', '\'ou=people,\' . $model->getDn()'),
			'backup' => array(self::HAS_ONE, 'dn', 'LdapVmBackup', '$model->getDn()', array('ou' => 'backup')),
			'settings' => array(self::HAS_ONE, 'dn', 'LdapVmConfigurationSettings', '$model->getDn()', array('ou' => 'settings')),
			'operatingsystem' => array(self::HAS_ONE, 'dn', 'LdapVmOperatingSystem', '$model->getDn()', array('ou' => 'operating system')),
			'softwarestack' => array(self::HAS_ONE, 'dn', 'LdapVmSoftwareStack', '$model->getDn()', array('ou' => 'software stack')),
		);
	}

	protected function createAttributes() {
		parent::createAttributes();

		$this->_attributes['sstfeature']['type'] = 'array';
		$this->_attributes['sstclocktimer']['type'] = 'array';

		if (isset($this->_attributes['sstthinprovisioningvirtualmachine'])) {
			$this->_attributes['sstthinprovisioningvirtualmachine']['type'] = 'array';
		}
	}
	
	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getIp()
	{
		foreach($this->network as $network) {
			if (false === strpos($network->cn)) {
				return $network->dhcpstatements['fixed-address'];
			}
		}
		return '???';
	}

	public function isActive()
	{
		$retval = false;
		$libvirt = CPhpLibvirt::getInstance();
		if (!$this->isNewEntry()) {
			if ($status = $libvirt->getVmStatus(array('libvirt' => $this->node->getLibvirtUri(), 'name' => $this->sstVirtualMachine))) {
				$retval = $status['active'];
			}
		}
		return $retval;
	}

	public function getSpiceUri() {
		return 'spice://' . $this->node->getSpiceIp() . '?port=' . $this->sstSpicePort . '&password=' . $this->sstSpicePassword;
	}

	public function getStartParams() {
		$params = array();
		$params['sstName'] = $this->sstVirtualMachine;
		$params['sstUuid'] = $this->sstVirtualMachine;
		$params['sstClockOffset'] = $this->sstClockOffset;
		$params['sstClockTimer'] = $this->sstClockTimer;
		$params['sstMemory'] = $this->sstMemory;
		$params['sstNetworkHostname'] = $this->sstNetworkHostname;
		$params['sstNetworkDomainName'] = $this->sstNetworkDomainName;
		//$params['sstNode'] = $this->sstNode;
		$params['libvirt'] = $this->node->getLibvirtUri();
		$params['sstOnCrash'] = $this->sstOnCrash;
		$params['sstOnPowerOff'] = $this->sstOnPowerOff;
		$params['sstOnReboot'] = $this->sstOnReboot;
		$params['sstOSArchitecture'] = $this->sstOSArchitecture;
		$params['sstOSBootDevice'] = $this->sstOSBootDevice;
		$params['sstOSMachine'] = $this->sstOSMachine;
		$params['sstOSType'] = $this->sstOSType;
		$params['sstType'] = $this->sstType;
		$params['sstVCPU'] = $this->sstVCPU;
		$params['sstFeature'] = $this->sstFeature;
		$params['devices'] = array();
		$params['devices']['usb'] = ($this->settings->isUsbAllowed() ? 'yes' : 'no');
		$params['devices']['sound'] = $this->settings->isSoundAllowed();
		$params['devices']['sstEmulator'] = $this->devices->sstEmulator;
		$params['devices']['sstMemBalloon'] = $this->devices->sstMemBalloon;
		$params['devices']['graphics'] = array();
		$params['devices']['graphics']['spiceport'] = $this->sstSpicePort;
		$params['devices']['graphics']['spicepassword'] = $this->sstSpicePassword;
		$params['devices']['graphics']['spicelistenaddress'] = $this->node->getVLanIP('pub');
		$params['devices']['graphics']['spiceacceleration'] = isset(Yii::app()->params['virtualization']['disableSpiceAcceleration'])
			&& Yii::app()->params['virtualization']['disableSpiceAcceleration'];
		$params['devices']['disks'] = array();
		foreach($this->devices->disks as $disk) {
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
			
			$params['devices']['disks'][$disk->sstDisk]['sstSourceProtocol'] = $disk->sstSourceProtocol;
			$params['devices']['disks'][$disk->sstDisk]['sstSourceName'] = $disk->sstSourceName;
			$params['devices']['disks'][$disk->sstDisk]['sstSourceHostName'] = $disk->sstSourceHostName; //  = Yii::app()->params['virtualization']['glusterFSHost'];
			$params['devices']['disks'][$disk->sstDisk]['sstSourcePort'] = $disk->sstSourcePort;
		}
		$params['devices']['interfaces'] = array();
		foreach($this->devices->interfaces as $interface) {
			$params['devices']['interfaces'][$interface->sstInterface] = array();
			$params['devices']['interfaces'][$interface->sstInterface]['sstInterface'] = $interface->sstInterface;
			$params['devices']['interfaces'][$interface->sstInterface]['sstMacAddress'] = $interface->sstMacAddress;
			$params['devices']['interfaces'][$interface->sstInterface]['sstModelType'] = $interface->sstModelType;
			$params['devices']['interfaces'][$interface->sstInterface]['sstSourceBridge'] = $interface->sstSourceBridge;
			$params['devices']['interfaces'][$interface->sstInterface]['sstType'] = $interface->sstType;
		}
		return $params;
	}

	public function assignUser() {
		$user = CLdapRecord::model('LdapUser')->findByDn('uid=' . Yii::app()->user->getUID() . ',ou=people');
		if (!is_null($user)) {
			$server = CLdapServer::getInstance();
			$data = array();
			$data['objectClass'] = array('top', 'organizationalUnit', 'labeledURIObject', 'sstRelationship');
			$data['ou'] = $user->uid;
			$data['description'] = array('This entry links to the user ' . $user->getName() . '.');
			$data['labeledURI'] = array('ldap:///' . $user->dn);
			$data['sstBelongsToCustomerUID'] = array(Yii::app()->user->customerUID);
			$data['sstBelongsToResellerUID'] = array(Yii::app()->user->resellerUID);
			$dn = 'ou=' . $user->uid . ',ou=people,' . $this->getDn();
			$server->add($dn, $data);
		}
	}


	public function hasActiveBackup() {
		$single = LdapVmSingleBackup::model();
		$single->branchDn = $this->getDn(); // Don't use 'ou=backup,' . $this->getDn(); because there might be no backup branch
		$active = $single->findAll(array('filterName' => 'active', 'depth' => true));
				
		return 0 < count($active);
	}
	
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'name' => Yii::t('vm', 'name')
		);
	}

	public function search()
	{
		$criteria = array(
			'attr' => array(
				'name' => $this->name
			),
		);

		return new CLdapDataProvider('LdapVm', array(
			'criteria' => $criteria,
			'pagination' => array(
				'pageSize' => 1,
			),
		));
	}
}
