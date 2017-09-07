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
 * This file is used to manage the location rules for computer.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    Walid Nouh
 * @author    David Durieux
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
 * Manage the automatic status changes
 */
class PluginFusioninventoryRuleState extends Rule {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "plugin_fusioninventory_rulestate";

   /**
    * Set these rules can be sorted
    *
    * @var boolean
    */
   public $can_sort = true;

   /**
    * Set these rules don't have specific parameters
    *
    * @var boolean
    */
   public $specific_parameters = false;


   /**
    * Get name of this type by language of the user connected
    *
    * @return string name of this type
    */
   function getTitle() {
      return __('State rules', 'fusioninventory');
   }


   /**
    * Define maximum number of actions possible in a rule
    *
    * @return integer
    */
   function maxActionsCount() {
      return 1;
   }



   /**
    * Code execution of actions of the rule
    *
    * @param array $output
    * @param array $params
    * @return array
    */
   function executeActions($output, $params) {

      PluginFusioninventoryToolbox::logIfExtradebug(
         "pluginFusioninventory-locationrules",
         "execute action\n"
      );

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  PluginFusioninventoryToolbox::logIfExtradebug(
                     "pluginFusioninventory-staterules",
                     "value ".$action->fields["value"]."\n"
                  );
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

            }
         }
      }
      return $output;
   }



   /**
    * Get the criteria available for the rule
    *
    * @return array
    */
   function getCriterias() {

      $criterias = [];

      $criterias['entities_id']['table']           = 'glpi_entities';
      $criterias['entities_id']['field']           = 'entities_id';
      $criterias['entities_id']['name']            = _n('Entity', 'Entities', 1);
      $criterias['entities_id']['linkfield']       = 'entities_id';
      $criterias['entities_id']['type']            = 'dropdown';
      $criterias['entities_id']['allow_condition'] = [Rule::PATTERN_IS,
                                                      Rule::PATTERN_IS_NOT,
                                                      Rule::PATTERN_CONTAIN,
                                                      Rule::PATTERN_NOT_CONTAIN,
                                                      Rule::PATTERN_BEGIN,
                                                      Rule::PATTERN_END,
                                                      Rule::REGEX_MATCH,
                                                      Rule::REGEX_NOT_MATCH];

      $criterias['itemtype']['field']  = 'itemtype';
      $criterias['itemtype']['name']   = __('Itemtype');

      $criterias['name']['field']      = 'name';
      $criterias['name']['name']       = __('Name');

      $criterias['serial']['field']    = 'serial';
      $criterias['serial']['name']     = __('Serial number');

      $criterias['comment']['field']   = 'comment';
      $criterias['comment']['name']    = __('Comments');

      $criterias['states_id']['field'] = 'states_id';
      $criterias['states_id']['name']  = __('Status');

      $criterias['_last_inventory']['field']  = '_last_inventory';
      $criterias['_last_inventory']['name']   = __('Last inventory', 'fusioninventory');

      $criterias['itemtype']['name']            = __('Item type');
      $criterias['itemtype']['type']            = 'dropdown_itemtype';
      $criterias['itemtype']['is_global']       = false;
      $criterias['itemtype']['allow_condition'] = [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT];

      return $criterias;
   }



   /**
    * Get the actions available for the rule
    *
    * @return array
    */
   function getActions() {

      $actions = [];

      $actions['states_id']['name']  = __('State');

      $actions['states_id']['type']          = 'dropdown';
      $actions['states_id']['table']         = 'glpi_states';
      $actions['states_id']['force_actions'] = ['assign'];

      return $actions;
   }



   /**
    * Display more conditions
    *
    * @param integer $condition
    * @param object $criteria
    * @param string $name
    * @param string $value
    * @param boolean $test
    * @return boolean
    */
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {
      if ($test) {
         return false;
      }

      switch ($condition) {
         case Rule::PATTERN_FIND:
            return false;

         case PluginFusioninventoryInventoryRuleImport::PATTERN_IS_EMPTY :
            Dropdown::showYesNo($name, 0, 0);
            return true;

         case Rule::PATTERN_EXISTS:
            echo Dropdown::showYesNo($name, 1, 0);
            return true;

         case Rule::PATTERN_DOES_NOT_EXISTS:
            echo Dropdown::showYesNo($name, 1, 0);
            return true;

      }

      return false;
   }

}
