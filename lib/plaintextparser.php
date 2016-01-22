<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Activity;


use OCP\IL10N;

class PlainTextParser {

	/** @var IL10N */
	protected $l;

	/**
	 * @param IL10N $l
	 */
	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	/**
	 * Parse the parameters in the subject and message
	 *
	 * @param string $message
	 * @return string
	 */
	public function parseMessage($message) {
		$message = $this->parseCollections($message);
		$message = $this->parseParameters($message);
		return $message;
	}

	/**
	 * Parse collections
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseCollections($message) {
		return preg_replace_callback('/<collection>(.*?)<\/collection>/', function($match) {
			$parameterList = explode('><', $match[1]);
			$parameterListLength = sizeof($parameterList);

			$parameters = [];
			for ($i = 0; $i < $parameterListLength; $i++) {
				$parameter = $parameterList[$i];
				if ($i > 0) {
					$parameter = '<' . $parameter;
				}
				if ($i + 1 < $parameterListLength) {
					$parameter = $parameter . '>';
				}

				$parameters[] = $this->parseParameters($parameter);
			}
			if ($parameterListLength === 1) {
				return array_pop($parameters);
			} else {
				$lastParameter = array_pop($parameters);
				return $this->l->t('%s and %s', [
					implode($this->l->t(', '), $parameters),
					$lastParameter,
				]);
			}
		}, $message);
	}

	/**
	 * Parse the parameters in the subject and message
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseParameters($message) {
		$message = $this->parseUntypedParameters($message);
		$message = $this->parseUserParameters($message);
		$message = $this->parseFederatedCloudIDParameters($message);
		$message = $this->parseFileParameters($message);
		return $message;
	}

	/**
	 * Display the parameter value
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseUntypedParameters($message) {
		return preg_replace_callback('/<parameter>(.*?)<\/parameter>/', function($match) {
			return $match[1];
		}, $message);
	}

	/**
	 * Display the users display name
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseUserParameters($message) {
		return preg_replace_callback('/<user\ display\-name=\"(.*?)\">(.*?)<\/user>/', function($match) {
			return $match[1];
		}, $message);
	}

	/**
	 * Display the full cloud id
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseFederatedCloudIDParameters($message) {
		return preg_replace_callback('/<federated-cloud-id\ display\-name=\"(.*?)\"\ user=\"(.*?)\"\ server=\"(.*?)\">(.*?)<\/federated-cloud-id>/', function($match) {
			return $match[1];
		}, $message);
	}

	/**
	 * Display the path for files
	 *
	 * @param string $message
	 * @return string
	 */
	protected function parseFileParameters($message) {
		return preg_replace_callback('/<file\ link=\"(.*?)\"\ id=\"(.*?)\">(.*?)<\/file>/', function($match) {
			return $match[3];
		}, $message);
	}
}