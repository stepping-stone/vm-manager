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
 * Licensed under the EUPL, Version 1.1 or � as soon they
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


// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.

return CMap::mergeArray(
    require(dirname(__FILE__).'/vm_config.php'),
	array(
	'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
	'name' => 'stoney cloud',
	'language'          =>'en',
	//'homeUrl' => '/site/login',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
		'application.extensions.ldaprecord.*',
	),

	// application components
	'components'=>array(
		'user'=>array(
			'class' => 'COsbdUser',
			// enable cookie-based authentication
			'allowAutoLogin' => true,
			'vmListUrl' => '/vmList/index',
		),
		// uncomment the following to enable URLs in path-format
		'urlManager'=>array(
			'urlFormat' => 'path',
			'showScriptName' => false,
			'urlSuffix' => '.html',
			'rules' => array(
				'' => 'site/login',
				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>/<id:\d+>/<cid:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>/<view:\w+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
            'errorAction'=>'site/error',
        ),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				// uncomment the following to log all messages to application.log
//				array(
//					'class'=>'CFileLogRoute',
//					'levels'=>'error, warning',
//				),
				// uncomment the following to show log messages on web pages
//				array(
//					'class'=>'CWebLogRoute',
//					//'showInFireBug' => true,
//				),

			),
		),
// 		'widgetFactory'=>array(
// 			'class'=>'CWidgetFactory',
// 			'widgets'=>array(
// 				'CJqGrid'=>array('cssFile'=>false, 'scriptFile'=>false),
// 				'CJqSingleselect'=>array('cssFile'=>false, 'scriptFile'=>false),
// 				'CJqDualselect'=>array('cssFile'=>false, 'scriptFile'=>false),
// 			)
// 		),      
		'clientScript' => array(
			'scriptMap' => array(
 				'jquery.js' => '/vm-manager/js/jquery-1.7.1.min.js',
				'jquerynew.js' => '/vm-manager/js/jquery-1.8.3.js',
				'jqueryuinew.js' => '/vm-manager/js/jquery-ui-1.9.2.custom.min.js',
				'globalize.js' => '/vm-manager/js/globalize.js',
				'globalizecultures.js' => '/vm-manager/js/cultures/globalize.cultures.js',
			)
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
	),
	// Modules
	'modules' => array(
	),
));
