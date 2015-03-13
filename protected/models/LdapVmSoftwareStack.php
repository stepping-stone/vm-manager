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

class LdapVmSoftwareStack extends CLdapRecord {
	protected $_branchDn = '';
	protected $_filter = array('all' => 'ou=*');
	protected $_dnAttributes = array('ou');
	protected $_objectClasses = array('sstGroupObjectClass', 'labeledURIObject', 'top');
	
	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
		'environment' => array(self::HAS_ONE, 'sstEnvironmentName', 'LdapConfigurationSoftwareStackEnvironment', 'uid'),
		);
	}

	/**
	 * Returns the static model of the specified LDAP class.
	 * @return CLdapRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
