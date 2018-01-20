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
 * Import sound card
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportDeviceSimcard extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'DeviceSimcard';

   //Store the key related to the
   protected $section = 'simcards';

   /**
    * Import firmwares
    * @since 9.2+2.0
    *
    * @param string $itemtype the itemtype to be inventoried
    * @param array   $a_inventory Inventory data
    * @param integer     Network equipment id
    *
    * @return void
    */
   function importItem($no_history = false) {
      if (!isset($this->a_inventory['simcards'])
         || !count($this->a_inventory['simcards'])) {
         return;
      }

      $simcard  = new DeviceSimcard();

      foreach ($this->a_inventory[$this->section] as $a_simcard) {
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

         $input['itemtype']    = $this->import_itemtype;
         $input['items_id']    = $this->items_id;
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
}
