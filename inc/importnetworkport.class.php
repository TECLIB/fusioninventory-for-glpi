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
class PluginFusioninventoryImportNetworkPort extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory;

   protected $device_itemtype = 'NetworkPort';

   //Store the key related to the
   protected $section = 'networkport';

   //Itemtype of the asset we're imported
   protected $import_itemtype = '';

   //Asset ID
   protected $items_id = 0;

   function transformItem() {
   }

   function canImport() {
      return ($this->pfConfig->getValue("component_networkcard") != 0);
   }
   /**
    * Import ports
    *
    * @param array $a_inventory
    * @param integer $items_id
    */
   function importItem() {
      $networkPort     = new NetworkPort();
      $pfNetworkPort   = new PluginFusioninventoryNetworkPort();
      $networkports_id = 0;

      foreach ($this->a_inventory[$this->section] as $a_port) {
         $a_ports_DB = current($networkPort->find(
                    "`itemtype`='".$this->import_itemtype."'
                    AND `items_id`='".$this->items_id."'
                       AND `instantiation_type`='NetworkPortEthernet'
                       AND `logical_number` = '".$a_port['logical_number']."'", '', 1));
         if (!isset($a_ports_DB['id'])) {
            // Add port
            $a_port['instantiation_type'] = 'NetworkPortEthernet';
            $a_port['items_id'] = $this->items_id;
            $a_port['itemtype'] = $this->import_itemtype;
            $networkports_id    = $networkPort->add($a_port);
            unset($a_port['id']);
            $a_pfnetworkport_DB = current($pfNetworkPort->find(
                    "`networkports_id`='".$networkports_id."'", '', 1));
            $a_port['id'] = $a_pfnetworkport_DB['id'];
            $pfNetworkPort->update($a_port);
         } else {
            // Update port
            $networkports_id = $a_ports_DB['id'];
            $a_port['id']    = $a_ports_DB['id'];
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
      $this->manageNetworkPort();
   }

   /**
    * Manage network ports
    *
    * @global object $DB
    * @param boolean $this->no_history
    */
   function manageNetworkPort() {
      global $DB;

      $networkPort            = new NetworkPort();
      $networkName            = new NetworkName();
      $iPAddress              = new IPAddress();
      $iPNetwork              = new IPNetwork();
      $item_DeviceNetworkCard = new Item_DeviceNetworkCard();

      foreach ($this->a_inventory[$this->section] as $a_networkport) {
         if ($a_networkport['mac'] != '') {
            $a_networkports = $networkPort->find("`mac`='".$a_networkport['mac']."'
               AND `itemtype`='PluginFusioninventoryUnmanaged'", "", 1);
            if (count($a_networkports) > 0) {
               $input                   = current($a_networkports);
               $unmanageds_id           = $input['items_id'];
               $input['logical_number'] = $a_networkport['logical_number'];
               $input['itemtype']       = $this->import_itemtype;
               $input['items_id']       = $this->items_id;
               $input['is_dynamic']     = 1;
               $input['name']           = $a_networkport['name'];
               $networkPort->update($input, !$this->no_history);
               $pfUnmanaged = new PluginFusioninventoryUnmanaged();
               $pfUnmanaged->delete(['id' => $unmanageds_id], 1);
            }
         }
      }
      // end get port from unknwon device

      $db_networkport = [];
      if ($this->no_history === false) {
         $params = [
            'FROM'   => 'glpi_networkports',
            'FIELDS' => [
               'id', 'name', 'mac',
               'instantiation_type', 'logical_number'
            ],
            'WHERE'  => [
               'items_id'   => $this->items_id,
               'itemtype'   => $this->import_itemtype,
               'is_dynamic' => 1
            ]
         ];
         foreach ($DB->request($params) as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            if (is_null($data['mac'])) {
               $data['mac'] = '';
            }
            if (preg_match("/[^a-zA-Z0-9 \-_\(\)]+/", $data['name'])) {
               $data['name'] = Toolbox::addslashes_deep($data['name']);
            }
            $db_networkport[$idtmp] = array_map('strtolower', $data);
         }
      }
      $simplenetworkport = [];
      foreach ($this->a_inventory[$this->section] as $key => $a_networkport) {
         // Add ipnetwork if not exist
         if ($a_networkport['gateway'] != ''
                 && $a_networkport['netmask'] != ''
                 && $a_networkport['subnet']  != '') {

            if (countElementsInTable('glpi_ipnetworks',
                                     "`address`='".$a_networkport['subnet']."'
                                     AND `netmask`='".$a_networkport['netmask']."'
                                     AND `gateway`='".$a_networkport['gateway']."'
                                     AND `entities_id`='".$_SESSION["plugin_fusioninventory_entity"]."'") == 0) {

               $input_ipanetwork = [
                   'name'        => $a_networkport['subnet'].'/'.
                                    $a_networkport['netmask'].' - '.
                                    $a_networkport['gateway'],
                   'network'     => $a_networkport['subnet'].' / '.
                                    $a_networkport['netmask'],
                   'gateway'     => $a_networkport['gateway'],
                   'entities_id' => $_SESSION["plugin_fusioninventory_entity"]
               ];
               $iPNetwork->add($input_ipanetwork, [], !$this->no_history);
            }
         }

         // End add ipnetwork
         $a_field = ['name', 'mac', 'instantiation_type'];
         foreach ($a_field as $field) {
            if (isset($a_networkport[$field])) {
               $simplenetworkport[$key][$field] = $a_networkport[$field];
            }
         }
      }
      foreach ($simplenetworkport as $key => $arrays) {
         $arrayslower = array_map('strtolower', $arrays);
         foreach ($db_networkport as $keydb => $arraydb) {
            $logical_number = $arraydb['logical_number'];
            unset($arraydb['logical_number']);
            if ($arrayslower == $arraydb) {
               if ($this->a_inventory[$this->section][$key]['logical_number'] != $logical_number) {
                  $input                   = [];
                  $input['id']             = $keydb;
                  $input['logical_number'] = $this->a_inventory[$this->section][$key]['logical_number'];
                  $networkPort->update($input, !$this->no_history);
               }

               // Add / update instantiation_type
               if (isset($this->a_inventory[$this->section][$key]['instantiation_type'])) {
                  $instantiation_type = $this->a_inventory[$this->section][$key]['instantiation_type'];
                  if (in_array($instantiation_type, ['NetworkPortEthernet',
                                                          'NetworkPortFiberchannel'])) {

                     $instance      = new $instantiation_type();
                     $portsinstance = $instance->find("`networkports_id`='".$keydb."'", '', 1);
                     if (count($portsinstance) == 1) {
                        $portinstance = current($portsinstance);
                        $input        = $portinstance;
                     } else {
                        $input = [
                           'networkports_id' => $keydb
                        ];
                     }

                     if (isset($this->a_inventory[$this->section][$key]['speed'])) {
                        $input['speed']             = $this->a_inventory[$this->section][$key]['speed'];
                        $input['speed_other_value'] = $this->a_inventory[$this->section][$key]['speed'];
                     }
                     if (isset($this->a_inventory[$this->section][$key]['wwn'])) {
                        $input['wwn'] = $this->a_inventory[$this->section][$key]['wwn'];
                     }
                     if (isset($this->a_inventory[$this->section][$key]['mac'])) {
                        $networkcards = $item_DeviceNetworkCard->find(
                                "`mac`='".$this->a_inventory[$this->section][$key]['mac']."' "
                                . " AND `itemtype`='Computer'"
                                . " AND `items_id`='".$this->items_id."'",
                                '',
                                1);
                        if (count($networkcards) == 1) {
                           $networkcard = current($networkcards);
                           $input['items_devicenetworkcards_id'] = $networkcard['id'];
                        }
                     }
                     $input['_no_history'] = $this->no_history;
                     if (isset($input['id'])) {
                        $instance->update($input);
                     } else {
                        $instance->add($input);
                     }
                  }
               }

               // Get networkname
               $a_networknames_find = current($networkName->find("`items_id`='".$keydb."'
                                                    AND `itemtype`='NetworkPort'", "", 1));
               if (!isset($a_networknames_find['id'])) {
                  $a_networkport['entities_id'] = $_SESSION["plugin_fusioninventory_entity"];
                  $a_networkport['items_id']    = $this->items_id;
                  $a_networkport['itemtype']    = $this->import_itemtype;
                  $a_networkport['is_dynamic']  = 1;
                  $a_networkport['_no_history'] = $this->no_history;
                  $a_networkport['items_id']    = $keydb;

                  unset($a_networkport['_no_history']);
                  $a_networkport['is_recursive'] = 0;
                  $a_networkport['itemtype']     = 'NetworkPort';
                  unset($a_networkport['name']);
                  $a_networkport['_no_history']  = $this->no_history;
                  $a_networknames_id = $networkName->add($a_networkport, [], !$this->no_history);
                  $a_networknames_find['id'] = $a_networknames_id;
               }

               // Same networkport, verify ipaddresses
               $db_addresses = [];
               $params = [
                  'FROM' => 'glpi_ipaddresses',
                  'FIELDS' => ['id', 'name'],
                  'WHERE' =>
                     ['items_id' => $a_networknames_find['id'],
                      'itemtype' => 'NetworkName'
                     ]
               ];
               foreach ($DB->request($params) as $data) {
                  $db_addresses[$data['id']] = $data['name'];
               }
               $a_computerinventory_ipaddress =
                           $this->a_inventory[$this->section][$key]['ipaddress'];
               $nb_ip = count($a_computerinventory_ipaddress);
               foreach ($a_computerinventory_ipaddress as $key2 => $arrays2) {
                  foreach ($db_addresses as $keydb2 => $arraydb2) {
                     if ($arrays2 == $arraydb2) {
                        unset($a_computerinventory_ipaddress[$key2]);
                        unset($db_addresses[$keydb2]);
                        break;
                     }
                  }
               }
               if (count($a_computerinventory_ipaddress) || count($db_addresses)) {
                  if (count($db_addresses) != 0 && $nb_ip > 0) {
                     // Delete ip address in DB
                     foreach (array_keys($db_addresses) as $idtmp) {
                        $iPAddress->delete(['id' => $idtmp], 1);
                     }
                  }
                  if (count($a_computerinventory_ipaddress) != 0) {
                     foreach ($a_computerinventory_ipaddress as $ip) {
                        $input = [];
                        $input['items_id']   = $a_networknames_find['id'];
                        $input['itemtype']   = 'NetworkName';
                        $input['name']       = $ip;
                        $input['is_dynamic'] = 1;
                        $iPAddress->add($input, [], !$this->no_history);
                     }
                  }
               }

               unset($db_networkport[$keydb]);
               unset($simplenetworkport[$key]);
               unset($this->a_inventory[$this->section][$key]);
               break;
            }
         }
      }

      if (count($this->a_inventory[$this->section]) == 0
         && count($db_networkport) == 0) {
         // Nothing to do
         $coding_std = true;
      } else {
         if (count($db_networkport) != 0) {
            // Delete networkport in DB
            foreach ($db_networkport as $idtmp => $data) {
               $networkPort->delete(['id'=>$idtmp], 1);
            }
         }
         if (count($this->a_inventory[$this->section]) != 0) {
            foreach ($this->a_inventory[$this->section] as $a_networkport) {
               $a_networkport['entities_id'] = $_SESSION["plugin_fusioninventory_entity"];
               $a_networkport['items_id']    = $this->items_id;
               $a_networkport['itemtype']    = $this->import_itemtype;
               $a_networkport['is_dynamic']  = 1;
               $a_networkport['_no_history'] = $this->no_history;
               $a_networkport['items_id']    = $networkPort->add($a_networkport, [], !$this->no_history);
               unset($a_networkport['_no_history']);
               $a_networkport['is_recursive'] = 0;
               $a_networkport['itemtype']     = 'NetworkPort';
               unset($a_networkport['name']);
               $a_networkport['_no_history'] = $this->no_history;
               $a_networknames_id = $networkName->add($a_networkport, [], !$this->no_history);
               foreach ($a_networkport['ipaddress'] as $ip) {
                  $input = [];
                  $input['items_id']   = $a_networknames_id;
                  $input['itemtype']   = 'NetworkName';
                  $input['name']       = $ip;
                  $input['is_dynamic'] = 1;
                  $input['_no_history'] = $this->no_history;
                  $iPAddress->add($input, [], !$this->no_history);
               }
               if (isset($a_networkport['instantiation_type'])) {
                  $instantiation_type = $a_networkport['instantiation_type'];
                  if (in_array($instantiation_type, ['NetworkPortEthernet',
                                                          'NetworkPortFiberchannel'])) {
                     $instance = new $instantiation_type;
                     $input = [
                        'networkports_id' => $a_networkport['items_id']
                     ];
                     if (isset($a_networkport['speed'])) {
                        $input['speed'] = $a_networkport['speed'];
                        $input['speed_other_value'] = $a_networkport['speed'];
                     }
                     if (isset($a_networkport['wwn'])) {
                        $input['wwn'] = $a_networkport['wwn'];
                     }
                     if (isset($a_networkport['mac'])) {
                        $networkcards = $item_DeviceNetworkCard->find(
                                "`mac`='".$a_networkport['mac']."' "
                                . " AND `itemtype`='".$this->import_itemtype."'"
                                . " AND `items_id`='".$this->items_id."'",
                                '',
                                1);
                        if (count($networkcards) == 1) {
                           $networkcard = current($networkcards);
                           $input['items_devicenetworkcards_id'] = $networkcard['id'];
                        }
                     }
                     $input['_no_history'] = $this->no_history;
                     $instance->add($input);
                  }
               }
            }
         }
      }
   }
}
