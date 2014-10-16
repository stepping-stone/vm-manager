<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 * and
 * Copyright (C) 2013 - 2014 stepping stone GmbH
 *                           Switzerland
 *                           http://www.stepping-stone.ch
 *                           support@stepping-stone.ch
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
	'VmProfile'=>array('index'),
	'Create',
);
$this->title = Yii::t('vmprofile', 'Create VMProfile');
//$this->helpurl = Yii::t('help', 'createVmProfile');

echo $this->renderPartial('_form', array('model'=>$model,'isofiles'=>$isofiles,'profiles' =>$profiles,'defaults'=>$defaults,'operatingsystems' =>$operatingsystems,'submittext'=>Yii::t('vmprofile','Create')));