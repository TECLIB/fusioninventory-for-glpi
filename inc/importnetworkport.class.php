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
class PluginFusioninventoryImportNetworkPort implements PluginFusioninventoryImportInterface {

   //Store the inventory to be processed
   protected $a_inventory;

   //Store the key related to the
   protected $section = 'networkport';

   //Itemtype of the asset we're imported
   protected $import_itemtype = '';

   //Asset ID
   protected $items_id = 0;

   function transformItem() {
   }

   /**
    * Import ports
    *
    * @param array $a_inventory
    * @param integer $items_id
    */
   function importItem($no_history = false) {
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
      }
   }
}
