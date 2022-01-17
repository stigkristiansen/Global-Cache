<?php

declare(strict_types=1);

require_once(__DIR__ . '/../libs/traits.php');

class iTachDeviceSerial extends IPSModule {
	use iTach;

	public function Create() {
		//Never delete this line!
		parent::Create();

		$this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');

		$this->RegisterPropertyString('Model', '');
		$this->RegisterPropertyString('Name', '');

		$this->RegisterPropertyInteger('1:1_Baudrate', 0); 
		$this->RegisterPropertyString('1:1_Flowcontrol', 'NA');
		$this->RegisterPropertyString('1:1_Parity', 'NA');
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
			$this->Init();
		}

		$this->UpdateConnectorConfig();
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		$this->SendDebug(__FUNCTION__, sprintf('Received a message: %d - %d - %d', $SenderID, $Message, $data[0]), 0);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->LogMessage('Detected "Kernel Ready"!', KL_NOTIFY);
			$this->Init();
		}
	}

	private function UpdateConnectorConfig() {
		$settings=sprintf('set_SERIAL,1:1,%d,%s,%s%cset_SERIAL,1:2,%d,%s,%s%cset_SERIAL,1:3,%d,%s,%s%c', 
			$this->ReadPropertyString('1:1_Baudrate'), $this->ReadPropertyString('1:1_Flowcontrol'), $this->ReadPropertyString('1:1_Parity'), 13, 
			$this->ReadPropertyString('1:2_Baudrate'), $this->ReadPropertyString('1:2_Flowcontrol'), $this->ReadPropertyString('1:2_Parity'), 13, 
			$this->ReadPropertyString('1:3_Baudrate'), $this->ReadPropertyString('1:3_Flowcontrol'), $this->ReadPropertyString('1:3_Parity'), 13); 
		
		if($this->HasActiveParent()) {
			$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $settings)));
		}
	}

	private function Init() {
		$msg = 'Initializing...';
			
		$this->SendDebug(__FUNCTION__, $msg, 0);

		$this->SetBuffer('IncomingData', json_encode(''));
	}

	public function GetConfigurationForm() {
		$this->GetConfig($this->ReadPropertyString('Model'));

		$return = file_get_contents(__DIR__ . '/form.json');
	}

	private function HandleConfig(array $Msg) {

		$this->SendDebug(__FUNCTION__, sprintf('Received configuration "%s"', json_encode($Msg) ), 0);
		
		$value = $Msg[2];
		$name = $Msg[1] . '_Baudrate';
		if($this->ReadPropertyString($name)!=$value) {
			$this->UpdateFormField($name, 'value', $value);	
		}

		$value = strtoupper($Msg[3]);
		$name = $Msg[1] . '_Flowcontrol';
		if($this->ReadPropertyString($name)!=$value) {
			$this->UpdateFormField($name, 'value', $value);	
		}

		$value = strtoupper($Msg[4]);
		$name = $Msg[1] . '_Parity';
		if($this->ReadPropertyString($name)!=$value) {
			$this->UpdateFormField($name, 'value', $value);	
		}
	}
}