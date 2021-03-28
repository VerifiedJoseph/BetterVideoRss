<?php

use Configuration as Config;
use Helper\Validate;
use Helper\Output;

class Feed {

	/** @var array $feedData Feed data from data class */
	private array $feedData = array();

	/** @var string $feedId YouTube channel or playlist ID */
	private string $feedId = '';

	/** @var string $feedType Feed type (channel or playlist) */
	private string $feedType = 'channel';

	/** @var string $feedFormat Feed format */
	private string $feedFormat = '';

	/** @var boolean $embedVideos Embed videos status */
	private bool $embedVideos = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->feedFormat = Config::getDefaultFeedFormat();
		$this->checkInputs();
	}

	/**
	 * Generate feed
	 */
	public function generate() {
		$data = new Data(
			$this->getFeedId(),
			$this->getFeedType()
		);

		$fetch = new Fetch(
			$this->getFeedId(),
			$this->getFeedType()
		);

		foreach ($data->getExpiredParts() as $part) {
			$parameter = '';

			if ($part === 'feed') {
				$fetch->feed();
				$data->updateFeed($fetch->getResponse());
			}

			if ($part === 'details') {
				$fetch->api(
					$part,
					$parameter,
					$this->data->getPartEtag($part)
				);

				$data->updateDetails($fetch->getResponse());
			}

			if ($part === 'videos') {
				$parameter = $data->getExpiredVideos();

				if (empty($parameter)) {
					continue;
				}

				$fetch->api(
					$part,
					$parameter,
					$data->getPartEtag($part)
				);

				$data->updateVideos($fetch->getResponse());
			}
		}

		$this->feedData = $data->getData();
	}

	public function output() {
		$formatClass = 'Format\\' . ucfirst($this->getFeedFormat());

		$format = new $formatClass(
			$this->getFeedData(),
			$this->getEmbedStatus()
		);

		$format->build();

		Output::feed(
			$format->get(),
			$format->getContentType(),
			$format->getLastModified()
		);
	}

	/**
	 * Check user inputs
	 *
	 * @throws Exception if a invalid format parameter is given.
	 * @throws Exception if an empty or invalid channel ID parameter is given.
	 * @throws Exception if an empty or invalid playlist ID parameter is given.
	 */
	private function checkInputs() {

		if (isset($_GET['format']) && empty($_GET['format']) === false) {
			$format = strtolower($_GET['format']);

			if (Validate::feedFormat($format) === false) {
				throw new Exception('Invalid format parameter given.');
			}

			$this->feedFormat = $format;
		}

		if (isset($_GET['channel_id'])) {

			if (empty($_GET['channel_id'])) {
				throw new Exception('No channel ID parameter given.');
			}

			if (Validate::channelId($_GET['channel_id']) === false) {
				throw new Exception('Invalid channel ID parameter given.');
			}

			$this->feedId = $_GET['channel_id'];
			$this->feedType = 'channel';
		}

		if (isset($_GET['playlist_id'])) {

			if (empty($_GET['playlist_id'])) {
				throw new Exception('No playlist ID parameter given.');
			}

			if (Validate::playlistId($_GET['playlist_id']) === false) {
				throw new Exception('Invalid playlist ID parameter given.');
			}

			$this->feedId = $_GET['playlist_id'];
			$this->feedType = 'playlist';
		}

		if (isset($_GET['embed_videos'])) {
			$this->embedVideos = filter_var($_GET['embed_videos'], FILTER_VALIDATE_BOOLEAN);
		}
	}

	/**
	 * Return feed data
	 *
	 * @return array
	 */
	private function getFeedData() {
		return $this->feedData;
	}

	/**
	 * Return feed type
	 *
	 * @return string
	 */
	private function getFeedType() {
		return $this->feedType;
	}

	/**
	 * Return feed ID
	 *
	 * @return string
	 */
	private function getFeedId() {
		return $this->feedId;
	}

	/**
	 * Return feed format
	 *
	 * @return string
	 */
	private function getFeedFormat() {
		return $this->feedFormat;
	}

	/**
	 * Return embed video status
	 *
	 * @return boolean
	 */
	private function getEmbedStatus() {
		return $this->embedVideos;
	}
}