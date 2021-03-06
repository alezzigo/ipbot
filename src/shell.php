<?php
	require_once(str_replace('shell.php', 'config.php', $_SERVER['SCRIPT_FILENAME']));
	$output = 'Error processing shell method, please check the parameters and try again.';

	if (
		!empty($config) &&
		!empty($_SERVER['argv'][1])
	) {
		$table = strtolower($_SERVER['argv'][1]);
		require_once($config->settings['base_path'] . '/models/' . $table . '.php');
		$shellObjectName = ucwords($table) . 'Model';
		$shellObject = new $shellObjectName();

		if (
			!empty($_SERVER['argv'][2]) &&
			method_exists($shellObject, $shellMethod = 'shell' . ucwords($_SERVER['argv'][2]))
		) {
			$response = $shellObject->$shellMethod($table);
			$output = 'Completed processing ' . $shellMethod . ' for ' . $table . '.';

			if (!empty($response['message']['text'])) {
				$output = $response['message']['text'];
			}
		}
	}

	echo $output;
	exit;
?>
