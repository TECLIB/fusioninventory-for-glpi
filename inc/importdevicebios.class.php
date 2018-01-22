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
class PluginFusioninventoryImportDeviceBios extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'DeviceFirmware';

   //Store the key related to the
   protected $section = 'bios';

   function getQuery() {
      return "SELECT `glpi_items_devicefirmwares`.`id`, `serial`,
            `designation`, `version`
            FROM `glpi_items_devicefirmwares`
               LEFT JOIN `glpi_devicefirmwares`
                  ON `devicefirmwares_id`=`glpi_devicefirmwares`.`id`
         WHERE `items_id` = '".$this->items_id."'
            AND `itemtype`='".$this->import_itemtype."'
            AND `is_dynamic`='1'";

   }
   /**
    * Import bioses
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

      $item_DeviceBios = new Item_DeviceFirmware();

      // * BIOS
      $db_bios = [];
      if ($this->no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_bios[$idtmp] = $data2;
         }
      }

      if (count($db_bios) == 0) {
         if (isset($a_inventory['bios'])) {
            $this->addBios($itemtype, $a_inventory['bios'],
                           $items_id);
         }
      } else {
         if (isset($a_inventory['bios'])) {
            $arrayslower = array_map('strtolower', $a_inventory['bios']);
            foreach ($db_bios as $keydb => $arraydb) {
               if (isset($arrayslower['version'])
                  && $arrayslower['version'] == $arraydb['version']) {
                  unset($a_inventory['bios']);
                  unset($db_bios[$keydb]);
                  break;
               }
            }
         }

         if (count($db_bios) != 0) {
            // Delete BIOS in DB
            foreach ($db_bios as $idtmp => $data) {
               $item_DeviceBios->delete(['id' => $idtmp], 1);
            }
         }

         if (isset($a_inventory['bios'])) {
            $this->addBios($itemtype, $a_inventory['bios'],
                           $items_id);
         }
      }
   }

   /**
    * Add a new bios component
    *
    * @param array $data
    * @param integer $$items_id
    * @param boolean $this->no_history
    */
   function addBios($itemtype, $data, $items_id) {
      $item_DeviceBios  = new Item_DeviceFirmware();
      $deviceBios       = new DeviceFirmware();

      $fwTypes = new DeviceFirmwareType();
      $fwTypes->getFromDBByQuery("WHERE `name` = 'BIOS'");
      $type_id = $fwTypes->getID();
      $data['devicefirmwaretypes_id'] = $type_id;

      $bios_id                      = $deviceBios->import($data);
      $data['devicefirmwares_id']   = $bios_id;
      $data['itemtype']             = $itemtype;
      $data['items_id']             = $items_id;
      $data['is_dynamic']           = 1;
      $data['_no_history']          = $this->no_history;
      $item_DeviceBios->add($data, [], !$this->no_history);
   }

}
