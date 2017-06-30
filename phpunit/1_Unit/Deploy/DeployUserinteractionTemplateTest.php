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
   public function testDefineTabs() {
      $expected = [
                   'PluginFusioninventoryDeployUserinteractionTemplate$1' => 'General',
                   'PluginFusioninventoryDeployUserinteractionTemplate$2' => 'Behaviors',
                   'Log$1' => 'Historical'
                  ];
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $this->assertEquals($expected, $template->defineTabs());
   }

   /**
    * @test
    */
   public function testGetTabNameForItem() {
      $expected = [  1 => 'General', 2 => 'Behaviors'];
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $this->assertEquals($expected, $template->getTabNameForItem($template));
   }

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
      $expected =  [ self::WTS_BUTTON_OK_SYNC
            => __('OK', 'fusioninventory'),
       self::WTS_BUTTON_OK_ASYNC
            => __('OK (asynchronous)', 'fusioninventory'),
       self::WTS_BUTTON_OK_CANCEL
            => __('OK - Cancel', 'fusioninventory'),
       self::WTS_BUTTON_YES_NO
            => __('Yes - No', 'fusioninventory'),
       self::WTS_BUTTON_ABORT_RETRY_IGNORE
            => __('OK - Abort - Retry', 'fusioninventory'),
       self::WTS_BUTTON_RETRY_CANCEL
            => __('Retry - Cancel', 'fusioninventory'),
       self::WTS_BUTTON_ABORT_RETRY_IGNORE
            => __('Abort - Retry - Ignore', 'fusioninventory'),
       self::WTS_BUTTON_CANCEL_TRY_CONTINUE
            => __('Cancel - Try - Continue', 'fusioninventory'),
       self::WTS_BUTTON_YES_NO_CANCEL
            => __('Yes - No - Cancel', 'fusioninventory')
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
   public function testAddJsonFieldsToArray() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $template->fields['json'] = '{"type":"wts","duration":4,"buttons":"ok_sync","retry_after":4,"nb_max_retry":4,"action_delay_over":"continue","action_no_active_session":"continue","action_multiple_action_session":"cancel"}';
      $result = ['name' => 'foo'];
      $result = $template->addJsonFieldsToArray($result);

      $expected = ['name'          => 'foo',
                   'type'          => 'wts',
                   'duration'      => 4,
                   'buttons'       => 'ok_sync',
                   'retry_after'   => 4,
                   'nb_max_retry'  => 4,
                   'on_timeout'    => 'continue',
                   'on_nouser'     => 'continue',
                   'on_multiusers' => 'cancel'];
      $this->assertEquals($expected, $result);
   }

   /**
    * @test
    */
   public function testGetIcons() {
      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons(PluginFusioninventoryDeployUserinteractionTemplate::ALERT_WTS);
      $this->assertEquals(5, count($icons));
      $this->assertEquals($icons, [ PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_NONE     => __('None'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_WARNING  => __('Warning'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_INFO     => _n('Information', 'Informations', 1),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_ERROR    => __('Error'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION => __('Question', 'fusioninventory')
                                   ]);

      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons('foo');
      $this->assertFalse($icons);

      $icons = PluginFusioninventoryDeployUserinteractionTemplate::getIcons();
      $this->assertEquals($icons, [ PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_NONE     => __('None'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_WARNING  => __('Warning'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_INFO     => _n('Information', 'Informations', 1),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_ERROR    => __('Error'),
                                    PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION => __('Question', 'fusioninventory')
                                   ]);

   }

   /**
    * @test
    */
   public function testGetBehaviors() {
      $behaviors = PluginFusioninventoryDeployUserinteractionTemplate::getBehaviors();
      $expected  = [PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY => __('Continue job with no user interaction'),
                    PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_POSTPONE_DEPLOY => __('Retry job later', 'fusioninventory'),
                    PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY   => __('Cancel')
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
              'buttons'                        => PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_SYNC,
              'icon'                           => 'warning',
              'retry_after'                    => 4,
              'nb_max_retry'                   => 4,
              'action_delay_over'              => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'action_no_active_session'       => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
              'action_multiple_action_session' => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY
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
                 'buttons'                        => PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_SYNC,
                 'icon'                           => 'warning',
                 'retry_after'                    => 4,
                 'nb_max_retry'                   => 4,
                 'action_delay_over'              => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'action_no_active_session'       => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY,
                 'action_multiple_action_session' => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_STOP_DEPLOY
                ];
      $interaction = new PluginFusioninventoryDeployUserinteractionTemplate();
      $result      = $interaction->saveToJson($values);
      $expected    = '{"type":"wts","duration":4,"buttons":"ok_sync","retry_after":4,"nb_max_retry":4,"action_delay_over":"continue","action_no_active_session":"continue","action_multiple_action_session":"cancel"}';
      $this->assertEquals($expected, $result);

      $result      = $interaction->saveToJson([]);
      $this->assertEquals($result, "[]");

   }

   /**
    * @test
    */
   function testGestMainFormFields() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $expected = ['platform', 'timeout', 'buttons', 'icon',
                   'retry_after', 'nb_max_retry'];
      $this->assertEquals($expected, $template->getMainFormFields());
   }

   /**
    * @test
    */
   function testGetBehaviorsFields() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $expected = ['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok', 'on_no',
                   'on_yes', 'on_cancel', 'on_abort', 'on_retry', 'on_ignore',
                   'on_continue'];
      $this->assertEquals($expected, $template->getBehaviorsFields());
   }


   /**
    * @test
    */
   function testGetJsonFields() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $expected = ['platform', 'timeout', 'buttons', 'icon',
                   'retry_after', 'nb_max_retry',
                   'on_timeout', 'on_nouser', 'on_multiusers', 'on_ok', 'on_no',
                   'on_yes', 'on_cancel', 'on_abort', 'on_retry', 'on_ignore',
                   'on_continue'];
      $this->assertEquals($expected, $template->getJsonFields());
   }

   /**
    * @test
    */
   public function testInitializeJsonFields() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $this->assertEquals(17, count($template->initializeJsonFields([])));
   }

   /**
    * @test
    */
   public function testGetEvents() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $this->assertEquals(11, count($template->getEvents()));
   }

   /**
    * @test
    */
   public function testGetBehaviorsToDisplay() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_SYNC);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers', 'on_ok'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_ASYNC);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_ok', 'on_cancel'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_OK_CANCEL);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_yes', 'on_no'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_YES_NO);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_yes', 'on_no', 'on_cancel'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_YES_NO_CANCEL);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                            'on_abort', 'on_retry', 'on_ignore'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_ABORT_RETRY_IGNORE);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                           'on_retry', 'on_cancel'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_RETRY_CANCEL);

      $this->assertEquals(['on_timeout', 'on_nouser', 'on_multiusers',
                           'on_cancel', 'on_retry', 'on_continue'],
                           PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_CANCEL_TRY_CONTINUE);

   }

   public function testPrepareInputForAdd() {
      $template = new PluginFusioninventoryDeployUserinteractionTemplate();
      $input = ['name'       => 'foo',
                'button'     => PluginFusioninventoryDeployUserinteractionTemplate::WTS_BUTTON_CANCEL_TRY_CONTINUE,
                'icon'       => PluginFusioninventoryDeployUserinteractionTemplate::WTS_ICON_QUESTION,
                'on_timeout' => PluginFusioninventoryDeployUserinteractionTemplate::BEHAVIOR_CONTINUE_DEPLOY
               ];
      $expected = '{"icon":"question","on_timeou...inue"}';
      $modified = $template->prepareInputForAdd($input);
      $this->assertEquals($expected, $modified['json']);
   }
}
