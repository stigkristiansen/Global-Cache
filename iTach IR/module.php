<?php

declare(strict_types=1);

require_once(__DIR__ . '/../libs/traits.php');

class iTachDeviceIR extends IPSModule {
	use Messages;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');

		$this->RegisterPropertyString('Port', '1:1');
		$this->RegisterPropertyString('IRCodes', '');

		$this->RegisterVariableBoolean('IR1', 'IR #1', '~Alert.Reversed', 0);
		$this->RegisterVariableBoolean('IR2', 'IR #2', '~Alert.Reversed', 1);
		$this->RegisterVariableBoolean('IR3', 'IR #3', '~Alert.Reversed', 2);
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		$this->SetValue('IR1', true);
		$this->SetValue('IR2', true);
		$this->SetValue('IR3', true);
	}

	public function GetConfigurationForm() {
		$this->GetIRConfig();		
		
		$return = file_get_contents(__DIR__ . '/form.json');
	}

	public function SendIRCommand(string $Device, string $Command) {
		$this->SendIRCommandEx($Device, $Command, $this->ReadPropertyString("Port"));
	}

	public function SendIRCommandEx(string $Device, string $Command, string $Port) {
		switch($Port) {
			case '1:1':
				$ident = 'IR1';
				break;
			case '1:2':
				$ident = 'IR2';
				break;
			case '1:3':
				$ident = 'IR3';
				break;
			default:
				$msg = sprintf('Invalid port specified: %s', $Port);
			
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);		

				return false;
		}

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
				$buffer = sprintf("sendir,%s,%d,%s%c",$Port, $this->InstanceID, $code['Code'], 13);
				break;
			}
		}

		if(strlen($buffer)>0) {
			try {
				$this->SetValue($ident, false);
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

	private function HandleIR(array $Msg) {
		switch($Msg[1]) {
			case '1:1':
				$this->SetValue('IR1', true);
				break;
			case '1:2':
				$this->SetValue('IR2', true);
				break;
			case '1:3':
				$this->SetValue('IR3', true);
				break;
			default: 
				$msg = sprintf('Received invalid port from parent: %s', $Msg[1]);

				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);
		}
	}

	private function GetIRConfig() {
		for($index=1;$index<3;$index++) {
			$buffer = sprintf('get_IR,1:%d%c', $index, 13);
			$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
		}
	}

	private function HandleIRConfig(array $Msg) {
		switch($Msg[1]) {
			case '1:1':
				
				break;
			case '1:2':
				
				break;
			case '1:3':
				
				break;
			default: 
				$msg = sprintf('Received invalid port from parent: %s', $port);

				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);
				return;
		}

		$this->SendDebug(__FUNCTION__, sprintf('Port "%s" is configured to "%s"', $Msg[1], $Msg[2] ), 0);
	}
}