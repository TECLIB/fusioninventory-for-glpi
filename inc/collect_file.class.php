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
 * This file is used to manage file collect of agent
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
 * Manage the files to search in collect module.
 */
class PluginFusioninventoryCollect_File extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_collect';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
      return __('Find file', 'fusioninventory');
   }



   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getID() > 0) {
         if ($item->fields['type'] == 'file') {
            return __('Find file', 'fusioninventory');
         }
      }
      return '';
   }



   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      $pfCollect_File = new PluginFusioninventoryCollect_File();
      $pfCollect_File->showFile($item->getID());
      $pfCollect_File->showForm($item->getID());
      return TRUE;
   }



   /**
    * Display files of this collect id
    *
    * @param integer $collects_id id of the collect
    */
   function showFile($collects_id) {

      $content = $this->find("`plugin_fusioninventory_collects_id`='".
                              $collects_id."'");

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th colspan=12>".__('Find file associated', 'fusioninventory')."</th>";
      echo "</tr>";
      echo "<tr>
      <th>".__("Name")."</th>
      <th>".__("Limit", "fusioninventory")."</th>
      <th>".__("Folder", "fusioninventory")."</th>
      <th>".__("Recursive", "fusioninventory")."</th>
      <th>".__("Regex", "fusioninventory")."</th>
      <th>".__("Size", "fusioninventory")."</th>
      <th>".__("Checksum SHA512", "fusioninventory")."</th>
      <th>".__("Checksum SHA2", "fusioninventory")."</th>
      <th>".__("Name", "fusioninventory")."</th>
      <th>".__("Iname", "fusioninventory")."</th>
      <th>".__("Type", "fusioninventory")."</th>
      <th>".__("Action")."</th>
      </tr>";
      foreach ($content as $data) {
         echo "<tr>";
         echo "<td align='center'>".$data['name']."</td>";
         echo "<td align='center'>".$data['limit']."</td>";
         echo "<td align='center'>".$data['dir']."</td>";
         echo "<td align='center'>".$data['is_recursive']."</td>";
         echo "<td align='center'>".$data['filter_regex']."</td>";
         echo "<td align='center'>";
         if (!empty($data['filter_sizeequals'])) {
            echo '= '.$data['filter_sizeequals'];
         } else if (!empty($data['filter_sizegreater'])) {
            echo '> '.$data['filter_sizegreater'];
         } else if (!empty($data['filter_sizelower'])) {
            echo '< '.$data['filter_sizelower'];
         }
         echo "</td>";
         echo "<td align='center'>".$data['filter_checksumsha512']."</td>";
         echo "<td align='center'>".$data['filter_checksumsha2']."</td>";
         echo "<td align='center'>".$data['filter_name']."</td>";
         echo "<td align='center'>".$data['filter_iname']."</td>";
         echo "<td align='center'>";
         if ($data['filter_is_file'] == 1) {
            echo __('File', 'fusioninventory');
         } else {
            echo __('Folder', 'fusioninventory');
         }
         echo "</td>";
         echo "<td align='center'>
            <form name='form_bundle_item' action='".Toolbox::getItemTypeFormURL(__CLASS__).
                   "' method='post'>";
            echo Html::hidden('id', array('value' => $data['id']));
            echo "<input type='image' name='delete' src='../pics/drop.png'>";
         Html::closeForm();
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "</div>";
   }



   /**
    * Display form to add a new file of collect
    *
    * @param integer $collects_id id of collect
    * @param array $options
    * @return true
    */
   function showForm($collects_id, $options=array()) {

      $ID = 0;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Name');
      echo "</td>";
      echo "<td>";
      echo Html::hidden('plugin_fusioninventory_collects_id',
                        array('value' => $collects_id));
      echo "<input type='text' name='name' value='' />";
      echo "</td>";
      echo "<td>".__('Limit', 'fusioninventory')."</td>";
      echo "<td>";
      Dropdown::showNumber('limit',
                           array('min'   => 1,
                                 'max'   => 100,
                                 'value' => 5
                                 )
                           );
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo _n('Filter', 'Filters', 2, 'fusioninventory');
      echo "</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Base folder', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='dir' value='/' size='50' />";
      echo "</td>";
      echo "<td>";
      echo __('Folder recursive', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_recursive', 1);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Regex', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_regex' value='' size='50' />";
      echo "</td>";
      echo "<td>";
      echo __('Size', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('sizetype', array(
          'none'    => __('Disabled', 'fusioninventory'),
          'equals'  => '=',
          'greater' => '>',
          'lower'   => '<'
      ));
      echo "<input type='text' name='size' value='' />";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Checksum SHA512', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_checksumsha512' value='' />";
      echo "</td>";
      echo "<td>";
      echo __('Checksum SHA2', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      echo "<input type='text' name='filter_checksumsha2' value='' />";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Filename', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('filter_nametype', array(
          'none'  => __('Disabled', 'fusioninventory'),
          'name'  => __('Non sentitive case', 'fusioninventory'),
          'iname' => __('Sentitive case', 'fusioninventory')
      ));
      echo "<input type='text' name='filter_name' value='' />";
      echo "</td>";
      echo "<td>";
      echo __('Type', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('type',
         array('file' => __('File', 'fusioninventory'),
               'dir'  => __('Folder', 'fusioninventory')
         )
      );
      echo "</td>";
      echo "</tr>\n";

      $this->showFormButtons($options);

      return TRUE;
   }
}

?>
