<?php

namespace Helper;

use Configuration as Config;
use DateTime;
use DateTimeZone;

class Convert {

	/** @var int $urlRegex URL regex */
	private static $urlRegex = '/(https?:\/\/(?:www\.)?(?:[a-zA-Z0-9-.]{2,256}\.[a-z]{2,20})(\:[0-9]{2,4})?(?:\/[a-zA-Z0-9@:%_\+.,~#"!?&\/\/=\-*]+|\/)?)/ims';

	/** @var int $urlRegex ISO 8601 regex */
	private static $iso8601Regex = '/^(-|)?P([0-9]+Y|)?([0-9]+M|)?([0-9]+D|)?T?([0-9]+H|)?([0-9]+M|)?([0-9]+S|)?$/';

	/** @var int $urlRegex ISO 8601 part regex */
	private static $iso8601PartRegex = '/((?!([0-9]|-)).)*/';

	/**
	 * Convert ISO 8601 video duration to hours, minutes and seconds
	 *
	 * @param string $duration ISO 8601 duration
	 * @param string $allowNegative Allow a negative duration
	 * @return boolean|string
	 */
	public static function videoDuration($duration, $allowNegative = true) {
		$matches = array();

		if(preg_match(self::$iso8601Regex, $duration, $matches)) {
			foreach($matches as &$match) {
				$match = preg_replace(self::$iso8601PartRegex, '', $match);
			}

			// Fetch min/plus symbol
			$result['symbol'] = ($matches[1] == '-') ? $matches[1] : '+';

			// Fetch duration parts
			$m = ($allowNegative) ? $matches[1] : '';
			$result['year'] = intval($m . $matches[2]);
			$result['month'] = intval($m . $matches[3]);
			$result['day'] = intval($m . $matches[4]);
			$result['hour'] = intval($m . $matches[5]);
			$result['minute'] = intval($m . $matches[6]);
			$result['second'] = intval($m . $matches[7]);

			if ($result['hour'] < 10) {
				$result['hour'] = 0 . $result['hour'];
			}

			if ($result['minute'] < 10) {
				$result['minute'] = 0 . $result['minute'];
			}

			if ($result['second'] < 10) {
				$result['second'] = 0 . $result['second'];
			}

			if($result['hour'] > 0) {
				$result = $result['hour'] . ':' . $result['minute'] . ':' . $result['second'];

			} else {
				$result = $result['minute'] . ':' . $result['second'];
			}

			return $result;
		}

		return false;
	}

	/**
	 * Convert Unix timestamp into a readable format
	 *
	 * @param string $timestamp Unix timestamp
	 * @param string $format DaeTime format
	 * @return string
	 */
	public static function unixTime(int $timestamp = 0, string $format = 'Y-m-d H:i:s') {
		$dt = new DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new DateTimeZone(config::get('TIMEZONE')));

		return $dt->format($format);
	}

	/**
	 * Convert URLs to HTML links
	 *
	 * @param string $string URL
	 * @return string
	 */
	public static function urls(string $string) {
		return preg_replace(
			self::$urlRegex,
			'<a href="$1" target="_blank">$1</a>',
			$string
		);
	}
}
