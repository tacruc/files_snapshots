<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_Snapshots;


class SnapshotManager {
	/** @var string */
	private $snapshotFormat;

	/** @var string */
	private $snapshotPrefix;

	/** @var string */
	private $snapshotPostfix;

	/** @var  string */
	private $dateFormat;

	/**
	 * SnapshotManager constructor.
	 *
	 * @param string $snapshotFormat
	 * @param string $dateFormat
	 */
	public function __construct($snapshotFormat, $dateFormat) {
		$this->snapshotFormat = $snapshotFormat;
		$this->dateFormat = $dateFormat;

		list($this->snapshotPrefix, $this->snapshotPostfix) = explode('/%snapshot%/', $snapshotFormat);
	}

	/**
	 * @return \Generator|Snapshot[]
	 */
	public function listAllSnapshots() {
		$dh = opendir($this->snapshotPrefix);
		while ($file = readdir($dh)) {
			$path = $this->snapshotPrefix . '/' . $file;
			if ($file[0] !== '' && is_dir($path) && is_dir($path . '/' . $this->snapshotPostfix)) {
				yield new Snapshot($path . '/' . $this->snapshotPostfix, $file, $this->dateFormat);
			}
		}
		closedir($dh);
	}

	/**
	 * @param $file
	 * @return Snapshot[]
	 */
	public function listSnapshotsForFile($file) {
		$lastMtime = 0;
		$allSnapshots = array_filter(iterator_to_array($this->listAllSnapshots()), function(Snapshot $snapshot) {
			return $snapshot->getSnapshotDate() instanceof \DateTime;
		});

		usort($allSnapshots, function (Snapshot $a, Snapshot $b) {
			return $a->getSnapshotDate()->getTimestamp() - $b->getSnapshotDate()->getTimestamp();
		});
		return array_filter($allSnapshots, function (Snapshot $snapshot) use (&$lastMtime, $file) {
			$snapshotMtime = $snapshot->getMtime($file);
			if ($snapshotMtime > $lastMtime) {
				$lastMtime = $snapshotMtime;
				return true;
			}
			return false;
		});
	}

	public function getSnapshot($id) {
		$path = $path = $this->snapshotPrefix . '/' . $id . '/' . $this->snapshotPostfix;
		return is_dir($path) ? new Snapshot($path, $id, $this->dateFormat) : null;
	}
}