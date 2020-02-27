<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ServersModel extends AppModel {

	/**
	 * Format 3proxy access controls
	 *
	 * @param array $serverDetails
	 *
	 * @return array $response
	 */
		protected function _format3proxy($serverDetails) {
			$response = array();
			// ..
			return $response;
		}

	/**
	 * Format Squid access controls
	 *
	 * @param array $serverDetails
	 *
	 * @return array $response
	 */
		protected function _formatSquid($serverDetails) {
			$disabledProxies = $formattedFiles = $formattedProxies = $formattedProxyProcessConfigurations = $formattedProxyProcessPorts = $formattedUsers = $gatewayAcls = $proxyAuthenticationAcls = $proxyIpAcls = $proxyWhitelistAcls = array();
			$configuration = $this->proxyConfigurations['squid'];
			$formattedAcls = array(
				'auth_param basic program ' . $configuration['paths']['authentication'] . ' ' . $configuration['paths']['passwords'],
				'auth_param basic children 88888',
				'auth_param basic realm ' . $this->settings['site_name'],
				'auth_param basic credentialsttl 88888 days',
				'auth_param basic casesensitive on'
			);
			$proxyIps = $proxyIpForwardingIndex = array();
			$userAclIndex = 1;
			$whitelistAclIndex = 0;

			if (
				($processMinimum = !empty($configuration['process_minimum']) ? $configuration['process_minimum'] : 1) &&
				count($serverDetails['proxy_processes']['squid']) < $processMinimum
			) {
				return false;
			}

			foreach ($serverDetails['proxy_ips'] as $proxyIp => $proxyIndex) {
				$proxyIpAcls[] = 'acl ip' . $proxyIndex . ' localip ' . $proxyIp;
				$proxyIpAcls[] = 'tcp_outgoing_address ' . $proxyIp . ' ip' . $proxyIndex;
				$proxyIps[$proxyIp] = $proxyIp;
				$proxyIpForwardingIndex[$proxyIp] = 0;
			}

			$globalProxyAuthentication = $this->keys['global_proxy_username'] . ':' . $this->keys['global_proxy_password'];
			$formattedProxies['authentication'][str_replace(':', $this->keys['start'], $globalProxyAuthentication)] = $proxyIps;

			foreach (range(0, max(1, $this->settings['proxies']['shared_ip_maximum'])) as $sharedProxyIpInstance) {
				$proxyPassword = $this->keys['global_proxy_password'] . '_' . $sharedProxyIpInstance;
				$proxyUsername = $this->keys['salt'] . $sharedProxyIpInstance . '_' . $this->keys['global_proxy_username'];
				$proxyAuthentication = $proxyUsername . $this->keys['start'] . $proxyPassword;
				$formattedProxies['authentication'][$proxyAuthentication] = $proxyIps;

				foreach (range(0, 20) as $rotationProxyIpChunk) {
					$formattedProxies['authentication'][$rotationProxyIpChunk . '_' . $this->keys['salt'] . $proxyAuthentication . '_' . $rotationProxyIpChunk] = $proxyIps;
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
			$splitForwardingProxyProcessPortIndexes = $splitStaticProxyProcessPortIndexes = array(0, 0);
			$splitForwardingProxyProcessPorts = array_reverse($serverDetails['proxy_process_ports']['squid']['secondary']);
			$splitProxyProcesses = array_chunk($serverDetails['proxy_processes']['squid'], round(count($serverDetails['proxy_processes']['squid']) / 2), false);

			foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
				$splitForwardingProxyProcessPorts[$splitForwardingProxyProcessPortKey] = array_chunk($forwardingProxyProcessPorts, round(count($forwardingProxyProcessPorts) / 2));
			}

			foreach ($splitProxyProcesses as $splitProxyProcessKey => $proxyProcesses) {
				$aclFilename = 'acls' . ((integer) $splitProxyProcessKey) . '.conf';
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
					$proxyHasForwardingProxies = (
						!empty($proxy['static_proxies'][1]) ||
						!empty($proxy['static_proxies'][0][1])
					);
					$proxyHasUserAcls = (
						!empty($proxy['username']) &&
						!empty($proxy['password'])
					);
					$proxyHasWhitelistAcls = !empty($proxy['whitelisted_ips']);

					if ($proxyHasUserAcls) {
						$formattedProxies['authentication'][$proxy['username'] . $this->keys['start'] . $proxy['password']][$proxy['ip']] = $proxy['ip'];
					}

					if ($proxyHasWhitelistAcls) {
						$sources = json_encode(array_filter(explode("\n", $proxy['whitelisted_ips'])));
						$formattedProxies['whitelist'][$sources][$proxy['ip']] = $proxy['ip'];
					}

					$forwardingSources = array(
						$proxy['ip']
					);
					$gatewayIpIndex = (integer) $serverDetails['proxy_ips'][$proxy['ip']];

					if (
						!empty($proxy['static_proxies']) &&
						(
							$proxyHasUserAcls ||
							$proxyHasWhitelistAcls
						)
					) {
						foreach ($proxy['static_proxies'] as $splitStaticProxyKey => $splitStaticProxies) {
							foreach ($splitForwardingProxyProcessPorts as $splitForwardingProxyProcessPortKey => $forwardingProxyProcessPorts) {
								$proxyProcessPorts = array(
									'forwarding' => $forwardingProxyProcessPorts[1],
									'static' => $forwardingProxyProcessPorts[0]
								);

								if (empty($proxyProcessPorts['forwarding'][$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]])) {
									$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey] = 0;
								}

								if ($proxyHasWhitelistAcls) {
									$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'never_direct allow s' . $whitelistAclIndex . ' ip' . $gatewayIpIndex;
								}

								if ($proxyHasUserAcls) {
									$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'never_direct allow ip' . $gatewayIpIndex . ' u' . $userAclIndex;
								}

								if ($proxyHasForwardingProxies) {
									$forwardingProxyAuthentication = $splitStaticProxyKey . '_' . $proxyIpForwardingIndex[$proxy['ip']] . '_' . $globalProxyAuthentication . '_' . $proxyIpForwardingIndex[$proxy['ip']] . '_' . $splitStaticProxyKey;
									$forwardingProxyAcl = $proxy['id'] . '_' . $splitStaticProxyKey;
									$forwardingProxyUser = $splitStaticProxyKey . $proxyIpForwardingIndex[$proxy['ip']];
									$forwardingProxyProcessPort = $proxyProcessPorts['forwarding'][$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]];
									$splitForwardingProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]++;
									$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer ' . $proxy['ip'] . ' parent ' . $forwardingProxyProcessPort . ' 0 connect-fail-limit=5 connect-timeout=5 login=' . $forwardingProxyAuthentication . ' name=' . $forwardingProxyAcl . ' round-robin';

									if ($proxyHasWhitelistAcls) {
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $forwardingProxyAcl . ' allow s' . $whitelistAclIndex;
									}

									if ($proxyHasUserAcls) {
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $forwardingProxyAcl . ' allow u' . $userAclIndex;
									}

									$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'never_direct allow ip' . $gatewayIpIndex . ' f' . $forwardingProxyUser;
								}

								foreach ($splitStaticProxies as $staticProxyKey => $staticProxy) {
									if (empty($proxyProcessPorts['static'][$splitStaticProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]])) {
										$splitStaticProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey] = 0;
									}

									$staticProxyAcl = $proxy['id'] . '_' . $staticProxy['id'];

									if ($proxyHasForwardingProxies) {
										$staticProxyProcessPort = $proxyProcessPorts['static'][$splitStaticProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]];
										$splitStaticProxyProcessPortIndexes[$splitForwardingProxyProcessPortKey]++;
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer ' . $staticProxy['ip'] . ' parent ' . $staticProxyProcessPort . ' 0 connect-fail-limit=5 connect-timeout=5 login=' . $globalProxyAuthentication . ' name=' . $staticProxyAcl . ' round-robin';
										$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $staticProxyAcl . ' allow ip' . $gatewayIpIndex . ' f' . $forwardingProxyUser;
									} else {
										$mergedProxyProcessPorts = array_merge($proxyProcessPorts['forwarding'], $proxyProcessPorts['static']);
										shuffle($mergedProxyProcessPorts);
										$mergedProxyProcessPorts = array_splice($mergedProxyProcessPorts, 0, 10);

										foreach ($mergedProxyProcessPorts as $mergedProxyProcessPortKey => $mergedProxyProcessPort) {
											$redundantStaticProxyAcl = $mergedProxyProcessPortKey . '_' . $staticProxyAcl;
											$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer ' . $staticProxy['ip'] . ' parent ' . $mergedProxyProcessPort . ' 0 connect-fail-limit=5 connect-timeout=5 login=' . $globalProxyAuthentication . ' name=' . $redundantStaticProxyAcl . ' round-robin';
											$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $redundantStaticProxyAcl . ' allow s' . $whitelistAclIndex;
											$gatewayAcls[$splitForwardingProxyProcessPortKey][] = 'cache_peer_access ' . $redundantStaticProxyAcl . ' allow u' . $userAclIndex;
										}
									}
								}
							}
						}

						$proxyIpForwardingIndex[$proxyIp]++;
					}

					if ($proxyHasUserAcls) {
						$userAclIndex++;
					}

					if ($proxyHasWhitelistAcls) {
						$whitelistAclIndex++;
					}

					if (!empty($proxy['disable_http'])) {
						$disabledProxies[$proxy['ip']] = $proxy['ip'];
					}
				}
			}

			$userAclIndex = $whitelistAclIndex = 0;

			if (!empty($formattedProxies['authentication'])) {
				$forwardingProxyAclSet = false;

				foreach ($formattedProxies['authentication'] as $credentials => $destinations) {
					$forwardingProxy = false;
					$splitAuthentication = explode($this->keys['start'], $credentials);
					$userAcl = 'u' . $userAclIndex;

					if (strpos($splitAuthentication[0], $this->keys['salt']) !== false) {
						if (strpos($splitAuthentication[0], $this->keys['salt'] . $this->keys['salt']) !== false) {
							$forwardingProxy = true;
						}

						$splitAuthentication[0] = str_replace($this->keys['salt'], '', $splitAuthentication[0]);
						$splitUsername = explode('_', $splitAuthentication[0]);
						$userAcl = 'f' . $splitUsername[0] . ($forwardingProxy ? $splitUsername[1] : '');
						$forwardingProxy = true;
					}

					$destinationAcl = !$forwardingProxy ? 'd' . $userAclIndex : 'f';
					$destinationContents = implode("\n", $destinations);
					$destinationPath = $configuration['paths']['users'] . (!$forwardingProxy ? $userAclIndex : 'f') . '/d.txt';
					$formattedAcls[] = 'acl ' . $userAcl . ' proxy_auth ' . $splitAuthentication[0];

					if (empty($formattedFiles[$destinationPath])) {
						$formattedFiles[$destinationPath] = array(
							'contents' => $destinationContents,
							'path' => $destinationPath
						);
					}

					$formattedUsers[$splitAuthentication[0]] = $splitAuthentication[1];

					if (
						!$forwardingProxy ||
						(
							$forwardingProxy &&
							(
								!$forwardingProxyAclSet &&
								($forwardingProxyAclSet = true)
							)
						)
					) {
						$proxyAuthenticationAcls[] = 'acl ' . $destinationAcl . ' localip "' . $destinationPath . '"';
					}

					$proxyAuthenticationAcls[] = 'http_access allow ' . $destinationAcl . ' ' . $userAcl;

					if (!$forwardingProxy) {
						$userAclIndex++;
					}
				}
			}

			$formattedAcls = array_merge($formattedAcls, $proxyIpAcls);

			if (!empty($formattedProxies['whitelist'])) {
				foreach ($formattedProxies['whitelist'] as $sources => $destinations) {
					$sources = json_decode($sources, true);
					$splitSources = array_chunk($sources, '500');

					foreach ($splitSources as $sourceChunk) {
						$destinationContents = implode("\n", $destinations);
						$destinationPath = $configuration['paths']['users'] . '_' . $whitelistAclIndex . '/d.txt';
						$sourceContents = implode("\n", $sourceChunk);
						$sourcePath = $configuration['paths']['users'] . '_' . $whitelistAclIndex . '/s.txt';

						if (empty($formattedFiles[$destinationPath])) {
							$formattedFiles[$destinationPath] = array(
								'contents' => $destinationContents,
								'path' => $destinationPath
							);
						}

						if (empty($formattedFiles[$sourcePath])) {
							$formattedFiles[$sourcePath] = array(
								'contents' => $sourceContents,
								'path' => $sourcePath
							);
						}

						$proxyWhitelistAcls[] = 'acl d' . $whitelistAclIndex . '_ localip "' . $destinationPath . '"';
						$proxyWhitelistAcls[] = 'acl s' . $whitelistAclIndex . ' src "' . $sourcePath . '"';
						$proxyWhitelistAcls[] = 'http_access allow s' . $whitelistAclIndex . ' d' . $whitelistAclIndex . '_';
						$whitelistAclIndex++;
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
				'files' => array_values($formattedFiles),
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
					if (
						!empty($gatewayProxy['rotation_frequency']) &&
						is_numeric($gatewayProxy['rotation_frequency']) &&
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
					} else {
						$gatewayProxyIdParameters = array(
							'conditions' => array(
								'gateway_proxy_id' => $gatewayProxy['id']
							),
							'fields' => array(
								'proxy_id'
							)
						);

						$gatewayProxyStaticProxyIds = $this->fetch('proxy_static_proxies', $gatewayProxyIdParameters);
						$staticProxyParameters = array_merge($proxyParameters, array(
							'limit' => max(1, $this->settings['proxies']['rotation_ip_pool_size_maximum']),
							'sort' => 'random'
						));
						$staticProxyParameters['conditions'] = array_merge($staticProxyParameters['conditions'], array(
							'id' => $gatewayProxyStaticProxyIds['data'],
							'type' => 'static'
						));
						unset($staticProxyParameters['conditions']['node_id']);
						$staticProxies = $this->fetch('proxies', $staticProxyParameters);

						if (!empty($staticProxies['count'])) {
							if (!empty($staticProxies['count'])) {
								$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array(
									$staticProxies['data']
								);
							}

							if (
								!empty($response['gateway_proxies'][$gatewayProxyKey]['static_proxies']) &&
								($gatewayStaticProxies = $response['gateway_proxies'][$gatewayProxyKey]['static_proxies'])
							) {
								$response['gateway_proxies'][$gatewayProxyKey]['static_proxies'] = array_chunk($gatewayStaticProxies[0], max(1, count($gatewayStaticProxies[0]) / 20));
							}
						}
					}
				}
			}

			$staticProxyParameters['conditions'] = array_merge($proxyParameters['conditions'], array(
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

			if (empty($proxyIps['count'])) {
				return false;
			}

			$proxyIps = array_unique($proxyIps['data']);
			$response['proxy_ips'] = array_combine($proxyIps, range(0, count($proxyIps) - 1));
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
