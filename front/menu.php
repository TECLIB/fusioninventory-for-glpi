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
 * This file is used to display the mail page of the plugin.
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

include ("../../../inc/includes.php");

if (PluginFusioninventoryMenu::canView()) {
   Html::header(__('FusionInventory', 'fusioninventory'), $_SERVER["PHP_SELF"], "admin",
                "pluginfusioninventorymenu", "menu");

   $timer = $_SESSION['glpirefresh_ticket_list'];
   $callback = 'window.location.reload()';
   $text = "";
   if ($timer > 0) {
      // set timer to millisecond from minutes
      $timer = $timer * MINUTE_TIMESTAMP * 1000;

      // call callback function to $timer interval
      $text = Html::scriptBlock("window.setInterval(function() {
            $callback
         }, $timer);");
   }
   echo $text;

   PluginFusioninventoryMenu::displayMenu();
   PluginFusioninventoryMenu::board();
} else {
   Html::displayRightError();
}

Html::footer();

?>
