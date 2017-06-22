<?php

/**
 * FusionInventory
 *
 * Copyright (C) 2010-2016 by the FusionInventory Development Team.
 *
 * http://www.fusioninventory.org/
 * https://github.com/fusioninventory/fusioninventory-for-glpi
 * http://forge.fusioninventory.org/
 *
 * ------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of FusionInventory project.
 *
 * FusionInventory is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FusionInventory is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.
 *
 * ------------------------------------------------------------------------
 *
 * This file is used to manage the actions in package for deploy system.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    Walid Nouh
 * @copyright Copyright (c) 2010-2016 FusionInventory team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      http://www.fusioninventory.org/
 * @link      https://github.com/fusioninventory/fusioninventory-for-glpi
 *
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage user interactions templates.
 */
class PluginFusioninventoryDeployUserinteractionTemplate extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_userinteractiontemplate';

   const ALERT_WTS                = 'wts'; //Alerts using Windows WTS API
   const BEHAVIOR_CONTINUE_DEPLOY = 'continue'; //Continue a software deployment
   const BEHAVIOR_CANCEL_DEPLOY   = 'cancel'; //Cancel a software deployment
   const BEHAVIOR_POSTPONE_DEPLOY = 'postpone'; //Postpone a software deployment

   const BUTTON_OK_SYNC           = 'ok_sync';
   const BUTTON_OK_NOSYNC         = 'ok_no_sync';
   const BUTTON_OK_CANCEL         = 'ok_cancel';
   const BUTTON_YES_NO            = 'yes_no';
   const BUTTON_OK_RETRY          = 'ok_retry';
   const BUTTON_OK_RETRY_CANCEL   = 'ok_retry_cancel';

   /**
    * Get list of supported interaction methods
    *
    * @since 9.2
    * @return array
    */
   static function getTypes() {
      return [self::ALERT_WTS => __("Windows system alert (WTS)", 'fusioninventory')];
   }

   /**
    * Get available buttons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
   static function getButtons($interaction_type = '') {
       $interactions =  [ self::ALERT_WTS =>
                           [ self::BUTTON_OK_SYNC         => __('OK sync', 'fusioninventory'),
                             self::BUTTON_OK_NOSYNC       => __('OK no sync', 'fusioninventory'),
                             self::BUTTON_OK_CANCEL       => __('OK - Cancel', 'fusioninventory'),
                             self::BUTTON_YES_NO          => __('Yes - No', 'fusioninventory'),
                             self::BUTTON_OK_RETRY        => __('OK - Retry', 'fusioninventory'),
                             self::BUTTON_OK_RETRY_CANCEL => __('OK - Retry - Cancel', 'fusioninventory')
                           ]
                       ];
      if (isset($interactions[$interaction_type])) {
         return $interactions[$interaction_type];
      } else {
         return false;
      }
   }

   /**
    * Get available icons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
   static function getIcons($interaction_type = '') {
       $icons =  [ self::ALERT_WTS =>
                           [ 'warning' => __('Warning'),
                             'info'    => _n('Information', 'Informations', 1),
                             'error'   => __('Error')
                           ]
                       ];
      if (isset($icons[$interaction_type])) {
         return $icons[$interaction_type];
      } else {
         return false;
      }
   }

   /**
    * Get available behaviors in case of user interactions
    *
    * @since 9.2
    * @return array
    */
   static function getBehaviors() {
      $behaviors = [self::BEHAVIOR_CONTINUE_DEPLOY => __('Continue'),
                    self::BEHAVIOR_POSTPONE_DEPLOY => __('Postpone', 'fusioninventory'),
                    self::BEHAVIOR_CANCEL_DEPLOY   => __('Cancel')
                   ];
      return $behaviors;
   }

   /**
   * Save form data as a json encoded array
   * @since 9.2
   * @param params form parameters
   * @return json encoded array
   */
   function saveToJson($params = []) {
      $fields = ['name', 'type', 'duration', 'buttons', 'icons',
                 'retry_after', 'nb_max_retry', 'action_delay_over',
                 'action_no_active_session', 'action_multiple_action_session'];
      $result = [];
      foreach ($fields as $field) {
         if (isset($params[$field])) {
            $result[$field] = $params[$field];
         }
      }
      return json_encode($result);
   }
   /**
   * Display an interaction template form
   * @since 9.2
   * @param $id id of a template to edit
   * @param options POST form options
   */
   function showForm($ID, $options = []) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td><label for='textfield_name$rand'>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark) .
           "</label></td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;

   }

   public function prepareInputForAdd($input) {
      //Save params as a json array, ready to be saved in db
      $input['json'] = $this->saveToJson($input);
      return $input;
   }
}
