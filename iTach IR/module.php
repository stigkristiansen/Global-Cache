<?php

declare(strict_types=1);

include __DIR__ . '/../libs/traits.php';

class iTachDeviceIR extends IPSModule {
	use iTach;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');

		$this->RegisterPropertyString('Port', '1:1');
		$this->RegisterPropertyString('IRCodes', '');
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();
	}

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		
		$this->SendDebug(__FUNCTION__, sprintf('Received data: %s', utf8_decode($data->Buffer)), 0);
	}

	
	public function SendIRCommand(string $Device, string $Command) {
		$this->SendIRCommandEx($Device, $Command, $this->ReadPropertyString("Port"));
	}

	public function SendIRCommandEx(string $Device, string $Command, string $Port) {
		$codes = json_decode($this->ReadPropertyString('IRCodes'), true);

		if($codes==NULL) {
			$this->SendDebug(__FUNCTION__, 'IR Codes table is empty!', 0);
			return false;
		}
				
		$device = strtolower($Device);
		$command = strtolower($Command);
		
		$buffer='';
		foreach($codes as $code) {
			if(strtolower($code['Device'])==$device && strtolower($code['Command'])==$command) {
				$buffer = sprintf("sendir1,%s,%d,%s%c%c",$Port, $this->InstanceID, $code['Code'], 13, 10);
				break;
			}
		}

		if(strlen($buffer)>0) {
			try {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
			} catch (Exeption $ex) {
				$msg = sprintf('Failed to send the command %s:%s. Error: %s',$Device, $Command, $ex->getMessage());
				
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);

				return false;
			}
		} else {
			$this->SendDebug(__FUNCTION__, sprintf('Unknown "Device" and/or "Command" (%s:%s)', $Device, $Command), 0);
			return false;
		}
	}
}