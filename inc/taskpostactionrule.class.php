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
 * This file is used to manage the display of task jobs.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    Teclib'
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

class PluginFusioninventoryTaskpostactionRule extends Rule {

   static $rightname           = "plugin_fusioninventory_taskpostactionrule";
   public $can_sort            = true;
   public $specific_parameters = false;


   function getTitle() {
      return _n('Post deployment action','Post deployment actions',
                Session::getPluralNumber(), 'fusioninventory');
   }

   static function getTypeName($nb=0) {
      return _n('Post deployment action','Post deployment actions',
                $nb, 'fusioninventory');
   }

   function getCriterias() {

      $criteria = [];

      $criteria['plugin_fusioninventory_tasks_id']['field']     = 'name';
      $criteria['plugin_fusioninventory_tasks_id']['name']      = __('Task', 'fusioninventory');
      $criteria['plugin_fusioninventory_tasks_id']['table']     = 'glpi_plugin_fusioninventory_tasks';
      $criteria['plugin_fusioninventory_tasks_id']['type']      = 'dropdown';
      $criteria['plugin_fusioninventory_tasks_id']['linkfield'] = 'plugin_fusioninventory_tasks_id';

      $criteria['plugin_fusioninventory_agents_id']['field']     = 'name';
      $criteria['plugin_fusioninventory_agents_id']['name']      = __('Agent', 'fusioninventory');
      $criteria['plugin_fusioninventory_agents_id']['table']     = 'glpi_plugin_fusioninventory_agents';
      $criteria['plugin_fusioninventory_agents_id']['type']      = 'dropdown';
      $criteria['plugin_fusioninventory_agents_id']['linkfield'] = 'plugin_fusioninventory_agents_id';

//      $criteria['method']['field']                = 'method';
//      $criteria['method']['name']                 = __('Module method', 'fusioninventory');

      $criteria['target_type']['field']           = 'target_type';
      $criteria['target_type']['name']            = __('Target Type', 'fusioninventory');
      $criteria['target_type']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      $criteria['target']['field']                = 'target';
      $criteria['target']['name']                 = __('Target Item', 'fusioninventory');
      $criteria['target']['allow_condition']      = array(Rule::PATTERN_CONTAIN, Rule::PATTERN_NOT_CONTAIN,
                                                     Rule::PATTERN_BEGIN,   Rule::PATTERN_END,
                                                     Rule::REGEX_MATCH,     Rule::REGEX_NOT_MATCH);

      $criteria['actor_type']['field']            = 'actor_type';
      $criteria['actor_type']['name']             = __('Actor Type', 'fusioninventory');
      $criteria['actor_type']['allow_condition']  = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      $criteria['actor']['field']                 = 'actor';
      $criteria['actor']['name']                  = __('Actor Item', 'fusioninventory');
      $criteria['actor']['allow_condition']       = array(Rule::PATTERN_CONTAIN, Rule::PATTERN_NOT_CONTAIN,
                                                     Rule::PATTERN_BEGIN,   Rule::PATTERN_END,
                                                     Rule::REGEX_MATCH,     Rule::REGEX_NOT_MATCH);

      $criteria['state']['field']                 = 'state';
      $criteria['state']['name']                  = __('Tasks running result', 'fusioninventory');
      $criteria['state']['allow_condition']       = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);


      return $criteria;
   }


   /**
    * @since version 0.84
    *
    * @see Rule::displayCriteriaSelectPattern()
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {
      global $PLUGIN_HOOKS;

      if (!isset($criteria['field'])
          || !in_array($condition, array(self::PATTERN_IS,        self::PATTERN_IS_NOT,
                                         self::PATTERN_NOT_UNDER, self::PATTERN_UNDER))) {
         return false;
      }

      switch ($criteria['field']) {
         case 'method':
            $modules_methods = PluginFusioninventoryStaticmisc::getModulesMethods();
            Dropdown::showFromArray($name, $modules_methods, ['value' => $value]);

            return true;
            break;

         case 'target_type':
            $tj              = new PluginFusioninventoryTaskjob();
            $modules_methods = PluginFusioninventoryStaticmisc::getModulesMethods();
            $targets         = [];
            foreach ($modules_methods as $method_key => $method_label) {
               $targets_method = $tj->getTypesForModule($method_key, 'targets');
               $targets        = array_merge($targets, $targets_method);
            }

            Dropdown::showFromArray($name, $targets, array('value' => $value));

            return true;
            break;

         case 'actor_type':
            $tj              = new PluginFusioninventoryTaskjob;
            $modules_methods = PluginFusioninventoryStaticmisc::getModulesMethods();
            $actors          = [];
            foreach ($modules_methods as $method_key => $method_label) {
               $actors_method = $tj->getTypesForModule($method_key, 'actors');
               $actors        = array_merge($actors, $actors_method);
            }

            Dropdown::showFromArray($name, $actors, ['value' => $value]);

            return true;
            break;

         case 'state':
            $joblog_states = PluginFusioninventoryTaskjoblog::dropdownStateValues();
            Dropdown::showFromArray($name, $joblog_states, ['value' => $value]);

            return true;
            break;
      }

      return false;
   }

   function displayAdditionalRuleAction(array $action, $value='') {
      switch ($action['type']) {
         case 'date':
            Html::showDateField('value', ['value' => $value]);
            return true;
            break;
         case 'datetime':
            Html::showDateTimeField('value', ['value' => $value]);
            return true;
            break;

      }
      return false;
   }


   function getActions() {
      $actions                                = [];

      $actions['states_id']['name']           = __('Status');
      $actions['states_id']['type']           = 'dropdown';
      $actions['states_id']['table']          = 'glpi_states';

      $actions['users_id_tech']['name']       = __('Technician in charge of the hardware');
      $actions['users_id_tech']['type']       = 'dropdown_users';

      $actions['groups_id_tech']['name']      = __('Group in charge of the hardware');
      $actions['groups_id_tech']['type']      = 'dropdown';
      $actions['groups_id_tech']['table']     = 'glpi_groups';
      $actions['groups_id_tech']['condition'] = '`is_assign`';

      $actions['groups_id']['name']           = __('Group');
      $actions['groups_id']['type']           = 'dropdown';
      $actions['groups_id']['table']          = 'glpi_groups';

      $actions['comment']['name']             = __('Comment');

      $actions['otherserial']['name']         = __('Inventory number');

      return $actions;
   }

}
