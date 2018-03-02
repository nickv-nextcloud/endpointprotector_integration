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

namespace OCA\EndpointProtectorIntegration;

use OC\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class BackgroundJob extends QueuedJob {

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var ITempManager */
	protected $tempManager;

	/** @var ISystemTagManager */
	protected $systemTagManager;

	/** @var ISystemTagObjectMapper */
	protected $objectMapper;

	/** @var IConfig */
	protected $config;

	/**
	 * BackgroundJob constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param ITempManager $tempManager
	 * @param ISystemTagManager $systemTagManager
	 * @param ISystemTagObjectMapper $objectMapper
	 * @param IConfig $config
	 */
	public function __construct(IRootFolder $rootFolder, ITempManager $tempManager, ISystemTagManager $systemTagManager, ISystemTagObjectMapper $objectMapper, IConfig $config) {
		$this->rootFolder = $rootFolder;
		$this->tempManager = $tempManager;
		$this->systemTagManager = $systemTagManager;
		$this->objectMapper = $objectMapper;
		$this->config = $config;
	}

	/**
	 * @param mixed $argument
	 */
	protected function run($argument) {
		$command = $this->config->getSystemValue('endpointprotector_command', '');
		if (empty($command)) {
			return;
		}

		$userId = (string) $argument['userId'];
		$fileId = (int) $argument['fileId'];

		$userFolder = $this->rootFolder->getUserFolder($userId);
		$nodes = $userFolder->getById($fileId);
		$file = array_shift($nodes);

		if (!$file instanceof File) {
			return;
		}

		try {
			$source = $file->fopen('r');
		} catch (NotPermittedException $e) {
			return;
		}

		if (!$source) {
			return;
		}

		$extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile($extension);
		file_put_contents($tmpFile, $source);

		$exec = $command . ' ' . escapeshellarg($tmpFile);

		$result = shell_exec($exec);

		if (!empty($result)) {
			$systemTag = $this->getSystenTag();
			$this->objectMapper->assignTags((string) $fileId, 'files', $systemTag->getId());
		}
	}

	/**
	 * @return \OCP\SystemTag\ISystemTag
	 */
	protected function getSystenTag() {
		try {
			return $this->systemTagManager->getTag('EndpointProtector', true, false);
		} catch (TagNotFoundException $e) {
			return $this->systemTagManager->createTag('EndpointProtector', true, false);
		}
	}
}
