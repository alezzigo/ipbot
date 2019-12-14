/*$nodeData = array();
		$ranges = array(
			'172.93.176.4-172.93.183.251',
			'172.93.212.4-172.93.215.251'
		);
		$serverData = array(
			'asn' => 'AS20278 Nexeon Technologies, Inc.',
			'city' => 'Chicago',
			'country_code' => 'US',
			'country_name' => 'United States',
			'isp' => 'Nexeon Technologies, Inc.',
			'region' => 'Illinois',
			'server_id' => 1
		);

		foreach ($ranges as $range) {
			$splitRanges = explode('-', $range);
			$startRangeSubnets = explode('.', $splitRanges[0]);
			$endRangeSubnets = explode('.', $splitRanges[1]);

			foreach (range($startRangeSubnets[2], $endRangeSubnets[2]) as $cClassSubnet) {
				foreach (range($startRangeSubnets[3], $endRangeSubnets[3]) as $dClassSubnet) {
					$nodeData[] = array(
						'ip' => $startRangeSubnets[0] . '.' . $startRangeSubnets[1] . '.' . $cClassSubnet . '.' . $dClassSubnet
					);
				}
			}
		}

		$nodeData = array_replace_recursive($nodeData, array_fill(0, count($nodeData), $serverData));
		$this->save('nodes', $nodeData);*/
