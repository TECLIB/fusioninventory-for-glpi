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
 * This file is used to manage the update / add information of computer
 * inventory into GLPI database.
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
 * Manage the update / add information of computer inventory into GLPI database.
 */
class PluginFusioninventoryInventoryComputerLib extends PluginFusioninventoryInventoryCommon {

   /**
    * Initialize the list of software
    *
    * @var array
    */
   var $softList = [];

   /**
    * Initialize the list of software versions
    *
    * @var array
    */
   var $softVersionList = [];

   /**
    * Initilize the list of logs to add in the database
    *
    * @var array
    */
   var $log_add = [];


   /**
    * Initilize a list of installation that should not be logged
    *
    * @var array
    */
   var $installationWithoutLogs = array();

   /**
    * __contruct function where initialize many variables
    */
   function __construct() {
      $this->software                = new Software();
      $this->softwareVersion         = new SoftwareVersion();
      $this->computerSoftwareVersion = new Computer_SoftwareVersion();
      $this->softcatrule             = new RuleSoftwareCategoryCollection();
      $this->computer                = new Computer();
   }


   /**
    * Update computer data
    *
    * @global object $DB
    * @global array $CFG_GLPI
    * @param array $a_computerinventory all data from the agent
    * @param integer $computers_id id of the computer
    * @param boolean $no_history set true if not want history
    * @param integer $setdynamic
    */
   function updateComputer($a_computerinventory, $computers_id, $no_history, $setdynamic = 0) {
      global $DB, $CFG_GLPI;

      $computer                     = new Computer();
      $pfInventoryComputerComputer  = new PluginFusioninventoryInventoryComputerComputer();
      $pfConfig                     = new PluginFusioninventoryConfig();

      $computer->getFromDB($computers_id);

      $a_lockable  = PluginFusioninventoryLock::getLockFields('glpi_computers', $computers_id);
      $entities_id = $_SESSION["plugin_fusioninventory_entity"];


      // * Computer
      $db_computer = $computer->fields;
      $computerName = $computer->fields['name'];
      $a_ret = PluginFusioninventoryToolbox::checkLock($a_computerinventory['Computer'],
                                                         $db_computer, $a_lockable);
      $a_computerinventory['Computer'] = $a_ret[0];

      $input = $a_computerinventory['Computer'];

      $input['id'] = $computers_id;
      $history     = true;
      if ($no_history) {
         $history = false;
      }
      $input['_no_history'] = $no_history;
      if (!in_array('states_id', $a_lockable)) {
         $input = PluginFusioninventoryToolbox::addDefaultStateIfNeeded('computer', $input);
      }
      $computer->update($input, !$no_history);

      $this->computer = $computer;

      // * Computer fusion (ext)
      $db_computer = [];
      if ($no_history === false) {
         $query = "SELECT * FROM `glpi_plugin_fusioninventory_inventorycomputercomputers`
                WHERE `computers_id` = '$computers_id'
                LIMIT 1";
         $result = $DB->query($query);
         while ($data = $DB->fetch_assoc($result)) {
            foreach ($data as $key=>$value) {
               $data[$key] = Toolbox::addslashes_deep($value);
            }
            $db_computer = $data;
         }
      }

      if (count($db_computer) == '0') { // Add
         $a_computerinventory['fusioninventorycomputer']['computers_id'] = $computers_id;
         $pfInventoryComputerComputer->add($a_computerinventory['fusioninventorycomputer'],
                                        [], false);
      } else { // Update
         if (!empty($db_computer['serialized_inventory'])) {
            $setdynamic = 0;
         }
         $idtmp = $db_computer['id'];
         unset($db_computer['id']);
         unset($db_computer['computers_id']);
         $a_ret = PluginFusioninventoryToolbox::checkLock(
                                    $a_computerinventory['fusioninventorycomputer'],
                                    $db_computer);
         $a_computerinventory['fusioninventorycomputer'] = $a_ret[0];
         $db_computer          = $a_ret[1];
         $input                = $a_computerinventory['fusioninventorycomputer'];
         $input['id']          = $idtmp;
         $input['_no_history'] = $no_history;
         $pfInventoryComputerComputer->update($input, !$no_history);
      }

      // Put all link item dynamic (in case of update computer not yet inventoried with fusion)
      if ($setdynamic == 1) {
         $this->setDynamicLinkItems($computers_id);
      }

      $params = [
         'a_inventory'      => $a_computerinventory,
         'import_itemtype'  => 'Computer',
         'items_id'         => $computers_id,
         'entities_id'      => $entities_id,
         'pfConfig'         => $pfConfig,
         'no_history'       => $no_history,
         'name'             => $computer->getName()
      ];

      $other_items = [
         'PluginFusioninventoryImportOperatingSystem',
         'PluginFusioninventoryImportSoftware',
         'PluginFusioninventoryImportNetworkPort',
         'PluginFusioninventoryImportVirtualMachine',
         'PluginFusioninventoryImportMonitor',
         'PluginFusioninventoryImportPrinter',
         'PluginFusioninventoryImportPeripheral',
         'PluginFusioninventoryImportItemDisk',
         'PluginFusioninventoryImportDeviceProcessor',
         'PluginFusioninventoryImportDeviceBattery',
         'PluginFusioninventoryImportDeviceMemory',
         'PluginFusioninventoryImportDeviceFirmware',
         'PluginFusioninventoryImportDeviceBios',
         'PluginFusioninventoryImportDeviceDrive',
         'PluginFusioninventoryImportDeviceHardDrive',
         'PluginFusioninventoryImportDeviceSimcard',
         'PluginFusioninventoryImportDeviceBattery',
         'PluginFusioninventoryImportDeviceControl',
         'PluginFusioninventoryImportDeviceGraphicCard',
         'PluginFusioninventoryImportDeviceSoundCard',
         'PluginFusioninventoryImportDeviceNetworkCard',
         'PluginFusioninventoryImportComputerRemoteManagement',
         'PluginFusioninventoryImportComputerLicenseInfo',
         'PluginFusioninventoryImportComputerAntivirus',
      ];

      foreach ($other_items as $item) {
         $itemInstance = new $item($params);
         $itemInstance->importItem();
      }

      Plugin::doHook("fusioninventory_inventory",
                     ['inventory_data' => $a_computerinventory,
                      'computers_id'   => $computers_id,
                      'no_history'     => $no_history
                     ]);
   }

   /**
    * Define items link to computer in dynamic mode
    *
    * @global object $DB
    * @param integer $computers_id
    */
   function setDynamicLinkItems($computers_id) {
      global $DB;

      $computer = new Computer();
      $input = ['id' => $computers_id];
      $input = PluginFusioninventoryToolbox::addDefaultStateIfNeeded('computer', $input);
      $computer->update($input);

      $a_tables = [
         'glpi_computerdisks',
         'glpi_computers_items',
         'glpi_computers_softwareversions',
         'glpi_computervirtualmachines'
      ];
      foreach ($a_tables as $table) {
         $DB->update(
            $table, [
               'is_dynamic' => 1
            ], [
               'computers_id' => $computers_id
            ]
         );
      }

      $a_tables = ["glpi_networkports", "glpi_items_devicecases", "glpi_items_devicecontrols",
                   "glpi_items_devicedrives", "glpi_items_devicegraphiccards",
                   "glpi_items_deviceharddrives", "glpi_items_devicememories",
                   "glpi_items_devicemotherboards", "glpi_items_devicenetworkcards",
                   "glpi_items_devicepcis", "glpi_items_devicepowersupplies",
                   "glpi_items_deviceprocessors", "glpi_items_devicesoundcards"];

      foreach ($a_tables as $table) {
         $DB->update(
            $table, [
               'is_dynamic'   => 1,
            ], [
               'itemtype'  => 'Computer',
               'items_id'  => $computers_id
            ]
         );
      }
   }
}
