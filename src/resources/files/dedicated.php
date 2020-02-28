<?php
	/*
		# Dedicated IP Checker

		## Metadata
		* Version 1.0.1
		* Author: Will Parsons
		* Website: https://parsonsbots.com

		## Repository
		* Transferred from https://github.com/parsonsbots/dedicated-ip-checker

		## Required:
		PHP 5.x+

		## Changelog:
		* 1.0.0: Initial release for IPv4 only
		* 1.0.1: Add support for IPv6

		## What is a Dedicated IP Checker?
		This simple dedicated IP checker was built to be integrated with hosting and VPN / proxy services to provide a way for users to verify dedicated IP address exclusivity.

		A dedicated IP is an IP address that's only provided to a single user as a usage preference and for security purposes. A semi-dedicated IP is an IP address that's provided to multiple users for affordability.

		This dedicated IP checker is improving the standard of transparency where dedicated IP addresses are purchased and will help prevent them from being shared between users (intentionally or unintentionally).

		Why are dedicated IPs important and why should users be able to verify dedicated IP exclusivity?

		- Websites may eventually block your dedicated IP if it's shared with other users and too many connections are coming from the same shared network IP.
		- When using a dedicated IP to whitelist access to sensitive systems (through a VPN, proxy server, etc), multiple users should never have access to that same IP for security purposes.

		## How Does it Work?
		You can enable users to search and verify dedicated IP exclusivity for a list of dedicated IPs without them being logged-in to their account.

		Each user should already have their own unique user ID to correspond with their purchased dedicated IPs.

		Here's an example of the verification process:

		1. User logs into your website's hosting portal or VPN / proxy control panel.
		2. User retrieves their unique user account ID and dedicated IPs you've provided them.
		3. User logs out of their account.
		4. User visits the dedicated IP checker page on your website.
		5. User inputs the list of dedicated IPs to see if they correspond with their unique user account ID.

		Inactive IPs, semi-dedicated IPs, shared IPs, reserved IPs or IPs that aren't related to your website will be masked with random user IDs for security and privacy purposes.

		## Usage
		### Define IP List to Check
			 * IP list to check separated by new line
			 *
			 * Reserved IP addresses below are used for demo purposes
			 *
				$ipListToCheck = implode("\n", array(
					'2001:db8:abcd:0008:847g:3e2:1088:8888',
					'2001:db8:abcd:0008::ffff:ffff',
					'2001:db8:abcd:0008:0000:0000:8888:ffff',
					'2001:db8:abcd:0008::1234:8888:FFFF',
					'172.16.88.10',
					'172.16.8.3',
					'172.16.200.3',
					'172.16.201.4',
					'192.168.0.0',
					'192.168.0.1',
					'192.168.0.2',
					'192.168.0.3',
					'192.168.0.4',
					'192.168.40.4',
					'192.168.49.4',
					'10.5.30.103',
					'10.5.30.105',
					'10.5.30.106',
					'10.5.31.108',
					'_.10.5.30.199_;*', // Invalid IP formats will be parsed
					'10.5.89.44',
					'10.8.8.100',
					'10.8.8.101',
					'10.8.8.102',
					'10.8.8.103'
				));

		### Define IPs That Belong to Users
			 * List of dedicated IP addresses that belong to each user (IP => ID format)
			 * Users should have access to see their own user ID
			 *
			 * Reserved IP addresses below are used for demo purposes
			 *
				$userIps = array(
					'2001:db8:abcd:0008:0000:0000:8888:ffff' => '5c9518c1-0ad8-4e41-80a1-5bd54221ccee',
					'192.168.0.2' => '5c95396e-97c8-49af-abb3-17e04221ccee',
					'192.168.0.3' => '5c95396e-97c8-49af-abb3-17e04221ccee',
					'10.5.31.108' => '5c95354f-43c8-4976-b69d-12334221ccee',
					'172.16.88.10' => '5c953514-e9e8-4cca-954c-12334221ccee',
					'172.16.200.3' => '5c953514-e9e8-4cca-954c-12334221ccee',
					'10.5.89.44' => '5c952170-f908-45e9-af43-6bf64221ccee',
					'10.5.30.199' => '5c9518c1-0ad8-4e41-80a1-5bd54221ccee',
					'172.16.8.3' => '5c951454-0418-4ca5-80f8-5b6b4221ccee',
					'192.168.40.4' => '5c951242-56a9-401a-8d74-5b7b4221ccee',
					'10.8.8.102' => '5c95396e-97c8-49af-abb3-17e04221ccee'
				);

				// Include formatted sample data (this file should be changed to your own database values and user input)
				require_once('data.php');

				// Include dedicated IP checker
				require_once('checker.php');

				$checker = new DedicatedIpChecker();
				$verifiedIpList = $checker->verify($ipListToCheck, $userIps);

		### Results
				echo '<pre>';

				foreach ($verifiedIpList as $ip => $userId) {
					echo 'IP: ' . $ip . "\n";
					echo 'User ID: ' . $userId . "\n\n";
				}

				echo '</pre>';

				Results:

				IP: 2001:db8:abcd:0008:847g:3e2:1088:8888
				User ID: 5c951454-0418-4ca5-80f8-5b6b4221ccee

				IP: 2001:db8:abcd:0008:0000:0000:ffff:ffff
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 2001:db8:abcd:0008:0000:0000:8888:ffff
				User ID: 5c9518c1-0ad8-4e41-80a1-5bd54221ccee

				IP: 2001:db8:abcd:0008:0000:1234:8888:FFFF
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 172.16.88.10
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 172.16.8.3
				User ID: 5c951454-0418-4ca5-80f8-5b6b4221ccee

				IP: 172.16.200.3
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 172.16.201.4
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 192.168.0.0
				User ID: 5c951242-56a9-401a-8d74-5b7b4221ccee

				IP: 192.168.0.1
				User ID: 5c951242-56a9-401a-8d74-5b7b4221ccee

				IP: 192.168.0.2
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

				IP: 192.168.0.3
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

				IP: 192.168.0.4
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

				IP: 192.168.40.4
				User ID: 5c951242-56a9-401a-8d74-5b7b4221ccee

				IP: 192.168.49.4
				User ID: 5c9518c1-0ad8-4e41-80a1-5bd54221ccee

				IP: 10.5.30.103
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

				IP: 10.5.30.105
				User ID: 5c951454-0418-4ca5-80f8-5b6b4221ccee

				IP: 10.5.30.106
				User ID: 5c9518c1-0ad8-4e41-80a1-5bd54221ccee

				IP: 10.5.31.108
				User ID: 5c95354f-43c8-4976-b69d-12334221ccee

				IP: 10.5.30.199
				User ID: 5c9518c1-0ad8-4e41-80a1-5bd54221ccee

				IP: 10.5.89.44
				User ID: 5c952170-f908-45e9-af43-6bf64221ccee

				IP: 10.8.8.100
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 10.8.8.101
				User ID: 5c953514-e9e8-4cca-954c-12334221ccee

				IP: 10.8.8.102
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

				IP: 10.8.8.103
				User ID: 5c95396e-97c8-49af-abb3-17e04221ccee

		## More From ParsonsBots
		- https://github.com/parsonsbots
		- https://parsonsbots.com
	*/

	class DedicatedIpChecker {

		public function __construct() {
			$this->userIds = array();
		}

	/**
	 * Get user ID for an IP address
	 *
	 * @param string $ip
	 * @param array $userIps
	 *
	 * @return string $response User ID for dedicated IP (if user ID doesn't exist for IP, pick an ID from existing user IDs)
	 */
		protected function _getUserId($ip, $userIps) {
			if (!empty($userIps[$ip])) {
				return $userIps[$ip];
			}

			if (strpos($ip, ':') !== false) {
				$characters = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz';
				$ipCharacters = str_replace(0, '', strtolower($ip));

				for ($i = 0; $i < strlen($ipCharacters); $i++) {
					if (($numericValue = strpos($characters, $ipCharacters[$i])) !== false) {
						$ipCharacters[$i] = $numericValue + 1 + ($numericValue & ($numericValue * $numericValue));
					}
				}

				$subnets = array_filter(explode(':', $ipCharacters));
				$key = array_sum($subnets) * count($subnets);
			} else {
				$subnets = explode('.', $ip);
				$key = max($subnets[0], 1) * max($subnets[3], 1) + $subnets[2] + $subnets[1];
			}

			$response = ($this->userIds[$key]);
			return $response;
		}

	/**
	 * Parse and filter IP address list
	 *
	 * @param mixed [array/string] $ips
	 *
	 * @return array $response
	 */
		protected function _parseIps($ips = array()) {
			if (!is_array($ips)) {
				$ips = array_filter(preg_split("/[](\r\n|\n|\r) <>()~{}|`\"'=?!*&@#$+,[;_-]/", $ips));
			}

			$response = $this->_validateIps($ips);
			return $response;
		}

	/**
	 * Validate IPv4 address
	 *
	 * @param string $ip
	 *
	 * @return mixed [boolean/string] $response
	 */
		protected function _validateIpv4($ip) {
			$response = false;
			$splitIpSubnets = explode('.', $ip);

			if (count($splitIpSubnets) === 4) {
				foreach ($splitIpSubnets as $splitIpSubnet) {
					if (
						!is_numeric($splitIpSubnet) ||
						strlen($splitIpSubnet) >= 4 ||
						$splitIpSubnet > 255 ||
						$splitIpSubnet < 0
					) {
						return false;
					}
				}

				$response = $ip;
			}

			return $response;
		}

	/**
	 * Validate IPv6 address
	 *
	 * @param string $ip
	 *
	 * @return mixed [boolean/string] $response
	 */
		protected function _validateIpv6($ip) {
			$response = false;

			if (strpos($ip, '::') !== false) {
				$ip = str_replace('::', str_repeat(':0000', 7 - (substr_count($ip, ':') - 1)) . ':', $ip);
			}

			$splitIpSubnets = explode(':', $ip);

			if (count($splitIpSubnets) === 8) {
				foreach ($splitIpSubnets as $splitIpSubnet) {
					if (strlen($splitIpSubnet) > 4) {
						return false;
					}
				}

				$response = $ip;
			}

			return $response;
		}

	/**
	 * Validate IP address list
	 *
	 * @param array $ips
	 *
	 * @return array $response
	 */
		protected function _validateIps($ips) {
			foreach ($ips as $key => $ip) {
				if (
					empty($ip) ||
					strlen($ip) < 7 ||
					!($ip = trim($ip, '.')) ||
					!($ip = trim($ip, ':')) ||
					(
						strpos($ip, ':::') === false &&
						substr_count($ip, ':') > 4 &&
						($ips[$key] = $this->_validateIpv6($ip)) === false
					) ||
					(
						strpos($ip, ':') === false &&
						($ips[$key] = $this->_validateIpv4($ip)) === false
					)
				) {
					unset($ips[$key]);
				}
			}

			$response = implode("\n", array_unique($ips));
			return $response;
		}

	/**
	 * Verify dedicated IPs
	 *
	 * @param array $ips
	 * @param array $userIps
	 *
	 * @return $response
	 */
		public function verify($ips, $userIps) {
			$ips = $this->_parseIps($ips);
			$this->userIds = array_values($userIps);

			if (!empty($ips)) {
				$userIdsFormatted = false;

				do {
					$this->userIds = array_merge($this->userIds, $this->userIds);

					if (count($this->userIds) > 255255) {
						$userIdsFormatted = true;
					}
				} while ($userIdsFormatted === false);
			}

			$ips = explode("\n", $ips);

			foreach ($ips as $key => $ip) {
				$ips[$ip] = $this->_getUserId($ip, $userIps);
				unset($ips[$key]);
			}

			$response = array_merge($ips, $userIps);
			return $response;
		}

	}
?>
