<?php
/*
 * Copyright (C) 2014 stepping stone GmbH
 *                    Switzerland
 *                    http://www.stepping-stone.ch
 *                    support@stepping-stone.ch
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

class LdapReseller extends CLdapRecord {
	protected $_branchDn = 'ou=reseller';
	protected $_filter = array('all' => 'uid=*');
	protected $_dnAttributes = array('uid');
	protected $_objectClasses = array('sstReseller', 'top');

	public function relations()
	{
		return array(
		// __construct($name,$attribute,$className,$foreignAttribute,$options=array())
			'customer' => array(self::HAS_MANY, 'uid', 'LdapCustomer', 'sstBelongsToResellerUID'),
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