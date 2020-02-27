<?php
	/*
		# Dynamic Proxy Node Reconfiguration
		* Version 1.0.0
		* Author: Will Parsons
		* Website: https://parsonsbots.com

		## Repository
		* Transferred from https://github.com/parsonsbots/dynamic-proxy-node-reconfiguration

		## Required:
		[Coming soon]

		## Changelog:
		* 1.0.0: Initial release

		## About

		### What is Dynamic Proxy Node Reconfiguration?
		Reconfigure and manage redundant proxy processes in Squid (HTTP) and 3proxy (SOCKS 5) with:

		- No connection interruptions at scale when sending reconfiguration signals to proxy processes
		- Dynamic authentication and whitelisting with an unlimited number of unique ACLs for individual nodes
		- Load balancing from a single listening port and single server using internal redundant process grid computing (with unicast)

		This PHP library enables high throughput and decreases network congestion for proxy servers and internet nodes:

		- Without installing custom TCP congestion control algorithms
		- Without opening port hijacking vulnerabilities with SO_REUSEPORT, shared IPs, etc
		- Without using excessive resources or requiring large anycast server clusters
		- Without integrating external proxy authentication methods (once API data is retrieved)

		[Full explanation coming soon]

		## Installation
		[Installation instructions coming soon]

		## Configuration
		[Configuration instructions coming soon]

		## Usage
		[Full usage instructions coming soon]

			ini_set('max_execution_time', 595); // Set time limit to 10 minutes for reconfiguration
			require_once('dynamic_reconfiguration.php');

			$apiUrl = 'https://ipbot.com/api/servers';
			$sshPorts = array(
				'22'
			);

			// Initiate dynamic proxy reconfiguration
			$dynamicProxyReconfiguration = new DynamicProxyReconfiguration($apiUrl, $sshPorts);

			// Reconfigure all proxy processes
			$dynamicProxyReconfiguration->start();


		## Coming Soon
		* 1.0.1
			- Full requirements with installation, configuration and usage instructions are coming soon

		* 1.0.2
			- Add support for additional HTTP and SOCKS proxy servers (varnish, dante, etc)

		## More From ParsonsBots
		- https://github.com/parsonsbots
		- https://parsonsbots.com
	*/

	class DynamicProxyReconfiguration {

		public $apiUrl;
		public $sshPorts;

		public function __construct($apiUrl, $sshPorts) {
			$this->apiUrl = $apiUrl;
			$this->sshPorts = $sshPorts;
		}

	/**
	 * Apply seamless processes reconfiguration
	 *
	 * @return boolean
	 */
		protected function _applyReconfiguration() {
			$serverJsonData = shell_exec('curl ' . $this->apiUrl . ' --connect-timeout 10');
			$this->server = json_decode($serverJsonData, true);
			$this->_verifyDns();
			$firewallIps = !empty($this->server['data']['proxy_ips']) ? $this->server['data']['proxy_ips'] : array();
			$firewallIp = key($firewallIps);
			$firewallPorts = array();

			if (empty($firewallIps)) {
				return false;
			}

			$processId = $this->server['data']['settings']['paths']['process_ids'] . 'reconfigure.pid';

			if (file_exists($processId)) {
				$lastRan = file_get_contents($processId);

				if ($lastRan > strtotime('-10 minutes')) {
					return false;
				}

				if (file_exists($processId)) {
					unlink($processId);
				}
			}

			$this->_createDirectories();
			$this->_createFiles();
			file_put_contents($processId, time());
			file_put_contents($this->server['data']['settings']['paths']['cache'] . 'serverData', $serverJsonData);

			if (
				!empty($this->server['data']['server_configuration']['kernel']) &&
				!empty($this->server['data']['server_configuration']['kernel']['options']) &&
				!empty($this->server['data']['server_configuration']['kernel']['path']) &&
				!empty($this->server['data']['server_configuration']['kernel']['save'])
			) {
				file_put_contents($this->server['data']['server_configuration']['kernel']['path'], implode("\n", $this->server['data']['server_configuration']['kernel']['options']));
				shell_exec($this->server['data']['server_configuration']['kernel']['save']);
			}

			$allFirewallPorts = $this->server['data']['forwarding_ports'] = array();

			foreach ($this->server['data']['proxy_process_ports'] as $proxyProcessName => $proxyProcessPorts) {
				foreach ($proxyProcessPorts['primary'] as $proxyProcessPortKey => $proxyProcessPort) {
					$allFirewallPorts[$proxyProcessName][] = $proxyProcessPort;
					$this->server['data']['forwarding_ports'][$proxyProcessName][] = $proxyProcessPort;
				}

				foreach ($proxyProcessPorts['secondary'] as $proxyProcessPortKey => $proxyProcessPorts) {
					foreach ($proxyProcessPorts as $proxyProcessPort) {
						$allFirewallPorts[$proxyProcessName][] = $proxyProcessPort;
						$this->server['data']['forwarding_ports'][$proxyProcessName][] = $proxyProcessPort;

						if ($this->_verifyPort($firewallIp, $proxyProcessPort, $this->server['data']['proxy_configurations'][$proxyProcessName]['protocol'])) {
							$firewallPorts[$proxyProcessName][] = $proxyProcessPort;
						}
					}
				}

				$this->server['data']['forwarding_ports'][$proxyProcessName] = array_unique($this->server['data']['forwarding_ports'][$proxyProcessName]);
				shuffle($this->server['data']['forwarding_ports'][$proxyProcessName]);
			}

			$this->_applyFirewallRules($firewallPorts);
			$proxyProcessDelays = $firewallPorts = array();

			foreach ($this->server['data']['proxy_processes'] as $proxyProcessName => $proxyProcesses) {
				$proxyProcessDelays[$proxyProcessName] = $proxyProcesses[0]['delays'];
				$this->_reconfigure($proxyProcesses[0]);
				unset($this->server['data']['proxy_processes'][$proxyProcessName][0]);
				$this->server['data']['proxy_processes'][$proxyProcessName] = array_chunk($proxyProcesses, round(count($proxyProcesses) / 2), true);
			}

			foreach (array(0, 1) as $value) {
				foreach ($this->server['data']['proxy_process_ports'] as $proxyProcessName => $proxyProcessPorts) {
					$mergedFirewallPorts = array_merge($proxyProcessPorts['primary'], $proxyProcessPorts['secondary'][($value ? 1 : 0)]);

					foreach ($mergedFirewallPorts as $mergedFirewallPort) {
						if ($this->_verifyPort($firewallIp, $mergedFirewallPort, $this->server['data']['proxy_configurations'][$proxyProcessName]['protocol'])) {
							$firewallPorts[$proxyProcessName][] = $mergedFirewallPort;
						}
					}
				}

				$this->_applyFirewallRules($firewallPorts);
				$firewallPorts = array();

				foreach ($this->server['data']['proxy_processes'] as $proxyProcessName => $splitProxyProcesses) {
					$splitProxyProcesses = $splitProxyProcesses[($value ? 0 : 1)];

					foreach ($splitProxyProcesses as $proxyProcessKey => $proxyProcess) {
						if (
							empty($splitProxyProcesses[($proxyProcessKey + 1)]) &&
							!empty($proxyProcessDelays[$proxyProcessName])
						) {
							$proxyProcess['delays'] = $proxyProcessDelays[$proxyProcessName];
						}

						$this->_reconfigure($proxyProcess);
					}
				}
			}

			foreach ($allFirewallPorts as $proxyProcessName => $proxyProcessPorts) {
				foreach ($proxyProcessPorts as $proxyProcessPortKey => $proxyProcessPort) {
					if ($this->_verifyPort($firewallIp, $proxyProcessPort, $this->server['data']['proxy_configurations'][$proxyProcessName]['protocol'])) {
						$firewallPorts[$proxyProcessName][] = $proxyProcessPort;
					}
				}
			}

			$this->_applyFirewallRules($firewallPorts);
			$this->_verifyDns();
			unlink($processId);
			return true;
		}

	/**
	 * Apply firewall rules
	 *
	 * @param array $firewallPorts Firewall ports
	 *
	 * @return array $firewallRules Firewall rules
	 */
		protected function _applyFirewallRules($firewallPorts) {
			if (empty($firewallPorts)) {
				return false;
			}

			$firewallRules = array(
				'*filter',
				':INPUT ACCEPT [0:0]',
				':FORWARD ACCEPT [0:0]',
				':OUTPUT ACCEPT [0:0]',
				'-A INPUT -p icmp -m hashlimit --hashlimit-name icmp --hashlimit-mode srcip --hashlimit 1/second --hashlimit-burst 2 -j ACCEPT'
			);

			if (
				!empty($this->sshPorts) &&
				is_array($this->sshPorts)
			) {
				foreach ($this->sshPorts as $sshPort) {
					if (is_numeric($sshPort)) {
						$firewallRules[] = '-A INPUT -p tcp -m tcp --dport ' . $sshPort . ' -m connlimit --connlimit-above 4 --connlimit-mask 32 --connlimit-saddr -j DROP';
						$firewallRules[] = '-A INPUT -p tcp -m tcp --dport ' . $sshPort . ' -m hashlimit --hashlimit-upto 15/hour --hashlimit-burst 3 --hashlimit-mode srcip --hashlimit-name ssh --hashlimit-htable-expire 500000 -j ACCEPT';
					}
				}
			}

			if (
				!empty($this->server['data']['firewall_filter']) &&
				is_array($this->server['data']['firewall_filter'])
			) {
				foreach ($this->server['data']['firewall_filter'] as $rule) {
					$firewallRules[] = $rule;
				}
			}

			foreach ($this->server['data']['dns_process_load_balance_ips'] as $sourceIp => $destinationIps) {
				$dnsIps = array_merge(array(
					$sourceIp
				), $destinationIps);
				$listDnsIps = implode(',', array_unique($destinationIps));
				$firewallRules[] = '-A OUTPUT -d ' . $listDnsIps . ' -p udp -m udp -j ACCEPT';
				$firewallRules[] = '-A OUTPUT -s ' . $listDnsIps . ' -p udp -m udp -j ACCEPT';
			}

			$firewallRules[] = 'COMMIT';
			$firewallRules[] = '*nat';
			$firewallRules[] = ':PREROUTING ACCEPT [0:0]';
			$firewallRules[] = ':INPUT ACCEPT [0:0]';
			$firewallRules[] = ':OUTPUT ACCEPT [0:0]';
			$firewallRules[] = ':POSTROUTING ACCEPT [0:0]';

			foreach ($this->server['data']['dns_process_load_balance_ips'] as $sourceIp => $destinationIps) {
				$destinationIps = array_values($destinationIps);
				krsort($destinationIps);

				foreach ($destinationIps as $destinationIpKey => $destinationIp) {
					$loadBalancer = $destinationIpKey > 0 ? '-m statistic --mode nth --every ' . ($destinationIpKey + 1) . ' --packet 0 ' : '';
					$firewallRules[] = '-A OUTPUT -d ' . $sourceIp . '/32 -p udp -m udp --dport 53 ' . $loadBalancer . '-j DNAT --to-destination ' . $destinationIp;
				}
			}

			foreach ($this->server['data']['forwarding_ports'] as $proxyProcessName => $proxyProcessForwardingPorts) {
				krsort($firewallPorts[$proxyProcessName]);
				$splitProxyProcessForwardingPorts = array_chunk($proxyProcessForwardingPorts, 10);

				foreach ($splitProxyProcessForwardingPorts as $proxyProcessForwardingPorts) {
					$proxyProcessForwardingPorts = implode(',', $proxyProcessForwardingPorts);

					foreach ($firewallPorts[$proxyProcessName] as $proxyProcessPortKey => $proxyProcessPort) {
						$loadBalancer = $proxyProcessPortKey > 0 ? '-m statistic --mode nth --every ' . ($proxyProcessPortKey + 1) . ' --packet 0 ' : '';
						$firewallRules[] = '-A PREROUTING -p tcp -m multiport --dports ' . $proxyProcessForwardingPorts . ' ' . $loadBalancer . '-j DNAT --to-destination :' . $proxyProcessPort . ' --persistent';
					}
				}
			}

			$firewallRules[] = 'COMMIT';
			$firewallRuleChunks = array_chunk($firewallRules, 100);
			$firewallRulePath = $this->server['data']['settings']['paths']['firewall_rules'] . 'rules';

			if (file_exists($firewallRulePath)) {
				unlink($firewallRulePath);
			}

			touch($firewallRulePath);

			foreach ($firewallRuleChunks as $firewallRuleChunk) {
				$saveRules = implode("\n", $firewallRuleChunk);
				shell_exec('echo "' . $saveRules . '" >> ' . $firewallRulePath);
			}

			shell_exec('iptables-restore < ' . $firewallRulePath);
			return $firewallRules;
		}

	/**
	 * Create directories
	 *
	 * @return
	 */
		protected function _createDirectories() {
			$paths = $this->server['data']['settings']['paths'];
			shell_exec('rm -rf ' . $paths['configurations'] . ' ' . $paths['firewall_rules'] . ' ' . $paths['users']);

			foreach ($this->server['data']['proxy_configurations'] as $proxyConfigurationType => $proxyConfiguration) {
				$paths = array_merge(array_values($proxyConfiguration['paths']), $paths);
			}

			foreach ($paths as $path) {
				$directory = substr($path, 0, strripos($path, '/'));

				if (!is_dir($directory)) {
					shell_exec('mkdir -m 777 -p ' . $directory);
				}
			}

			return;
		}

	/**
	 * Create files
	 *
	 * @return
	 */
		protected function _createFiles() {
			$passwordsPath = $this->server['data']['settings']['paths']['passwords'];

			if (!empty($this->server['data']['files'])) {
				foreach ($this->server['data']['files'] as $file) {
					$directory = substr($file['path'], 0, strripos($file['path'], '/'));

					if (
						!empty($directory) &&
						!is_dir($directory)
					) {
						shell_exec('mkdir -m 777 -p ' . $directory);
					}

					if (!file_exists($file['path'])) {
						shell_exec('touch ' . $file['path']);
						file_put_contents($file['path'], $file['contents']);
					}
				}
			}

			shell_exec('htpasswd -cb ' . $passwordsPath . ' default default');
			shell_exec('htpasswd -D ' . $passwordsPath . ' default');

			if (!empty($this->server['data']['users'])) {
				foreach ($this->server['data']['users'] as $username => $password) {
					shell_exec('htpasswd -b ' . $passwordsPath . ' ' . $username . ' ' . $password);
				}
			}

			return;
		}

	/**
	 * Get process IDs from process name
	 *
	 * @param string $processName Process name
	 * @param string $configurationFile Full path to process configuration file
	 *
	 * @return array $processIds Process IDs
	 */
		protected function _getProcessIds($processName, $configurationFile) {
			$processIds = array();
			exec('ps -fC ' . $processName . ' 2>&1', $processes);

			if (!empty($processes[0])) {
				unset($processes[0]);

				foreach ($processes as $process) {
					$processColumns = array_values(array_filter(explode(' ', $process)));

					if (
						!empty($processColumns[1]) &&
						(
							empty($processes[2]) ||
							strpos($configurationFile, '-redundant') === false ||
							$this->_strposa($process, array(
								$processName . ' ',
								$configurationFile
							)) !== false
						)
					) {
						$processIds[] = $processColumns[1];
					}
				}
			}

			return $processIds;
		}

	/**
	 * Reconfigure specific process
	 *
	 * @param array $proxyProcess Proxy process data
	 *
	 * @return
	 */
		protected function _reconfigure($proxyProcess) {
			$basePath = $this->server['data']['settings']['paths']['base'];
			$configurationPath = $proxyProcess['paths']['configuration'];
			$delayEnd = $proxyProcess['delays']['end'];
			$delayStart = $proxyProcess['delays']['start'];

			if (
				!empty($delayStart) &&
				is_numeric($delayStart)
			) {
				sleep($delayStart);
			}

			$killProcesses = $this->_getProcessIds($proxyProcess['name'], $configurationPath);
			$shellCommands = array(
				'#!' . $this->server['data']['server_configuration']['shell']
			);
			$shellScriptFile = $proxyProcess['protocol'] . '.sh';

			foreach ($killProcesses as $killProcess) {
				$shellCommands[] = 'kill -9 ' . trim($killProcess);
			}

			if (count($shellCommands > 1)) {
				if (file_exists($basePath . $shellScriptFile)) {
					unlink($basePath . $shellScriptFile);
				}

				file_put_contents($basePath . $shellScriptFile, implode("\n", $shellCommands));
				shell_exec('chmod +x ' . $basePath . $shellScriptFile);
				shell_exec($basePath . './' . $shellScriptFile);
			}

			if (
				!empty($configurationPath) &&
				!empty($proxyProcess['parameters'])
			) {
				file_put_contents($configurationPath, $proxyProcess['parameters']);
			}

			if (file_exists($proxyProcess['paths']['process_id'])) {
				unlink($proxyProcess['paths']['process_id']);
			}

			sleep(2);
			shell_exec($proxyProcess['start_command']);

			if (
				$delayEnd &&
				is_numeric($delayEnd)
			) {
				sleep($delayEnd);
			}

			$this->_verifyDns();
			return;
		}

	/**
	 * Start reconfiguration
	 *
	 * @return boolean $response
	 */
		public function start() {
			$response = $this->_applyReconfiguration();
			return $response;
		}

	/**
	 * Format strpos to use array as needle
	 *
	 * @param array $haystack
	 * @param array $needles
	 * @param integer $offset
	 *
	 * @return boolean True if match is found, false if no match
	 */
		protected function _strposa($haystack, $needles, $offset = 0) {
			if (!is_array($needles)) {
				$needles = array($needles);
			};

			foreach ($needles as $needle) {
				if (strpos($haystack, $needle, $offset) !== false) {
					return true;
				}
			}

			return false;
		}

	/**
	 * DNS redundancy health checks and process recovery
	 *
	 * @return boolean
	 */
		protected function _verifyDns() {
			if (empty($this->server['data']['dns_process_source_ips'])) {
				return false;
			}

			$dnsIps = array_values($this->server['data']['dns_process_source_ips']);
			$basePath = $this->server['data']['settings']['paths']['base'];

			foreach ($dnsIps as $dnsIpKey => $dnsIp) {
				$processName = $dnsIpKey == 0 ? 'named' : 'named-redundant' . $dnsIpKey;
				$dnsResponse = array();
				exec('dig +time=2 +tries=1 proxies @' . $dnsIp . ' 2>&1', $dnsResponse);

				if (
					!empty($dnsResponse[3]) &&
					strpos(strtolower($dnsResponse[3]), 'got answer') === false
				) {
					$dnsProcesses = array();
					exec('ps $(pgrep named) 2>&1', $dnsProcesses);

					if (!empty($dnsProcesses)) {
						foreach ($dnsProcesses as $dnsProcess) {
							$dnsProcess = array_map('strtolower', array_map('trim', array_values(array_filter(explode(' ', $dnsProcess)))));

							if (
								!empty($dnsProcess[0]) &&
								is_numeric($dnsProcess[0]) &&
								in_array('/usr/sbin/' . $processName, $dnsProcess)
							) {
								$killProcesses = array();
								$shellCommands = array(
									'#!' . $this->server['data']['server_configuration']['shell'],
									'kill -9 ' . trim($dnsProcess[0])
								);

								if (file_exists($basePath . 'dns.sh')) {
									unlink($basePath . 'dns.sh');
								}

								file_put_contents($basePath . 'dns.sh', implode("\n", $shellCommands));
								shell_exec('chmod +x ' . $basePath . 'dns.sh');
								shell_exec($basePath . './dns.sh');
							}
						}
					}

					shell_exec('service ' . str_replace('named', 'bind9', $processName) . ' start');
					sleep(1);
					$this->_verifyDns();
				}
			}

			return true;
		}

	/**
	 * Check HTTP and SOCKS ports
	 *
	 * @param string $ip Proxy IP
	 * @param string $port Proxy port
	 * @param string $protocol Proxy protocol
	 * @param integer $integer Request timeout
	 *
	 * @return boolean $alive True if port is active, false if refusing connections
	 */
		protected function _verifyPort($ip, $port, $protocol, $timeout = 5) {
			$response = false;

			switch ($protocol) {
				case 'http':
					$response = shell_exec('curl -I -s -x ' . $ip . ':' . $port . ' http://squid -v --connect-timeout ' . $timeout . ' --max-time ' . $timeout);

					if ($this->_strposa(strtolower($response), array(
						'407 proxy',
						'403 forbidden',
						' 503 ',
						' timed out '
					)) !== false) {
						$response = true;
					}

					break;
				case 'socks':
					exec('curl --socks5-hostname ' . $ip . ':' . $port . ' http://socks/ -v --connect-timeout ' . $timeout . ' --max-time ' . $timeout . ' 2>&1', $socksResponse);
					$socksResponse = end($socksResponse);
					$response = (strpos(strtolower($socksResponse), 'empty reply ') !== false);
					break;
			}

			return $response;
		}

	}
?>
