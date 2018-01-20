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
class PluginFusioninventoryImportComputerAntivirus extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'ComputerAntivirus';

   //Store the key related to the
   protected $section = 'antivirus';

   function getQuery() {
      return [
         'FROM' => 'glpi_computerantiviruses',
         'FIELDS' => ['id', 'name', 'antivirus_version'],
         'WHERE'  => ['computers_id' => $this->items_id]
      ];
   }

   function importItem($no_history = false) {
      global $DB;
      $computerAntivirus = new ComputerAntivirus();
      $db_antivirus = [];
      if ($no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_antivirus[$idtmp] = $data2;
         }
      }
      $simpleantivirus = [];
      foreach ($this->a_inventory[$this->section] as $key => $a_antivirus) {
         $a_field = ['name', 'antivirus_version'];
         foreach ($a_field as $field) {
            if (isset($a_antivirus[$field])) {
               $simpleantivirus[$key][$field] = $a_antivirus[$field];
            }
         }
      }
      foreach ($simpleantivirus as $key => $arrays) {
         $arrayslower = array_map('strtolower', $arrays);
         foreach ($db_antivirus as $keydb => $arraydb) {
            if ($arrayslower == $arraydb) {
               $input               = [];
               $input               = $this->a_inventory[$this->section][$key];
               $input['id']         = $keydb;
               $input['is_dynamic'] = 1;
               $computerAntivirus->update($input, !$no_history);
               unset($simpleantivirus[$key]);
               unset($this->a_inventory[$this->section][$key]);
               unset($db_antivirus[$keydb]);
               break;
            }
         }
      }
      if (count($this->a_inventory[$this->section]) || count($db_antivirus)) {
         if (count($db_antivirus) != 0) {
            foreach ($db_antivirus as $idtmp => $data) {
               $computerAntivirus->delete(['id' => $idtmp], 1);
            }
         }
         if (count($this->a_inventory[$this->section]) != 0) {
            foreach ($this->a_inventory[$this->section] as $a_antivirus) {
               $a_antivirus['computers_id'] = $this->items_id;
               $a_antivirus['is_dynamic']   = true;
               $computerAntivirus->add($a_antivirus, [], !$no_history);
            }
         }
      }
   }
}
