<?php

declare(strict_types=1);
class iTachDevice extends IPSModule {
	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('IPAddress', '');
		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');

		$this->RegisterPropertyString('Port', '1:3');
		$this->RegisterPropertyString('IRCodes', '');
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
	}

	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

		$this->RegisterMessage(0, IPS_KERNELMESSAGE);

		if (IPS_GetKernelRunlevel() == KR_READY) {
			//$this->Init();
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		$this->SendDebug(__FUNCTION__, sprintf('Received a message: %d - %d - %d', $SenderID, $Message, $data[0]), 0);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->LogMessage('Detected "Kernel Ready"!', KL_NOTIFY);
			//$this->Init();
		}
	}

	private function Init() {
		$msg = 'Initializing...';
		
		$this->LogMessage($msg, KL_NOTIFY);
		$this->SendDebug(__FUNCTION__, $msg, 0);

		
	}

	public function RequestAction($Ident, $Value) {
		switch (strtolower($Ident)) {
			case 'checkioconfig':
				$this->CheckIOConfig();
				break;
			}
	}

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		
		$this->SendDebug(__FUNCTION__, sprintf('Received data: %s', utf8_decode($data->Buffer)), 0);
	}

	public function SendIRCommand(string $Device, string $Command) {
		$codes = json_decode($this->ReadPropertyString('IRCodes'), true);
				
		$device = strtolower($Device);
		$command = strtolower($Command);
		
		$buffer='';
		foreach($codes as $code) {
			if(strtolower($code['Device'])==$device && strtolower($code['Command'])==$command) {
				//$buffer = sprintf("sendir,%s,%d,%s",$this->ReadPropertyString("Port"), $this->InstanceID, $code['Code']);
				//$buffer .= chr(13).chr(10);

				$buffer = sprintf("sendir,%s,%d,%s%c%c",$this->ReadPropertyString("Port"), $this->InstanceID, $code['Code'], 13, 10);
				break;
			}
		}

		if(strlen($buffer)>0) {
			try {
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $buffer)));
				return true;
			} catch (Exeption $ex) {
				$msg = sprintf('Failed to send the command %s:%s. Error: %S',$Device, $Command, $ex->getMessage());
				
				$this->LogMessage($msg, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $msg, 0);

				return false;
			}
		} else {
			$this->SendDebug(__FUNCTION__, sprintf('Unknown "Device" and/or "Command" (%s:%s)', $Device, $Command), 0);
			return false;
		}
		
		
		
		return true;
	 
		$this->SendDebug("The command is not registered: ".$Device.":".$Command, 0);
		return false;
				
	}
}