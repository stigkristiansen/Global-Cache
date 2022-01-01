<?php

declare(strict_types=1);

trait Messages {
    public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		$buffer = utf8_decode($data->Buffer);
		
		$this->SendDebug(__FUNCTION__, sprintf('Received data: %s', $buffer)), 0);

		$buffer = strtolower($buffer);

		if(stripos($buffer, chr(13))!==false) {
			$buffer = substr($buffer, 0, strlen($buffer)-1);
			
			$msg = explode(',', $buffer);
			if(count($msg)>1) {
				switch(strtolower($msg[0])) {
					case 'state':
						HandleState($msg);
						break;
					case 'err_0:0':
						HandleError($msg);
						break;
				}
			} else {
				$error = 'Received incomplete data.';

				$this->LogMessage($error, KL_ERROR);
				$this->SendDebug(__FUNCTION__, $error, 0);	
			}
		} else {
			$error = 'Received incomplete data.';

			$this->LogMessage($error, KL_ERROR);
			$this->SendDebug(__FUNCTION__, $error, 0);
		}
	}

	private function HandleError(array $Msg) {
		$error = (int)$Msg[1]);

        $errorText = $this->ErrorLookup($error);

        $this->LogMessage($error, KL_ERROR);
		$this->SendDebug(__FUNCTION__, $error, 0);
	}


    private ErrorLookup(int $ErrorNumber) {
        return 'Generic Error Description';
    }
	
}
