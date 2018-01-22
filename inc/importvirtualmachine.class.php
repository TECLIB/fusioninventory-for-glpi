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
class PluginFusioninventoryImportVirtualMachine extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'ComputerVirtualMachine';

   //Store the key related to the
   protected $section = 'virtualmachine';

   protected $entities_id = 0;

   function getQuery() {
      return [
         'SELECT' => ['id', 'name', 'uuid', 'virtualmachinesystems_id'],
         'FROM'   => 'glpi_computervirtualmachines',
         'WHERE'  => [
            'computers_id' => $this->items_id,
            'is_dynamic'   => 1
         ]
      ];
   }

   function canImport() {
      return ($this->pfConfig->getValue("import_vm") == 1);
   }

   /**
    * Import virtual machines
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $this->no_history should history be added in the logs
    *
    * @return void
    */
   function importItem() {
      global $DB;

      $computerVirtualmachine = new computerVirtualmachine();

      $db_computervirtualmachine = [];
      if ($this->no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $db_computervirtualmachine[$idtmp] = $data1;
         }
      }
      $simplecomputervirtualmachine = [];
      if (isset($this->a_inventory[$this->section])) {
         foreach ($this->a_inventory[$this->section] as $key=>$a_computervirtualmachine) {
            $a_field = ['name', 'uuid', 'virtualmachinesystems_id'];
            foreach ($a_field as $field) {
               if (isset($a_computervirtualmachine[$field])) {
                  $simplecomputervirtualmachine[$key][$field] =
                              $a_computervirtualmachine[$field];
               }
            }
         }
      }
      foreach ($simplecomputervirtualmachine as $key => $arrays) {
         foreach ($db_computervirtualmachine as $keydb => $arraydb) {
            if ($arrays == $arraydb) {
               $input       = [];
               $input['id'] = $keydb;

               $fields = [
                  'vcpu', 'ram', 'virtualmachinetypes_id',
                  'virtualmachinestates_id', 'virtualmachinestates_id'
               ];
               foreach ($fields as $field) {
                  if (isset($this->a_inventory[$this->section][$key][$field])) {
                     $input[$field] = $this->a_inventory[$this->section][$key][$field];
                  }
               }
               $computerVirtualmachine->update($input, !$this->no_history);
               unset($simplecomputervirtualmachine[$key]);
               unset($this->a_inventory[$this->section][$key]);
               unset($db_computervirtualmachine[$keydb]);
               break;
            }
         }
      }
      if (count($this->a_inventory[$this->section]) || count($db_computervirtualmachine)) {
         if (count($db_computervirtualmachine) != 0) {
            // Delete virtualmachine in DB
            foreach ($db_computervirtualmachine as $idtmp => $data) {
               $computerVirtualmachine->delete(['id' => $idtmp], 1);
            }
         }
         if (count($this->a_inventory[$this->section]) != 0) {
            foreach ($this->a_inventory[$this->section] as $a_virtualmachine) {
               $a_virtualmachine['computers_id'] = $this->items_id;
               $computerVirtualmachine->add($a_virtualmachine, [], !$this->no_history);
            }
         }
      }

      if ($this->pfConfig->getValue("create_vm") == 1) {
         // Create VM based on information of section VIRTUALMACHINE
         $pfAgent = new PluginFusioninventoryAgent();

         // Use ComputerVirtualMachine::getUUIDRestrictRequest to get existant
         // vm in computer list
         $computervm = new Computer();
         if (isset($a_computerinventory['virtualmachine_creation'])
         && is_array($a_computerinventory['virtualmachine_creation'])) {
            foreach ($a_computerinventory['virtualmachine_creation'] as $a_vm) {
               // Define location of physical computer (host)
               $a_vm['locations_id'] = $computer->fields['locations_id'];

               if (isset($a_vm['uuid'])
               && $a_vm['uuid'] != '') {
                  $computers_vm_id = 0;

                  $params = [
                     'import_itemtype' => $this->import_itemtype,
                     'entities_id'     => $this->entities_id,
                     'no_history'      => $this->no_history,
                     'a_inventory'     => $a_vm['networkport']
                  ];

                  $importPort = new PluginFusioninventoryImportNetworkPort($params);
                  $query = "SELECT * FROM `glpi_computers` WHERE `uuid`"
                     .ComputerVirtualMachine::getUUIDRestrictRequest($a_vm['uuid']);
                  $iterator = $DB->request($query);
                  if ($iterator->numrows() == 0) {
                     // Add computer
                     $a_vm['entities_id'] = $this->entities_id;
                     $computers_vm_id     = $computervm->add($a_vm, [], !$this->no_history);
                     $params['items_id']  = $computers_vm_id;
                     // Manage networks
                     $importPort->manageNetworkPort();
                  } else {
                     if ($pfAgent->getAgentWithComputerid($computers_vm_id) === false) {
                        // Update computer
                        $a_vm['id']         = $computers_vm_id;
                        $params['items_id'] = $computers_vm_id;

                        $computervm->update($a_vm, !$this->no_history);
                        // Manage networks
                        $importPort->manageNetworkPort();
                     }
                  }
               }
            }
         }
      }
   }
}
