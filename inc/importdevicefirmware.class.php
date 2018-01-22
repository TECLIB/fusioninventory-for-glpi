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
class PluginFusioninventoryImportDeviceFirmware extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory;

   //The itemtype of the device we're processing
   protected $device_itemtype = 'DeviceFirmware';

   //Store the key related to the
   protected $section = 'firmwares';

   public function importItem() {
      if (!isset($a_inventory['firmwares']) || !count($a_inventory['firmwares'])) {
         return;
      }

      $ftype = new DeviceFirmwareType();
      $ftype->getFromDBByCrit(['name' => 'Firmware']);
      $default_type = $ftype->getId();
      foreach ($a_inventory['firmwares'] as $a_firmware) {
         $firmware = new DeviceFirmware();
         $input = [
            'designation'              => $a_firmware['name'],
            'version'                  => $a_firmware['version'],
            'devicefirmwaretypes_id'   => isset($a_firmware['devicefirmwaretypes_id']) ? $a_firmware['devicefirmwaretypes_id'] : $default_type,
            'manufacturers_id'         => $a_firmware['manufacturers_id']
         ];

         //Check if firmware exists
         $firmware->getFromDBByCrit($input);
         if ($firmware->isNewItem()) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
            //firmware does not exists yet, create it
            $fid = $firmware->add($input);
         } else {
            $fid = $firmware->getID();
         }
         $relation = new Item_DeviceFirmware();
         $input = [
            'itemtype'           => $itemtype,
            'items_id'           => $items_id,
            'devicefirmwares_id' => $fid
         ];
         //Check if firmware relation with equipment
         $relation->getFromDBByCrit($input);
         if ($relation->isNewItem()) {
            $input = $input + [
               'is_dynamic'   => 1,
               'entities_id'  => $_SESSION['glpiactive_entity']
            ];
            $relation->add($input);
         }
      }
   }
}
