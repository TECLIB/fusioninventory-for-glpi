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
 * Import memory
 * @since 9.3+1.0
 */
class PluginFusioninventoryImportDeviceMemory extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = 'DeviceMemory';

   //Store the key related to the
   protected $section = 'memory';

   function getQuery() {
      return "SELECT `glpi_items_devicememories`.`id`, `designation`, `size`,
               `frequence`, `serial`, `devicememorytypes_id`,
               `glpi_items_devicememories`.`busID`
               FROM `glpi_items_devicememories`
               LEFT JOIN `glpi_devicememories` ON `devicememories_id`=`glpi_devicememories`.`id`
               WHERE `items_id` = '".$this->items_id."'
                  AND `itemtype`='".$this->import_itemtype."'
                  AND `is_dynamic`='1'";
   }

   function checkAfter(&$a_inventory, &$db_devices, $key, $arrays) {
      $deviceMemory = new deviceMemory();

      $frequence = (int) $arrays['frequence'];
      unset($arrays['frequence']);
      foreach ($db_devices as $keydb => $arraydb) {
         $frequencedb = (int) $arraydb['frequence'];
         unset($arraydb['frequence']);
         if ($arrays == $arraydb) {
            $a_criteria = $deviceMemory->getImportCriteria();
            $criteriafrequence = $a_criteria['frequence'];
            $compare = explode(':', $criteriafrequence);
            if ($frequence > ($frequencedb - $compare[1])
                    && $frequence < ($frequencedb + $compare[1])) {
               unset($a_inventory['memory'][$key]);
               unset($db_devices[$keydb]);
               break;
            }
         }
      }
   }
}
