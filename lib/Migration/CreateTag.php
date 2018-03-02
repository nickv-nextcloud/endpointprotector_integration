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

namespace OCA\EndpointProtectorIntegration\Migration;


use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;

class CreateTag implements IRepairStep {

	/** @var ISystemTagManager */
	protected $systemTagManager;

	/**
	 * @param ISystemTagManager $systemTagManager
	 */
	public function __construct(ISystemTagManager $systemTagManager) {
		$this->systemTagManager = $systemTagManager;
	}

	/**
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Create unremovable systemtag';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		try {
			$this->systemTagManager->getTag('EndpointProtector', true, false);
		} catch (TagNotFoundException $e) {
			$this->systemTagManager->createTag('EndpointProtector', true, false);
		}
	}
}
