<?php

declare(strict_types=1);

include __DIR__ . '/../libs/traits.php';

class iTachDeviceRelay extends IPSModule {
	use iTach;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');
		$this->RegisterPropertySting('Relay', '1:1');
	}

	public function SendRelayCommand(bool $State, string $Relay = '') {
		if(strlen($Relay)==0) {
			$Relay = $this->ReadPropertyString("Relay");
		}

		$buffer = sprintf('setstate,%s,%d%c%c', $Relay, $State?1:0,13,10);
		
		try {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
		} catch (Exeption $ex) {
				$msg = sprintf('Failed to send the state "%d" to %s. Error: %s', $State?1:0, $Relay, $ex->getMessage());
				
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);

				return false;
		}
	}
}