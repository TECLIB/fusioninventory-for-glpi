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
   function checkAfter(&$a_inventory, &$db_devices, $key, $arrays) {
      $item_DeviceBattery = new item_DeviceBattery();
      $arrayslower        = array_map('strtolower', $arrays);

      foreach ($db_devices as $keydb => $arraydb) {
         if (isset($arrayslower['serial'])
            && isset($arraydb['serial'])
            && $arrayslower['serial'] == $arraydb['serial']
         ) {
            $update = false;
            if ($arraydb['capacity'] == 0
                     && $arrayslower['capacity'] > 0) {
               $input = [
                  'id'       => $keydb,
                  'capacity' => $arrayslower['capacity']
               ];
               $update = true;
            }

            if ($arraydb['voltage'] == 0
                     && $arrayslower['voltage'] > 0) {
               $input = [
                  'id'        => $keydb,
                  'voltage'   => $arrayslower['voltage']
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
}
