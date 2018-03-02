<?php
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\EndpointProtectorIntegration\AppInfo;

use OC\Files\Filesystem;
use OCA\EndpointProtectorIntegration\Operation;
use OCA\EndpointProtectorIntegration\StorageWrapper;
use OCA\Files_Sharing\ISharedStorage;
use OCP\AppFramework\App;
use OCP\AppFramework\QueryException;
use OCP\Files\Storage\IStorage;
use OCP\Util;

class Application extends App {

	/**
	 * Application constructor.
	 */
	public function __construct() {
		parent::__construct('endpointprotector_integration');
	}

	/**
	 * Register all hooks and listeners
	 */
	public function register() {
		Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');
	}

	/**
	 * @internal
	 */
	public function addStorageWrapper() {
		// Needs to be added as the first layer
		Filesystem::addStorageWrapper('files_automatedtagging', [$this, 'addStorageWrapperCallback'], -1);
	}

	/**
	 * @internal
	 * @param $mountPoint
	 * @param IStorage $storage
	 * @return StorageWrapper|IStorage
	 */
	public function addStorageWrapperCallback($mountPoint, IStorage $storage) {
		if (!\OC::$CLI && !$storage->instanceOfStorage(ISharedStorage::class)) {
			try {
				$operation = $this->getContainer()->query(Operation::class);
			} catch (QueryException $e) {
				return $storage;
			}

			return new StorageWrapper([
				'storage' => $storage,
				'operation' => $operation,
				'mountPoint' => $mountPoint,
			]);
		}

		return $storage;
	}
}
