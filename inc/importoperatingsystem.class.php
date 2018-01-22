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
 * Importprinters
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportOperatingSystem extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'OperatingSystem';

   //Store the key related to the
   protected $section = '';

   protected $entities_id = 0;

   function canImport() {
      return ($this->import_itemtype == 'Computer');
   }

   function importItem() {
      global $DB;

         // Manage operating system
      if (isset($this->a_inventory['fusioninventorycomputer']['items_operatingsystems_id'])) {
         $ios  = new Item_OperatingSystem();
         $pfos = $this->a_inventory['fusioninventorycomputer']['items_operatingsystems_id'];
         $ios->getFromDBByCrit([
            'itemtype' => $this->import_itemtype,
            'items_id' => $this->items_id
         ]);

         $input_os = [
            'itemtype'                          => $this->import_itemtype,
            'items_id'                          => $this->items_id,
            'operatingsystemarchitectures_id'   => $pfos['operatingsystemarchitectures_id'],
            'operatingsystemkernelversions_id'  => $pfos['operatingsystemkernelversions_id'],
            'operatingsystems_id'               => $pfos['operatingsystems_id'],
            'operatingsystemversions_id'        => $pfos['operatingsystemversions_id'],
            'operatingsystemservicepacks_id'    => $pfos['operatingsystemservicepacks_id'],
            'operatingsystemeditions_id'        => $pfos['operatingsystemeditions_id'],
            'license_id'                        => $pfos['licenseid'],
            'license_number'                    => $pfos['license_number'],
            'is_dynamic'                        => 1,
            'entities_id'                       => $this->entities_id
         ];

         if (!$ios->isNewItem()) {
            //OS exists, check for updates
            $same = true;
            foreach ($input_os as $key => $value) {
               if ($ios->fields[$key] != $value) {
                  $same = false;
                  break;
               }
            }
            if ($same === false) {
               $ios->update(['id' => $ios->getID()] + $input_os);
            }
         } else {
            $ios->add($input_os, [], $this->no_history);
         }
      }
   }
}
