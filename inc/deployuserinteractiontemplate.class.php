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
class PluginFusioninventoryDeployUserinteractionTemplate extends CommonDropdown {

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

   const ICON_NONE                = 'none';
   const ICON_WARNING             = 'warn';
   const ICON_QUESTION            = 'question';
   const ICON_INFO                = 'info';
   const ICON_ERROR               = 'error';

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
         return _n('User interaction template',
                   'User interaction templates', $nb, 'fusioninventory');
   }

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
    * Display a dropdown with the list of alert types
    *
    * @since 9.2
    * @param type the type of alert (if one already selected)
    * @return rand
    */
   function dropdownTypes($type = self::ALERT_WTS) {
      $types = self::getTypes();
      return Dropdown::showFromArray('type', $types, ['value' => $type]);
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
    * Display a dropdown with the list of buttons available
    *
    * @since 9.2
    * @param type the type of button (if one already selected)
    * @return rand
    */
   public function dropdownButtons($button = self::BUTTON_OK_SYNC) {
      $types = self::getButtons();
      return Dropdown::showFromArray('buttons', $buttons, ['value' => $button]);
   }

   /**
    * Get available icons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
   static function getIcons($interaction_type = self::ALERT_WTS) {
       $icons = [ self::ALERT_WTS =>
                           [ self::ICON_NONE     => __('None'),
                             self::ICON_WARNING  => __('Warning'),
                             self::ICON_INFO     => _n('Information', 'Informations', 1),
                             self::ICON_ERROR    => __('Error'),
                             self::ICON_QUESTION => __('Question', 'fusioninventory')
                           ]
                       ];
      if (isset($icons[$interaction_type])) {
         return $icons[$interaction_type];
      } else {
         return false;
      }
   }

   /**
    * Display a dropdown with the list of buttons available
    *
    * @since 9.2
    * @param type the type of button (if one already selected)
    * @return rand
    */
   function dropdownIcons($icon = self::ICON_NONE) {
      $icons = self::getIcons();
      return Dropdown::showFromArray('icons', $icons, ['value' => $icon]);
   }

   /**
    * Get available behaviors in case of user interactions
    *
    * @since 9.2
    * @return array
    */
   static function getBehaviors() {
      $behaviors = [self::BEHAVIOR_CONTINUE_DEPLOY => __('Continue'),
                    self::BEHAVIOR_POSTPONE_DEPLOY => __('Retry later', 'fusioninventory'),
                    self::BEHAVIOR_CANCEL_DEPLOY   => __('Cancel')
                   ];
      return $behaviors;
   }

   /**
    * Display a dropdown with the list of available behaviors
    *
    * @since 9.2
    * @param type the type of bahaviors (if one already selected)
    * @return rand
    */
   function dropdownBehaviors($name, $behavior = self::BEHAVIOR_CONTINUE_DEPLOY) {
      $behaviors = self::getBehaviors();
      return Dropdown::showFromArray($name, $behaviors, ['value' => $behavior]);
   }


   /**
   * Get the fields to be encoded in json
   * @since 9.2
   * @return an array of field names
   */
   function getJsonFields() {
      return  ['type', 'duration', 'buttons', 'icons',
               'retry_after', 'nb_max_retry', 'action_delay_over',
               'action_no_active_session', 'action_multiple_action_session'];

   }

   /**
   * Initialize json fields
   * @since 9.2
   *
   * @return an array of field names
   */
   function initializeJsonFields($json_fields) {
      foreach ($this->getJsonFields() as $field) {
         if (!isset($json_fields[$field])) {
            $json_fields[$field] = '';
         }
      }
      return $json_fields;
   }

   /**
   * Save form data as a json encoded array
   * @since 9.2
   * @param params form parameters
   * @return json encoded array
   */
   function saveToJson($params = []) {
      $result = [];
      foreach ($this->getJsonFields() as $field) {
         if (isset($params[$field])) {
            $result[$field] = $params[$field];
         }
      }
      return json_encode($result);
   }

   /**
   * Add the json template fields to package
   *
   */
   function addJsonFieldsToArray($params = []) {
      $fields = json_decode($this->fields['json'], true);
      foreach ($this->getJsonFields() as $field) {
         $params[$field] = $fields[$field];
      }
      return $params;
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

      $json_data = json_decode($this->fields['json'], true);
      $json_data = $this->initializeJsonFields($json_data);

      echo "<tr class='tab_bg_1'>";

      $rand    = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td><label for='textfield_name$rand'>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark) .
           "</label></td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', [ 'value'     => $objectName,
                                                     'rand'      => $rand
                                                   ]);
      echo "</td>";

      echo "<td>".__('Interaction format', 'fusioninventory')."</td>";
      echo "<td>";
      $this->dropdownTypes($json_data['type']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alert display duration', 'fusioninventory')."</td>";
      echo "<td>";
      Dropdown::showInteger('duration', $json_data['duration'], 1, 120, 1);
      echo "&nbsp;"._n('Minute', 'Minutes', 2);
      echo "</td>";

      echo "<td>".__('Alert icon', 'fusioninventory')."</td>";
      echo "<td>";
      $this->dropdownIcons($json_data['icons']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Retry job after', 'fusioninventory')."</td>";
      echo "<td>";
      Dropdown::showInteger('retry_after', $json_data['retry_after'], 1, 24, 1);
      echo "&nbsp;"._n('Hour', 'Hours', 2);
      echo "</td>";

      echo "<td>".__('Maximum number of retry allowed', 'fusioninventory')."</td>";
      echo "<td>";
      Dropdown::showInteger('nb_max_retry', $json_data['nb_max_retry'], 1, 20, 1);
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>".__('Advanced information')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('In case of alert duration exceeded', 'fusioninventory')."</td>";
      echo "<td>";
      $this->dropdownBehaviors('action_delay_over', $json_data['action_delay_over']);
      echo "</td>";

      echo "<td>".__('In case of no active session', 'fusioninventory')."</td>";
      echo "<td>";
      $this->dropdownBehaviors('action_no_active_session', $json_data['action_no_active_session']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('In case of several active sessions', 'fusioninventory')."</td>";
      echo "<td>";
      $this->dropdownBehaviors('action_multiple_action_session', $json_data['action_multiple_action_session']);
      echo "</td>";

      echo "<td colspan='2'></td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;

   }

   public function prepareInputForAdd($input) {
      //Save params as a json array, ready to be saved in db
      $input['json'] = $this->saveToJson($input);
      return $input;
   }

   public function prepareInputForUpdate($input) {
      return $this->prepareInputForAdd($input);
   }

}
