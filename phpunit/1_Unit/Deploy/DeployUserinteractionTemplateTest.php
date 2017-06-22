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
   public function testGetTypeName() {
      $this->assertEquals('User interaction templates',
                           PluginFusioninventoryDeployUserinteractionTemplate::getTypeName());
      $this->assertEquals('User interaction template',
                           PluginFusioninventoryDeployUserinteractionTemplate::getTypeName(1));
      $this->assertEquals('User interaction templates',
                           PluginFusioninventoryDeployUserinteractionTemplate::getTypeName(2));
   }

   /**
    * @test
    */
   public function testGetTypes() {
      $types = PluginFusioninventoryDeployUserinteractionTemplate::getTypes();
      $this->assertEquals($types,
                          [PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS => __("Windows system alert (WTS)", 'fusioninventory')]);
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
      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons(PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS);
      $this->assertEquals(3, count($icons));
      $this->assertEquals($icons, [ 'warning' => __('Warning'),
                                    'info'    => _n('Information', 'Informations', 1),
                                    'error'   => __('Error')
                                   ]);


      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons('foo');
      $this->assertFalse($icons);

      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons();
      $this->assertEquals($icons, [ 'warning' => __('Warning'),
                                    'info'    => _n('Information', 'Informations', 1),
                                    'error'   => __('Error')
                                   ]);

   }

   /**
    * @test
    */
   public function testGetBehaviors() {
      $behaviors = PluginFusioninventoryDeployUserinteractionTemplate::getBehaviors();
      $expected  = [PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY => __('Continue'),
                    PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_POSTPONE_DEPLOY => __('Retry later', 'fusioninventory'),
                    PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CANCEL_DEPLOY   => __('Cancel')
                   ];
      $this->assertEquals($expected, $behaviors);
   }

   /**
    * @test
    */
   public function testAdd() {
      $interaction = new PluginFusioninventoryDeployUserinteractionTemplate();
      $tmp = ['name'         => 'test',
              'entities_id'  => 0,
              'is_recursive' => 0,
              'json'         => ''
             ];
      $this->assertEquals(1, $interaction->add($tmp));
      $interaction->getFromDB(1);
      $this->assertEquals('[]', $interaction->fields['json']);

      $tmp = ['name'         => 'test2',
              'entities_id'  => 0,
              'is_recursive' => 0,
              'type'                           => PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS,
              'duration'                       => 4,
              'buttons'                        => PluginFusioninventoryDeployUserinteractionTemplate::BUTTON_OK_SYNC,
              'icon'                           => 'warning',
              'retry_after'                    => 4,
              'nb_max_retry'                   => 4,
              'action_delay_over'              => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'action_no_active_session'       => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'action_multiple_action_session' => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CANCEL_DEPLOY
             ];
      $this->assertEquals(2, $interaction->add($tmp));
      $interaction->getFromDB(2);
      $expected = '{"type":"wts","duration":4,"buttons":"ok_sync","retry_after":4,"nb_max_retry":4,"action_delay_over":"continue","action_no_active_session":"continue","action_multiple_action_session":"cancel"}';
      $this->assertEquals($expected, $interaction->fields['json']);

   }


   /**
    * @test
    */
   public function testUpdate() {
      $interaction = new PluginFusioninventoryDeployUserinteractionTemplate();
      $tmp = ['id'   => 1,
              'name' => 'test_update',
              'json' => ''
             ];
      $this->assertTrue($interaction->update($tmp));

      $interaction->getFromDB(1);
      $this->assertEquals('test_update', $interaction->fields['name']);

   }

   /**
    * @test
    */
   public function testSaveToJson() {
      $values = ['name'                           => 'interaction',
                 'type'                           => PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS,
                 'duration'                       => 4,
                 'buttons'                        => PluginFusioninventoryDeployUserinteractionTemplate::BUTTON_OK_SYNC,
                 'icon'                           => 'warning',
                 'retry_after'                    => 4,
                 'nb_max_retry'                   => 4,
                 'action_delay_over'              => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'action_no_active_session'       => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'action_multiple_action_session' => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CANCEL_DEPLOY
                ];
      $interaction = new PluginFusioninventoryDeployUserinteractionTemplate();
      $result      = $interaction->saveToJson($values);
      $expected    = '{"type":"wts","duration":4,"buttons":"ok_sync","retry_after":4,"nb_max_retry":4,"action_delay_over":"continue","action_no_active_session":"continue","action_multiple_action_session":"cancel"}';
      $this->assertEquals($expected, $result);

      $result      = $interaction->saveToJson([]);
      $this->assertEquals($result, "[]");

   }
}
