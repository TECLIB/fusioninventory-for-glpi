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
class PluginFusioninventoryImportComputerRemoteManagement extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'ComputerRemoteManagement';

   //Store the key related to the
   protected $section = 'remote_mgmt';

   function getQuery() {
      return [
         'FROM' => 'glpi_plugin_fusioninventory_computerremotemanagements',
         'FIELDS' => ['id', 'type', 'number'],
         'WHERE'  => ['computers_id' => $this->items_id]
      ];
   }

   function importItem($no_history = false) {
      global $DB;
      $pfComputerRemotemgmt = new PluginFusioninventoryComputerRemoteManagement();
      $db_remotemgmt        = [];
      if ($no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
             $idtmp = $data['id'];
             unset($data['id']);
             $data1 = Toolbox::addslashes_deep($data);
             $data2 = array_map('strtolower', $data1);
             $db_remotemgmt[$idtmp] = $data2;
         }
      }
      foreach ($this->a_inventory[$this->section] as $key => $arrays) {
         $arrayslower = array_map('strtolower', $arrays);
         foreach ($db_remotemgmt as $keydb => $arraydb) {
            if ($arrayslower == $arraydb) {
               unset($this->a_inventory[$this->section][$key]);
               unset($db_remotemgmt[$keydb]);
               break;
            }
         }
       }
       if (count($this->a_inventory[$this->section]) || count($db_remotemgmt)) {
         if (count($db_remotemgmt) != 0) {
            foreach ($db_remotemgmt as $idtmp => $data) {
               $pfComputerRemotemgmt->delete(['id'=>$idtmp], 1);
            }
         }
         if (count($this->a_inventory[$this->section]) != 0) {
            foreach ($this->a_inventory[$this->section] as $a_remotemgmt) {
               $a_remotemgmt['computers_id'] = $this->items_id;
               $pfComputerRemotemgmt->add($a_remotemgmt, [], !$no_history);
            }
         }
      }
   }
}
