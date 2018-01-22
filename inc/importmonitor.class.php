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
 * This file is used to manage the extended information of a computer.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
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
 * Import graphic card
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportMonitor extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'Monitor';

   //Store the key related to the
   protected $section = 'monitor';

   protected $entities_id = 0;

   function getQuery() {
      return "SELECT `glpi_monitors`.`id`,
                             `glpi_computers_items`.`id` as link_id
                  FROM `glpi_computers_items`
               LEFT JOIN `glpi_monitors` ON `items_id`=`glpi_monitors`.`id`
               WHERE `itemtype`='Monitor'
                  AND `computers_id`='".$this->items_id."'
                  AND `entities_id`='".$this->entities_id."'
                  AND `glpi_computers_items`.`is_dynamic`='1'
                  AND `glpi_monitors`.`is_global`='0'";
   }

   function importItem() {
      global $DB;

      $monitor       = new Monitor();
      $computer_Item = new computer_Item();

      // * Monitors
      $rule       = new PluginFusioninventoryInventoryRuleImportCollection();
      $a_monitors = [];
      foreach ($this->a_inventory[$this->section] as $key => $arrays) {
         $input = [];
         $input['itemtype'] = "Monitor";
         $input['name']     = $arrays['name'];
         $input['serial']   = isset($arrays['serial'])
                               ? $arrays['serial']
                               : "";
         $data = $rule->processAllRules($input, [], ['class'=> $this, 'return' => true]);
         if (isset($data['found_equipment'])) {
            if ($data['found_equipment'][0] == 0) {
               // add monitor
               $arrays['entities_id'] = $this->entities_id;
               $a_monitors[] = $monitor->add($arrays);
            } else {
               $a_monitors[] = $data['found_equipment'][0];
            }
         }
      }

      $db_monitors = [];
      foreach ($DB->request($this->getQuery()) as $data) {
         $idtmp = $data['link_id'];
         unset($data['link_id']);
         $db_monitors[$idtmp] = $data['id'];
      }

      if (count($db_monitors) == 0) {
         foreach ($a_monitors as $monitors_id) {
            $input = [];
            $input['computers_id']   = $this->items_id;
            $input['itemtype']       = 'Monitor';
            $input['items_id']       = $monitors_id;
            $input['is_dynamic']     = true;
            $input['_no_history']    = $this->no_history;
            $computer_Item->add($input, [], !$this->no_history);
         }
      } else {
         // Check all fields from source:
         foreach ($a_monitors as $key => $monitors_id) {
            foreach ($db_monitors as $keydb => $monits_id) {
               if ($monitors_id == $monits_id) {
                  unset($a_monitors[$key]);
                  unset($db_monitors[$keydb]);
                  break;
               }
            }
         }

         if (count($a_monitors) || count($db_monitors)) {
            if (count($db_monitors) != 0) {
               // Delete monitors links in DB
               foreach ($db_monitors as $idtmp => $monits_id) {
                  $computer_Item->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_monitors) != 0) {
               foreach ($a_monitors as $key => $monitors_id) {
                  $input = [];
                  $input['computers_id']   = $this->items_id;
                  $input['itemtype']       = 'Monitor';
                  $input['items_id']       = $monitors_id;
                  $input['is_dynamic']     = 1;
                  $input['_no_history']    = $this->no_history;
                  $computer_Item->add($input, [], !$this->no_history);
               }
            }
         }
      }
   }
}
