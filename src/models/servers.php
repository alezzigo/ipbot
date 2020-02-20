<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ServersModel extends AppModel {

	/**
	 * Format Squid access controls
	 *
	 * @param array $serverDetails
	 *
	 * @return array $response
	 */
		protected function _formatSquid($serverDetails) {
			// TODO: simplify reconfiguration to match new API settings
			$disabledProxies = $formattedFiles = $formattedProxies = $formattedProxyProcessConfigurations = $formattedProxyProcessPorts = $formattedUsers = $gatewayAcls = $proxyAuthenticationAcls = $proxyIpAcls = $proxyWhitelistAcls = array();
			$configuration = $this->proxyConfigurations['squid'];
			$formattedAcls = array(
				'auth_param basic program ' . $configuration['paths']['authentication'] . ' ' . $configuration['paths']['passwords'],
				'auth_param basic children 88888',
				'auth_param basic realm ' . $this->settings['site_name'],
				'auth_param basic credentialsttl 88888 days',
				'auth_param basic casesensitive on'
			);
			$userIndex = 0;

			if (
				($processMinimum = !empty($configuration['process_minimum']) ? $configuration['process_minimum'] : 1) &&
				count($serverDetails['proxy_processes']['squid']) < $processMinimum
			) {
				return false;
			}

			if (!empty($serverDetails['proxy_ips'])) {
				foreach ($serverDetails['proxy_ips'] as $proxyIp => $proxyIndex) {
					$proxyIpAcls[] = 'acl ip' . $proxyIndex . ' localip ' . $proxyIp;
					$proxyIpAcls[] = 'tcp_outgoing_address ' . $proxyIp . ' ip' . $proxyIndex;
				}
			}

			foreach ($serverDetails['proxy_processes']['squid'] as $key => $proxyProcess) {
				$proxyProcessConfigurationParameters = implode("\n", $configuration['parameters']);
				$proxyProcessName = $configuration['process_name'] . ($proxyProcess['number'] ? '-redundant' . $proxyProcess['number'] : '');
				$proxyProcessConfigurationFilePath = $configuration['paths']['configurations'] . $proxyProcessName . '.conf';
				$proxyProcessIdPath = $configuration['paths']['process_id'] . $proxyProcessName . '.pid';
				$proxyProcessConfigurationParameters = str_replace('[dns_ips]', implode(' ', $proxyProcess['dns_ips']), $proxyProcessConfigurationParameters);
				$proxyProcessConfigurationParameters = str_replace('[pid]', 'pid_filename ' . $proxyProcessIdPath, $proxyProcessConfigurationParameters);
				$proxyProcessConfigurationParameters = str_replace('[ports]', 'http_port ' . implode("\n" . 'http_port ', $proxyProcess['ports']), $proxyProcessConfigurationParameters);
				$proxyProcess['parameters'] = $proxyProcessConfigurationParameters;
				$proxyProcess = array_merge(array(
					'delays' => array(
						'start' => 0,
						'end' => ($proxyProcess['number'] ? 0 : 75)
					),
					'name' => $proxyProcessName,
					'parameters' => $proxyProcessConfigurationParameters,
					'paths' => array(
						'configuration' => $proxyProcessConfigurationFilePath,
						'process_id' => $proxyProcessIdPath
					),
					'start_command' => $proxyProcessName . ' start -f ' . $proxyProcessConfigurationFilePath
				), $proxyProcess);
				$formattedProxyProcessConfigurations[] = $proxyProcess;

				foreach ($proxyProcess['ports'] as $port) {
					$formattedProxyProcessPorts[] = $port;
				}
			}

			$serverProxies = array_intersect_key($serverDetails, array(
				'gateway_proxies' => true,
				'static_proxies' => true
			));
			$splitForwardingProxyProcessPortIndexes = array(0, 0);
			$splitForwardingProxyProcessPorts = array_reverse($serverDetails['proxy_process_ports']['squid']['secondary']);
			$splitProxyProcesses = array_chunk($serverDetails['proxy_processes']['squid'], round(count($serverDetails['proxy_processes']['squid']) / 2), false);

			foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $splitForwardingProxyProcessPort) {
				$splitForwardingProxyProcessPorts[$splitForwardingProxyProcessPortKey] = array(
					$splitForwardingProxyProcessPort
				);
			}

			foreach ($splitProxyProcesses as $splitProxyProcessKey => $proxyProcesses) {
				$aclFilename =  'acls' . ((integer) $splitProxyProcessKey) . '.conf';
				$splitProxyProcessKeyStartingIndex = 0;

				if (!empty($splitProxyProcesses[$splitProxyProcessKey - 1])) {
					$previousProxyProcesss = $splitProxyProcesses[$splitProxyProcessKey - 1];
					end($previousProxyProcesss);
					$splitProxyProcessKeyStartingIndex = (key($previousProxyProcesss) * $splitProxyProcessKey) + 1;
				}

				foreach ($proxyProcesses as $proxyProcessKey => $proxyProcess) {
					$formattedProxyProcessKey = $proxyProcessKey + $splitProxyProcessKeyStartingIndex;
					$formattedProxyProcessConfigurations[$formattedProxyProcessKey]['parameters'] = str_replace('[acl_filepath]', $configuration['paths']['configurations'] . $aclFilename, $formattedProxyProcessConfigurations[$formattedProxyProcessKey]['parameters']);
				}
			}

			foreach ($serverProxies as $proxyType => $proxies) {
				foreach ($proxies as $proxy) {
					if (
						!empty($proxy['whitelisted_ips']) &&
						!empty($proxy['require_authentication'])
					) {
						$sources = json_encode(array_filter(explode("\n", $proxy['whitelisted_ips'])));

						if (
							empty($formattedProxies['whitelist'][$sources]) ||
							!in_array($proxy['ip'], $formattedProxies['whitelist'][$sources])
						) {
							$formattedProxies['whitelist'][$sources][$proxy['ip']] = $proxy['ip'];
						}
					}

					if (
						!empty($proxy['username']) &&
						!empty($proxy['password']) &&
						!empty($proxy['require_authentication'])
					) {
						if (
							empty($formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']]) ||
							!in_array($proxy['ip'], $formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']])
						) {
							$formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']][] = $proxy['ip'];
						}
					}

					$forwardingSources = array(
						$proxy['ip']
					);

					if (!empty($proxy['global_forwarding_proxies'])) {
						foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
							$splitForwardingProxyProcessPorts[$splitForwardingProxyProcessPortKey] = array_chunk($forwardingProxyProcessPorts[0], round(count($forwardingProxyProcessPorts[0]) / 2));
						}

						foreach ($proxy['global_forwarding_proxies'] as $globalForwardingProxy) {
							foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
								$forwardingProxyProcessPorts = $forwardingProxyProcessPorts[1];

								if (empty($forwardingProxyProcessPorts[$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]])) {
									$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey] = 0;
								}

								$forwardingProxyProcessPort = $forwardingProxyProcessPorts[$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]];
								$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]++;
								$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer ' . $globalForwardingProxy['ip'] . ' parent ' . $forwardingProxyProcessPort . ' 0 connect-fail-limit=1 connect-timeout=2 name=' . $globalForwardingProxy['id'] . ' round-robin';
								$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $globalForwardingProxy['id'] . ' allow ip' . $serverDetails['proxy_ips'][$proxy['ip']];
							}

							if (
								!empty($proxy['local_forwarding_proxies']) &&
								in_array($globalForwardingProxy['id'], $proxy['local_forwarding_proxies'])
							) {
								$formattedProxies['whitelist'][json_encode($forwardingSources)][$globalForwardingProxy['ip']] = $globalForwardingProxy['ip'];
							}
						}
					}

					if (
						$proxyType === 'gateway_proxies' &&
						empty($proxy['allow_direct'])
					) {
						foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
							$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'never_direct allow ip' . $serverDetails['proxy_ips'][$proxy['ip']];
						}
					}

					if (!empty($proxy['static_proxies'])) {
						$splitStaticProxies = $proxy['static_proxies'];

						foreach ($splitStaticProxies as $splitStaticProxyKey => $staticProxies) {
							$gatewayIp = $proxy['ip'];
							$staticProxyIps = array();

							if (!empty($proxy['global_forwarding_proxies'])) {
								$forwardingSources[] = $gatewayIp = $proxy['global_forwarding_proxies'][$splitStaticProxyKey]['ip'];
							}

							$gatewayIpIndex = (integer) $serverDetails['proxy_ips'][$gatewayIp];
							shuffle($staticProxies);

							foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
								$forwardingProxyProcessPorts = $forwardingProxyProcessPorts[0];

								foreach ($staticProxies as $staticProxy) {
									if (empty($forwardingProxyProcessPorts[$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]])) {
										$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey] = 0;
									}

									$forwardingProxyProcessPort = $forwardingProxyProcessPorts[$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]];
									$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]++;
									$staticProxyProcessPorts = array(
										$forwardingProxyProcessPort
									);

									if (empty($staticProxies[1])) {
										$staticProxyProcessPorts = $forwardingProxyProcessPorts;
									}

									foreach ($staticProxyProcessPorts as $staticProxyProcessPortKey => $staticProxyProcessPort) {
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer ' . $staticProxy['ip'] . ' parent ' . $staticProxyProcessPort . ' 0 connect-fail-limit=1 connect-timeout=2 name=' . $staticProxy['id'] . $staticProxyProcessPortKey . ' round-robin';
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $staticProxy['id'] . $staticProxyProcessPortKey . ' allow ip' . $gatewayIpIndex;
									}

									$formattedProxies['whitelist'][json_encode($forwardingSources)][$staticProxy['ip']] = $staticProxy['ip'];
								}

								if (
									$gatewayIp !== $proxy['ip'] &&
									empty($proxy['global_forwarding_proxies'][$splitStaticProxyKey]['allow_direct'])
								) {
									$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'never_direct allow ip' . $gatewayIpIndex;
								}
							}
						}
					}

					if (!empty($proxy['disable_http'])) {
						$disabledProxies[$proxy['ip']] = $proxy['ip'];
					}
				}
			}

			if (!empty($formattedProxies['authentication'])) {
				foreach ($formattedProxies['authentication'] as $credentials => $destinations) {
					$splitAuthentication = explode($this->keys['start'], $credentials);
					$formattedAcls[] = 'acl user' . $userIndex . ' proxy_auth ' . $splitAuthentication[0];
					$formattedFiles[] = array(
						'contents' => implode("\n", $destinations),
						'path' => $configuration['paths']['users'] . $userIndex . '/d.txt'
					);
					$formattedUsers[$splitAuthentication[0]] = $splitAuthentication[1];
					$proxyAuthenticationAcls[] = 'acl d' . $userIndex . ' localip "' . $configuration['paths']['users'] . $userIndex . '/d.txt"';
					$proxyAuthenticationAcls[] = 'http_access allow d' . $userIndex . ' user' . $userIndex;
					$userIndex++;
				}
			}

			$formattedAcls = array_merge($formattedAcls, $proxyIpAcls);

			if (!empty($formattedProxies['whitelist'])) {
				foreach ($formattedProxies['whitelist'] as $sources => $destinations) {
					$sources = json_decode($sources, true);
					$splitSources = array_chunk($sources, '500');

					foreach ($splitSources as $sourceChunk) {
						$formattedFiles[] = array(
							'contents' => implode("\n", $destinations),
							'path' => $configuration['paths']['users'] . $userIndex . '/d.txt'
						);
						$formattedFiles[] = array(
							'contents' => implode("\n", $sourceChunk),
							'path' => $configuration['paths']['users'] . $userIndex . '/s.txt'
						);
						$proxyWhitelistAcls[] = 'acl d' . $userIndex . ' localip "' . $configuration['paths']['users'] . $userIndex . '/d.txt"';
						$proxyWhitelistAcls[] = 'acl s' . $userIndex . ' src "' . $configuration['paths']['users'] . $userIndex . '/s.txt"';
						$proxyWhitelistAcls[] = 'http_access allow s' . $userIndex . ' d' . $userIndex;
						$userIndex++;
					}
				}
			}

			$formattedAcls = array_merge($formattedAcls, $proxyWhitelistAcls, $proxyAuthenticationAcls);
			$formattedAcls[] = 'http_access deny all';

			foreach ($formattedProxyProcessConfigurations as $formattedProxyProcessConfiguration) {
				$formattedFiles[] = array(
					'contents' => $formattedProxyProcessConfiguration['parameters'],
					'path' => $formattedProxyProcessConfiguration['paths']['configuration']
				);
			}

			foreach (array(0, 1) as $splitAclFileNumber) {
				$splitAclFileContents = $formattedAcls;

				if (!empty($gatewayAcls[$splitAclFileNumber])) {
					$splitAclFileContents = array_merge($splitAclFileContents, $gatewayAcls[$splitAclFileNumber]);
				}

				$formattedFiles[] = array(
					'contents' => implode("\n", $splitAclFileContents),
					'path' => $configuration['paths']['configurations'] . 'acls' . ((integer) $splitAclFileNumber) . '.conf'
				);
			}

			$response = array(
				'files' => $formattedFiles,
				'proxy_processes' => array(
					'squid' => $formattedProxyProcessConfigurations
				)
			);

			if (empty($serverDetails['users'])) {
				$response['users'] = $formattedUsers;
			}

			if (!empty($disabledProxies)) {
				// TODO: use ipset for disabled IPs and ports
				$splitDisabledPorts = array_chunk($formattedProxyProcessPorts, '10');
				$splitDisabledProxies = array_chunk($disabledProxies, '10');

				foreach ($splitDisabledProxies as $disabledProxies) {
					foreach ($splitDisabledPorts as $disabledPorts) {
						$response['firewall_filter'][] = '-A INPUT -p tcp ! -i lo -d ' . implode(',', $disabledProxies) . ' -m multiport --dports ' . implode(',', $disabledPorts) . ' -j DROP';
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve server details
	 *
	 * @return array $response
	 */
		protected function _retrieveServerDetails() {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'Access denied from ' . ($serverIp = $_SERVER['REMOTE_ADDR']) . ', please try again.'
				)
			);
			$server = $this->fetch('servers', array(
				'conditions' => array(
					'ip' => $serverIp,
					'status' => 'online'
				),
				'fields' => array(
					'configuration',
					'id'
				)
			));
			$proxyConfiguration = $serverConfiguration = array();

			if (!empty($server['count'])) {
				$response['message']['text'] = 'Duplicate server IPs, please check server options in database.';
				$serverData = $server['data'][0];

				if ($server['count'] === 1) {
					$response['message']['text'] = 'No active nodes available on gateway server.';
					$nodeIds = $this->fetch('nodes', array(
						'conditions' => array(
							'allocated' => true,
							'server_id' => $serverData['id']
						),
						'fields' => array(
							'id'
						)
					));

					if (!empty($nodeIds['count'])) {
						$response['message']['text'] = 'No active proxies available on server.';
						$serverProxyDetails = $this->_retrieveServerProxyDetails(array(
							'id' => $serverData['id'],
							'node_ids' => $nodeIds['data']
						));

						if (!empty($serverProxyDetails)) {
							$response['message']['status'] = 'Invalid server configuration, please check your configuration file and server options in database.';

							if (
								!empty($serverData['configuration']) &&
								!empty($this->serverConfigurations[$serverData['configuration']])
							) {
								$response['message']['status'] = 'Invalid proxy configuration settings, please check your configuration file and server options in database.';
								$serverConfiguration = $this->serverConfigurations[$serverData['configuration']];

								if (
									!empty($this->proxyConfigurations) &&
									is_array($this->proxyConfigurations)
								) {
									$response = array(
										'data' => array_merge($serverProxyDetails, array(
											'proxy_configurations' => $this->proxyConfigurations,
											'server_configuration' => $serverConfiguration,
											'settings' => $this->settings['reconfiguration']
										)),
										'message' => array(
											'status' => 'success',
											'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
										)
									);

									foreach ($this->proxyConfigurations as $proxyType => $proxyConfiguration) {
										if (
											method_exists($this, ($method = '_format' . ucwords($proxyType))) &&
											($formattedProxyProcessItems = $this->$method($response['data']))
										) {
											foreach ($formattedProxyProcessItems as $formattedProxyProcessItemKey => $formattedProxyProcessItem) {
												if (empty($response['data'][$formattedProxyProcessItemKey])) {
													$response['data'][$formattedProxyProcessItemKey] = $formattedProxyProcessItem;
												} elseif ($formattedProxyProcessItemKey !== 'users') {
													$response['data'][$formattedProxyProcessItemKey] = array_merge($response['data'][$formattedProxyProcessItemKey], $formattedProxyProcessItem);
												}
											}
										}
									}

									if (!empty($response['data'])) {
										$response['message'] = array(
											'status' => 'success',
											'text' => 'Proxies retrieved for server ' . $serverIp . ' successfully.'
										);
									}
								}
							}
						}
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve server proxy details
	 *
	 * @param array $serverData
	 *
	 * @return array $response
	 */
		protected function _retrieveServerProxyDetails($serverData) {
			$response = array(
				'proxy_processes' => $this->_retrieveServerProxyProcesses($serverData['id'])
			);

			if (empty($response['proxy_processes'])) {
				return false;
			}

			foreach ($response['proxy_processes'] as $proxyProcessType => $proxyProcesses) {
				$splitProxyProcesses = array_chunk($proxyProcesses, round(count($proxyProcesses) / 2), false);

				foreach ($proxyProcesses as $proxyProcessKey => $proxyProcess) {
					$proxyProcessPortKey = empty($proxyProcessKey) ? 'primary' : 'secondary';
					$splitProxyProcessPortKey = (
						!empty($splitProxyProcessPortKey) ||
						$proxyProcess['ports'][0] === $splitProxyProcesses[1][0]['ports'][0]
					) ? 1 : 0;

					foreach ($proxyProcess['dns'] as $dnsProcesses) {
						$response['dns_process_load_balance_ips'][$dnsProcesses['listening_ip']][$dnsProcesses['source_ip']] = $dnsProcesses['source_ip'];
						$response['dns_process_source_ips'][$dnsProcesses['source_ip']] = $dnsProcesses['source_ip'];
						$response['proxy_processes'][$proxyProcessType][$proxyProcessKey]['dns_ips'][$dnsProcesses['listening_ip']] = $dnsProcesses['listening_ip'];
					}

					foreach ($proxyProcess['ports'] as $proxyProcessPort) {
						$response['proxy_process_ports'][$proxyProcessType][$proxyProcessPortKey][$splitProxyProcessPortKey][] = $proxyProcessPort;
					}
				}

				$response['proxy_process_ports'][$proxyProcessType]['primary'] = $response['proxy_process_ports'][$proxyProcessType]['primary'][0];
			}

			$proxyParameters = array(
				'conditions' => array(
					'node_id' => $serverData['node_ids'],
					'type' => 'gateway',
					'NOT' => array(
						'status' => 'offline'
					)
				),
				'fields' => array(
					'allow_direct',
					'disable_http',
					'http_port',
					'id',
					'ip',
					'isp',
					'next_rotation_date',
					'node_id',
					'password',
					'previous_rotation_proxy_id',
					'previous_rotation_proxy_ip',
					'require_authentication',
					'rotation_frequency',
					'rotation_proxy_id',
					'rotation_proxy_ip',
					'status',
					'type',
					'username',
					'whitelisted_ips'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'ASC'
				)
			);
			$gatewayProxies = $this->fetch('proxies', $proxyParameters);

			if (!empty($gatewayProxies['count'])) {
				$response['gateway_proxies'] = $gatewayProxies['data'];

				foreach ($response['gateway_proxies'] as $gatewayProxyKey => $gatewayProxy) {
					$gatewayProxyIdParameters = array(
						'conditions' => array(
							'gateway_proxy_id' => $gatewayProxy['id']
						),
						'fields' => array(
							'proxy_id'
						)
					);

					if (
						$rotateOnEveryRequest = (
							empty($gatewayProxy['rotation_frequency']) &&
							!is_numeric($gatewayProxy['rotation_frequency'])
						)
					) {
						$gatewayProxyForwardingProxyIds = $this->fetch('proxy_forwarding_proxies', $gatewayProxyIdParameters);
						$globalForwardingProxyParameters = $localForwardingProxyParameters = $proxyParameters;

						if (!empty($gatewayProxyForwardingProxyIds['count'])) {
							$globalForwardingProxyParameters['conditions'] = $localForwardingProxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
								'id' => $gatewayProxyForwardingProxyIds['data'],
								'type' => 'forwarding'
							));
							unset($globalForwardingProxyParameters['conditions']['node_id']);
							$localForwardingProxyParameters['fields'] = array(
								'id'
							);
							$globalForwardingProxies = $this->fetch('proxies', $globalForwardingProxyParameters);
							$localForwardingProxies = $this->fetch('proxies', $localForwardingProxyParameters);

							if (!empty($globalForwardingProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies'] = $globalForwardingProxies['data'];
							}

							if (!empty($localForwardingProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['local_forwarding_proxies'] = $localForwardingProxies['data'];
							}
						}
					}

					$gatewayProxyStaticProxyIds = $this->fetch('proxy_static_proxies', $gatewayProxyIdParameters);

					if (
						$rotateOnEveryRequest &&
						!empty($gatewayProxyStaticProxyIds['count'])
					) {
						$staticProxyParameters = $proxyParameters;
						$staticProxyParameters['conditions'] = array_merge($staticProxyParameters['conditions'], array(
							'id' => $gatewayProxyStaticProxyIds['data'],
							'type' => 'static'
						));
						unset($staticProxyParameters['conditions']['node_id']);
						$staticProxies = $this->fetch('proxies', $staticProxyParameters);

						if (!empty($staticProxies['count'])) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array(
								$staticProxies['data']
							);
						}

						if (
							!empty($response['gateway_proxies'][$gatewayProxyKey]['static_proxies']) &&
							!empty($response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies']) &&
							($gatewayGlobalForwardingProxies = $response['gateway_proxies'][$gatewayProxyKey]['global_forwarding_proxies']) &&
							($gatewayStaticProxies = $response['gateway_proxies'][$gatewayProxyKey]['static_proxies'])
						) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array_chunk($gatewayStaticProxies[0], ceil(count($gatewayStaticProxies[0]) / count($gatewayGlobalForwardingProxies)));
						}
					} elseif (
						!empty($gatewayProxy['rotation_proxy_id']) &&
						!empty($gatewayProxy['rotation_proxy_ip'])
					) {
						$rotationIntervalProxyParameters = $proxyParameters;
						$rotationIntervalProxyParameters['conditions'] = array(
							'id' => $gatewayProxy['rotation_proxy_id']
						);
						$rotationIntervalProxy = $this->fetch('proxies', $rotationIntervalProxyParameters);

						if (!empty($rotationIntervalProxy['count'])) {
							$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array(
								$rotationIntervalProxy['data']
							);
						}
					}
				}
			}

			$staticProxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
				'allow_direct' => true,
				'type' => 'static'
			));
			$staticProxies = $this->fetch('proxies', $staticProxyParameters);

			if (!empty($staticProxies['count'])) {
				$response['static_proxies'] = $staticProxies['data'];
			}

			unset($proxyParameters['conditions']['type']);
			$proxyParameters['fields'] = array(
				'ip'
			);
			$proxyIps = $this->fetch('proxies', $proxyParameters);

			if (!empty($proxyIps['count'])) {
				$proxyIps['data'] = array_unique($proxyIps['data']);
				$response['proxy_ips'] = array_combine($proxyIps['data'], range(0, count($proxyIps['data']) - 1));
			}

			return $response;
		}

	/**
	 * Retrieve server proxy processes
	 *
	 * @param integer $serverId
	 *
	 * @return array $response
	 */
		protected function _retrieveServerProxyProcesses($serverId) {
			$response = array();
			$serverProxyProcesses = $this->fetch('server_proxy_processes', array(
				'conditions' => array(
					'server_id' => $serverId
				),
				'fields' => array(
					'id',
					'number',
					'protocol',
					'type'
				)
			));

			if (!empty($serverProxyProcesses['count'])) {
				foreach ($serverProxyProcesses['data'] as $key => $serverProxyProcess) {
					$response[$serverProxyProcess['type']][$key] = array_merge($serverProxyProcess, array(
						'dns' => $this->_retrieveServerProxyProcessDnsProcesses($serverProxyProcess['id']),
						'ports' => $this->_retrieveServerProxyProcessPorts($serverProxyProcess['id'])
					));

					if (
						empty($response[$serverProxyProcess['type']][$key]['dns']) ||
						empty($response[$serverProxyProcess['type']][$key]['ports'])
					) {
						unset($response[$serverProxyProcess['type']][$key]);
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve server proxy process DNS processes
	 *
	 * @param integer $serverProxyProcessId
	 *
	 * @return array $response
	 */
		protected function _retrieveServerProxyProcessDnsProcesses($serverProxyProcessId) {
			$response = array();
			$serverProxyProcessDnsProcesses = $this->fetch('server_proxy_process_dns_processes', array(
				'conditions' => array(
					'server_proxy_process_id' => $serverProxyProcessId
				),
				'fields' => array(
					'listening_ip',
					'local',
					'sort',
					'source_ip'
				),
				'sort' => array(
					'field' => 'sort',
					'order' => 'ASC'
				)
			));

			if (!empty($serverProxyProcessDnsProcesses['count'])) {
				foreach ($serverProxyProcessDnsProcesses['data'] as $serverProxyProcessDnsProcess) {
					$response[] = $serverProxyProcessDnsProcess;
				}
			}

			return $response;
		}

	/**
	 * Retrieve server proxy process ports
	 *
	 * @param integer $serverProxyProcessId
	 *
	 * @return array $response
	 */
		protected function _retrieveServerProxyProcessPorts($serverProxyProcessId) {
			$response = array();
			$serverProxyProcessPorts = $this->fetch('server_proxy_process_ports', array(
				'conditions' => array(
					'server_proxy_process_id' => $serverProxyProcessId
				),
				'fields' => array(
					'number'
				)
			));

			if (!empty($serverProxyProcessPorts['count'])) {
				foreach ($serverProxyProcessPorts['data'] as $serverProxyProcessPort) {
					$response[] = $serverProxyProcessPort;
				}
			}

			return $response;
		}

	}
?>
