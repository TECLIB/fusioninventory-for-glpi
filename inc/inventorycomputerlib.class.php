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
      $history = true;
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

      // * Software
      if ($pfConfig->getValue("import_software") != 0) {
         $this->importSoftware('Computer', $a_computerinventory,
                               $computer, $no_history);
      }

      $other_items = [
         'PluginFusioninventoryImportOperatingSystem',
         'PluginFusioninventoryImportNetworkPort',
         'PluginFusioninventoryImportVirtualMachine',
         'PluginFusioninventoryImportMonitor',
         'PluginFusioninventoryImportPrinter',
         'PluginFusioninventoryImportPeripheral',
         'PluginFusioninventoryImportComputerDisk',
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

      $this->addLog();
   }


   /**
    * Load softwares from database that are matching softwares coming from the
    * currently processed inventory
    *
    * @global object $DB
    * @param integer $entities_id entitity id
    * @param array $a_soft list of software from the agent inventory
    * @param integer $lastid last id search to not search from beginning
    * @return integer last software id
    */
   function loadSoftwares($entities_id, $a_soft, $lastid = 0) {
      global $DB;

      $whereid = '';
      if ($lastid > 0) {
         $whereid .= ' AND `id` > "'.$lastid.'"';
      }
      $a_softSearch = [];
      $nbSoft = 0;
      if (count($this->softList) == 0) {
         foreach ($a_soft as $a_software) {
            $a_softSearch[] = "'".$a_software['name']."$$$$".$a_software['manufacturers_id']."'";
            $nbSoft++;
         }
      } else {
         foreach ($a_soft as $a_software) {
            if (!isset($this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']])) {
               $a_softSearch[] = "'".$a_software['name']."$$$$".$a_software['manufacturers_id']."'";
               $nbSoft++;
            }
         }
      }
      $whereid .= " AND CONCAT_WS('$$$$', `name`, `manufacturers_id`) IN (".implode(",", $a_softSearch).")";

      $sql     = "SELECT max( id ) AS max FROM `glpi_softwares`";
      $result  = $DB->query($sql);
      $data    = $DB->fetch_assoc($result);
      $lastid  = $data['max'];
      $whereid.= " AND `id` <= '".$lastid."'";
      if ($nbSoft == 0) {
         return $lastid;
      }

      $sql = "SELECT `id`, `name`, `manufacturers_id`
              FROM `glpi_softwares`
              WHERE `entities_id`='".$entities_id."'".$whereid;
      foreach ($DB->request($sql) as $data) {
         $this->softList[Toolbox::addslashes_deep($data['name'])."$$$$".$data['manufacturers_id']] = $data['id'];
      }
      return $lastid;
   }


   /**
    * Load software versions from DB are in the incomming inventory
    *
    * @global object $DB
    * @param integer $entities_id entitity id
    * @param array $a_softVersion list of software versions from the agent inventory
    * @param integer $lastid last id search to not search from beginning
    * @return integer last software version id
    */
   function loadSoftwareVersions($entities_id, $a_softVersion, $lastid = 0) {
      global $DB;

      $whereid = '';
      if ($lastid > 0) {
         $whereid .= ' AND `id` > "'.$lastid.'"';
      }
      $arr = [];
      $a_versions = [];
      foreach ($a_softVersion as $a_software) {
         $softwares_id = $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']];
         if (!isset($this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']])) {
            $a_versions[$a_software['version']][] = $softwares_id;
         }
      }

      $nbVersions = 0;
      foreach ($a_versions as $name=>$a_softwares_id) {
         foreach ($a_softwares_id as $softwares_id) {
            $arr[] = "'".$name."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']."'";
         }
         $nbVersions++;
      }
      $whereid .= " AND CONCAT_WS('$$$$', `name`, `softwares_id`, `operatingsystems_id`) IN ( ";
      $whereid .= implode(',', $arr);
      $whereid .= " ) ";

      $sql = "SELECT max( id ) AS max FROM `glpi_softwareversions`";
      $result = $DB->query($sql);
      $data = $DB->fetch_assoc($result);
      $lastid = $data['max'];
      $whereid .= " AND `id` <= '".$lastid."'";

      if ($nbVersions == 0) {
         return $lastid;
      }

      $sql = "SELECT `id`, `name`, `softwares_id`, `operatingsystems_id` FROM `glpi_softwareversions`
      WHERE `entities_id`='".$entities_id."'".$whereid;
      $result = $DB->query($sql);
      while ($data = $DB->fetch_assoc($result)) {
         $this->softVersionList[strtolower($data['name'])."$$$$".$data['softwares_id']."$$$$".$data['operatingsystems_id']] = $data['id'];
      }

      return $lastid;
   }


   /**
    * Add a new software
    *
    * @param array $a_software
    * @param array $options
    */
   function addSoftware($a_software, $options) {
      $a_softwares_id = $this->software->add($a_software, $options, false);
      $this->addPrepareLog($a_softwares_id, 'Software');

      $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']] = $a_softwares_id;
   }


   /**
    * Add a software version
    *
    * @param array $a_software
    * @param integer $softwares_id
    */
   function addSoftwareVersion($a_software, $softwares_id) {

      $options = [];
      $options['disable_unicity_check'] = true;

      $a_software['name']         = $a_software['version'];
      $a_software['softwares_id'] = $softwares_id;
      $a_software['_no_history']  = true;
      $softwareversions_id = $this->softwareVersion->add($a_software, $options, false);
      $this->addPrepareLog($softwareversions_id, 'SoftwareVersion');
      $this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']] = $softwareversions_id;
   }


   /**
    * Link software versions with the computer
    *
    * @global object $DB
    * @param array $a_input
    */
   function addSoftwareVersionsComputer($a_input) {
      global $DB;

      $insert_query = $DB->buildInsert(
         'glpi_computers_softwareversions', [
            'computers_id'          => new \QueryParam(),
            'softwareversions_id'   => new \QueryParam(),
            'is_dynamic'            => new \QueryParam(),
            'entities_id'           => new \QueryParam(),
            'date_install'          => new \QueryParam()
         ]
      );
      $stmt = $DB->prepare($insert_query);

      foreach ($a_input as $input) {
         $stmt->bind_param(
            'sssss',
            $input['computers_id'],
            $input['softwareversions_id'],
            $input['is_dynamic'],
            $input['entities_id'],
            $input['date_install']
         );
         $stmt->execute();
      }
      mysqli_stmt_close($stmt);
   }


   /**
    * Link software version with the computer
    *
    * @param array $a_software
    * @param integer $computers_id
    * @param boolean $no_history
    * @param array $options
    */
   function addSoftwareVersionComputer($a_software, $computers_id, $no_history, $options) {

      $options['disable_unicity_check'] = true;

      $softwares_id = $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']];
      $softwareversions_id = $this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']];

      $this->softwareVersion->getFromDB($softwareversions_id);
      $a_software['computers_id']         = $computers_id;
      $a_software['softwareversions_id']  = $softwareversions_id;
      $a_software['is_dynamic']           = 1;
      $a_software['is_template_computer'] = false;
      $a_software['is_deleted_computer']  = false;
      $a_software['_no_history']          = true;
      $a_software['entities_id']          = $computers_id['entities_id'];

      //Check if historical has been disabled for this software only
      $comp_key = strtolower($a_software['name']).
                   PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.strtolower($a_software['version']).
                   PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['manufacturers_id'].
                   PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['entities_id'].
                   PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['operatingsystems_id'];
      if (isset($a_software['no_history']) && $a_software['no_history']) {
         $no_history_for_this_software = true;
      } else {
         $no_history_for_this_software = false;
      }

      if ($this->computerSoftwareVersion->add($a_software, $options, FALSE)) {
         if (!$no_history && !$no_history_for_this_software) {
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->computerSoftwareVersion->getHistoryNameForItem1($this->softwareVersion, 'add'));
            $this->addPrepareLog($computers_id, 'Computer', 'SoftwareVersion', $changes,
                         Log::HISTORY_INSTALL_SOFTWARE);

            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->computerSoftwareVersion->getHistoryNameForItem2($this->computer, 'add'));
            $this->addPrepareLog($softwareversions_id, 'SoftwareVersion', 'Computer', $changes,
                         Log::HISTORY_INSTALL_SOFTWARE);
         }
      }
   }


   /**
    * Arraydiff function to have real diff between 2 arrays
    *
    * @param array $arrayFrom
    * @param array $arrayAgainst
    * @return array
    */
   function arrayDiffEmulation($arrayFrom, $arrayAgainst) {
      $arrayAgainsttmp = [];
      foreach ($arrayAgainst as $key => $data) {
         $arrayAgainsttmp[serialize($data)] = $key;
      }

      foreach ($arrayFrom as $key => $value) {
         if (isset($arrayAgainsttmp[serialize($value)])) {
            unset($arrayFrom[$key]);
         }
      }
      return $arrayFrom;
   }


   /**
    * Prepare add history in database
    *
    * @param integer $items_id
    * @param string $itemtype
    * @param string $itemtype_link
    * @param array $changes
    * @param integer $linked_action
    */
   function addPrepareLog($items_id, $itemtype, $itemtype_link = '', $changes = ['0', '', ''], $linked_action = Log::HISTORY_CREATE_ITEM) {
      $this->log_add[] = [$items_id, $itemtype, $itemtype_link, $_SESSION["glpi_currenttime"], $changes, $linked_action];
   }


   /**
    * Insert logs are in queue
    *
    * @global object $DB
    */
   function addLog() {
      global $DB;

      if (count($this->log_add) > 0) {
         $qparam = new \QueryParam();
         $stmt = $DB->prepare(
            $DB->buildInsert(
               'glpi_logs', [
                  'items_id'           => $qparam,
                  'itemtype'           => $qparam,
                  'itemtype_link'      => $qparam,
                  'date_mod'           => $qparam,
                  'linked_action'      => $qparam,
                  'id_search_option'   => $qparam,
                  'old_value'          => $qparam,
                  'new_value'          => $qparam,
                  'user_name'          => $qparam
               ]
            )
         );
         $username = addslashes($_SESSION["glpiname"]);

         foreach ($this->log_add as $data) {
            $changes = $data[4];
            unset($data[4]);
            $data = array_values($data);
            $id_search_option = $changes[0];
            $old_value = $changes[1];
            $new_value = $changes[2];

            $stmt->bind_param(
               'sssssssss',
               $data[0],
               $data[1],
               $data[2],
               $data[3],
               $data[4],
               $id_search_option,
               $old_value,
               $new_value,
               $username
            );
            $stmt->execute();
         }

         $this->log_add = [];
      }
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

   /**
    * Import software
    * @since 9.2+2.0
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer asset id
    *
    * @return void
    */
   function importSoftware($itemtype, $a_inventory, $computer, $no_history) {
      global $DB;

      //By default entity  = root
      $entities_id  = 0;
      $computers_id = $computer->getID();

      //Try to guess the entity of the software
      if (count($a_inventory['software']) > 0) {
         //Get the first software of the list
         $a_softfirst = current($a_inventory['software']);
         //Get the entity of the first software : this info has been processed
         //in formatconvert, so it's either the computer's entity or
         //the entity as defined in the entity's configuration
         if (isset($a_softfirst['entities_id'])) {
            $entities_id = $a_softfirst['entities_id'];
         }
      }
      $db_software = [];

      //If we must take care of historical : it means we're not :
      //- at computer first inventory
      //- during the first inventory after an OS upgrade/change
      if ($no_history === false) {
         $query = "SELECT `glpi_computers_softwareversions`.`id` as sid,
                    `glpi_softwares`.`name`,
                    `glpi_softwareversions`.`name` AS version,
                    `glpi_softwares`.`manufacturers_id`,
                    `glpi_softwareversions`.`entities_id`,
                    `glpi_softwareversions`.`operatingsystems_id`,
                    `glpi_computers_softwareversions`.`is_template_computer`,
                    `glpi_computers_softwareversions`.`is_deleted_computer`
             FROM `glpi_computers_softwareversions`
             LEFT JOIN `glpi_softwareversions`
                  ON (`glpi_computers_softwareversions`.`softwareversions_id`
                        = `glpi_softwareversions`.`id`)
             LEFT JOIN `glpi_softwares`
                  ON (`glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`)
             WHERE `glpi_computers_softwareversions`.`computers_id` = '$computers_id'
               AND `glpi_computers_softwareversions`.`is_dynamic`='1'";
         foreach ($DB->request($query) as $data) {
            $idtmp = $data['sid'];
            unset($data['sid']);
            //Escape software name if needed
            if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['name'])) {
               $data['name'] = Toolbox::addslashes_deep($data['name']);
            }
            //Escape software version if needed
            if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['version'])) {
               $data['version'] = Toolbox::addslashes_deep($data['version']);
            }
            $comp_key = strtolower($data['name']).
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.strtolower($data['version']).
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$data['manufacturers_id'].
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$data['entities_id'].
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$data['operatingsystems_id'];
            $db_software[$comp_key] = $idtmp;
         }
      }


      $lastSoftwareid  = 0;
      $lastSoftwareVid = 0;

      /*
      * Schema
      *
      * LOCK software
      * 1/ Add all software
      * RELEASE software
      *
      * LOCK softwareversion
      * 2/ Add all software versions
      * RELEASE softwareversion
      *
      * 3/ add version to computer
      *
      */

      if (count($db_software) == 0) { // there are no software associated with computer
         $nb_unicity = count(FieldUnicity::getUnicityFieldsConfig("Software", $entities_id));
         $options    = [];
         //There's no unicity rules, do not enable this feature
         if ($nb_unicity == 0) {
            $options['disable_unicity_check'] = TRUE;
         }

         $lastSoftwareid = $this->loadSoftwares($entities_id,
                                                $a_inventory['software'],
                                                $lastSoftwareid);

         //-----------------------------------
         //Step 1 : import softwares
         //-----------------------------------
         //Put a lock during software import for this computer
         $queryDBLOCK = $DB->buildInsert(
            'glpi_plugin_fusioninventory_dblocksoftwares', [
            'value' => 1
            ]
         );
         $CFG_GLPI["use_log_in_files"] = false;
         //If the lock is already in place : another software import is running
         //loop until lock release!
         while (!$DB->query($queryDBLOCK)) {
            usleep(100000);
         }
         $CFG_GLPI["use_log_in_files"] = TRUE;
         $this->loadSoftwares($entities_id,
                              $a_inventory['software'],
                              $lastSoftwareid);

         //Browse softwares: add new software in database
         foreach ($a_inventory['software'] as $a_software) {
            if (!isset($this->softList[$a_software['name']."$$$$".
                     $a_software['manufacturers_id']])) {
               $this->addSoftware($a_software, $options);
            }
         }

         //Release the lock
         $DB->delete(
             'glpi_plugin_fusioninventory_dblocksoftwares', [
                 'value' => 1
             ]
           );

         //-----------------------------------
         //Step 2 : import software versions
         //-----------------------------------
         $lastSoftwareVid = $this->loadSoftwareVersions($entities_id,
                                        $a_inventory['software'],
                                        $lastSoftwareVid);
         $queryDBLOCK = $DB->buildInsert(
             'glpi_plugin_fusioninventory_dblocksoftwareversions', [
             'value' => 1
             ]
         );
         $CFG_GLPI["use_log_in_files"] = false;
         while (!$DB->query($queryDBLOCK)) {
            usleep(100000);
         }
         $CFG_GLPI["use_log_in_files"] = TRUE;
         $this->loadSoftwareVersions($entities_id,
                                     $a_inventory['software'],
                                     $lastSoftwareVid);
         foreach ($a_inventory['software'] as $a_software) {
            $softwares_id = $this->softList[$a_software['name']
               .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['manufacturers_id']];
            if (!isset($this->softVersionList[strtolower($a_software['version'])
            .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$softwares_id
            .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['operatingsystems_id']])) {
               $this->addSoftwareVersion($a_software, $softwares_id);
            }
         }
         $DB->delete(
               'glpi_plugin_fusioninventory_dblocksoftwareversions', [
                  'value' => 1
               ]
         );
         $a_toinsert = [];
         foreach ($a_inventory['software'] as $a_software) {
            $softwares_id = $this->softList[$a_software['name']
               .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['manufacturers_id']];
            $softwareversions_id = $this->softVersionList[strtolower($a_software['version'])
               .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$softwares_id
               .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['operatingsystems_id']];
            $a_tmp = array(
                'computers_id'        => $computers_id,
                'softwareversions_id' => $softwareversions_id,
                'is_dynamic'          => 1,
                'entities_id'         => $computer->fields['entities_id'],
                'date_install'        => null
                );
            //By default date_install is null: if an install date is provided,
            //we set it
            if (isset($a_software['date_install'])) {
               $a_tmp['date_install'] = $a_software['date_install'];
            }
            $a_toinsert[] = $a_tmp;
         }
         if (count($a_toinsert) > 0) {
            $this->addSoftwareVersionsComputer($a_toinsert);

            //Check if historical has been disabled for this software only
            $comp_key = strtolower($a_software['name']).
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.strtolower($a_software['version']).
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['manufacturers_id'].
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['entities_id'].
                         PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['operatingsystems_id'];
            if (isset($a_software['no_history']) && $a_software['no_history']) {
               $no_history_for_this_software = true;
            } else {
               $no_history_for_this_software = false;
            }

            if (!$no_history && !$no_history_for_this_software) {
               foreach ($a_inventory['software'] as $a_software) {
                  $softwares_id = $this->softList[$a_software['name']
                     .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['manufacturers_id']];
                  $softwareversions_id = $this->softVersionList[strtolower($a_software['version'])
                     .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$softwares_id
                     .PluginFusioninventoryFormatconvert::FI_SOFTWARE_SEPARATOR.$a_software['operatingsystems_id']];

                  $changes[0] = '0';
                  $changes[1] = "";
                  $changes[2] = $a_software['name']." - ".
                          sprintf(__('%1$s (%2$s)'), $a_software['version'], $softwareversions_id);
                  $this->addPrepareLog($computers_id, 'Computer', 'SoftwareVersion', $changes,
                               Log::HISTORY_INSTALL_SOFTWARE);

                  $changes[0] = '0';
                  $changes[1] = "";
                  $changes[2] = sprintf(__('%1$s (%2$s)'), $computer->getName(), $computers_id);
                  $this->addPrepareLog($softwareversions_id, 'SoftwareVersion', 'Computer', $changes,
                               Log::HISTORY_INSTALL_SOFTWARE);
               }
            }
         }

      } else {

         //It's not the first inventory, or not an OS change/upgrade

         //Do software migration first if needed
         $a_inventory = $this->migratePlatformForVersion($a_inventory, $db_software);

         //If software exists in DB, do not process it
         foreach ($a_inventory['software'] as $key => $arrayslower) {
            //Software installation already exists for this computer ?
            if (isset($db_software[$key])) {
               //It exists: remove the software from the key
               unset($a_inventory['software'][$key]);
               unset($db_software[$key]);
            }
         }

         if (count($a_inventory['software']) == 0
            && count($db_software) == 0) {
            // Nothing to do
         } else {
            if (count($db_software) > 0) {
               // Delete softwares in DB
               foreach ($db_software as $idtmp) {

                  if (isset($this->installationWithoutLogs[$idtmp])) {
                     $no_history_for_this_software = true;
                  } else {
                     $no_history_for_this_software = false;
                  }
                  $this->computerSoftwareVersion->getFromDB($idtmp);
                  $this->softwareVersion->getFromDB($this->computerSoftwareVersion->fields['softwareversions_id']);

                  if (!$no_history && !$no_history_for_this_software) {
                     $changes[0] = '0';
                     $changes[1] = addslashes($this->computerSoftwareVersion->getHistoryNameForItem1($this->softwareVersion, 'delete'));
                     $changes[2] = "";
                     $this->addPrepareLog($computers_id, 'Computer', 'SoftwareVersion', $changes,
                                  Log::HISTORY_UNINSTALL_SOFTWARE);

                     $changes[0] = '0';
                     $changes[1] = sprintf(__('%1$s (%2$s)'), $computer->getName(), $computers_id);
                     $changes[2] = "";
                     $this->addPrepareLog($idtmp, 'SoftwareVersion', 'Computer', $changes,
                                  Log::HISTORY_UNINSTALL_SOFTWARE);
                  }
               }
               $DB->delete(
                  'glpi_computers_softwareversions', [
                  'id' => $db_software
                  ]
               );
            }
            if (count($a_inventory['software']) > 0) {
               $nb_unicity = count(FieldUnicity::getUnicityFieldsConfig("Software",
                                                                        $entities_id));
               $options = [];
               if ($nb_unicity == 0) {
                  $options['disable_unicity_check'] = TRUE;
               }
               $lastSoftwareid = $this->loadSoftwares($entities_id, $a_inventory['software'], $lastSoftwareid);
               $queryDBLOCK = $DB->buildInsert(
                  'glpi_plugin_fusioninventory_dblocksoftwares', [
                  'value' => 1
                  ]
               );
               $CFG_GLPI["use_log_in_files"] = false;
               while (!$DB->query($queryDBLOCK)) {
                  usleep(100000);
               }
               $CFG_GLPI["use_log_in_files"] = TRUE;
               $this->loadSoftwares($entities_id, $a_inventory['software'], $lastSoftwareid);
               foreach ($a_inventory['software'] as $a_software) {
                  if (!isset($this->softList[$a_software['name']."$$$$".
                           $a_software['manufacturers_id']])) {
                     $this->addSoftware($a_software,
                                        $options);
                  }
               }
               $DB->delete(
                  'glpi_plugin_fusioninventory_dblocksoftwares', [
                  'value' => 1
                  ]
               );

               $lastSoftwareVid = $this->loadSoftwareVersions($entities_id,
                                              $a_inventory['software'],
                                              $lastSoftwareVid);
               $queryDBLOCK = $DB->buildInsert(
                  'glpi_plugin_fusioninventory_dblocksoftwareversions', [
                  'value' => 1
                  ]
               );
               $CFG_GLPI["use_log_in_files"] = false;
               while (!$DB->query($queryDBLOCK)) {
                  usleep(100000);
               }
               $CFG_GLPI["use_log_in_files"] = TRUE;
               $this->loadSoftwareVersions($entities_id,
                                           $a_inventory['software'],
                                           $lastSoftwareVid);
               foreach ($a_inventory['software'] as $a_software) {
                  $softwares_id = $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']];
                  if (!isset($this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']])) {
                     $this->addSoftwareVersion($a_software, $softwares_id);
                  }
               }
               $DB->delete(
                  'glpi_plugin_fusioninventory_dblocksoftwareversions', [
                  'value' => 1
                  ]
               );
               $a_toinsert = [];
               foreach ($a_inventory['software'] as $key => $a_software) {
                  //Check if historical has been disabled for this software only
                  if (isset($a_software['no_history']) && $a_software['no_history']) {
                     $no_history_for_this_software = true;
                  } else {
                     $no_history_for_this_software = false;
                  }
                  $softwares_id = $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']];
                  $softwareversions_id = $this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']];
                  $a_tmp = [
                     'computers_id'        => $computers_id,
                     'softwareversions_id' => $softwareversions_id,
                     'is_dynamic'          => 1,
                     'entities_id'         => $computer->fields['entities_id'],
                     'date_install'        => 'NULL'
                  ];
                  if (isset($a_software['date_install'])) {
                     $a_tmp['date_install'] = $a_software['date_install'];
                  }
                  $a_toinsert[] = $a_tmp;
               }
               $this->addSoftwareVersionsComputer($a_toinsert);

               if (!$no_history && !$no_history_for_this_software) {
                  foreach ($a_inventory['software'] as $a_software) {
                     $softwares_id = $this->softList[$a_software['name']."$$$$".$a_software['manufacturers_id']];
                     $softwareversions_id = $this->softVersionList[strtolower($a_software['version'])."$$$$".$softwares_id."$$$$".$a_software['operatingsystems_id']];

                     $changes[0] = '0';
                     $changes[1] = "";
                     $changes[2] = $a_software['name']." - ".
                           sprintf(__('%1$s (%2$s)'), $a_software['version'], $softwareversions_id);
                     $this->addPrepareLog($computers_id, 'Computer', 'SoftwareVersion', $changes,
                                  Log::HISTORY_INSTALL_SOFTWARE);

                     $changes[0] = '0';
                     $changes[1] = "";
                     $changes[2] = sprintf(__('%1$s (%2$s)'), $computer->getName(), $computers_id);
                     $this->addPrepareLog($softwareversions_id, 'SoftwareVersion', 'Computer', $changes,
                                  Log::HISTORY_INSTALL_SOFTWARE);
                  }
               }
            }
         }
      }
   }
