<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2016 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Walid Nouh
   @co-author
   @copyright Copyright (c) 2010-2016 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

//This call is to check that the ESX inventory service is up and running
if (isset($_GET['status'])) {
   return 'ok';
}
ob_start();
include ("../../../../inc/includes.php");
ob_end_clean();

$response = false;
//Agent communication using REST protocol
if (isset($_GET['action']) && isset($_GET['machineid'])) {

   switch ($_GET['action']) {

      case 'getJobs':
         $pfAgent        = new PluginFusioninventoryAgent();
         $pfCredentialip = new PluginFusioninventoryCredentialIp();
         $pfCredential   = new PluginFusioninventoryCredential();

         $agent = $pfAgent->getByDeviceID(Toolbox::addslashes_deep($_GET['machineid']));
         if (isset($agent['id'])) {
            if (isset($_GET['host'])) {
               $sql     = "`ip`='".$_GET['host']."'";
               $sql    .= getEntitiesRestrictRequest(" AND",
                                                     'glpi_plugin_fusioninventory_credentialips',
                                                     'entities_id',
                                                     $agent['entities_id'],
                                                     true);
               $results = $pfCredentialip->find($sql);
               if (count($results) == 1) {
                  $credentialip = current($results);
                  if ($credentialip['plugin_fusioninventory_credentials_id'] &&
                     $pfCredential->getFromDB($credentialip['plugin_fusioninventory_credentials_id']) ) {
                     $response = ['username' => $pfCredential->getField('username'),
                                  'password' => $pfCredential->getField('password'),
                                  'ip'       => $credentialip['ip']
                                 ];
                     $response = json_encode($response);
                  }
               } else {
                  $response = "{}";
               }
            }
         }
         break;

   }

   if ($response !== false) {
      echo $response;
   } else {
      echo json_encode((object)[]);
   }
}

?>
