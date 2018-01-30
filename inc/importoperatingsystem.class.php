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
class PluginFusioninventoryImportOperatingSystem extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'OperatingSystem';

   //Store the key related to the
   protected $section = '';

   protected $entities_id = 0;

   function canImport() {
      return ($this->import_itemtype == 'Computer');
   }

   function transformItem($inventory_as_array = [], $output_inventory = []) {
      if (!isset($inventory_as_array['OPERATINGSYSTEM']) || empty($inventory_as_array['OPERATINGSYSTEM'])) {
         $inventory_as_array['OPERATINGSYSTEM'] = [];
         if (isset($inventory_as_array['HARDWARE']['OSNAME'])) {
            $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'] = $inventory_as_array['HARDWARE']['OSNAME'];
         }
         if (isset($inventory_as_array['HARDWARE']['OSVERSION'])) {
            $inventory_as_array['OPERATINGSYSTEM']['VERSION'] = $inventory_as_array['HARDWARE']['OSVERSION'];
         }
         if (isset($inventory_as_array['HARDWARE']['OSCOMMENTS'])
                 && $inventory_as_array['HARDWARE']['OSCOMMENTS'] != ''
                 && !strstr($inventory_as_array['HARDWARE']['OSCOMMENTS'], 'UTC')) {
            $inventory_as_array['OPERATINGSYSTEM']['SERVICE_PACK'] = $inventory_as_array['HARDWARE']['OSCOMMENTS'];
         }
      }

      if (isset($array['OPERATINGSYSTEM'])) {
         $array_tmp = $this->addValues(
                 $array['OPERATINGSYSTEM'],
                 [
                  'NAME'           => 'operatingsystems_id',
                  'VERSION'        => 'operatingsystemversions_id',
                  'SERVICE_PACK'   => 'operatingsystemservicepacks_id',
                  'ARCH'           => 'operatingsystemarchitectures_id',
                  'KERNEL_NAME'    => 'operatingsystemkernels_id',
                  'KERNEL_VERSION' => 'operatingsystemkernelversions_id'
                 ]);

         if (isset($array['OPERATINGSYSTEM']['HOSTID'])) {
            $a_inventory['fusioninventorycomputer']['hostid'] = $array['OPERATINGSYSTEM']['HOSTID'];
         }

         if (isset($a_inventory['Computer']['licenseid'])) {
            $array_tmp['licenseid'] = $a_inventory['Computer']['licenseid'];
            unset($a_inventory['Computer']['licenseid']);
         }

         if (isset($a_inventory['Computer']['license_number'])) {
            $array_tmp['license_number'] = $a_inventory['Computer']['license_number'];
            unset($a_inventory['Computer']['license_number']);
         }

         $array_tmp['operatingsystemeditions_id'] = '';
         if (isset($inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'])
            && $this->pfConfig->getValue('manage_osname') == 1) {
            $matches = [];
            preg_match("/.+ Windows (XP |\d\.\d |\d{1,4} |Vista(™)? )(.*)/",
                       $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'],
                       $matches);
            if (count($matches) == 4) {
               $array_tmp['operatingsystemeditions_id'] = $matches[3];
               if ($array_tmp['operatingsystemversions_id'] == '') {
                  $matches[1] = trim($matches[1]);
                  if ($matches[2] != '') {
                     $matches[1] = trim($matches[1], $matches[2]);
                  }
                  $array_tmp['operatingsystemversions_id'] = $matches[1];
               }
            } else if (count($matches) == 2) {
               $array_tmp['operatingsystemeditions_id'] = $matches[1];
            } else {
               preg_match("/^(.*) GNU\/Linux (\d{1,2}|\d{1,2}\.\d{1,2}) \((.*)\)$/",
                          $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'],
                          $matches);
               if (count($matches) == 4) {
                  if (empty($array_tmp['operatingsystems_id'])) {
                     $array_tmp['operatingsystems_id'] = $matches[1];
                  }
                  if (empty($array_tmp['operatingsystemkernelversions_id'])) {
                     $array_tmp['operatingsystemkernelversions_id'] = $array_tmp['operatingsystemversions_id'];
                     $array_tmp['operatingsystemversions_id'] = $matches[2]." (".$matches[3].")";
                  } else if (empty($array_tmp['operatingsystemversions_id'])) {
                     $array_tmp['operatingsystemversions_id'] = $matches[2]." (".$matches[3].")";
                  }
                  if (empty($array_tmp['operatingsystemkernels_id'])) {
                     $array_tmp['operatingsystemkernels_id'] = 'linux';
                  }
               } else {
                  preg_match("/Linux (.*) (\d{1,2}|\d{1,2}\.\d{1,2}) \((.*)\)$/",
                             $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'],
                             $matches);
                  if (count($matches) == 4) {
                     if (empty($array_tmp['operatingsystemversions_id'])) {
                        $array_tmp['operatingsystemversions_id'] = $matches[2];
                     }
                     if (empty($array_tmp['operatingsystemarchitectures_id'])) {
                        $array_tmp['operatingsystemarchitectures_id'] = $matches[3];
                     }
                     if (empty($array_tmp['operatingsystemkernels_id'])) {
                        $array_tmp['operatingsystemkernels_id'] = 'linux';
                     }
                     $array_tmp['operatingsystemeditions_id'] = trim($matches[1]);
                  } else {
                     preg_match("/\w[\s\S]{0,4} (?:Windows[\s\S]{0,4} |)(.*) (\d{4} R2|\d{4})(?:, | |)(.*|)$/",
                               $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'],
                               $matches);
                     if (count($matches) == 4) {
                        $array_tmp['operatingsystemversions_id'] = $matches[2];
                        $array_tmp['operatingsystemeditions_id'] = trim($matches[1]." ".$matches[3]);
                     } else if ($inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'] == 'Microsoft Windows Embedded Standard') {
                        $array_tmp['operatingsystemeditions_id'] = 'Embedded Standard';
                     } else if (empty($array_tmp['operatingsystems_id'])) {
                        $array_tmp['operatingsystems_id'] = $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'];
                     }
                  }
               }
            }
         } else if (isset($inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'])) {
            $array_tmp['operatingsystems_id'] = $inventory_as_array['OPERATINGSYSTEM']['FULL_NAME'];
         }
         if (isset($array_tmp['operatingsystemarchitectures_id'])
                 && $array_tmp['operatingsystemarchitectures_id'] != '') {

            $rulecollection = new RuleDictionnaryOperatingSystemArchitectureCollection();
            $res_rule = $rulecollection->processAllRules(["name" => $array_tmp['operatingsystemarchitectures_id']]);
            if (isset($res_rule['name'])) {
               $array_tmp['operatingsystemarchitectures_id'] = $res_rule['name'];
            }
            if ($array_tmp['operatingsystemarchitectures_id'] == '0') {
               $array_tmp['operatingsystemarchitectures_id'] = '';
            }
         }
         if ($array_tmp['operatingsystemservicepacks_id'] == '0') {
            $array_tmp['operatingsystemservicepacks_id'] = '';
         }

         $output_inventory['fusioninventorycomputer']['items_operatingsystems_id'] = $array_tmp;
      }
      
      return [
         'inventory_as_array' => $inventory_as_array,
         'output_inventory' => $output_inventory
      ];
   }

   function importItem() {
      global $DB;

         // Manage operating system
      if (isset($this->a_inventory['fusioninventorycomputer']['items_operatingsystems_id'])) {
         $ios  = new Item_OperatingSystem();
         $pfos = $this->a_inventory['fusioninventorycomputer']['items_operatingsystems_id'];
         $ios->getFromDBByCrit([
            'itemtype' => $this->import_itemtype,
            'items_id' => $this->items_id
         ]);

         $input_os = [
            'itemtype'                          => $this->import_itemtype,
            'items_id'                          => $this->items_id,
            'operatingsystemarchitectures_id'   => $pfos['operatingsystemarchitectures_id'],
            'operatingsystemkernelversions_id'  => $pfos['operatingsystemkernelversions_id'],
            'operatingsystems_id'               => $pfos['operatingsystems_id'],
            'operatingsystemversions_id'        => $pfos['operatingsystemversions_id'],
            'operatingsystemservicepacks_id'    => $pfos['operatingsystemservicepacks_id'],
            'operatingsystemeditions_id'        => $pfos['operatingsystemeditions_id'],
            'license_id'                        => $pfos['licenseid'],
            'license_number'                    => $pfos['license_number'],
            'is_dynamic'                        => 1,
            'entities_id'                       => $this->entities_id
         ];

         if (!$ios->isNewItem()) {
            //OS exists, check for updates
            $same = true;
            foreach ($input_os as $key => $value) {
               if ($ios->fields[$key] != $value) {
                  $same = false;
                  break;
               }
            }
            if ($same === false) {
               $ios->update(['id' => $ios->getID()] + $input_os);
            }
         } else {
            $ios->add($input_os, [], $this->no_history);
         }
      }
   }
}
