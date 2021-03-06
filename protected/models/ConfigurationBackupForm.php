<?php
/*
 * Copyright (C) 2013 FOSS-Group
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

class ConfigurationBackupForm extends CFormModel {
	public $sstBackupNumberOfIterations;
	public $sstVirtualizationVirtualMachineForceStart;
	
	public $sstCronMinute;
	public $sstCronHour;
	public $sstCronDayOfWeek;
	public $sstCronActive;
	public $cronTime;
	public $everyDay;
	
	public function rules()
	{
		return array(
			array('sstVirtualizationVirtualMachineForceStart, sstCronMinute, sstCronHour, sstCronDayOfWeek, sstCronActive, cronTime, everyDay', 'safe'),
			array('sstBackupNumberOfIterations', 'numerical', 'integerOnly' => true, 'min' => 0, 'allowEmpty' => false),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'sstBackupNumberOfIterations' => Yii::t('configuration', 'no. of iterations'),
			'sstVirtualizationVirtualMachineForceStart' => Yii::t('configuration', 'vm force start'),
			'sstCronActiveFalse' => Yii::t('configuration', 'no schedule'),
			'sstCronActiveTrue' => Yii::t('configuration', 'at'),
			'everyDayTrue' => Yii::t('configuration', 'every day'),
		);
	}
}