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
 *  Michael Eichenberger <michael.eichenberger@stepping-stone.ch>
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

$this->pageTitle=Yii::app()->name . ' - About';
$this->breadcrumbs=array(
	'About',
);

$this->title = 'About stoney cloud';
?>

<h2>Version</h2>
<p><?= Yii::app()->getSession()->get('version', ''); ?></p>
<h2>stoney cloud ID</h2>
<p><?= Yii::app()->getSession()->get('cloudid', ''); ?></p>
<h2>Projects incorporated within the stoney cloud</h2>
<table bgcolor="white" border="1" cellpadding="10" cellspacing="0">
<tr><td><strong>Open Source Solution</strong></td><td><strong>License</strong></td></tr>
<tr><td><a target="blank" href="http://www.gentoo.org">Gentoo</a> is a free operating system based on Linux, that can be automatically optimized and customized for just about any application or need.</td><td><a target="blank" href="http://www.gnu.org/licenses">GPL</a></td></tr>
<tr><td><a target="blank" href="http://www.sysresccd.org">SystemRescueCD</a> is an operating system for the x86 computer platform, which is used to kick-start the stoney cloud installation.</td><td><a target="blank" href="http://www.opensource.org/licenses/gpl-license.html">GPL</a></td></tr>
<tr><td><a target="blank" href="http://www.linux-kvm.org">KVM</a> (Kernel-based Virtual Machine) is a virtualization infrastructure for the Linux kernel which turns it into a hypervisor.</td><td><a target="blank" href="http://www.gnu.org/licenses">GPL</a></td></tr>
<tr><td><a target="blank" href="http://wiki.qemu.org/Index.html">QEMU</a> (short for "Quick EMUlator") is a free and open-source hosted hypervisor that performs hardware virtualization.</td><td><a target="blank" href="http://www.gnu.org/licenses">GPL</a></td></tr>
<tr><td>The <a target="blank" href="http://www.spice-space.org">Spice</a> project aims to provide a complete open source solution for interaction with virtualized desktop devices.</td><td><a target="blank" href="http://www.gnu.org/licenses">GPL</a></td></tr>
<tr><td><a target="blank" href="http://www.libvirt.org/">libvirt</a> is an open source API, daemon and management tool for managing platform virtualization.</td><td><a target="blank" href="http://www.gnu.org/licenses/lgpl-2.1.html">LGPL</a></td></tr>
<tr><td><a target="blank" href="http://libvirt.org/php/">libvirt-php</a> is a php module that provides PHP bindings for libvirt virtualization toolkit and therefore you can access libvirt directly from your PHP scripts with no need to have virt-manager or libvirt-based CLI/GUI tools installed.</td><td><a target="blank" href="http://www.php.net/license/">PHP</a><td></tr>
<tr><td><a target="blank" href="http://www.php.net">PHP</a> is a server-side scripting language designed for web development but also used as a general-purpose programming language.</td><td><a target="blank" href="http://www.php.net/license/">PHP</a></td></tr>
<tr><td><a target="blank" href="http://www.yiiframework.com">Yii</a> (<?=Yii::getVersion();?>) is an open source, object-oriented, component-based MVC PHP web application framework.</td><td> <a target="blank" href="http://www.yiiframework.com/license/">BSD</a></td></tr>
</table>

<h2>People involved into the stoney cloud project</h2>
<table bgcolor="white" border="1" cellpadding="10" cellspacing="0">
<tr><td><strong>Person</strong></td><td><strong>Role</strong></td></tr>
<tr><td>Christian Affolter (<a target="blank" href="https://github.com/paraenggu">paraenggu</a>)</td><td>stoney cloud installer, firewall libvirt-hooks, operating system, syslog-ng, ...</td></tr>
<tr><td>Christian Wittkowski (<a target="blank" href="https://github.com/flinx27">flinx27</a>)</td><td>vm-manager</td></tr>
<tr><td>Christoph Scheurer (<a target="blank" href="https://github.com/cyberfarm">cyberfarm</a>)</td><td>ucarp, testing</td></tr>
<tr><td>David Vollmer (<a target="blank" href="https://github.com/dukje">dukje</a>)</td><td>firewall libvirt-hooks, testing</td></tr>
<tr><td>Lucas Bickel (<a target="blank" href="https://github.com/hairmare">hairmare</a>)</td><td>automation, puppet, syslog-ng, ...</td></tr>
<tr><td>Michael Eichenberger (<a target="blank" href="https://github.com/meichenberger">meichenberger</a>)</td><td>architecture, project management, ldap, ...</td></tr>
<tr><td>Pascal Jufer (<a target="blank" href="https://github.com/paescuj">paescuj</a>)</td><td>documentation, testing</td></tr>
<tr><td>Pat Kläy (<a target="blank" href="https://github.com/patklaey">patklaey</a>)</td><td>backup, notification, documentation</td></tr>
<tr><td>Tatiana Durisova Eichenberger(<a target="blank" href="https://github.com/teichenberger">teichenberger</a>)</td><td>testing</td></tr>
<tr><td>Tiziano Müller (<a target="blank" href="https://github.com/dev-zero">dev-zero</a>)</td><td>stoney cloud build, libvirt, operating system, documentation, ...</td></tr>
</table>
