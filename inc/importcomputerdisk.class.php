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
class PluginFusioninventoryImportComputerDisk extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'ComputerDisk';

   //Store the key related to the
   protected $section = 'computerdisk';

   function getQuery() {
      return [
         'SELECT' => ['id', 'name', 'device', 'mountpoint'],
         'FROM'   => 'glpi_computerdisks',
         'WHERE'  => [
            'computers_id' => $this->items_id,
            'is_dynamic'   => 1
         ]
      ];
   }

   function canImport() {
      return ($this->pfConfig->getValue("import_volume") > 0);
   }

   function importItem() {
      global $DB;

      $computerDisk    = new computerDisk();
      $db_computerdisk = [];

      if ($this->no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_computerdisk[$idtmp] = $data2;
         }
      }
      $simplecomputerdisk = [];
      foreach ($this->a_inventory[$this->section] as $key=>$a_computerdisk) {
         $a_field = ['name', 'device', 'mountpoint'];
         foreach ($a_field as $field) {
            if (isset($a_computerdisk[$field])) {
               $simplecomputerdisk[$key][$field] = $a_computerdisk[$field];
            }
         }
      }
      foreach ($simplecomputerdisk as $key => $arrays) {
         $arrayslower = array_map('strtolower', $arrays);
         foreach ($db_computerdisk as $keydb => $arraydb) {
            if ($arrayslower == $arraydb) {
               $input = [];
               $input['id'] = $keydb;
               if (isset($this->a_inventory[$this->section][$key]['filesystems_id'])) {
                  $input['filesystems_id'] =
                           $this->a_inventory[$this->section][$key]['filesystems_id'];
               }
               $input['totalsize'] = $this->a_inventory[$this->section][$key]['totalsize'];
               $input['freesize'] = $this->a_inventory[$this->section][$key]['freesize'];
               $input['_no_history'] = true;
               $computerDisk->update($input, false);
               unset($simplecomputerdisk[$key]);
               unset($this->a_inventory[$this->section][$key]);
               unset($db_computerdisk[$keydb]);
               break;
            }
         }
      }

      if (count($this->a_inventory[$this->section]) || count($db_computerdisk)) {
         if (count($db_computerdisk) != 0) {
            // Delete computerdisk in DB
            foreach ($db_computerdisk as $idtmp => $data) {
               $computerDisk->delete(['id'=>$idtmp], 1);
            }
         }
         if (count($this->a_inventory[$this->section]) != 0) {
            foreach ($this->a_inventory[$this->section] as $a_computerdisk) {
               $a_computerdisk['computers_id']  = $this->items_id;
               $a_computerdisk['is_dynamic']    = 1;
               $a_computerdisk['_no_history']   = $this->no_history;
               $computerDisk->add($a_computerdisk, [], !$this->no_history);
            }
         }
      }
   }
}
