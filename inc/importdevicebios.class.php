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
      return [
         'FROM' => 'glpi_items_devicefirmwares',
         'FIELDS' => [
            'glpi_items_devicefirmwares' => ['id', 'serial'],
            'glpi_devicefirmwares' => ['designation', 'version']
         ],
         'LEFT JOIN' => [
            'glpi_devicefirmwares' => [
               'FKEY' => [
                  'glpi_items_devicefirmwares' => 'devicefirmwares_id',
                  'glpi_devicefirmwares'       => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'items_id'   => $this->items_id,
            'itemtype'   => $this->import_itemtype,
            'is_dynamic' => 1
         ]
      ];
   }

   function transformItem($inventory_as_array = [], $output_inventory = []) {
      // * BIOS
      if (isset($inventory_as_array['BIOS'])) {
         if (isset($inventory_as_array['BIOS']['ASSETTAG'])
                 && !empty($inventory_as_array['BIOS']['ASSETTAG'])) {
            $output_inventory['Computer']['otherserial'] = $inventory_as_array['BIOS']['ASSETTAG'];
         }
         if ((isset($inventory_as_array['BIOS']['SMANUFACTURER']))
               && (!empty($inventory_as_array['BIOS']['SMANUFACTURER']))) {
            $output_inventory['Computer']['manufacturers_id'] = $inventory_as_array['BIOS']['SMANUFACTURER'];
         } else if ((isset($inventory_as_array['BIOS']['MMANUFACTURER']))
                      && (!empty($inventory_as_array['BIOS']['MMANUFACTURER']))) {
            $output_inventory['Computer']['manufacturers_id'] = $inventory_as_array['BIOS']['MMANUFACTURER'];
         } else if ((isset($inventory_as_array['BIOS']['BMANUFACTURER']))
                      && (!empty($inventory_as_array['BIOS']['BMANUFACTURER']))) {
            $output_inventory['Computer']['manufacturers_id'] = $inventory_as_array['BIOS']['BMANUFACTURER'];
         } else {
            if ((isset($inventory_as_array['BIOS']['MMANUFACTURER']))
                         && (!empty($inventory_as_array['BIOS']['MMANUFACTURER']))) {
               $output_inventory['Computer']['manufacturers_id'] = $inventory_as_array['BIOS']['MMANUFACTURER'];
            } else {
               if ((isset($inventory_as_array['BIOS']['BMANUFACTURER']))
                            && (!empty($inventory_as_array['BIOS']['BMANUFACTURER']))) {
                  $output_inventory['Computer']['manufacturers_id'] = $inventory_as_array['BIOS']['BMANUFACTURER'];
               }
            }
         }
         if ((isset($inventory_as_array['BIOS']['MMANUFACTURER']))
                      && (!empty($inventory_as_array['BIOS']['MMANUFACTURER']))) {
            $output_inventory['Computer']['mmanufacturer'] = $inventory_as_array['BIOS']['MMANUFACTURER'];
         }
         if ((isset($inventory_as_array['BIOS']['BMANUFACTURER']))
                      && (!empty($inventory_as_array['BIOS']['BMANUFACTURER']))) {
            $output_inventory['Computer']['bmanufacturer'] = $inventory_as_array['BIOS']['BMANUFACTURER'];
         }

         if (isset($inventory_as_array['BIOS']['SMODEL']) && $inventory_as_array['BIOS']['SMODEL'] != '') {
            $output_inventory['Computer']['computermodels_id'] = $inventory_as_array['BIOS']['SMODEL'];
         } else if (isset($inventory_as_array['BIOS']['MMODEL']) && $inventory_as_array['BIOS']['MMODEL'] != '') {
            $output_inventory['Computer']['computermodels_id'] = $inventory_as_array['BIOS']['MMODEL'];
         }
         if (isset($inventory_as_array['BIOS']['MMODEL']) && $inventory_as_array['BIOS']['MMODEL'] != '') {
            $output_inventory['Computer']['mmodel'] = $inventory_as_array['BIOS']['MMODEL'];
         }

         if (isset($inventory_as_array['BIOS']['SSN'])) {
            $output_inventory['Computer']['serial'] = trim($inventory_as_array['BIOS']['SSN']);
            // HP patch for serial begin with 'S'
            if ((isset($output_inventory['Computer']['manufacturers_id']))
                  && (strstr($output_inventory['Computer']['manufacturers_id'], "ewlett"))
                    && preg_match("/^[sS]/", $output_inventory['Computer']['serial'])) {
               $output_inventory['Computer']['serial'] = trim(
                                                preg_replace("/^[sS]/",
                                                             "",
                                                             $output_inventory['Computer']['serial']));
            }
         }
         if (isset($inventory_as_array['BIOS']['MSN'])) {
            $output_inventory['Computer']['mserial'] = trim($inventory_as_array['BIOS']['MSN']);
         }
      }

      // * BIOS
      if (isset($inventory_as_array['BIOS'])) {
         $a_bios = $this->addValues(
            $inventory_as_array['BIOS'],
            [
               'BDATE'           => 'date',
               'BVERSION'        => 'version',
               'BMANUFACTURER'   => 'manufacturers_id',
               'BIOSSERIAL'      => 'serial'
            ]
         );

         $a_bios['designation'] = sprintf(
            __('%1$s BIOS'),
            isset($inventory_as_array['BIOS']['BMANUFACTURER'])
               ? $inventory_as_array['BIOS']['BMANUFACTURER'] : ''
         );

         $matches = [];
         preg_match("/^(\d{2})\/(\d{2})\/(\d{4})$/", $a_bios['date'], $matches);
         if (count($matches) == 4) {
            $a_bios['date'] = $matches[3]."-".$matches[1]."-".$matches[2];
         } else {
            unset($a_bios['date']);
         }

         $output_inventory['bios'] = $a_bios;
      }

      return [
         'inventory_as_array' => $inventory_as_array,
         'output_inventory' => $output_inventory
      ];
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
         if (isset($this->a_inventory['bios'])) {
            $this->addBios($this->a_inventory['bios']);
         }
      } else {
         if (isset($this->a_inventory['bios'])) {
            $arrayslower = array_map('strtolower', $this->a_inventory['bios']);
            foreach ($db_bios as $keydb => $arraydb) {
               if (isset($arrayslower['version'])
                  && $arrayslower['version'] == $arraydb['version']) {
                  unset($this->a_inventory['bios']);
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

         if (isset($this->a_inventory['bios'])) {
            $this->addBios($this->a_inventory['bios'], $this->items_id);
         }
      }
   }

   /**
    * Add a new bios component
    *
    * @param array $data
    * @param integer $$this->items_id
    * @param boolean $this->no_history
    */
   function addBios($data) {
      $item_DeviceBios  = new Item_DeviceFirmware();
      $deviceBios       = new DeviceFirmware();

      $fwTypes = new DeviceFirmwareType();
      $fwTypes->getFromDBByQuery("WHERE `name` = 'BIOS'");
      $type_id = $fwTypes->getID();
      $data['devicefirmwaretypes_id'] = $type_id;

      $bios_id                      = $deviceBios->import($data);
      $data['devicefirmwares_id']   = $bios_id;
      $data['itemtype']             = $this->import_itemtype;
      $data['items_id']             = $this->items_id;
      $data['is_dynamic']           = 1;
      $data['_no_history']          = $this->no_history;
      $item_DeviceBios->add($data, [], !$this->no_history);
   }

}
