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
class PluginFusioninventoryImportDevice implements PluginFusioninventoryImportInterface {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = '';

   //Itemtype of the asset we're imported
   protected $import_itemtype = '';

   //Asset ID
   protected $items_id = 0;

   //Store the key related to the
   protected $section = '';

   //Store plugin configuration
   protected $pfConfig   = null;

   protected $no_history = false;

   public function __construct($params = []) {
      foreach ($params as $key => $value) {
         $this->$key = $value;
      }
   }

   function transformItem() {

   }

   function getItemDeviceClass() {
      $class = 'Item_'.$this->device_itemtype;
      return new $class();
   }

   /**
    * Add a new device component
    * @since 9.3+1.0
    *
    * @return boolean true if the device is added, false if something went wrong
    */
   function addDevice($a_inventory) {
      $item_device = $this->getItemDeviceClass();
      $device      = new $this->device_itemtype();
      $fk_device   = getForeignKeyFieldForTable(getTableForItemType($this->device_itemtype));
      $devices_id  = $device->import($a_inventory);
      if ($devices_id) {
         $data = [
            $fk_device   => $devices_id,
            'itemtype'   => $this->import_itemtype,
            'items_id'   => $this->items_id,
            'is_dynamic' => 1,
            '_no_history' => $this->no_history
         ];
         return $item_device->add($data, [], !$this->no_history);
      } else {
         return false;
      }
   }

   /**
    * Check device data before import in database
    * @since 9.3+1.0
    *
    * @param array  $data Device data
    * @return array device data modified
    */
   function checkBefore($data) {
      unset($data['id']);
      if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['designation'])) {
         $data['designation'] = Toolbox::addslashes_deep($data['designation']);
      }
      $data['designation'] = trim(strtolower($data['designation']));
      return $data;
   }

   /**
    * Check device data before import in database
    * @since 9.3+1.0
    *
    * @param array  $data Device data
    * @return array device data modified
    */
   function checkAfter(&$a_inventory, &$db_devices, $key, $arrays) {
      $arrays['designation'] = strtolower($arrays['designation']);
      foreach ($db_devices as $keydb => $arraydb) {
         if ($arrays == $arraydb) {
            unset($a_inventory[$this->section][$key]);
            unset($db_devices[$keydb]);
            break;
         }
      }
   }

   /**
    * Get query
    * @since 9.3+1.0
    *
    * @param boolean $this->no_history should history be added in the logs
    *
    * @return void
    */
   function getQuery() {
      return '';
   }

   /**
   * Check if the itemtype must be processed
   * @since 9.3+1.0
   *
   * @return true if it must be processed
   */
   function canImport() {
      return true;
   }

   /**
    * Import a device
    * @since 9.3+1.0
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

      if (!$this->canImport()) {
         return true;
      }
      $item_device = $this->getItemDeviceClass();
      $device      = new $this->device_itemtype();

      $device_table      = $device->getTable();
      $db_devices        = [];

      if ($this->no_history === false) {
         foreach ($DB->request($this->getQuery()) as $data) {
            $idtmp              = $data['id'];
            $db_devices[$idtmp] = $this->checkBefore($data);
         }
      }
      if (count($db_devices) == 0) {
         foreach ($this->a_inventory[$this->section] as $a_device) {
            $this->addDevice($a_device, $this->no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'mac'
         foreach ($this->a_inventory[$this->section] as $key => $arrays) {
            $this->checkAfter($a_inventory, $db_devices, $key, $arrays);
         }

         if (count($this->a_inventory[$this->section]) || count($db_devices)) {
            if (count($db_devices) != 0) {
               // Delete devices in DB
               foreach ($db_devices as $idtmp => $data) {
                  $item_device->delete(['id' => $idtmp], true);
               }
            }
            if (count($this->a_inventory[$this->section]) != 0) {
               foreach ($this->a_inventory[$this->section] as $a_device) {
                  $this->addDevice($a_device, $this->no_history);
               }
            }
         }
      }
   }

   function toEndProcess() {
      return true;
   }
}
