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
 * Common inventory methods (local or snmp)
 */
class PluginFusioninventoryInventoryCommon extends CommonDBTM {



   /**
    * Import all standard devices for a computer
    *
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    * @param boolean $check_import check it there's a import option
    *
    * @return void
    */
   function importDevicesForAComputer($params = []) {

      $devices = ['DeviceProcessor', 'DeviceBattery', 'DeviceMemory',
                  'DeviceFirmware', 'DeviceBios', 'DeviceDrive',
                  'DeviceHardDrive', 'DeviceSimcard', 'DeviceBattery',
                  'DeviceControl', 'DeviceGraphicCard', 'DeviceSoundCard',
                  'DeviceNetworkCard'
      ];
      $this->importDevicesFromList($devices, $params);
   }

   /**
    * Import all standard devices for a network equipment
    *
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    * @param boolean $check_import check it there's a import option
    *
    * @return void
    */
   function importDevicesForSNMPAsset($itemtype, $a_inventory, $items_id,
                                      $no_history, $entities_id,
                                      $check_import = false) {

      $devices = [
         'DeviceProcessor', 'DeviceBattery', 'DeviceMemory',
         'DeviceFirmware', 'DeviceSimcard', 'DeviceBattery',
         'DeviceNetworkCard'
      ];
      $this->importDevicesFromList($devices, $a_inventory, $itemtype,
                                   $items_id, $entities_id, $no_history);

   }

   /**
    * Import a list of devices for an asset
    *
    * @param array $devices a list of devices to import
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    * @param boolean $check_import check it there's a import option
    *
    * @return void
    */
   function importDevicesFromList($devices, $params = []) {
      foreach ($devices as $device) {
         $classname = 'PluginFusioninventoryImport'.$device;
         $class     = new $classname($params);
         $class->importItem();
      }
   }
}
