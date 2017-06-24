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

class DeployUserinteractionTest extends RestoreDatabase_TestCase {

   /**
    * @test
    */
   public function testGetTypeName() {
      $this->assertEquals('User interaction',
                           PluginFusioninventoryDeployUserinteraction::getTypeName());
      $this->assertEquals('User interaction',
                           PluginFusioninventoryDeployUserinteraction::getTypeName(1));
      $this->assertEquals('User interactions',
                           PluginFusioninventoryDeployUserinteraction::getTypeName(2));
   }

   /**
    * @test
    */
   public function testGetEvents() {
      $events = PluginFusioninventoryDeployUserinteraction::getEvents();
      $this->assertEquals(5, count($events));

      $this->assertEquals(5, count($events));
      $this->assertEquals("Alert after audits", $events[self::EVENT_AFTER_AUDITS]);
      $this->assertEquals("Alert after downlod", $events[self::EVENT_AFTER_DOWNLOAD]);
   }

   /**
    * @test
    */
   public function testGetEvents() {
      $audit       = PluginFusioninventoryDeployUserinteractionTemplate::getEventLabel(self::EVENT_AFTER_AUDITS);
      $this->assertEquals("Alert after audits", $audit);

      $download     = PluginFusioninventoryDeployUserinteractionTemplate::getEventLabel(self::EVENT_AFTER_DOWNLOAD);
      $this->assertEquals("Alert after download", $download);

      $action       = PluginFusioninventoryDeployUserinteractionTemplate::getEventLabel(self::EVENT_AFTER_ACTIONS);
      $this->assertEquals("Alert after actions", $action);

      $fail_download = PluginFusioninventoryDeployUserinteractionTemplate::getEventLabel(self::EVENT_DOWNLOAD_FAILED);
      $this->assertEquals("Alert on failed download", $fail_download);

      $fail_actions = PluginFusioninventoryDeployUserinteractionTemplate::getEventLabel(self::EVENT_ACTION_FAILED);
      $this->assertEquals("Alert on failed actions", $fail_actions);

   }

}
