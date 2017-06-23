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
 * Manage user interactions.
 */
class PluginFusioninventoryDeployUserinteraction extends CommonDBTM {

   //Events

   //Audits are all been executed successfully, just before download
   const EVENT_AFTER_AUDITS    = 1;
   //File download has been done, just before actions execution
   const EVENT_AFTER_DOWNLOAD  = 2;
   //Actions have been executed, deployement is finished
   const EVENT_AFTER_ACTIONS   = 3;
   //At least one downlod has failed
   const EVENT_DOWNLOAD_FAILED = 4;
   //At least one action has failed
   const EVENT_ACTION_FAILED   = 5;

   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
         return _n('User interaction',
                   'User interactions', $nb, 'fusioninventory');
   }

   /**
    * Get events with name => description
    * @since 9.2
    * @return array
    */
   static function getEvents() {
      return [self::EVENT_AFTER_AUDITS    => __("Alert after audits", 'fusioninventory'),
              self::EVENT_AFTER_DOWNLOAD  => __("Alert after download", 'fusioninventory'),
              self::EVENT_AFTER_ACTIONS   => __("Alert after actions", 'fusioninventory'),
              self::EVENT_DOWNLOAD_FAILED => __("Alert on failed download", 'fusioninventory'),
              self::EVENT_ACTION_FAILED   => __("Alert on failed actions", 'fusioninventory')
             ];
   }

   /**
    * Get an event label by it's identifier
    * @since 9.2
    * @return array
    */
   static function getEventLabel($event) {
      $events = self::getEvents();
      if (isset($events[$event])) {
         return $events[$event];
      } else {
         return false;
      }
   }

   /**
    * Display the dropdown to select type of element
    *
    * @global array $CFG_GLPI
    * @param array $config order item configuration
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode mode in use (create, edit...)
    */
   static function displayDropdownType($config, $rand, $mode) {
      global $CFG_GLPI;

      /*
       * Build dropdown options
       */
      $dropdown_options['rand'] = $rand;
      if ($mode === 'edit') {
         $dropdown_options['value'] = $config['type'];
         $dropdown_options['readonly'] = true;
      }

      /*
       * Build actions types list
       */
      if ($mode === 'create') {
         $events = self::getEvents();
      } else {
         $events = [];
         foreach (self::getEvents() as $label => $data) {
            $events[] = $data;
         }
      }
      array_unshift($events, "---");

      /*
       * Display dropdown html
       */
      echo "<table class='package_item'>";
      echo "<tr>";
      echo "<th>".__("Type", 'fusioninventory')."</th>";
      echo "<td>";
      Dropdown::showFromArray("deploy_userinteractiontype", $events, $dropdown_options);
      echo "</td>";
      echo "</tr></table>";

      //ajax update of check value span
      if ($mode === 'create') {
         $params = [
                     'value'  => '__VALUE__',
                     'rand'   => $rand,
                     'myname' => 'method',
                     'type'   => "userinteraction",
                     'mode'   => $mode
         ];

         Ajax::updateItemOnEvent(
            "dropdown_deploy_userinteractiontype$rand",
            "show_userinteraction_value$rand",
            $CFG_GLPI["root_doc"].
            "/plugins/fusioninventory".
            "/ajax/deploy_displaytypevalue.php",
            $params,
            ["change", "load"]
         );
      }
   }

   /**
    * Display different fields relative the check selected
    *
    * @param array $config
    * @param array $request_data
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode mode in use (create, edit...)
    * @return boolean
    */
   static function displayAjaxValues($config, $request_data, $rand, $mode) {
      global $CFG_GLPI;

      $pfDeployPackage = new PluginFusioninventoryDeployPackage();

      if (isset($request_data['packages_id'])) {
         $pfDeployPackage->getFromDB($request_data['orders_id']);
      } else {
         $pfDeployPackage->getEmpty();
      }

      /*
       * Get type from request params
       */
      $type = NULL;
      if ($mode === 'create') {
         $type = $request_data['value'];
         $config_data = NULL;
      } else {
         $type = $config['type'];
         $config_data = $config['data'];
      }

      $values = self::getValues($type, $config_data, $mode);
      Toolbox::logDebug($values);
      if ($values === FALSE) {
         return FALSE;
      }

      echo "<table class='package_item'>";
      echo "<tr>";
      echo "<th>".PluginFusioninventoryDeployUserinteractionTemplate::getTypeName(1)."</th>";
      echo "<td><input type='text' name='name' id='userinteraction_name{$rand}' value=\"{$values['name_value']}\" /></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>{$values['title_label']}</th>";
      echo "<td><input type='text' name='path' id='userinteraction_title{$rand}' value=\"{$values['title_value']}\" />";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>{$values['description_label']}</th>";
      echo "<td><textarea name='path' id='userinteraction_description{$rand}' rows='5'/>{$values['description_value']}</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>{$values['template_label']}</th>";
      echo "<td>";
      Dropdown::show('PluginFusioninventoryDeployUserinteractionTemplate', ['value' => $values['template_value']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>";
      echo "</td>";
      echo "<td>";
      if ($pfDeployPackage->can($pfDeployPackage->getID(), UPDATE)) {
         if ($mode === 'edit') {
            echo "<input type='submit' name='save_item' value=\"".
               _sx('button', 'Save')."\" class='submit' >";
         } else {
            echo "<input type='submit' name='add_item' value=\"".
               _sx('button', 'Add')."\" class='submit' >";
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }

   /**
    * Get fields for the check type requested
    *
    * @param string $type the type of check
    * @param array $data fields yet defined in edit mode
    * @param string $mode mode in use (create, edit...)
    *
    * @return string|false
    */
   static function getValues($type, $data, $mode) {
      $values = [
         'name_value'          => "",
         'name_label'          => __('Interaction label', 'fusioninventory'),
         'name_type'           => "input",
         'title_label'         => __('Title').self::getMandatoryMark(),
         'title_value'         => "",
         'title_type'          => "input",
         'description_label'   => __('Message'),
         'description_type'    => "text",
         'description_value'   => "",
         'template_label'      => PluginFusioninventoryDeployUserinteractionTemplate::getTypeName(1).self::getMandatoryMark(),
         'template_value'      => "",
         'template_type'       => "dropdown",
      ];

      if ($mode === 'edit') {
         $values['name_value']        = isset($data['name'])?$data['name']:"";
         $values['title_value']       = isset($data['title'])?$data['title']:"";
         $values['description_value'] = isset($data['description_value'])?$data['description_value']:"";
         $values['template_value']    = isset($data['template_value'])?$data['template_value']:"";
      }

      return $values;
   }

   static function getMandatoryMark() {
      return "&nbsp;<span class='red'>*</span>";
   }

   /**
    * Display form
    *
    * @param object $package PluginFusioninventoryDeployPackage instance
    * @param array $request_data
    * @param string $rand unique element id used to identify/update an element
    * @param string $mode possible values: init|edit|create
    */
   static function displayForm(PluginFusioninventoryDeployPackage $package, $request_data, $rand, $mode) {
      /*
       * Get element config in 'edit' mode
       */
      $config = NULL;
      if ($mode === 'edit' && isset($request_data['index'])) {
         /*
          * Add an hidden input about element's index to be updated
          */
         echo "<input type='hidden' name='index' value='".$request_data['index']."' />";

         $c = $package->getSubElement('userinteractions', $request_data['index']);

         if (is_array($c) && count($c)) {

            $config = array(
               'type' => $c['type'],
               'data' => $c
            );
         }
      }

      /*
       * Display start of div form
       */
      if (in_array($mode, array('init'), TRUE)) {
         echo "<div id='userinteractions_block$rand' style='display:none'>";
      }

      /*
       * Display element's dropdownType in 'create' or 'edit' mode
       */
      if (in_array($mode, array('create', 'edit'), TRUE)) {
         self::displayDropdownType($config, $rand, $mode);
      }

      /*
       * Display element's values in 'edit' mode only.
       * In 'create' mode, those values are refreshed with dropdownType 'change'
       * javascript event.
       */
      if (in_array($mode, array('create', 'edit'), TRUE)) {
         echo "<span id='show_userinteraction_value{$rand}'>";
         if ($mode === 'edit') {
            self::displayAjaxValues($config, $request_data, $rand, $mode);
         }
         echo "</span>";
      }

      /*
       * Close form div
       */
      if (in_array($mode, array('init'), TRUE)) {
         echo "</div>";
      }
   }


      /**
       * Display list of user interactions
       *
       * @global array $CFG_GLPI
       * @param object $package PluginFusioninventoryDeployPackage instance
       * @param array $datas array converted of 'json' field in DB where stored checks
       * @param string $rand unique element id used to identify/update an element
       */
      static function displayList(PluginFusioninventoryDeployPackage $package, $datas, $rand) {
         global $CFG_GLPI;

         $interaction_types = self::getEvents();
         $package_id   = $package->getID();
         $canedit      = $package->canUpdateContent();
         echo "<table class='tab_cadrehov package_item_list' id='table_userinteraction_$rand'>";
         $i = 0;
         foreach ($datas['jobs']['userinteractions'] as $interaction) {

            echo Search::showNewLine(Search::HTML_OUTPUT, ($i%2));
            if ($canedit) {
               echo "<td class='control'>";
               Html::showCheckbox(array('name' => 'userinteraction_entries['.$i.']'));
               echo "</td>";
            }

            //Get the audit full description (with type and return value)
            //to be displayed in the UI
            $text = self::getInteractionDescription($interaction);
            if (isset($interaction['name']) && !empty($interaction['name'])) {
               $interaction_label = $interaction['name'].' ('.$text.')';
            } else {
               $interaction_label = $text;
            }
            echo "<td>";
            if ($canedit) {
               echo "<a class='edit'
                        onclick=\"edit_subtype('userinteraction', $package_id, $rand ,this)\">";
            }
            echo $interaction_label;
            if ($canedit) {
               echo "</a>";
            }

            echo "</td>";
            if ($canedit) {
               echo "<td class='rowhandler control' title='".__('drag', 'fusioninventory').
                  "'><div class='drag row'></div></td>";
            }
            echo "</tr>";
            $i++;
         }
         if ($canedit) {
            echo "<tr><th>";
            Html::checkAllAsCheckbox("userinteractionsList$rand", mt_rand());
            echo "</th><th colspan='3' class='mark'></th></tr>";
         }
         echo "</table>";
         if ($canedit) {
            echo "&nbsp;&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left.png' alt='' />";
            echo "<input type='submit' name='delete' value=\"".
               __('Delete', 'fusioninventory')."\" class='submit' />";
         }
      }

      static function getInteractionDescription($interaction) {
         $text = '';
         if (isset($interaction['interaction_label'])) {
            $text = $interaction['interaction_label'];
         } else {
            $text .= self::getEventLabel($interaction['type']);
            $text.= ' ' .Dropdown::getDropdownName('glpi_plugin_fusioninventory_deployuserinteractiontemplates',
                                              $interaction['template']);
         }
         return $text;
      }
      /**
       * Add a new item in checks of the package
       *
       * @param array $params list of fields with value of the check
       */
      static function add_item($params) {
         Toolbox::logDebug($params);
         if (!isset($params['description_value'])) {
            $params['description_value'] = "";
         }
         if (!isset($params['template_value'])) {
            $params['template_value'] = 0;
         }

         if ($params['template_value']) {
            $template = new PluginFusioninventoryDeployUserinteractionTemplate();
            $template->getFromDB($params['template_value']);

         }

         //prepare new check entry to insert in json
         $new_entry = [
            'title'       => $params['title_value'],
            'description' => $params['description'],
            'type'        => $params['deploy_userinteractiontype'],
            'template'    => $params['template']
         ];

         //get current order json
         $datas = json_decode(
                 PluginFusioninventoryDeployPackage::getJson($params['id']),
                 TRUE
         );

         //add new entry
         $datas['jobs']['userinteractions'][] = $new_entry;

         //update order
         PluginFusioninventoryDeployPackage::updateOrderJson(
            $params['id'], $datas
         );
      }

      /**
       * Save the item in checks
       *
       * @param array $params list of fields with value of the check
       */
      static function save_item($params) {

         if (!isset($params['value'])) {
            $params['value'] = "";
         }
         if (!isset($params['name'])) {
            $params['name'] = "";
         }

         //prepare new check entry to insert in json
         $new_entry = [
            'title'       => $params['title_value'],
            'description' => $params['description'],
            'type'        => $params['deploy_userinteractiontype'],
            'template'    => $params['template']
         ];

         //get current order json
         $datas = json_decode(PluginFusioninventoryDeployPackage::getJson($params['id']), TRUE);

         //unset index
         unset($datas['jobs']['userinteractions'][$params['index']]);

         //add new datas at index position
         //(array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
         array_splice($datas['jobs']['userinteractions'], $params['index'], 0, array($entry));

         //update order
         PluginFusioninventoryDeployPackage::updateOrderJson($params['id'], $datas);
      }



      /**
       * Remove an item
       *
       * @param array $params
       * @return boolean
       */
      static function remove_item($params) {
         if (!isset($params['userinteraction_entries'])) {
            return FALSE;
         }

         //get current order json
         $datas = json_decode(PluginFusioninventoryDeployPackage::getJson($params['packages_id']), TRUE);

         //remove selected checks
         foreach ($params['userinteraction_entries'] as $index => $checked) {
            if ($checked >= "1" || $checked == "on") {
               unset($datas['jobs']['userinteractions'][$index]);
            }
         }

         //Ensure checks is an array and not a dictionnary
         //Note: This happens when removing an array element from the begining
         $datas['jobs']['userinteractions'] = array_values($datas['jobs']['userinteractions']);

         //update order
         PluginFusioninventoryDeployPackage::updateOrderJson($params['packages_id'], $datas);
         return TRUE;
      }



      /**
       * Move an item
       *
       * @param array $params
       */
      static function move_item($params) {
         //get current order json
         $datas = json_decode(PluginFusioninventoryDeployPackage::getJson($params['id']), TRUE);

         //get data on old index
         $moved_check = $datas['jobs']['userinteractions'][$params['old_index']];

         //remove this old index in json
         unset($datas['jobs']['userinteractions'][$params['old_index']]);

         //insert it in new index (array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
         array_splice($datas['jobs']['userinteractions'], $params['new_index'], 0, array($moved_check));

         //update order
         PluginFusioninventoryDeployPackage::updateOrderJson($params['id'], $datas);
      }

}
