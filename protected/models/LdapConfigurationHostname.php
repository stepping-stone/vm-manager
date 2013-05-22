<?php
/*
 * Copyright (C) 2013 stepping stone GmbH
 * Switzerland
 * http://www.stepping-stone.ch
 * support@stepping-stone.ch
 *
 * Authors:
 * Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1.
 *
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

class LdapConfigurationHostname extends CLdapRecord {
	protected $_branchDn = 'ou=settings,ou=configuration,ou=virtualization,ou=services';
	protected $_filter = array('all' => 'ou=hostname');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstHostnameDefinitionObjectClass', 'organizationalUnit', 'top');

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getNextHostname() {
		$retval = null;  // means that someone else want's to get a hostname at the moment

		$server = CLdapServer::getInstance();
			
		$this->setOverwrite(true);
		$this->businessCategory = 'locked';
		$this->update(array('seeAlso'));
	
		$number = (int) $this->sstNetworkHostnameNextFreeNumber;
	
		$this->sstNetworkHostnameNextFreeNumber = $number + 1;
		$this->update(array('sstNetworkHostnameNextFreeNumber'));
	
		$data = array('businessCategory' => array());
		$server->modify_del($this->getDn(), $data);
		
		$retval = sprintf($this->sstNetworkHostnameFormat, $number);

		return $retval;
	}
}