<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
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

return array(
	// application components
	'components'=>array(
		'log'=>array(
			'routes'=>array(
				// uncomment the following to log messages for ldap actions
//				array(
//					'class' => 'CFileLogRoute',
//					'levels' => 'profile',
//					'categories' => 'ext.ldaprecord.*',
//					'logFile' => 'ldaprecord.log'
//				),
				// uncomment the following to log messages for libvirt actions
//				array(
//					'class' => 'CFileLogRoute',
//					'levels' => 'profile',
//					'categories' => 'phplibvirt',
//					'logFile' => 'phplibvirt.log'
//				),
				array(
					'class'=>'ext.ESysLogRoute',
					'logName'=>'vm-manager',
					'logFacility'=>LOG_LOCAL0,
					'levels'=>'warning',
					'categories' => 'ext.ldaprecord.* phplibvirt.log',
				),
			),
		),
		'ldap'=>array(
			'class' => 'ext.ldaprecord.LdapComponent',
			'serverclass' => 'COsbdLdapServer',
			'server' => 'ldap://127.0.0.1/',
			'port' => 389,
			'bind_rdn' => 'cn=admin,dc=devroom,dc=de',
			'bind_pwd' => 'flinx',
			'base_dn' => 'dc=devroom,dc=de',
			'passwordtype' => 'SSHA',
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		'virtualization' => array(
			'version' => '1.2.18.13',

			// Disk specific settings.
			'disk' => array(
				// Path / volume of the source.
				'sstSourceName' => array(
					'vm-persistent' => array('/var/virtualization', 'virtualization'), // [0] = search string, [1] = replace string
					'vm-templates'  => array('/var/virtualization', 'virtualization'),
					'vm-dynamic'    => array('/var/virtualization', 'virtualization'),
				),
				// The hostname of the source host.
				'sstSourceHostName' => array(
					'vm-persistent' => '10.1.120.11',
					'vm-templates'  => '10.1.120.11',
					'vm-dynamic'    => '10.1.120.11',
				),
				// The port of the source host.
				'sstSourcePort' => array(
					'vm-persistent' => 24007,
					'vm-templates'  => 24007,
					'vm-dynamic'    => 24007,
				),
			),
			// Don't change the following params if you don't know what you are doing.
			'spiceByName' => false,
			'disableSpiceAcceleration' => false,
			// CPU specific settings.
			'cpu' => array(
				// The maximum number of available vCPU's (used for CPU hotplug).
				'maxVCPU' => 16,
			),
			'libvirt' => array(
				// The bandwidth used for blockjobs (cloning). In MiB/s.
				'bandwidth' => 250,
			),
		),
	),
);
