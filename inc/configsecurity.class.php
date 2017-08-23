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
 * This file is used to manage the SNMP authentication: v1, v2c and v3
 * support.
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
   die("Sorry. You can't access this file directly");
}

/**
 * Manage the SNMP authentication: v1, v2c and v3 support.
 */
class PluginFusioninventoryConfigSecurity extends CommonDBTM {

   /**
    * We activate the history.
    *
    * @var boolean
    */
   public $dohistory = true;

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_configsecurity';

   /**
    * Name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
   **/
   static function getTypeName($nb = 0) {
      return __('SNMP authentication', 'fusioninventory');
   }

   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options= []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function getSearchOptionsNew() {
      global $CFG_GLPI;

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'snmpversion',
         'name'               => __('SNMP version', 'fusioninventory'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'username',
         'name'               => _n('User', 'Users', 1),
         'massiveaction'      => false, // implicit field is id
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'authentication',
         'name'               => __('Encryption protocol for authentication ',
                                    'fusioninventory'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'auth_passphrase',
         'name'               => __('Authentication password', 'fusioninventory'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'encryption',
         'name'               => __('Encryption protocol for data',
                                    'fusioninventory'),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'priv_passphrase',
         'name'               => __('Encryption password', 'fusioninventory'),
         'massiveaction'      => false
      ];

      return $tab;
   }

   /**
    * Display form
    *
    * @param integer $id
    * @param array $options
    * @return true
    */
   function showForm($id, $options=[]) {
      Session::checkRight('plugin_fusioninventory_configsecurity', READ);
      $this->initForm($id, $options);
      $this->showFormHeader($options);

      if (($this->isNewID($id)
         && isset($options['snmpv3'])) || $this->fields["snmpversion"] == 3) {
         $show_v3 = true;
      } else {
         $show_v3 = false;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td align='center'>" . __('Name') . "</td>";
      echo "<td align='center'>";
      Html::autocompletionTextField($this,'name');
      echo "</td>";

      if (!$show_v3) {

         echo "<td align='center'>" . __('SNMP version', 'fusioninventory') . "</td>";
         echo "<td align='center'>";
            $this->showDropdownSNMPVersion($this->fields["snmpversion"], $show_v3);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>" . __('Community', 'fusioninventory') . "</td>";
         echo "<td align='center'>";
         Html::autocompletionTextField($this, 'community');
         echo "</td>";
         $url = $this->getLinkURL(true).'&snmpv3=on';
         echo "<td colspan='2'><a href='$url'>".__('Enable SNMP v3', 'fusioninventory')."</a></td>";
         echo "</tr>";

      } else {

         echo "<td align='center' colspan='2'><input type='hidden' name='snmpversion' value='3'></td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>" . __('User') . "</td>";
         echo "<td align='center'>";
         Html::autocompletionTextField($this,'username');
         echo "</td>";

         echo "<td align='center'>".__('Encryption protocol for authentication ', 'fusioninventory').
                 "</td>";
         echo "<td align='center'>";
            $this->showDropdownSNMPAuth($this->fields["authentication"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>" . __('Authentication password', 'fusioninventory') . "</td>";
         echo "<td align='center'>";
         Html::passwordField('auth_passphrase', ['value' => $this->fields['auth_passphrase']]);
         echo "</td>";

         echo "<td align='center'>" . __('Encryption protocol for data', 'fusioninventory') . "</td>";
         echo "<td align='center'>";
            $this->showDropdownSNMPEncryption($this->fields["encryption"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td align='center'>" . __('Encryption password', 'fusioninventory') . "</td>";
         echo "<td align='center'>";
         Html::passwordField('priv_passphrase', ['value' => $this->fields['priv_passphrase']]);
         echo "</td>";
         echo "<td colspan='2'></td>";
         echo "</tr>";

      }


      $this->showFormButtons($options);

      return true;
   }



   /**
    * Display SNMP version (dropdown)
    *
    * @param null|string $p_value
    */
   function showDropdownSNMPVersion($p_value='v1') {
      Dropdown::showFromArray("snmpversion", ['1' => 'v1', '2c' => 'v2c'],
                              ['value' => $p_value]);
   }



   /**
    * Get real version of SNMP
    *
    * @param integer $id
    * @return string
    */
   function getSNMPVersion($id) {
      switch($id) {

         case '1':
            return '1';

         case '2':
            return '2c';

         case '3':
            return '3';

      }
      return '';
   }



   /**
    * Display SNMP authentication encryption (dropdown)
    *
    * @param null|string $p_value
    */
   function showDropdownSNMPAuth($p_value = 'MD5') {
      Dropdown::showFromArray("authentication", ['MD5', 'SHA'],
                              ['value' => $p_value]);
   }



   /**
    * Get SNMP authentication protocol
    *
    * @param integer $id
    * @return string
    */
   function getSNMPAuthProtocol($id) {
      switch($id) {

         case '1':
            return 'MD5';

         case '2':
            return 'SHA';

      }
      return '';
   }

   /**
    * display a value according to a field
    *
    * @since version 0.83
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'encryption':
            $security = new self();
            return $security->getSNMPEncryption($values[$field]);

         case 'authentication':
            $security = new self();
            return $security->getSNMPAuthProtocol($values[$field]);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);

   }

   /**
    * Show dropdown of SNMP encryption protocol
    *
    * @param string $p_value
    */
   function showDropdownSNMPEncryption($p_value=NULL) {
      $encryptions = ['DES', 'AES128', 'AES192', 'AES256', 'Triple-DES'];
      $options     = [];
      if (!is_null($p_value)) {
         $options = ['value' => $p_value];
      }
      Dropdown::showFromArray("encryption", $encryptions, $options);
   }



   /**
    * Get the SNMP encryption
    *
    * @param integer $id
    * @return string
    */
   function getSNMPEncryption($id) {
      switch($id) {

         case '1':
            return 'DES';

         case '2':
            return 'AES';

         case '3':
            return 'AES192';

         case '4':
            return 'AES256';

         case '5':
            return '3DES';

      }
      return '';
   }



   /**
    * Show dropdown of SNMP authentication
    *
    * @param string $selected
    */
   static function authDropdown($selected="") {

      Dropdown::show("PluginFusioninventoryConfigSecurity",
                      array('name' => "plugin_fusioninventory_configsecurities_id",
                           'value' => $selected,
                           'comment' => false));
   }



   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() == 'assign_auth') {
         PluginFusioninventoryConfigSecurity::authDropdown();
         echo Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
         return true;
      }
      return false;
   }



   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      $itemtype = $item->getType();

      switch ($ma->getAction()) {

         case "assign_auth" :
            switch($itemtype) {

               case 'NetworkEquipment':
                  $equipement = new PluginFusioninventoryNetworkEquipment();
                  break;

               case 'Printer':
                  $equipement = new PluginFusioninventoryPrinter();
                  break;

               case 'PluginFusioninventoryUnmanaged':
                  $equipement = new PluginFusinvsnmpUnmanaged();
                  break;

            }
            $fk = getForeignKeyFieldForItemType($itemtype);
            foreach ($ids as $key) {
               $found = $equipement->find("`$fk`='".$key."'");
               $input = array();
               if (count($found) > 0) {
                  $current = current($found);
                  $equipement->getFromDB($current['id']);
                  $input['id'] = $equipement->fields['id'];
                  $input['plugin_fusioninventory_configsecurities_id'] =
                              $_POST['plugin_fusioninventory_configsecurities_id'];
                  $return = $equipement->update($input);
               } else {
                  $input[$fk] = $key;
                  $input['plugin_fusioninventory_configsecurities_id'] =
                              $_POST['plugin_fusioninventory_configsecurities_id'];
                  $return = $equipement->add($input);
               }

               if ($return) {
                  //set action massive ok for this item
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  // KO
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }
         break;

      }
   }
}

?>
