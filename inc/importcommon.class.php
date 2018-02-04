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
class PluginFusioninventoryImportVirtualMachine extends PluginFusioninventoryImportDevice {

   //Store the inventory to be processed
   protected $a_inventory = [];

   //The itemtype of the device we're processing
   protected $device_itemtype = '';

   //Store the key related to the
   protected $section = '';

   protected $entities_id = 0;

   public function prepareTransformItem($inventory_as_array = [], $temporary_array = []) {
      // * HARDWARE
      $temporary_array =  $this->addValues($inventory_as_array['HARDWARE'],
                              [
                                 'NAME'              => 'name',
                                 'WINPRODID'         => 'licenseid',
                                 'WINPRODKEY'        => 'license_number',
                                 'WORKGROUP'         => 'domains_id',
                                 'UUID'              => 'uuid',
                                 'LASTLOGGEDUSER'    => 'users_id',
                                 'manufacturers_id'  => 'manufacturers_id',
                                 'computermodels_id' => 'computermodels_id',
                                 'serial'            => 'serial',
                                 'computertypes_id'  => 'computertypes_id'
                              ]
      );
      if (isset($temporary_array['users_id'])) {
         if ($temporary_array['users_id'] == '') {
            unset($temporary_array['users_id']);
         } else {
            $temporary_array['contact'] = $temporary_array['users_id'];
            $tmp_users_id               = $temporary_array['users_id'];
            $split_user                 = explode("@", $tmp_users_id);
            $params = [
               'FROM'   => 'glpi_users',
               'FIELDS' => ['id'],
               'WHERE'  => [
                  'name' => $split_user['0']
               ],
               'LIMIT'  => 1
            ];
            $iterator = $DB->request($params);
            if ($iterator->numrows() == 1) {
               $data = $iterator->next();
               $temporary_array['users_id'] = $data['id'];
            } else {
               $temporary_array['users_id'] = 0;
            }
         }
      }
      return $temporary_array;
   }
}
