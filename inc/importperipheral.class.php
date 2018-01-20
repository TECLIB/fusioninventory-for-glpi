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
 * Importprinters
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportPeripheral extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'Peripheral';

   //Store the key related to the
   protected $section = 'peripheral';

   protected $entities_id = 0;

   function getQuery() {
      return "SELECT `glpi_peripherals`.`id`, `glpi_computers_items`.`id` as link_id
            FROM `glpi_computers_items`
         LEFT JOIN `glpi_peripherals` ON `items_id`=`glpi_peripherals`.`id`
         WHERE `itemtype`=$this->section
         AND `computers_id`='".$this->items_id."'
         AND `entities_id`='".$this->entities_id."'
            AND `glpi_computers_items`.`is_dynamic`='1'
            AND `glpi_peripherals`.`is_global`='0'";
   }

   function importItem($no_history = false) {
      global $DB;

      $peripheral    = new Peripheral();
      $computer_Item = new computer_Item();

      // * Peripheral
      $rule = new PluginFusioninventoryInventoryRuleImportCollection();
      $a_peripherals = [];
      foreach ($this->inventory[$this->section] as $key => $arrays) {
         $input = [];
         $input['itemtype'] = "Peripheral";
         $input['name']     = $arrays['name'];
         $input['serial']   = isset($arrays['serial'])
                               ? $arrays['serial']
                               : "";
         $data = $rule->processAllRules($input, [], ['class'=>$this, 'return' => true]);
         if (isset($data['found_equipment'])) {
            if ($data['found_equipment'][0] == 0) {
               // add peripheral
               $arrays['entities_id'] = $entities_id;

               $a_peripherals[] = $peripheral->add($arrays);
            } else {
               $a_peripherals[] = $data['found_equipment'][0];
            }
         }
      }
      $db_peripherals = [];
      foreach ($DB->request($this->getQuery()) as $data) {
         $idtmp = $data['link_id'];
         unset($data['link_id']);
         $db_peripherals[$idtmp] = $data['id'];
      }

      if (count($db_peripherals) == 0) {
         foreach ($a_peripherals as $peripherals_id) {
            $input                   = [];
            $input['computers_id']   = $this->items_id;
            $input['itemtype']       = $this->section;
            $input['items_id']       = $peripherals_id;
            $input['is_dynamic']     = true;
            $input['_no_history']    = $no_history;
            $computer_Item->add($input, [], !$no_history);
         }
      } else {
         // Check all fields from source:
         foreach ($a_peripherals as $key => $peripherals_id) {
            foreach ($db_peripherals as $keydb => $periphs_id) {
               if ($peripherals_id == $periphs_id) {
                  unset($a_peripherals[$key]);
                  unset($db_peripherals[$keydb]);
                  break;
               }
            }
         }

         if (count($a_peripherals) || count($db_peripherals)) {
            if (count($db_peripherals) != 0) {
               // Delete peripherals links in DB
               foreach ($db_peripherals as $idtmp => $data) {
                  $computer_Item->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_peripherals) != 0) {
               foreach ($a_peripherals as $peripherals_id) {
                  $input                   = [];
                  $input['computers_id']   = $this->items_id;
                  $input['itemtype']       = $this->section;
                  $input['items_id']       = $peripherals_id;
                  $input['is_dynamic']     = true;
                  $input['_no_history']    = $no_history;
                  $computer_Item->add($input, [], !$no_history);
               }
            }
         }
      }
   }
}
