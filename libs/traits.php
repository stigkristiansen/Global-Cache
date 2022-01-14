<?php

declare(strict_types=1);

trait Messages {
    private function Init() {
		$msg = 'Initializing...';
			
		$this->SendDebug(__FUNCTION__, $msg, 0);

		$this->SetBuffer('IncomingData', json_encode(''));
	}

    public function ReceiveData($JSONString) {
		$data = json_decode($JSONString);
		$buffer = utf8_decode($data->Buffer);
		
		$this->SendDebug(__FUNCTION__, sprintf('Received data: %s', $buffer), 0);

		$buffer = strtolower($buffer);
        $buffer = json_decode($this->GetBuffer('IncomingData')) . $buffer;
        
        $newBuffer = '';

		if(stripos($buffer, chr(13))!==false) {
			$msgs = explode(chr(13), $buffer);
            $max = count($msgs)-1;
    
            if(substr($buffer, strlen($buffer)-1, 1) != chr(13)) {
                $newBuffer = $msgs[$max];
                $max = $max-1;
            } 
            
            for($index=0;$index<=$max;$index++) {
                $msg = explode(',', $msgs[$index]);
                
                if(count($msg)>1) {
                    switch($msg[0]) {
                        case 'setstate':
                            case 'state':    
                                $this->HandleState($msg);
                                break;
                            case 'err_0:0':
                                $this->HandleError($msg);
                                break;
                            case 'unknowncommand':
                                $this->HandleError($msg);
                                break;
                            case 'completeir':
                                $this->HandleIR($msg);
                            case 'ir':
                                $this->HandleIRConfig($msg);
                                break;
                            default:
                                $this->SendDebug(__FUNCTION__, 'Received data that is not handled!', 0);	
                    }
                }  else {
                    $error = 'Received incomplete data.';
    
                    $this->LogMessage($error, KL_ERROR);
                    $this->SendDebug(__FUNCTION__, $error, 0);	
                }
            }
		} else {
			$error = 'Received incomplete data. Saving for lates usage...';
            $newBuffer = $buffer;

			$this->LogMessage($error, KL_ERROR);
			$this->SendDebug(__FUNCTION__, $error, 0);
		}

        $this->SetBuffer('IncomingData', json_encode($newBuffer));
	}

	private function HandleError(array $Msg) {
		$errorNum = (int)$Msg[1];

        $errorText = $this->ErrorLookup($errorNum);

        $this->LogMessage($errorText, KL_ERROR);
		$this->SendDebug(__FUNCTION__, $errorText, 0);
	}


    private function ErrorLookup(int $ErrorNum) {
        
        switch($ErrorNum) {
            case 1:
                return 'Invalid command. Command not found';
            case 1:
                return 'Invalid module address (does not exist)';
            case 1:
                return 'Invalid connector address (does not exist)';
            case 1:
                return 'Invalid ID value';
            case 1:
                return 'Invalid frequency value';
            case 1:
                return 'Invalid repeat value';
            case 1:
                return 'Invalid offset value';
            case 1:
                return 'Invalid pulse count';
            case 1:
                return 'Invalid pulse data';
            case 1:
                return 'Uneven amount of <on|off> statements';
            case 1:
                return 'No carriage return found';
            case 1:
                return 'Repeat count exceeded';
            case 1:
                return 'IR command sent to input connector';
            case 1:
                return 'Blaster command sent to non-blaster connector';
            case 1:
                return 'No carriage return before buffer full';
            case 1:
                return 'No carriage return';
            case 1:
                return 'Bad command syntax';
            case 1:
                return 'Sensor command sent to non-input connector';
            case 1:
                return 'Repeated IR transmission failure';
            case 1:
                return 'Above designated IR <on|off> pair limit';
            case 1:
                return 'Symbol odd boundary';
            case 1:
                return 'Undefined symbol';
            case 1:
                return 'Unknown option';
            case 1:
                return 'Invalid baud rate setting';
            case 1:
                return 'Invalid flow control setting';
            case 1:
                return 'Invalid parity setting';
            case 1:
                return 'Settings are locked';
            default:
                return 'An unknown error has occured!';
         }
    }
	
}
