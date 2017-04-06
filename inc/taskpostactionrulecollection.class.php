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

class PluginFusioninventoryTaskpostactionRuleCollection extends RuleCollection {

   static $rightname = "plugin_fusioninventory_taskpostactionrule";

   // From RuleCollection
   public $stop_on_first_match=false;

   function getTitle() {
      return _n('Post deployment action','Post deployment actions',
                Session::getPluralNumber(), 'fusioninventory');
   }

   static function launchProcess($input, $pfTaskjobstate) {
      //Only process deployment tasks
      if ($input['itemtype'] != 'PluginFusioninventoryDeployPackage') {
         return false;
      }

      $pfTaskjob = new PluginFusioninventoryTaskjob();
      $pfTaskjob->getFromDB($pfTaskjobstate->fields['plugin_fusioninventory_taskjobs_id']);

      $input['plugin_fusioninventory_agents_id']
         = $pfTaskjobstate->fields['plugin_fusioninventory_agents_id'];
      $input['plugin_fusioninventory_tasks_id']
         = $pfTaskjob->fields['plugin_fusioninventory_tasks_id'];
      $input['method'] = $pfTaskjob->fields['method'];

      //Get targets for the task
      $targets     = importArrayFromDB($pfTaskjob->fields['targets']);
      $targettypes = [];
      $targetitems = [];
      foreach ($targets as $num => $data) {
         $itemtype          = key($data);
         $items_id          = current($data);
         $targettypes[$num] = $itemtype;
         $targetitems[$num]
            = Dropdown::getDropdownName(getTableForItemType($itemtype), $items_id).
                                        " ($items_id)";
      }
      $input['target_type'] = $targettypes;
      $input['target']      = implode(",", $targetitems);

      // decode actors
      $actors     = importArrayFromDB($pfTaskjob->fields['actors']);
      $actortypes = [];
      $actoritems = [];
      foreach ($actors as $num => $data) {
         $itemtype         = key($data);
         $items_id         = current($data);
         $actortypes[$num] = $itemtype;
         $actoritems[$num] = Dropdown::getDropdownName(getTableForItemType($itemtype), $items_id).
                             " ($items_id)";
      }
      $input['actor_type'] = $actortypes;
      $input['actor']      = implode(",", $actoritems);

      // execute rule engine
      $collection = new self();
      $output     = $collection->processAllRules($input);

      // alter computer with the rule engin results
      $agent = new PluginFusioninventoryAgent();
      if ($agent->getFromDB($input['plugin_fusioninventory_agents_id'])) {
         $computer = new Computer();
         $update   = array_merge(['id' => $agent->fields['computers_id']], $output);
         return $computer->update($update);
      } else {
         return false;
      }
   }
}
