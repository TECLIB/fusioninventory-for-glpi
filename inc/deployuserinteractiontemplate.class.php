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
 * This file is used to manage the actions in package for deploy system.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    Walid Nouh
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
 * Manage user interactions templates.
 */
class PluginFusioninventoryDeployUserinteractionTemplate extends CommonDBTM {


   const ALERT_WTS                = 'wts'; //Alerts using Windows WTS API
   const BEHAVIOR_CONTINUE_DEPLOY = 'continue'; //Continue a software deployment
   const BEHAVIOR_CANCEL_DEPLOY   = 'cancel'; //Cancel a software deployment
   const BEHAVIOR_POSTPONE_DEPLOY = 'postpone'; //Postpone a software deployment

   /**
    * Get list of supported interaction methods
    *
    * @since 9.2
    * @return array
    */
   static function getTypes() {
      return [self::ALERT_WTS => __("Windows system alert (WTS)", 'fusioninventory')];
   }

   /**
    * Get available buttons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
   static function getButtons($interaction_type = '') {
       $interactions =  [ self::ALERT_WTS =>
                           [ 'ok_sync'         => __('OK sync', 'fusioninventory'),
                             'ok_no_sync'      => __('OK no sync', 'fusioninventory'),
                             'ok_cancel'       => __('OK - Cancel', 'fusioninventory'),
                             'yes_no'          => __('Yes - No', 'fusioninventory'),
                             'ok_retry'        => __('OK - Retry', 'fusioninventory'),
                             'ok_retry_cancel' => __('OK - Retry - Cancel', 'fusioninventory')
                           ]
                       ];
      if (isset($interactions[$interaction_type])) {
         return $interaction_type;
      } else {
         return false;
      }
   }

   /**
    * Get available icons for alerts, by interaction type
    *
    * @since 9.2
    * @param interaction_type the type of interaction
    * @return array
    */
   static function getIcons($interaction_type = '') {
       $icons =  [ self::ALERT_WTS =>
                           [ 'warning' => __('Warning'),
                             'info'    => _n('Information', 'Informations', 1),
                             'error'   => __('Error')
                           ]
                       ];
      if (isset($icons[$interaction_type])) {
         return $icons;
      } else {
         return false;
      }
   }

   /**
    * Get available behaviors in case of user interactions
    *
    * @since 9.2
    * @return array
    */
   static function getBehaviors() {
      $behaviors = [self::BEHAVIOR_CONTINUE_DEPLOY => __('Continue'),
                    self::BEHAVIOR_POSTPONE_DEPLOY => __('Postpone', 'fusioninventory'),
                    self::BEHAVIOR_CANCEL_DEPLOY   => __('Cancel')
                   ];
      return $behaviors;
   }

   /**
   * Display an interaction template form
   * @since 9.2
   * @param $templates_id id of a template to edit
   */
   function showForm($templates_id) {

   }
}
