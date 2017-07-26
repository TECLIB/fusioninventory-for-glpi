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
 * This file is used to manage the update of information into Phone in
 * GLPI.
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
 * Manage the update of information into phone in GLPI.
 */
class PluginFusioninventoryInventoryPhoneLib extends CommonDBTM {


   /**
    * Function to update Phone
    *
    * @global object $DB
    * @param array $a_inventory data fron agent inventory
    * @param integer $phones_id id of the Phone
    */
   function updatePhone($a_inventory, $phones_id) {
      global $DB;

      $phone   = new Phone();
      $pfPhone = new PluginFusioninventoryPhone();

      $phone->getFromDB($phones_id);

      if (!isset($_SESSION['glpiactiveentities_string'])) {
         $_SESSION['glpiactiveentities_string'] = $phone->fields['entities_id'];
      }
      if (!isset($_SESSION['glpiactiveentities'])) {
         $_SESSION['glpiactiveentities'] = array($phone->fields['entities_id']);
      }
      if (!isset($_SESSION['glpiactive_entity'])) {
         $_SESSION['glpiactive_entity'] = $phone->fields['entities_id'];
      }

      // * Phone
      $db_phone =  $phone->fields;

      $a_lockable = PluginFusioninventoryLock::getLockFields('glpi_phones', $phones_id);

      $a_ret = PluginFusioninventoryToolbox::checkLock($a_inventory['Phone'],
                                                       $db_phone, $a_lockable);
      $a_inventory['Phone'] = $a_ret[0];

      $input = $a_inventory['Phone'];

      $input['id'] = $phones_id;
      $phone->update($input);

      // * Phone fusion (ext)
      $db_phone = array();
      $query = "SELECT *
            FROM `".  getTableForItemType("PluginFusioninventoryPhone")."`
            WHERE `phones_id` = '$phones_id'";
      $result = $DB->query($query);
      while ($data = $DB->fetch_assoc($result)) {
         foreach ($data as $key=>$value) {
            $db_phone[$key] = Toolbox::addslashes_deep($value);
         }
      }
      if (count($db_phone) == '0') { // Add
         $a_inventory['PluginFusioninventoryPhone']['phones_id'] =
            $phones_id;
         $pfPhone->add($a_inventory['PluginFusioninventoryPhone']);
      } else { // Update
         $idtmp = $db_phone['id'];
         unset($db_phone['id']);
         unset($db_phone['phones_id']);
         unset($db_phone['plugin_fusioninventory_configsecurities_id']);

         $a_ret = PluginFusioninventoryToolbox::checkLock(
                     $a_inventory['PluginFusioninventoryPhone'],
                     $db_phone);
         $a_inventory['PluginFusioninventoryPhone'] = $a_ret[0];
         $input = $a_inventory['PluginFusioninventoryPhone'];
         $input['id'] = $idtmp;
         $pfPhone->update($input);
      }

      // * Ports
      $this->importPorts($a_inventory, $phones_id);
   }



   /**
    * Import ports
    *
    * @param array $a_inventory
    * @param integer $phones_id
    */
   function importPorts($a_inventory, $phones_id) {

      $networkPort   = new NetworkPort();
      $pfNetworkPort = new PluginFusioninventoryNetworkPort();

      $networkports_id = 0;
      foreach ($a_inventory['networkport'] as $a_port) {
         $a_ports_DB = current($networkPort->find(
                    "`itemtype`='Phone'
                       AND `items_id`='".$phones_id."'
                       AND `instantiation_type`='NetworkPortEthernet'
                       AND `logical_number` = '".$a_port['logical_number']."'", '', 1));
         if (!isset($a_ports_DB['id'])) {
            // Add port
            $a_port['instantiation_type'] = 'NetworkPortEthernet';
            $a_port['items_id'] = $phones_id;
            $a_port['itemtype'] = 'Phone';
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
}
