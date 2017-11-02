<?
class UMG96 extends IPSModule
{
    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID);
    }
    public function Create()
    {
        parent::Create();
        $this->ConnectParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}");
        $this->RegisterPropertyInteger("Interval", 0);
	$this->RegisterPropertyBoolean("TemperatureInput1", false);
	$this->RegisterPropertyBoolean("TemperatureInput2", false);
        $this->RegisterTimer("UpdateTimer", 0, "UMG96_RequestRead(\$_IPS['TARGET']);");
    }
    public function ApplyChanges()
    {
        parent::ApplyChanges();
	//Variable Anlegen oder LÃ¶schen 
	//Temperatur 1
	if ($this->ReadPropertyBoolean("TemperatureInput1") === true)
		{
	$this->RegisterVariableFloat("Temp1", "Temperatur 1", "Temperature", 8);
	}
	else
	{
	$this->UnregisterVariable("Temp1");
	}
	//Temperatur 2
	if ($this->ReadPropertyBoolean("TemperatureInput2") === true)
                {
        $this->RegisterVariableFloat("Temp2", "Temperatur 2", "Temperature", 9);
        }
        else
        {
        $this->UnregisterVariable("Temp2");
        }

        $this->RegisterVariableFloat("VoltL1", "Volt L1-L2", "Volt", 1);
        $this->RegisterVariableFloat("VoltL2", "Volt L2-L3", "Volt", 1);
        $this->RegisterVariableFloat("VoltL3", "Volt L1-L3", "Volt", 1);
	    
	$this->RegisterVariableFloat("Volt2L1", "Volt L1", "Volt.230", 2);
        $this->RegisterVariableFloat("Volt2L2", "Volt L2", "Volt.230", 2);
        $this->RegisterVariableFloat("Volt2L3", "Volt L3", "Volt.230", 2);
	    
        $this->RegisterVariableFloat("AmpereL1", "Ampere L1", "Ampere.16", 3);
        $this->RegisterVariableFloat("AmpereL2", "Ampere L2", "Ampere.16", 3);
        $this->RegisterVariableFloat("AmpereL3", "Ampere L3", "Ampere.16", 3);
	    
        $this->RegisterVariableFloat("WattL1", "Watt L1", "Watt.14490", 4);
        $this->RegisterVariableFloat("WattL2", "Watt L2", "Watt.14490", 4);
        $this->RegisterVariableFloat("WattL3", "Watt L3", "Watt.14490", 4);
	    
        $this->RegisterVariableFloat("Watt_Total", "Verbrach Gesamt", "Watt.14490", 5);
        
       
        $this->RegisterVariableFloat("Frequenz", "Frequenz", "Hertz.50", 6);
	    
        $this->RegisterVariableFloat("Total", "Total kWh", "Electricity", 7);
        
        if ($this->ReadPropertyInteger("Interval") > 0)
            $this->SetTimerInterval("UpdateTimer", $this->ReadPropertyInteger("Interval"));
        else
            $this->SetTimerInterval("UpdateTimer", 0);
    }
    public function RequestRead()
    {
        $Gateway = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($Gateway == 0)
            return false;
        $IO = IPS_GetInstance($Gateway)['ConnectionID'];
        if ($IO == 0)
            return false;
        if (!$this->lock($IO))
            return false;
	//Spannung L1-L2, L2-L3, L3-L1
         for ($index = 0; $index < 3; $index++)
        {
            $Volt = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19006 + ($index * 2), "Quantity" => 2, "Data" => "")));
            if ($Volt === false)
            {
                $this->unlock($IO);
                return false;
            }
            $Volt = unpack("f", strrev(substr($Volt, 2)))[1];
            $this->SendDebug('Volt L1-L2', $Volt, 0);
	    SetValue($this->GetIDForIdent("VoltL" . ($index + 1)), $Volt);
        }
	//Spannung L1-N, L2-N, L3-N	
	for ($index = 0; $index < 3; $index++)
        {
            $Volt2 = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19000 + ($index * 2), "Quantity" => 2, "Data" => "")));
            if ($Volt2 === false)
            {
                $this->unlock($IO);
                return false;
            }
            $Volt2 = unpack("f", strrev(substr($Volt2, 2)))[1];
            $this->SendDebug('Volt L'. ($index + 1), $Volt2, 0);
	    SetValue($this->GetIDForIdent("Volt2L" . ($index + 1)), $Volt2);
        }
	//Strom
        for ($index = 0; $index < 3; $index++)
        {
            $Ampere = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19012 + ($index * 2), "Quantity" => 2, "Data" => "")));
            if ($Ampere === false)
            {
                $this->unlock($IO);
                return false;
            }
            $Ampere = unpack("f", strrev(substr($Ampere, 2)))[1];
            $this->SendDebug('Ampere L' . ($index + 1), $Ampere, 0);
            SetValue($this->GetIDForIdent("AmpereL" . ($index + 1)), $Ampere);
        }
	//Arbeit
        for ($index = 0; $index < 3; $index++)
        {
            $Watt = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19020 + ($index * 2), "Quantity" => 2, "Data" => "")));
            if ($Watt === false)
            {
                $this->unlock($IO);
                return false;
            }
            $Watt = unpack("f", strrev(substr($Watt, 2)))[1];
            $this->SendDebug('Watt L' . ($index + 1), $Watt, 0);
            SetValue($this->GetIDForIdent("WattL" . ($index + 1)), $Watt);
        }
	//Arbeit Gesammt
        $Watt_Total = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19026, "Quantity" => 2, "Data" => "")));
        if ($Watt_Total=== false)
        {
            $this->unlock($IO);
            return false;
        }
        $Watt_Total= unpack("f", strrev(substr($Watt_Total, 2)))[1];
        $this->SendDebug('Verbrauch Gesammt', $Watt_Total, 0);
        SetValue($this->GetIDForIdent("Watt_Total"), $Watt_Total);
        
	//Frequenz
        $Frequenz = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19050, "Quantity" => 2, "Data" => "")));
        if ($Frequenz === false)
        {
            $this->unlock($IO);
            return false;
        }
        $Frequenz = unpack("f", strrev(substr($Frequenz, 2)))[1];
        $this->SendDebug('Frequenz', $Frequenz, 0);
        SetValue($this->GetIDForIdent("Frequenz"), $Frequenz);
	    
	//Verbrauch
        
          $total= $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 19060, "Quantity" => 2, "Data" => "")));   
        if ($total === false)   
        {   
            $this->unlock($IO);   
            return false;   
        }  
        $total = unpack("f", strrev(substr($total,2)))[1] / 1000;
        $this->SendDebug('Total', $total, 0);  
    	SetValue($this->GetIDForIdent("Total"), $total);   
      
 	//Temperatur 1
        $Temp1 = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 10865, "Quantity" => 2, "Data" => "")));
        if ($Temp1 === false)
        {
            $this->unlock($IO);
            return false;
        }
        $Temp1 = unpack("f", strrev(substr($Temp1, 2)))[1];
        $this->SendDebug('Temperatur 1', $Temp1, 0);
        SetValue($this->GetIDForIdent("Temp1"), $Temp1);

	//Temperatur 2
        $Temp2 = $this->SendDataToParent(json_encode(Array("DataID" => "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 3, "Address" => 10867, "Quantity" => 2, "Data" => "")));
        if ($Temp2 === false)
        {
            $this->unlock($IO);
            return false;
        }
        $Temp2 = unpack("f", strrev(substr($Temp2, 2)))[1];
        $this->SendDebug('Temperatur 2', $Temp2, 0);
        SetValue($this->GetIDForIdent("Temp2"), $Temp2);




        IPS_Sleep(333);
        $this->unlock($IO);
        return true;
    }
    /**
     * Versucht eine Semaphore zu setzen und wiederholt dies bei Misserfolg bis zu 100 mal.
     * @param string $ident Ein String der den Lock bezeichnet.
     * @return boolean TRUE bei Erfolg, FALSE bei Misserfolg.
     */
    private function lock($ident)
    {
        for ($i = 0; $i < 100; $i++)
        {
            if (IPS_SemaphoreEnter('ModBus' . '.' . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                IPS_Sleep(5);
            }
        }
        return false;
    }
    /**
     * LÃƒÂ¶scht eine Semaphore.
     * @param string $ident Ein String der den Lock bezeichnet.
     */
    private function unlock($ident)
    {
        IPS_SemaphoreLeave('ModBus' . '.' . (string) $ident);
    }
}
?>
