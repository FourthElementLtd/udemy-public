<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

require_once 'SagePayCw/Util.php';
require_once 'Customweb/Database/Migration/IScript.php';

class SagePayCw_Migration_1_1_0 implements Customweb_Database_Migration_IScript {

	public function execute(Customweb_Database_IDriver $driver){
		global $wpdb;	

		$query = "SHOW TABLES IN `".DB_NAME."` LIKE  'sagepaycw_storage';";
		$select = $driver->query($query)->fetch();
		if(is_array($select) && count($select) > 0) {
			$entityManager = SagePayCw_Util::getEntityManager();
			
			//We already have a tabel generated by the entity manager but it ignored the wordpress table prefix
			$tableName = $entityManager->getTableNameForEntityByClassName('SagePayCw_Entity_Storage');
			$driver->query("RENAME TABLE `sagepaycw_storage` TO `" . $tableName . "`")->execute();
		}
		else {
			$driver->query(
			
				"CREATE TABLE IF NOT EXISTS `".$wpdb->prefix . "woocommerce_sagepaycw_storage` (
				`keyId` bigint(20) NOT NULL AUTO_INCREMENT,
				`keyName` varchar (165),
				`keySpace` varchar (165),
				`keyValue` LONGTEXT,
				PRIMARY KEY (`keyId`)
				)
				DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB;")->execute();
		}
		return true;
	}
}