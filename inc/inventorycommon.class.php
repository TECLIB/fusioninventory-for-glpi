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
    * Import firmwares
    * @since 9.2+2.0
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    *
    * @return void
    */
   function importFirmwares($itemtype, $a_inventory, $items_id) {
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

   /**
    * Import ports
    *
    * @param array $a_inventory
    * @param integer $items_id
    */
   function importPorts($itemtype, $a_inventory, $items_id) {

      $networkPort     = new NetworkPort();
      $pfNetworkPort   = new PluginFusioninventoryNetworkPort();
      $networkports_id = 0;

      foreach ($a_inventory['networkport'] as $a_port) {
         $a_ports_DB = current($networkPort->find(
                    "`itemtype`='$itemtype'
                       AND `items_id`='".$items_id."'
                       AND `instantiation_type`='NetworkPortEthernet'
                       AND `logical_number` = '".$a_port['logical_number']."'", '', 1));
         if (!isset($a_ports_DB['id'])) {
            // Add port
            $a_port['instantiation_type'] = 'NetworkPortEthernet';
            $a_port['items_id'] = $items_id;
            $a_port['itemtype'] = $itemtype;
            $networkports_id = $networkPort->add($a_port);
            unset($a_port['id']);
            $a_pfnetworkport_DB = current($pfNetworkPort->find(
                    "`networkports_id`='".$networkports_id."'", '', 1));
            $a_port['id'] = $a_pfnetworkport_DB['id'];
            $pfNetworkPort->update($a_port);
         } else {
            // Update port
            $networkports_id = $a_ports_DB['id'];
            $a_port['id'] = $a_ports_DB['id'];
            $networkPort->update($a_port);
            unset($a_port['id']);

            // Check if pfnetworkport exist.
            $a_pfnetworkport_DB = current($pfNetworkPort->find(
                    "`networkports_id`='".$networkports_id."'", '', 1));
            $a_port['networkports_id'] = $networkports_id;
            if (isset($a_pfnetworkport_DB['id'])) {
               $a_port['id'] = $a_pfnetworkport_DB['id'];
               $pfNetworkPort->update($a_port);
            } else {
               $a_port['networkports_id'] = $networkports_id;
               $pfNetworkPort->add($a_port);
            }
         }
      }
   }

   /**
    * Import firmwares
    * @since 9.2+2.0
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    *
    * @return void
    */
   function importSimcards($itemtype, $a_inventory, $items_id) {
      if (!isset($a_inventory['simcards']) || !count($a_inventory['simcards'])) {
         return;
      }

      $simcard  = new DeviceSimcard();

      foreach ($a_inventory['simcards'] as $a_simcard) {
         $relation = new Item_DeviceSimcard();

         $input = [
            'designation' => 'Simcard',
         ];

         //Check if the simcard already exists
         $simcard->getFromDBByCrit($input);
         if ($simcard->isNewItem()) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
            //firmware does not exists yet, create it
            $simcards_id = $simcard->add($input);
         } else {
            $simcards_id = $simcard->getID();
         }

         //Import Item_DeviceSimcard
         $input = [
            'serial'            => $a_simcard['serial'],
            'msin'              => $a_simcard['msin'],
            'devicesimcards_id' => $simcards_id
         ];
         //Check if there's already a connection between the simcard and an asset
         $relation->getFromDBByCrit($input);

         $input['itemtype']    = $itemtype;
         $input['items_id']    = $items_id;
         $input['is_dynamic']  = 1;
         $input['entities_id'] = $_SESSION['glpiactive_entity'];
         if ($relation->isNewItem()) {
            $relations_id = $relation->add($input);
         } else {
            $input['id']  = $relation->getID();
            $relations_id = $relation->update($input);
         }
      }
   }

   /**
    * Import processors
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importProcessors($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $deviceProcessor      = new DeviceProcessor();
      $item_DeviceProcessor = new Item_DeviceProcessor();
      $db_processors = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_deviceprocessors`.`id`, `designation`,
                  `frequency`, `frequence`, `frequency_default`,
                  `serial`, `manufacturers_id`, `glpi_items_deviceprocessors`.`nbcores`,
                  `glpi_items_deviceprocessors`.`nbthreads`
               FROM `glpi_items_deviceprocessors`
               LEFT JOIN `glpi_deviceprocessors`
                  ON `deviceprocessors_id`=`glpi_deviceprocessors`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $db_processors[$idtmp] = Toolbox::addslashes_deep($data);
         }
      }
      if (count($db_processors) == 0) {
         foreach ($a_inventory['processor'] as $a_processor) {
            $this->addDevice('DeviceProcessor', $itemtype, $a_processor,
                             $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'serial', 'manufacturers_id',
         // 'frequence'
         foreach ($a_inventory['processor'] as $key => $arrays) {
            $frequence = $arrays['frequence'];
            unset($arrays['frequence']);
            unset($arrays['frequency']);
            unset($arrays['frequency_default']);
            foreach ($db_processors as $keydb => $arraydb) {
               $frequencedb = $arraydb['frequence'];
               unset($arraydb['frequence']);
               unset($arraydb['frequency']);
               unset($arraydb['frequency_default']);
               if ($arrays == $arraydb) {
                  $a_criteria = $deviceProcessor->getImportCriteria();
                  $criteriafrequence = $a_criteria['frequence'];
                  $compare = explode(':', $criteriafrequence);
                  if ($frequence > ($frequencedb - $compare[1])
                          && $frequence < ($frequencedb + $compare[1])) {
                     unset($a_inventory['processor'][$key]);
                     unset($db_processors[$keydb]);
                     break;
                  }
               }
            }
         }

         if (count($a_inventory['processor']) || count($db_processors)) {
            if (count($db_processors) != 0) {
               // Delete processor in DB
               foreach ($db_processors as $idtmp => $data) {
                  $item_DeviceProcessor->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['processor']) != 0) {
               foreach ($a_inventory['processor'] as $a_processor) {
                  $this->addDevice('DeviceProcessor', $itemtype, $a_processor,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import memories
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importMemories($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $deviceMemory      = new DeviceMemory();
      $item_DeviceMemory = new Item_DeviceMemory();

      $db_memories  = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicememories`.`id`, `designation`, `size`,
                  `frequence`, `serial`, `devicememorytypes_id`,
                  `glpi_items_devicememories`.`busID`
                  FROM `glpi_items_devicememories`
               LEFT JOIN `glpi_devicememories` ON `devicememories_id`=`glpi_devicememories`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $db_memories[$idtmp] = $data1;
         }
      }

      if (count($db_memories) == 0) {
         foreach ($a_inventory['memory'] as $a_memory) {
            $this->addDevice('DeviceMemory', $itemtype, $a_memory,
                             $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'serial', 'size',
         // 'devicememorytypes_id', 'frequence'
         foreach ($a_inventory['memory'] as $key => $arrays) {
            $frequence = (int) $arrays['frequence'];
            unset($arrays['frequence']);
            foreach ($db_memories as $keydb => $arraydb) {
               $frequencedb = (int) $arraydb['frequence'];
               unset($arraydb['frequence']);
               if ($arrays == $arraydb) {
                  $a_criteria = $deviceMemory->getImportCriteria();
                  $criteriafrequence = $a_criteria['frequence'];
                  $compare = explode(':', $criteriafrequence);
                  if ($frequence > ($frequencedb - $compare[1])
                          && $frequence < ($frequencedb + $compare[1])) {
                     unset($a_inventory['memory'][$key]);
                     unset($db_memories[$keydb]);
                     break;
                  }
               }
            }
         }

         if (count($a_inventory['memory']) || count($db_memories)) {
            if (count($db_memories) != 0) {
               // Delete memory in DB
               foreach ($db_memories as $idtmp => $data) {
                  $item_DeviceMemory->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['memory']) != 0) {
               foreach ($a_inventory['memory'] as $a_memory) {
                  $this->addDevice('DeviceMemory', $itemtype, $a_memory,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import harddrives
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importHarddrives($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $item_DeviceHardDrive = new item_DeviceHardDrive();
      $db_harddrives        = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_deviceharddrives`.`id`, `serial`,
                  `capacity`
                  FROM `glpi_items_deviceharddrives`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_harddrives[$idtmp] = $data2;
         }
      }

      if (count($db_harddrives) == 0) {
         foreach ($a_inventory['harddrive'] as $a_harddrive) {
            $this->addDevice('DeviceHardDrive', $itemtype, $a_harddrive,
                             $items_id, $no_history);
         }
      } else {
         foreach ($a_inventory['harddrive'] as $key => $arrays) {
            $arrayslower = array_map('strtolower', $arrays);

            // if disk has no serial, don't add and unset it
            if (!isset($arrayslower['serial'])) {
               unset($a_inventory['harddrive'][$key]);
               break;
            }

            foreach ($db_harddrives as $keydb => $arraydb) {
               if ($arrayslower['serial'] == $arraydb['serial']) {
                  if ($arraydb['capacity'] == 0
                          AND $arrayslower['capacity'] > 0) {
                     $input = [
                        'id'       => $keydb,
                        'capacity' => $arrayslower['capacity']
                     ];
                     $item_DeviceHardDrive->update($input);
                  }
                  unset($a_inventory['harddrive'][$key]);
                  unset($db_harddrives[$keydb]);
                  break;
               }
            }
         }

         if (count($a_inventory['harddrive']) || count($db_harddrives)) {
            if (count($db_harddrives) != 0) {
               // Delete hard drive in DB
               foreach ($db_harddrives as $idtmp => $data) {
                  $item_DeviceHardDrive->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['harddrive']) != 0) {
               foreach ($a_inventory['harddrive'] as $a_harddrive) {
                  $this->addDevice('DeviceHardDrive', $itemtype, $a_harddrive,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import drives
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importDrives($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $item_DeviceDrive = new Item_DeviceDrive();
      $db_drives            = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicedrives`.`id`, `serial`,
                  `glpi_devicedrives`.`designation`
                  FROM `glpi_items_devicedrives`
               LEFT JOIN `glpi_devicedrives` ON `devicedrives_id`=`glpi_devicedrives`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_drives[$idtmp] = $data2;
         }
      }

      if (count($db_drives) == 0) {
         foreach ($a_inventory['drive'] as $a_drive) {
            $this->addDevice('DeviceDrive', $itemtype, $a_drive,
                             $items_id, $no_history);
         }
      } else {
         foreach ($a_inventory['drive'] as $key => $arrays) {
            $arrayslower = array_map('strtolower', $arrays);
            if ($arrayslower['serial'] == '') {
               foreach ($db_drives as $keydb => $arraydb) {
                  if ($arrayslower['designation'] == $arraydb['designation']) {
                     unset($a_inventory['drive'][$key]);
                     unset($db_drives[$keydb]);
                     break;
                  }
               }
            } else {
               foreach ($db_drives as $keydb => $arraydb) {
                  if ($arrayslower['serial'] == $arraydb['serial']) {
                     unset($a_inventory['drive'][$key]);
                     unset($db_drives[$keydb]);
                     break;
                  }
               }
            }
         }

         if (count($a_inventory['drive']) || count($db_drives)) {
            if (count($db_drives) != 0) {
               // Delete drive in DB
               foreach ($db_drives as $idtmp => $data) {
                  $item_DeviceDrive->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['drive']) != 0) {
               foreach ($a_inventory['drive'] as $a_drive) {
                  $this->addDevice('DeviceDrive', $itemtype, $a_drive,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }


   /**
    * Add a new device component
    *
    * @param string $device_type the class of the device to add
    * @param array $data
    * @param integer $$items_id
    * @param boolean $no_history
    * @return boolean true if the device is added, false if something went wrong
    */
   function addDevice($device_type, $itemtype, $data, $items_id, $no_history) {
      $device_class = 'Item_'.$device_type;
      $item_device = new $device_class();
      $device      = new $device_type();
      $fk_device   = getForeignKeyFieldForTable(getTableForItemType($device_type));
      $devices_id = $device->import($data);
      $data = [
         $fk_device   => $devices_id,
         'itemtype'   => $itemtype,
         'items_id'   => $items_id,
         'is_dynamic' => 1,
         '_no_history' => $no_history
      ];
      return $item_device->add($data, [], !$no_history);
   }

   /**
    * Import network cards
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importNetworkCards($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $db_networkcards = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicenetworkcards`.`id`, `designation`, `mac`,
                  `manufacturers_id`
                  FROM `glpi_items_devicenetworkcards`
               LEFT JOIN `glpi_devicenetworkcards`
                  ON `devicenetworkcards_id`=`glpi_devicenetworkcards`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['designation'])) {
               $data['designation'] = Toolbox::addslashes_deep($data['designation']);
            }
            $data['designation'] = trim(strtolower($data['designation']));
            $db_networkcards[$idtmp] = $data;
         }
      }

      if (count($db_networkcards) == 0) {
         foreach ($a_inventory['networkcard'] as $a_networkcard) {
            $this->addDevice('DeviceNetworkCard', $itemtype, $a_processor,
                             $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'mac'
         foreach ($a_inventory['networkcard'] as $key => $arrays) {
            $arrays['designation'] = strtolower($arrays['designation']);
            foreach ($db_networkcards as $keydb => $arraydb) {
               if ($arrays == $arraydb) {
                  unset($a_inventory['networkcard'][$key]);
                  unset($db_networkcards[$keydb]);
                  break;
               }
            }
         }

         if (count($a_inventory['networkcard']) || count($db_networkcards)) {
            if (count($db_networkcards) != 0) {
               // Delete networkcard in DB
               foreach ($db_networkcards as $idtmp => $data) {
                  $item_DeviceNetworkCard->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['networkcard']) != 0) {
               foreach ($a_inventory['networkcard'] as $a_networkcard) {
                  $this->addDevice('DeviceNetworkCard', $itemtype, $a_processor,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import controllers
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importControls($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $db_controls = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicecontrols`.`id`, `interfacetypes_id`,
                  `manufacturers_id`, `designation` FROM `glpi_items_devicecontrols`
               LEFT JOIN `glpi_devicecontrols` ON `devicecontrols_id`=`glpi_devicecontrols`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $data2 = array_map('strtolower', $data1);
            $db_controls[$idtmp] = $data2;
         }
      }

      if (count($db_controls) == 0) {
         foreach ($a_inventory['controller'] as $a_control) {
            $this->addDevice('DeviceControl', $itemtype, $a_control,
                             $items_id, $no_history);
         }
      } else {
         // Check all fields from source:
         foreach ($a_inventory['controller'] as $key => $arrays) {
            $arrayslower = array_map('strtolower', $arrays);
            foreach ($db_controls as $keydb => $arraydb) {
               if ($arrayslower == $arraydb) {
                  unset($a_inventory['controller'][$key]);
                  unset($db_controls[$keydb]);
                  break;
               }
            }
         }

         if (count($a_inventory['controller']) || count($db_controls)) {
            if (count($db_controls) != 0) {
               // Delete controller in DB
               foreach ($db_controls as $idtmp => $data) {
                  $item_DeviceControl->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['controller']) != 0) {
               foreach ($a_inventory['controller'] as $a_control) {
                  $this->addDevice('DeviceControl', $itemtype, $a_control,
                                   $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import soundcards
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importSoundcards($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $db_soundcards = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicesoundcards`.`id`, `designation`, `comment`,
                  `manufacturers_id` FROM `glpi_items_devicesoundcards`
               LEFT JOIN `glpi_devicesoundcards`
                  ON `devicesoundcards_id`=`glpi_devicesoundcards`.`id`
               WHERE `items_id` = '$items_id'
                  AND `itemtype`='$itemtype'
                  AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data1 = Toolbox::addslashes_deep($data);
            $db_soundcards[$idtmp] = $data1;
         }
      }

      if (count($db_soundcards) == 0) {
         foreach ($a_inventory['soundcard'] as $a_soundcard) {
            $this->addDevice('DeviceSoundcard', $itemtype,
                              $a_soundcard, $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'memory', 'manufacturers_id'
         foreach ($a_inventory['soundcard'] as $key => $arrays) {
            //               $arrayslower = array_map('strtolower', $arrays);
            $arrayslower = $arrays;
            foreach ($db_soundcards as $keydb => $arraydb) {
               if ($arrayslower == $arraydb) {
                  unset($a_inventory['soundcard'][$key]);
                  unset($db_soundcards[$keydb]);
                  break;
               }
            }
         }

         if (count($a_inventory['soundcard']) || count($db_soundcards)) {
            if (count($db_soundcards) != 0) {
               // Delete soundcard in DB
               foreach ($db_soundcards as $idtmp => $data) {
                  $item_DeviceSoundCard->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['soundcard']) != 0) {
               foreach ($a_inventory['soundcard'] as $a_soundcard) {
                  $this->addDevice('DeviceSoundcard', $itemtype,
                                    $a_soundcard, $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import bioses
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importBios($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $item_DeviceBios = new Item_DeviceFirmware();

      // * BIOS
      $db_bios = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicefirmwares`.`id`, `serial`,
               `designation`, `version`
               FROM `glpi_items_devicefirmwares`
                  LEFT JOIN `glpi_devicefirmwares`
                     ON `devicefirmwares_id`=`glpi_devicefirmwares`.`id`
            WHERE `items_id` = '$items_id'
               AND `itemtype`='$itemtype'
               AND `is_dynamic`='1'";
         $result = $DB->query($query);
         while ($data = $DB->fetch_assoc($result)) {
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
                           $items_id, $no_history);
         }
      } else {
         if (isset($a_inventory['bios'])) {
            $arrayslower = array_map('strtolower', $a_inventory['bios']);
            foreach ($db_bios as $keydb => $arraydb) {
               if (isset($arrayslower['version']) && $arrayslower['version'] == $arraydb['version']) {
                  unset($a_inventory['bios']);
                  unset($db_bios[$keydb]);
                  break;
               }
            }
         }

         if (count($db_bios) != 0) {
            // Delete BIOS in DB
            foreach ($db_bios as $idtmp => $data) {
               $item_DeviceBios->delete(['id'=>$idtmp], 1);
            }
         }

         if (isset($a_inventory['bios'])) {
            $this->addBios($itemtype, $a_inventory['bios'],
                           $items_id, $no_history);
         }
      }
   }

   /**
    * Add a new bios component
    *
    * @param array $data
    * @param integer $$items_id
    * @param boolean $no_history
    */
   function addBios($itemtype, $data, $items_id, $no_history) {
      $item_DeviceBios  = new Item_DeviceFirmware();
      $deviceBios       = new DeviceFirmware();

      $fwTypes = new DeviceFirmwareType();
      $fwTypes->getFromDBByQuery("WHERE `name` = 'BIOS'");
      $type_id = $fwTypes->getID();
      $data['devicefirmwaretypes_id'] = $type_id;

      $bios_id = $deviceBios->import($data);
      $data['devicefirmwares_id']   = $bios_id;
      $data['itemtype']             = $itemtype;
      $data['items_id']             = $items_id;
      $data['is_dynamic']           = 1;
      $data['_no_history']          = $no_history;
      $item_DeviceBios->add($data, [], !$no_history);
   }

   /**
    * Import graphic cards
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    *
    * @return void
    */
   function importGraphiccards($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $item_DeviceGraphicCard = new item_DeviceGraphicCard();
      $db_graphiccards        = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicegraphiccards`.`id`, `designation`, `memory`
                  FROM `glpi_items_devicegraphiccards`
               LEFT JOIN `glpi_devicegraphiccards`
                  ON `devicegraphiccards_id`=`glpi_devicegraphiccards`.`id`
               WHERE `items_id` = '$$items_id'
                  AND `itemtype`='Computer'
                  AND `is_dynamic`='1'";
         $result = $DB->query($query);
         while ($data = $DB->fetch_assoc($result)) {
            $idtmp = $data['id'];
            unset($data['id']);
            if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['designation'])) {
               $data['designation'] = Toolbox::addslashes_deep($data['designation']);
            }
            $data['designation'] = trim(strtolower($data['designation']));
            $db_graphiccards[$idtmp] = $data;
         }
      }

      if (count($db_graphiccards) == 0) {
         foreach ($a_inventory['graphiccard'] as $a_graphiccard) {
            $this->addDevice('DeviceGraphicCard', $itemtype,
                              $a_graphiccard, $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'memory'
         foreach ($a_inventory['graphiccard'] as $key => $arrays) {
            $arrays['designation'] = strtolower($arrays['designation']);
            foreach ($db_graphiccards as $keydb => $arraydb) {
               if ($arrays == $arraydb) {
                  unset($a_inventory['graphiccard'][$key]);
                  unset($db_graphiccards[$keydb]);
                  break;
               }
            }
         }

         if (count($a_inventory['graphiccard']) || count($db_graphiccards)) {
            if (count($db_graphiccards) != 0) {
               // Delete graphiccard in DB
               foreach ($db_graphiccards as $idtmp => $data) {
                  $item_DeviceGraphicCard->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($a_inventory['graphiccard']) != 0) {
               foreach ($a_inventory['graphiccard'] as $a_graphiccard) {
                  $this->addDevice('DeviceGraphicCard', $itemtype,
                                    $a_graphiccard, $items_id, $no_history);
               }
            }
         }
      }
   }

   /**
    * Import all standard devices
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Asset id
    * @param boolean $no_history should history be added in the logs
    * @param boolean $check_import check it there's a import option
    *
    * @return void
    */
   function importDevices($itemtype, $a_inventory, $items_id, $no_history,
                          $check_import = false) {
      $dbutil  = new DBUtils();
      $devices = [
         'processor', 'memory', 'harddrive', 'drive', 'networkcard',
         'control', 'soundcard', 'graphiccard', 'battery'
      ];
      foreach ($devices as $device) {
         if (!$check_import
            || ($check_import && $pfConfig->getValue("component_".$device) != 0)) {
            $method = 'import'.ucfirst($dbutil->getPlural($device));
            $this->$method($itemtype, $a_inventory, $items_id, $no_history);
         }
      }
   }

   function importBatteries($itemtype, $a_inventory, $items_id, $no_history) {
      global $DB;

      $item_DeviceBattery = new item_DeviceBattery();
      $db_batteries = [];
      if ($no_history === false) {
         $query = "SELECT `glpi_items_devicebatteries`.`id`, `serial`, `voltage`, `capacity`
                     FROM `glpi_items_devicebatteries`
                        LEFT JOIN `glpi_devicebatteries` ON `devicebatteries_id`=`glpi_devicebatteries`.`id`
                     WHERE `items_id` = '$items_id'
                        AND `itemtype`='$itemtype'
                        AND `is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data = Toolbox::addslashes_deep($data);
            $data = array_map('strtolower', $data);
            $db_batteries[$idtmp] = $data;
         }
      }

      if (count($db_batteries) == 0) {
         foreach ($a_inventory['batteries'] as $a_battery) {
            $this->addDevice('DeviceBattery', $itemtype, $a_battery,
                              $items_id, $no_history);
         }
      } else {
         // Check all fields from source: 'designation', 'serial', 'size',
         // 'devicebatterytypes_id', 'frequence'
         foreach ($a_inventory['batteries'] as $key => $arrays) {
            $arrayslower = array_map('strtolower', $arrays);
            foreach ($db_batteries as $keydb => $arraydb) {
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
                  unset($db_batteries[$keydb]);
                  break;
               }
            }

            //delete remaining batteries in database
            if (count($db_batteries) > 0) {
               // Delete battery in DB
               foreach ($db_batteries as $idtmp => $data) {
                  $item_DeviceBattery->delete(['id' => $idtmp], 1);
               }
            }

            //add new batteries in database
            if (count($a_inventory['batteries']) != 0) {
               foreach ($a_inventory['batteries'] as $a_battery) {
                  $this->addDevice('DeviceBattery', $itemtype, $a_battery,
                                    $items_id, $no_history);
               }
            }
         }
      }
   }
}
