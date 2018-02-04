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
 * Import drive
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportDeviceBattery extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory;

   //The itemtype of the device we're processing
   protected $device_itemtype = 'DeviceBattery';

   //Store the key related to the
   protected $section = 'batteries';

   function getQuery() {
      return "SELECT `glpi_items_devicebatteries`.`id`, `serial`, `voltage`, `capacity`
              FROM `glpi_items_devicebatteries`
              LEFT JOIN `glpi_devicebatteries` ON `devicebatteries_id`=`glpi_devicebatteries`.`id`
                 WHERE `items_id` = '".$this->items_id."'
                    AND `itemtype`='".$this->import_itemtype."'
                    AND `is_dynamic`='1'";
   }

   function checkBefore($data) {
      unset($data['id']);
      return $data;
   }

   /**
    * Check device data after import in database
    * @since 9.3+1.0
    *
    * @param array  $data Device data
    * @return array device data modified
    */
   function checkAfter(&$a_inventory, &$db_devices, $key, $inventory_as_arrays) {
      $item_DeviceBattery = new item_DeviceBattery();
      $inventory_as_arrayslower        = array_map('strtolower', $inventory_as_arrays);

      foreach ($db_devices as $keydb => $inventory_as_arraydb) {
         if (isset($inventory_as_arrayslower['serial'])
            && isset($inventory_as_arraydb['serial'])
            && $inventory_as_arrayslower['serial'] == $inventory_as_arraydb['serial']
         ) {
            $update = false;
            if ($inventory_as_arraydb['capacity'] == 0
                     && $inventory_as_arrayslower['capacity'] > 0) {
               $input = [
                  'id'       => $keydb,
                  'capacity' => $inventory_as_arrayslower['capacity']
               ];
               $update = true;
            }

            if ($inventory_as_arraydb['voltage'] == 0
                     && $inventory_as_arrayslower['voltage'] > 0) {
               $input = [
                  'id'        => $keydb,
                  'voltage'   => $inventory_as_arrayslower['voltage']
               ];
               $update = true;
            }

            if ($update === true) {
               $item_DeviceBattery->update($input);
            }

            unset($a_inventory['batteries'][$key]);
            unset($db_devices[$keydb]);
            break;
         }
      }
   }

   public function transformItem($inventory_as_array = [], $output_inventory = []) {
      // * BATTERIES
      $a_inventory['batteries'] = [];
      if ($pfConfig->getValue('component_battery') == 1) {
         if (isset($inventory_as_array['BATTERIES'])) {
            foreach ($inventory_as_array['BATTERIES'] as $a_batteries) {
               $a_battery = $this->addValues($a_batteries,
                  [
                     'NAME'         => 'designation',
                     'MANUFACTURER' => 'manufacturers_id',
                     'SERIAL'       => 'serial',
                     'DATE'         => 'manufacturing_date',
                     'CAPACITY'     => 'capacity',
                     'CHEMISTRY'    => 'devicebatterytypes_id',
                     'VOLTAGE'      => 'voltage'
                  ]
               );

               // test date_install
               $matches = [];
               if (isset($a_battery['manufacturing_date'])) {
                  preg_match("/^(\d{2})\/(\d{2})\/(\d{4})$/", $a_battery['manufacturing_date'], $matches);
                  if (count($matches) == 4) {
                     $a_battery['manufacturing_date'] = $matches[3]."-".$matches[2]."-".$matches[1];
                  } else {
                     unset($a_battery['manufacturing_date']);
                  }
               }
               $output_inventory['batteries'][] = $a_battery;
            }
         }
      }
   }
}
