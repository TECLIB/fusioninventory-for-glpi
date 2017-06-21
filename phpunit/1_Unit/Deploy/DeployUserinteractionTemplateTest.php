<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2016 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Walid Nouh
   @co-author
   @copyright Copyright (c) 2010-2016 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2013

   ------------------------------------------------------------------------
 */

class DeployUserinteractionTemplateTest extends RestoreDatabase_TestCase {


   /**
    * @test
    */
   public function testGetTypes() {
      $types = PluginFusioninventoryDeployUserinteractionTemplate::getTypes();
      $this->assertEquals($types,
                          [self::ALERT_WTS => __("Windows system alert (WTS)", 'fusioninventory')]);
   }

   /**
    * @test
    */
   public function testGetButtons() {
      $buttons  = PluginFusioninventoryDeployUserinteractionTemplate::getButtons(PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS);
      $expected =  ['ok_sync'         => __('OK sync', 'fusioninventory'),
                    'ok_no_sync'      => __('OK no sync', 'fusioninventory'),
                    'ok_cancel'       => __('OK - Cancel', 'fusioninventory'),
                    'yes_no'          => __('Yes - No', 'fusioninventory'),
                    'ok_retry'        => __('OK - Retry', 'fusioninventory'),
                    'ok_retry_cancel' => __('OK - Retry - Cancel', 'fusioninventory')
                    ];
      $this->assertEquals($buttons, $expected);

      $buttons = PluginFusioninventoryDeployUserinteractionTemplate::getButtons('foo');
      $this->assertFalse($buttons);

      $buttons = PluginFusioninventoryDeployUserinteractionTemplate::getButtons();
      $this->assertFalse($buttons);

   }

   /**
    * @test
    */
   public function testGetIcons() {
      $icons = PluginFusioninventoryDeployCheck::getIcons(PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS);
      $this->assertEquals(3, count($icons));
      $this->assertEquals($icons, [ 'warning' => __('Warning'),
                                    'info'    => _n('Information', 'Informations', 1),
                                    'error'   => __('Error')
                                   ]);


      $icons = PluginFusioninventoryDeployCheck::getIcons('foo');
      $this->assertFalse($icons);

      $icons = PluginFusioninventoryDeployCheck::getIcons();
      $this->assertFalse($icons);

   }

}
