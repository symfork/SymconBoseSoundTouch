<?
/*

SymconBoseSoundTouch

Bose SoundTouch Module for IP-Symcon

filename:       module.php
description:    Bose SoundTouch module
version         1.0.0
date:           28.12.2015
publisher:      (c) 2015 Ulrich Bittner
environment:    IP-Symcon 4.0 (beta) on RPi

changelog:      version 1.0.0 28.12.2015 initialscript

todo:           (none)

github:         https://github.com/ubittner/SymconBoseSoundTouch.git

*/

// Class definition
class BoseSoundTouch extends IPSModule 
{
    // overwrites the internal IPS_Create($id) function
    public function Create() 
    {
        // never delete this line!
        parent::Create();
        // these lines are parsed on Symcon startup or instance creation
        // you cannot use variables here, just static values.

        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // create properties
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        $this->RegisterPropertyString("Room", "");
        $this->RegisterPropertyString("DeviceIP", "");
        $this->RegisterPropertyString("DeviceID", "");
        $this->RegisterPropertyString("Timeout", "500");
        }

    // overwrites the internal IPS_ApplyChanges($id) function
    public function ApplyChanges() 
    {
        // get instance id
        $instanceid = IPS_GetInstance($this->InstanceID)['InstanceID']; 
        // rename instance 
        $room = $this->ReadPropertyString("Room");
        if ($room <> "") {
            IPS_SetName($instanceid, $room); 
        }
        // check device
        $deviceip = $this->ReadPropertyString("DeviceIP");
        $timeout = $this->ReadPropertyString("Timeout");
        if ($deviceip <> "") {
            // check device availability
            if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                //    throw new Exception("Device ".$deviceip." is not available");
                die("Unable to connect to device $deviceip");
            }
            include_once(__DIR__."/bose_soundtouch_api.php");
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $result = $bosedevice->getDeviceNowPlayingAPI();
            // get device id for multiroom
            $deviceid = $this->ReadPropertyString("DeviceID");
            if ($deviceid == "" AND $deviceip <>"") {
                $deviceid = $result['deviceid'];
                IPS_SetProperty($instanceid, "DeviceID", "".$deviceid."");
                if(IPS_HasChanges($instanceid)) {
                    IPS_ApplyChanges($instanceid);
                }
            }
        }
        
        

        // never delete this line!
        parent::ApplyChanges();
        
        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // create profiles
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        //create zone profiles for multiroom 
        $allboseinstances = IPS_GetInstanceListByModuleID("{4836EF46-FF79-4D6A-91C9-FE54F1BDF2DB}");
        // create profile for each instance / device
        foreach ($allboseinstances as $key => $instanceid) {
            $multiroomprofilename = "bose.Device".$instanceid."MasterZones";
            // delete zone profiles
            if (IPS_VariableProfileExists($multiroomprofilename)) {
                IPS_DeleteVariableProfile($multiroomprofilename);
            }
            IPS_CreateVariableProfile($multiroomprofilename, 1);
            // set values for profile
            foreach ($allboseinstances as $key => $value) {
                if ($instanceid == $value) {
                    IPS_SetVariableProfileAssociation($multiroomprofilename, "0", "Off", "", 0x0000FF);
                }
                else {
                $instancename = IPS_GetName($value);
                    if ($instancename <> "") {
                        IPS_SetVariableProfileAssociation($multiroomprofilename, "".($key+1)."", "".$instancename."", "", 0x0000FF);  
                    }
                }
            }
        }


        // create volume slider profile
        $volumesliderprofilename = "bose.VolumeSlider";
        if (!IPS_VariableProfileExists($volumesliderprofilename)) {
            IPS_CreateVariableProfile($volumesliderprofilename, 1);
        }
        IPS_SetVariableProfileValues($volumesliderprofilename, 0, 100, 1);
        IPS_SetVariableProfileText($volumesliderprofilename, "", "%");
        IPS_SetVariableProfileIcon($volumesliderprofilename, "Speaker");


        // play pause preset
        $playpauseprofilename = "bose.PlayPause";
        if (!IPS_VariableProfileExists($playpauseprofilename)) {
            IPS_CreateVariableProfile($playpauseprofilename, 1);
        }
        IPS_SetVariableProfileAssociation($playpauseprofilename, "0", "Pause", "", 0x0000FF);
        IPS_SetVariableProfileAssociation($playpauseprofilename, "1", "Play", "", 0x0000FF);
        IPS_SetVariableProfileIcon($playpauseprofilename, "HollowArrowRight");


        // create presets and radiolocations profiles
        $instanceid = IPS_GetInstance($this->InstanceID)['InstanceID']; 
        $radiostationsprofilename = "bose.Device".$instanceid."RadioStations";
        if (!IPS_VariableProfileExists($radiostationsprofilename)) {
            IPS_CreateVariableProfile($radiostationsprofilename, 1);
        }
        $radiolocationsprofilename = "bose.Device".$instanceid."RadioLocations";
        if (!IPS_VariableProfileExists($radiolocationsprofilename)) {
            IPS_CreateVariableProfile($radiolocationsprofilename, 1);
        }
        // get device presets
        if ($deviceip <> "") {
            include_once(__DIR__."/bose_soundtouch_api.php");
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $result = $bosedevice->getDevicePresetsAPI();
            for ($i=0; $i < 6; $i++) {
                $value = $i+1;
                $radiostationname = $result->preset[$i]->ContentItem->itemName;
                $radiostationlocation = $result->preset[$i]->ContentItem["location"];
            IPS_SetVariableProfileAssociation($radiostationsprofilename, "".$value."", "".$radiostationname."", "", 0x0000FF);
            IPS_SetVariableProfileAssociation($radiolocationsprofilename, "".$value."", "".$radiostationlocation."", "", 0x0000FF);
            }
            IPS_SetVariableProfileIcon($radiostationsprofilename, "Cloud");
        }


        // create timer hour profile
        $timerhourprofilename = "bose.TimerHour";
        if (!IPS_VariableProfileExists($timerhourprofilename)) {
            IPS_CreateVariableProfile($timerhourprofilename, 1);
        }
        $i = 0;
        for ($i=0; $i < 24; $i++) {
            IPS_SetVariableProfileAssociation($timerhourprofilename, $i, $i, "", 0x0000FF);
        }
        IPS_SetVariableProfileIcon($timerhourprofilename, "Clock");


        // create timer minute profile
        $timerminuteprofilename = "bose.TimerMinute";
        if (!IPS_VariableProfileExists($timerminuteprofilename)) {
            IPS_CreateVariableProfile($timerminuteprofilename, 1);
        }
        $i = 0;
        for ($i=0; $i <= 55; $i=$i+5) {
            IPS_SetVariableProfileAssociation($timerminuteprofilename, $i, $i, "", 0x0000FF);
        }
        IPS_SetVariableProfileIcon($timerminuteprofilename, "Clock");

        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // register variables
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        // device power
        $this->RegisterVariableBoolean("DevicePower", "Device Power", "~Switch", 1);
        $this->EnableAction("DevicePower");
        SetValue($this->GetIDForIdent("DevicePower"), 0);

        // volume slider
        $this->RegisterVariableInteger("VolumeSlider", "Volume", "bose.VolumeSlider", 2);
        $this->EnableAction("VolumeSlider");
        SetValue($this->GetIDForIdent("VolumeSlider"), 10);
        
        // play pause
        $this->RegisterVariableInteger("PlayPause", "Play / Pause", "bose.PlayPause", 3);
        $this->EnableAction("PlayPause");
        SetValue($this->GetIDForIdent("PlayPause"), true);

        // join zone (multiroom)
        $instanceid = IPS_GetInstance($this->InstanceID)['InstanceID']; 
        $this->RegisterVariableInteger("JoinZone", "Join Zone (MultiRoom)", "bose.Device".$instanceid."MasterZones", 4);
        $this->EnableAction("JoinZone");
        IPS_SetIcon($this->GetIDForIdent("JoinZone"), "Network");
        SetValue($this->GetIDForIdent("JoinZone"), 0);

        // radio
        $this->RegisterVariableInteger("Radio", "Radio", "bose.Device".$instanceid."RadioStations", 5);
        $this->EnableAction("Radio");
        SetValue($this->GetIDForIdent("Radio"), 1);
        
        // device mode
        $this->RegisterVariableString("DeviceMode", "Device Mode", "", 7);
        IPS_SetIcon($this->GetIDForIdent("DeviceMode"), "Information");
        
        // device state
        $this->RegisterVariableString("DeviceState", "Device State", "", 8);
        IPS_SetIcon($this->GetIDForIdent("DeviceState"), "Information");
        
        // now playing
        $this->RegisterVariableString("NowPlaying", "Now Playing", "", 9);
        IPS_SetIcon($this->GetIDForIdent("NowPlaying"), "Information");
        
        // description
        $this->RegisterVariableString("Description", "Description", "", 10);
        IPS_SetIcon($this->GetIDForIdent("Description"), "Information");
        
        // logo
        $this->RegisterVariableString("Logo", "Logo", "~HTMLBox", 11);
        IPS_SetIcon($this->GetIDForIdent("Logo"), "Information");
        
        // timer hour
        $this->RegisterVariableInteger("TimerHour", "Timer Hour", "bose.TimerHour", 12);
        $this->EnableAction("TimerHour");
        SetValue($this->GetIDForIdent("TimerHour"), 12);
        
        // timer minute
        $this->RegisterVariableInteger("TimerMinute", "Timer Minute", "bose.TimerMinute", 13);
        $this->EnableAction("TimerMinute");
        SetValue($this->GetIDForIdent("TimerMinute"), 0);

        // alarm clock
        $this->RegisterVariableBoolean("AlarmClock", "Alarm Clock", "~Switch", 14);
        $this->EnableAction("AlarmClock");
        SetValue($this->GetIDForIdent("AlarmClock"), false);

        // volume fade in
        $this->RegisterVariableBoolean("VolumeFadeIn", "Volume Fade In (15 min)", "~Switch", 15);
        $this->EnableAction("VolumeFadeIn");
        SetValue($this->GetIDForIdent("VolumeFadeIn"), false);
               
        // next power on
        $this->RegisterVariableString("NextPowerOn", "Next Power On", "", 16);
        IPS_SetIcon($this->GetIDForIdent("NextPowerOn"), "Information"); 
        
        // alarm cycle
        IPS_SetHidden($this->RegisterVariableString("AlarmCycle", "Alarm Cycle", "", 17), true);
        SetValue($this->GetIDForIdent("AlarmCycle"), 0);

        // alarm radio station
        IPS_SetHidden($this->RegisterVariableString("AlarmRadioStation", "Alarm Radio Station", "", 18), true);
        SetValue($this->GetIDForIdent("VolumeFadeIn"), false);

        // alarm clock volume
        IPS_SetHidden($this->RegisterVariableInteger("AlarmClockVolume", "Alarm Clock Volume", "bose.VolumeSlider", 19), true);
        SetValue($this->GetIDForIdent("AlarmRadioStation"), "");

        // sleep timer
        $this->RegisterVariableBoolean("SleepTimer", "Sleep Timer", "~Switch", 20);
        $this->EnableAction("SleepTimer");  
        SetValue($this->GetIDForIdent("SleepTimer"), false);

        // volume fade out
        $this->RegisterVariableBoolean("VolumeFadeOut", "Volume Fade Out (15 min)", "~Switch", 21);
        $this->EnableAction("VolumeFadeOut");             
        SetValue($this->GetIDForIdent("VolumeFadeOut"), false);

        // good night
        $this->RegisterVariableBoolean("GoodNight", "Good Night (30 min)", "~Switch", 22);
        IPS_SetIcon($this->GetIDForIdent("GoodNight"), "Moon");
        $this->EnableAction("GoodNight");             
        SetValue($this->GetIDForIdent("GoodNight"), false);

        // next power off
        $this->RegisterVariableString("NextPowerOff", "Next Power Off", "", 23);
        IPS_SetIcon($this->GetIDForIdent("NextPowerOff"), "Information");
        SetValue($this->GetIDForIdent("NextPowerOff"), "");

        // sleep cycle
        IPS_SetHidden($this->RegisterVariableString("SleepCycle", "Sleep Cycle", "", 24), true);
        SetValue($this->GetIDForIdent("SleepCycle"), 0);

        /////////////////////////////////////////////////////////////////////////////////////////////////////////
        // create scripts
        /////////////////////////////////////////////////////////////////////////////////////////////////////////

        // device information script
        $DeviceInformationControlScript ='<?
if ($_IPS["SENDER"] == "Execute") {
	addRadioStations();
}
if ($_IPS["SENDER"] == "TimerEvent") {
	getDeviceInformation();
}

function addRadioStations()
{
   // Radio Stations
   // if you want less radio stations, delete the unnecessary positions
   // if you want to have more radio stations, add positions
   // get the radio station location id from www.vtuner.com
   // http://vtuner.com/setupapp/guide/asp/BrowseStations/startpage.asp
   // example for WDR 2 Ruhrgebiet
   // url: http://www.vtuner.com/vtunerweb/mms/m3u32760.m3u
   // location = 32760

   // Radio Station 7
	$RadioStations["station7"]["name"]         = "AH.FM";
	$RadioStations["station7"]["location"]     = "24836";
   // Radio Station 8
	$RadioStations["station8"]["name"]         = "Sunshine Live Classics";
	$RadioStations["station8"]["location"]     = "44882";
   // Radio Station 9
	$RadioStations["station9"]["name"]         = "Sunshine Live Trance";
	$RadioStations["station9"]["location"]     = "48651";
   // Radio Station 10
	$RadioStations["station10"]["name"]        = "Sunshine Live Be Easy";
	$RadioStations["station10"]["location"]    = "44879";
   // Radio Station 11
	$RadioStations["station11"]["name"]        = "Sunshine Live Be DJ";
	$RadioStations["station11"]["location"]    = "44881";
   // Radio Station 12
	$RadioStations["station12"]["name"]        = "Sunshine Live Radioclub";
	$RadioStations["station12"]["location"]    = "62373";
   // Radio Station 13
	$RadioStations["station13"]["name"]        = "SPORT1.fm";
	$RadioStations["station13"]["location"]    = "52773";
   // Radio Station 14
	$RadioStations["station14"]["name"]        = "SPORT1.fm Spiel1";
	$RadioStations["station14"]["location"]    = "53341";
   // Radio Station 15
	$RadioStations["station15"]["name"]        = "SPORT1.fm Spiel2";
	$RadioStations["station15"]["location"]    = "53343";
   // Radio Station 16
	$RadioStations["station16"]["name"]        = "SPORT1.fm Spiel3";
	$RadioStations["station16"]["location"]    = "53344";
   // Radio Station 17
	$RadioStations["station17"]["name"]        = "SPORT1.fm Spiel4";
	$RadioStations["station17"]["location"]    = "53346";
   // Radio Station 18
	$RadioStations["station18"]["name"]        = "SPORT1.fm Spiel5";
	$RadioStations["station18"]["location"]    = "53347";
   // Radio Station 19
	$RadioStations["station19"]["name"]        = "SPORT1.fm Spiel6";
	$RadioStations["station19"]["location"]    = "53349";
   // Radio Station 20
	$RadioStations["station20"]["name"]        = "WDR2 Ruhrgebiet";
	$RadioStations["station20"]["location"]    = "32760";
   // Radio Station 21
	$RadioStations["station21"]["name"]        = "BFBS Germany";
	$RadioStations["station21"]["location"]    = "35142";

   // extend radio station and radio location profile
	$instanceid = IPS_GetParent($_IPS["SELF"]);
	$presetprofilename = "bose.Device".$instanceid."RadioStations";
	$radiolocationsprofilename = "bose.Device".$instanceid."RadioLocations";
	$variable = $RadioStations;
	$i = 7;
	foreach ($variable as $key => $value) {
		$radiostationname = $value["name"];
		$radiostationlocation = $value["location"];
		IPS_SetVariableProfileAssociation($presetprofilename, "".$i."", "".$radiostationname."", "", 0x0000FF);
		IPS_SetVariableProfileAssociation($radiolocationsprofilename, "".$i."", "".$radiostationlocation."", "", 0x0000FF);
		$i++;
	}
}

function getDeviceInformation()
{
	$deviceip = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "DeviceIP");
	$timeout = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "Timeout");
	$instanceid = IPS_GetParent($_IPS["SELF"]);
	if ($deviceip == "") {
		die;
	}
	try {
      // check device availibility
		if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
			throw new Exception("Device ".$deviceip." is not available");
		}
		include_once("../modules/SymconBoseSoundTouch/Bose/bose_soundtouch_api.php");
		$bosedevice = new BoseSoundTouchAPI($deviceip);
		$result = $bosedevice->getDeviceNowPlayingAPI();
		// power switch
		if ($result["devicemode"] == "Standby") {
			$powerstate = false;
		}
		else {
			$powerstate = true;
		}
		$powerswitch =  GetValue(IPS_GetObjectIDByName("Device Power", IPS_GetParent($_IPS["SELF"])));
		if ($powerstate <> $powerswitch) {
			SetValue(IPS_GetObjectIDByName("Device Power", IPS_GetParent($_IPS["SELF"])), $powerstate);
		}
		// device mode
		$devicemode = GetValue(IPS_GetObjectIDByName("Device Mode", IPS_GetParent($_IPS["SELF"])));
		if ($result["devicemode"] <> $devicemode) {
			SetValue(IPS_GetObjectIDByName("Device Mode", IPS_GetParent($_IPS["SELF"])), $result["devicemode"]);
		}
      // device state
		$devicestate = GetValue(IPS_GetObjectIDByName("Device State", IPS_GetParent($_IPS["SELF"])));
		if ($result["devicestate"] <> $devicestate) {
			SetValue(IPS_GetObjectIDByName("Device State", IPS_GetParent($_IPS["SELF"])), $result["devicestate"]);
		}
      // now playing
		$nowplaying = GetValue(IPS_GetObjectIDByName("Now Playing", IPS_GetParent($_IPS["SELF"])));
		if ($result["nowplaying"] <> $nowplaying) {
			SetValue(IPS_GetObjectIDByName("Now Playing", IPS_GetParent($_IPS["SELF"])), $result["nowplaying"]);
			$instanceid = IPS_GetParent($_IPS["SELF"]);
			$associations = IPS_GetVariableProfile("bose.Device".$instanceid."RadioStations")["Associations"];
			$index = 0;
			foreach($associations as $key => $value) {
				if ($value["Name"] == $result["nowplaying"]) {
					$index = $value["Value"];
				}
			}
			if ($index <> 0){
				SetValue(IPS_GetObjectIDByName("Radio", IPS_GetParent($_IPS["SELF"])), $index);
			}
		}
      // description
		$description = GetValue(IPS_GetObjectIDByName("Description", IPS_GetParent($_IPS["SELF"])));
		if ($result["description"] <> $description) {
			SetValue(IPS_GetObjectIDByName("Description", IPS_GetParent($_IPS["SELF"])), $result["description"]);
		}
      // logo
		$logo = GetValue(IPS_GetObjectIDByName("Logo", IPS_GetParent($_IPS["SELF"])));
		if ($result["logourl"] <> $logo) {
			SetValue(IPS_GetObjectIDByName("Logo", IPS_GetParent($_IPS["SELF"])), $result["logourl"]);
		}
      // volume
		$result = $bosedevice->getDeviceVolumeAPI();
		$volumeslider = GetValue(IPS_GetObjectIDByName("Volume", IPS_GetParent($_IPS["SELF"])));
		if ($result["actualvolume"] <> $volumeslider) {
			SetValue(IPS_GetObjectIDByName("Volume", IPS_GetParent($_IPS["SELF"])), $result["actualvolume"]);
		}
		// zone
		$result = $bosedevice->getDeviceZoneAPI();
		$zonemasterid = $result["zonemasterid"];
		$zonememberid = $result["zonememberid"];
		$deviceid = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "DeviceID");
		$zonemembers = $result["zonememberid"];
		$zonemember = false;
		$masterzone = 0;
		foreach ($zonemembers as $key => $value) {
			// device is member
			if ($value == $deviceid) {
				// device is the master
				if ($zonemasterid == $deviceid) {
				   $masterzone = 0;
				}
				// get zone master
				else {
				   $allboseinstances = IPS_GetInstanceListByModuleID("{4836EF46-FF79-4D6A-91C9-FE54F1BDF2DB}");
					foreach ($allboseinstances as $key => $value) {
						$masterdeviceid = IPS_GetProperty($value, "DeviceID");
						if ($masterdeviceid == $zonemasterid) {
   						$zonemastername = IPS_GetName($value);
						}
					}
					$associations = IPS_GetVariableProfile("bose.Device".$instanceid."MasterZones")["Associations"];
					foreach($associations as $key => $value) {
						if ($value["Name"] == $zonemastername) {
            			$masterzone = $value["Value"];
            		}
        			}
				}
			} 
      }
		$zonestate = GetValue(IPS_GetObjectIDByName("Join Zone (MultiRoom)", IPS_GetParent($_IPS["SELF"])));
		if ($masterzone <> $zonestate) {
			SetValue(IPS_GetObjectIDByName("Join Zone (MultiRoom)", IPS_GetParent($_IPS["SELF"])), $masterzone);
		}
	}
   // error message
	catch (Exception $e) {
		echo $e->getMessage();
	}
}
?>';
        // create script
        $deviceinformationcontrolscriptid = @$this->GetIDForIdent("DeviceInformationControl");
        if ( $deviceinformationcontrolscriptid === false ){
            $deviceinformationcontrolscriptid = $this->RegisterScript("DeviceInformationControl", "DeviceInformationControl", $DeviceInformationControlScript, 0);
        }
        else {
            IPS_SetScriptContent($deviceinformationcontrolscriptid, $DeviceInformationControlScript);
        }
        IPS_SetHidden($deviceinformationcontrolscriptid,true);
        IPS_SetScriptTimer($deviceinformationcontrolscriptid, 5);


        // alarm clock script
        $AlarmClockControlScript = '<?
/*

SymconBoseSoundTouch

Bose SoundTouch Module for IP-Symcon

filename:       AlarmClockControl.php
description:    Alarm Clock Control Script
version         1.0.0
date:           28.12.2015
publisher:      (c) 2015 Ulrich Bittner
environment:    IP-Symcon 4.0 (beta) on RPi

changelog:      version 1.0.0 28.12.2015 initialscript

todo:           (none)

github:         https://github.com/ubittner/SymconBoseSoundTouch.git

*/

// variable definitions
$instanceid = IPS_GetParent(IPS_GetParent($_IPS["SELF"]));
$deviceip = IPS_GetProperty($instanceid, "DeviceIP");
$timeout = IPS_GetProperty($instanceid, "Timeout");
$alarmclockscriptid = IPS_GetScriptIDByName("AlarmClockControl", IPS_GetParent($_IPS["SELF"]));
$alarmclockeventid = @IPS_GetEventIDByName("AlarmClockEvent", $alarmclockscriptid);
$alarmclockcycleseconds = GetValue(IPS_GetObjectIDByName("Alarm Cycle", $instanceid));

// get actual device mode
try {
   // check device availibility
   if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
      throw new Exception("Device ".$deviceip." is not available");
   }
   include_once("../modules/SymconBoseSoundTouch/Bose/bose_soundtouch_api.php");
   $bosedevice = new BoseSoundTouchAPI($deviceip);
   // get device mode
   $result = $bosedevice->getDeviceNowPlayingAPI();
   $devicemode = $result["devicemode"];
   // get volume
   $result = $bosedevice->getDeviceVolumeAPI();
   SetValue(IPS_GetObjectIDByName("Volume", $instanceid), $result["actualvolume"]);
   // power device on
   if ($devicemode == "Standby"){
      $result = $bosedevice->powerDeviceAPI();
      // set radio station
      $alarmradiostation = GetValue(IPS_GetObjectIDByName("Alarm Radio Station", $instanceid));
      SetValue(IPS_GetObjectIDByName("Radio", $instanceid), $alarmradiostation);
      $associations = IPS_GetVariableProfile("bose.Device".$instanceid."RadioLocations")["Associations"];
      foreach($associations as $key => $value) {
         if ($value["Value"] == $alarmradiostation) {
            $location = $value["Name"];
         }
      }
      $result = $bosedevice->setDeviceRadioStationAPI($location);
      if ($alarmclockcycleseconds <> 0) {
         $volume = 1;
         $result = $bosedevice->setDeviceVolumeAPI($volume);
         SetValue(IPS_GetObjectIDByName("Volume", $instanceid), $volume);
         setNewEventCycleTime();
      }
      else {
         switchAlarmClockOff();
      }
      SetValue(IPS_GetObjectIDByName("Device Power", $instanceid), true);
      // get now playing
      getnowplaying:
      $result = $bosedevice->getDevicenowPlayingAPI();
      if ($result["devicestate"] == "Reload") {
         goto getnowplaying;
      }
      SetValue(IPS_GetObjectIDByName("Device Mode", $instanceid), $result["devicemode"]);
      SetValue(IPS_GetObjectIDByName("Device State", $instanceid), $result["devicestate"]);
      SetValue(IPS_GetObjectIDByName("Now Playing", $instanceid), $result["nowplaying"]);
      SetValue(IPS_GetObjectIDByName("Description", $instanceid), $result["description"]);
      SetValue(IPS_GetObjectIDByName("Logo", $instanceid), $result["logourl"]);
   }
   // device is already on
   else {
      if ($alarmclockcycleseconds <> 0) {
         $actualvolume =  GetValue(IPS_GetObjectIDByName("Volume", $instanceid));
         $alarmclockvolume = GetValue(IPS_GetObjectIDByName("Alarm Clock Volume", $instanceid));
         if ($actualvolume < $alarmclockvolume) {
            $targetvolume = ($actualvolume+1);
            $result = $bosedevice->setDeviceVolumeAPI($targetvolume);
            SetValue(IPS_GetObjectIDByName("Volume", $instanceid), $targetvolume);
            setNewEventCycleTime();
         }
         else {
            switchAlarmClockOff();
         }
      }
   }
} 
// error message
catch (Exception $e) {
   echo $e->getMessage();
}


// set new event cycle time
function setNewEventCycleTime()
{
   global   $alarmclockeventid,
            $alarmclockcycleseconds;
   $eventinfo = IPS_GetEvent($alarmclockeventid);
   $eventtimestamp = ($eventinfo["NextRun"]);
   $neweventtimestamp = strtotime("+ " .$alarmclockcycleseconds. " seconds", $eventtimestamp);
   $newhour = date("H",$neweventtimestamp);
   $newminute = date("i",$neweventtimestamp);
   $newsecond = date("s",$neweventtimestamp);
   IPS_SetEventCyclicTimeFrom($alarmclockeventid, $newhour, $newminute, $newsecond);
   IPS_SetEventActive($alarmclockeventid, true);
}

// switch alarm clock off
function switchAlarmClockOff()
{
   global   $instanceid,
            $alarmclockeventid;
   SetValue(IPS_GetObjectIDByName("Alarm Clock", $instanceid), false);
   IPS_SetEventActive($alarmclockeventid, false);
   SetValue(IPS_GetObjectIDByName("Alarm Cycle", $instanceid), 0);
   SetValue(IPS_GetObjectIDByName("Next Power On", $instanceid), "");
}
?>';
        // create script
        $alarmclockvariableid = @$this->GetIDForIdent("AlarmClock"); 
        $alarmclockcontrolscriptid = @IPS_GetScriptIDByName("AlarmClockControl", $alarmclockvariableid);
        if ($alarmclockcontrolscriptid === false ){
            $alarmclockcontrolscriptid = $this->RegisterScript("AlarmClockControl", "AlarmClockControl", $AlarmClockControlScript, 0);
        }
        else {
            IPS_SetScriptContent($alarmclockcontrolscriptid, $AlarmClockControlScript);
        }
        IPS_SetParent($alarmclockcontrolscriptid, $alarmclockvariableid);
        IPS_SetHidden($alarmclockcontrolscriptid, true);   
        $alarmclockscripteventid = @IPS_GetEventIDByName("AlarmClockEvent", $alarmclockcontrolscriptid);
        if ($alarmclockscripteventid == false) {
        $alarmclockscripteventid = IPS_CreateEvent(1);
        IPS_SetParent($alarmclockscripteventid, $alarmclockcontrolscriptid);
        IPS_SetName($alarmclockscripteventid, "AlarmClockEvent");
        }         


        // SleepTimer Script
        $SleepTimerControlScript ='<?
/*

SymconBoseSoundTouch

Bose SoundTouch Module for IP-Symcon

filename:       SleepTimerControl.php
description:    Sleep Timer Control Script
version         1.0.0
date:           28.12.2015
publisher:      (c) 2015 Ulrich Bittner
environment:    IP-Symcon 4.0 (beta) on RPi

changelog:      version 1.0.0 28.12.2015 initialscript

todo:           (none)

github:         https://github.com/ubittner/SymconBoseSoundTouch.git

*/

$instanceid = IPS_GetParent(IPS_GetParent($_IPS["SELF"]));
$deviceip = IPS_GetProperty($instanceid, "DeviceIP");
$timeout = IPS_GetProperty($instanceid, "Timeout");
$sleepcycle = GetValue(IPS_GetObjectIDByName("Sleep Cycle", $instanceid));
$sleeptimerscriptid = IPS_GetScriptIDByName("SleepTimerControl", IPS_GetParent($_IPS["SELF"]));
$sleeptimereventid = @IPS_GetEventIDByName("SleepTimerEvent", $sleeptimerscriptid);
include_once("../modules/SymconBoseSoundTouch/Bose/bose_soundtouch_api.php");

try {
   // check device availibility
   if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
      throw new Exception("Device ".$deviceip." is not available");
   }
   $bosedevice = new BoseSoundTouchAPI($deviceip);
   $result = $bosedevice->getDevicenowPlayingAPI();
   // device is on
   if ($result["devicestate"] == "Play"){
      if ($sleepcycle <> 0) {
         decreaseVolume();
      }
      else {
         switchDeviceOff();
      }
   }
   // device is already off
   else {
      resetValues();
   }
} 

// error message
catch (Exception $e) {
   echo $e->getMessage();
}


// decrease volume
function decreaseVolume()
{
   global   $deviceip,
            $timeout,
            $sleeptimereventid,
            $instanceid,
            $sleepcycle;
   try {
      // check device availibility
      if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
         throw new Exception("Device ".$deviceip." is not available");
      }
      $bosedevice = new BoseSoundTouchAPI($deviceip);
      $result = $bosedevice->getDeviceVolumeAPI();
      if ($result["actualvolume"] == 1) {
         switchDeviceOff();
         IPS_SetEventActive($sleeptimereventid, false);
      }
      else {
         $volume = ($result["actualvolume"])-1;
         $setvolume = $bosedevice->setDeviceVolumeAPI($volume);
         SetValue(IPS_GetObjectIDByName("Volume", $instanceid), $volume);
         $eventinfo = IPS_GetEvent($sleeptimereventid);
         $eventtimestamp = ($eventinfo["NextRun"]);
         $neweventtimestamp = strtotime("+ " . $sleepcycle . " seconds", $eventtimestamp);
         $newhour = date("H",$neweventtimestamp);
         $newminute = date("i",$neweventtimestamp);
         $newsecond = date("s",$neweventtimestamp);
         IPS_SetEventCyclicTimeFrom($sleeptimereventid, $newhour, $newminute, $newsecond);
      }
   }
   // error message
   catch (Exception $e) {
      echo $e->getMessage();
   }
}


// switch device off
function switchDeviceOff()
{
    global  $deviceip,
            $timeout;
   // power off
   try {
      if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
         throw new Exception("Device ".$deviceip." is not available");
      }   
      $bosedevice = new BoseSoundTouchAPI($deviceip);
      $result = $bosedevice->powerDeviceAPI();
      resetValues();
      }
   // error message
   catch (Exception $e) {
      echo $e->getMessage();
   }
}


// reset values
function resetValues()
{
   global   $instanceid,
            $sleeptimereventid;
   SetValue(IPS_GetObjectIDByName("Device Power", $instanceid), false);
   SetValue(IPS_GetObjectIDByName("Device Mode", $instanceid), "");
   SetValue(IPS_GetObjectIDByName("Device State", $instanceid), "");
   SetValue(IPS_GetObjectIDByName("Now Playing", $instanceid), "");
   SetValue(IPS_GetObjectIDByName("Description", $instanceid), "");
   SetValue(IPS_GetObjectIDByName("Logo", $instanceid), "");
   SetValue(IPS_GetObjectIDByName("Sleep Timer", $instanceid), false);
   SetValue(IPS_GetObjectIDByName("Volume Fade Out (15 min)", $instanceid), false);
   SetValue(IPS_GetObjectIDByName("Good Night (30 min)", $instanceid), false);
   SetValue(IPS_GetObjectIDByName("Sleep Cycle", $instanceid), 0);
   SetValue(IPS_GetObjectIDByName("Next Power Off", $instanceid), "");
   IPS_SetEventActive($sleeptimereventid, false);
}
?>';
      $sleeptimervariableid = @$this->GetIDForIdent("SleepTimer"); 
      $sleeptimercontrolscriptid = @IPS_GetScriptIDByName("SleepTimerControl", $sleeptimervariableid);
      if ($sleeptimercontrolscriptid === false ){
         $sleeptimercontrolscriptid = $this->RegisterScript("SleepTimerControl", "SleepTimerControl", $SleepTimerControlScript, 0);
      }
      else {
         IPS_SetScriptContent($sleeptimercontrolscriptid, $SleepTimerControlScript);
      }
      IPS_SetParent($sleeptimercontrolscriptid, $sleeptimervariableid);
      IPS_SetHidden($sleeptimercontrolscriptid,true);   
      $sleeptimerscripteventid = @IPS_GetEventIDByName("SleepTimerEvent", $sleeptimercontrolscriptid);
      if ($sleeptimerscripteventid == false) {
      $sleeptimerscripteventid = IPS_CreateEvent(1);
      IPS_SetParent($sleeptimerscripteventid, $sleeptimercontrolscriptid);
      IPS_SetName($sleeptimerscripteventid, "SleepTimerEvent");
      }         
   
   }
 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // start of modul functions
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // toogle power switch
    public function tooglePowerSwitch() 
    {
        $deviceip = $this->ReadPropertyString("DeviceIP");
        $timeout = $this->ReadPropertyString("Timeout");
        include_once(__DIR__."/bose_soundtouch_api.php");
        try {
            // check device availability
            if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                throw new Exception("Device ".$deviceip." is not available");
            }
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $result = $bosedevice->getDeviceNowPlayingAPI();    
            $devicemode = $result["devicemode"];
            // power device on
            if ($devicemode == "Standby") {
                $power = $bosedevice->powerDeviceAPI();
                SetValue($this->GetIDForIdent("DevicePower"), true);
            }
            // power device off
            if ($devicemode <> "Standby") {
                // check multiroom
                $joinzonevalue = GetValue($this->GetIDForIdent("JoinZone"));
                if ($joinzonevalue <> 0) {
                    $result = $bosedevice->getDeviceZoneAPI();
                    $zonemasterid = $result["zonemasterid"];
                    $zonememberip = $this->ReadPropertyString("DeviceIP");
                    $zonememberid = $result["zonememberid"];
                    $deviceid = $this->ReadPropertyString("DeviceID");
                    foreach ($zonememberid as $key => $value) {
                        if ($value == $deviceid) {
                            $result = $bosedevice->removeDeviceZoneSlaveAPI($zonemasterid,$zonememberip,$zonememberid);
                        }
                    }
                }
                else {
                    $power = $bosedevice->powerDeviceAPI();    
                }
                // reset values
                SetValue($this->GetIDForIdent("DevicePower"), false);
                SetValue($this->GetIDForIdent("JoinZone"), false);
                SetValue($this->GetIDForIdent("DeviceMode"), "");
                SetValue($this->GetIDForIdent("DeviceState"), "");
                SetValue($this->GetIDForIdent("NowPlaying"), "");
                SetValue($this->GetIDForIdent("Description"), "");
                SetValue($this->GetIDForIdent("Logo"), "");  
            }
        } 
        // error message
        catch (Exception $e) {
            echo $e->getMessage();
        } 
    }


    // set volume
    public function setVolume()
    {
        $timeout = $this->ReadPropertyString("Timeout");
        $deviceip = $this->ReadPropertyString("DeviceIP");
        try {
            if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                throw new Exception("Device ".$deviceip." is not available");
            }
            include_once(__DIR__."/bose_soundtouch_api.php");
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $volume = GetValue($this->GetIDForIdent("VolumeSlider"));
            $result = $bosedevice->setDeviceVolumeAPI($volume);
        }
        // error message
        catch (Exception $e) {
            echo $e->getMessage();
        } 
    }


    // toogle play / pause
    public function tooglePlayPause()
    {
        $timeout = $this->ReadPropertyString("Timeout");
        $deviceip = $this->ReadPropertyString("DeviceIP");
        try {
            if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                throw new Exception("Device ".$deviceip." is not available");
            }
            include_once(__DIR__."/bose_soundtouch_api.php");
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $result = $bosedevice->getDeviceNowPlayingAPI();
            if ($result['devicestate'] == "Play") {
                $pause = $bosedevice->setDevicePlayPauseAPI();
                SetValue($this->GetIDForIdent("PlayPause"), 0);
            }
            if ($result['devicestate'] == "Stop") {
                $play = $bosedevice->setDevicePlayPauseAPI();
                SetValue($this->GetIDForIdent("PlayPause"), 1);
            }    
        }
        // error message
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    // join zone (multiroom)
    public function joinZone()
    {
        $joinzonename = GetValueFormatted($this->GetIDForIdent("JoinZone"));
        if ($joinzonename <> "Off") {
            $allboseinstances = IPS_GetInstanceListByModuleID("{4836EF46-FF79-4D6A-91C9-FE54F1BDF2DB}");
            foreach ($allboseinstances as $key => $value) {
                $instancename = IPS_GetName($value);
                if ($instancename == $joinzonename) {
                    $zonemasterip = IPS_GetProperty($value, "DeviceIP"); 
                    $zonemasterid = IPS_GetProperty($value, "DeviceID"); 
                }
            }
            $zonememberip = $this->ReadPropertyString("DeviceIP");
            $zonememberid = $this->ReadPropertyString("DeviceID");
            // check if masterdevice is on
            $timeout = $this->ReadPropertyString("Timeout");
            $deviceip = $zonemasterip;
            try {
                if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                    throw new Exception("Device ".$deviceip." is not available");
                }
                include_once(__DIR__."/bose_soundtouch_api.php");
                $bosedevice = new BoseSoundTouchAPI($deviceip);
                $result = $bosedevice->getDeviceNowPlayingAPI();    
                $devicemode = $result["devicemode"];
                if ($devicemode <> "Standby") {
                    $joinzone = $bosedevice->setDeviceZoneAPI($zonemasterip,$zonemasterid,$zonememberip,$zonememberid);
                }
                else {
                    SetValue($this->GetIDForIdent("JoinZone"), false);
                }
            }
            // error message
            catch (Exception $e) {
                echo $e->getMessage();
            }        
        }
    }


    // set radio
    public function setRadio()
    {
        // reset values
        SetValue($this->GetIDForIdent("DeviceMode"), "");
        SetValue($this->GetIDForIdent("DeviceState"), "");
        SetValue($this->GetIDForIdent("NowPlaying"), "");
        SetValue($this->GetIDForIdent("Description"), "");
        SetValue($this->GetIDForIdent("Logo"), "");
        // get radio
        $instanceid = IPS_GetInstance($this->InstanceID)['InstanceID']; 
        $stationname = GetValueFormatted($this->GetIDForIdent("Radio"));
        $associations = IPS_GetVariableProfile("bose.Device".$instanceid."RadioStations")["Associations"];
        foreach($associations as $key => $value) {
            if ($value["Name"] == $stationname) {
                $index = $value["Value"];
            }
        }
        $associations = IPS_GetVariableProfile("bose.Device".$instanceid."RadioLocations")["Associations"];
        foreach($associations as $key => $value) {
            if ($value["Value"] == $index) {
                $location = $value["Name"];
            }
        }
        // set location
        $timeout = $this->ReadPropertyString("Timeout");
        $deviceip = $this->ReadPropertyString("DeviceIP");
        try {
            if ($timeout && Sys_Ping($deviceip, $timeout) != true) {
                throw new Exception("Device ".$deviceip." is not available");
            }
            include_once(__DIR__ . "/bose_soundtouch_api.php");
            $bosedevice = new BoseSoundTouchAPI($deviceip);
            $result = $bosedevice->setDeviceRadioStationAPI($location);
            // get now playing ???
            SetValue($this->GetIDForIdent("DevicePower"), true);
        }
        // error message
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    // set alarm clock
    public function setAlarmClock()
    {
        $alarmclockstate = GetValue($this->GetIDForIdent("AlarmClock"));
        $alarmclockvariableid = @$this->GetIDForIdent("AlarmClock"); 
        $alarmclockcontrolscriptid = @IPS_GetScriptIDByName("AlarmClockControl", $alarmclockvariableid);
        $alarmclockcontroleventid = @IPS_GetEventIDByName("AlarmClockEvent", $alarmclockcontrolscriptid);
        $hour = GetValue($this->GetIDForIdent("TimerHour"));
        $minute = GetValue($this->GetIDForIdent("TimerMinute"));
        $volumefadein =  GetValue($this->GetIDForIdent("VolumeFadeIn"));
        if ($alarmclockstate == true) {
            IPS_SetEventCyclicTimeFrom($alarmclockcontroleventid, $hour, $minute, 0);
            IPS_SetEventActive($alarmclockcontroleventid, true);
            // calculate cycle time
            $actualvolume =  GetValue($this->GetIDForIdent("VolumeSlider"));
            SetValue($this->GetIDForIdent("AlarmClockVolume"), $actualvolume);
            
            $radiostationname = GetValue($this->GetIDForIdent("Radio"));
            SetValue($this->GetIDForIdent("AlarmRadioStation"), $radiostationname);
            if ($volumefadein == true) {
            $cycletime = floor(900/$actualvolume);
            SetValue($this->GetIDForIdent("AlarmCycle"), $cycletime);   
            }    
        }
        if ($alarmclockstate == false) {
            IPS_SetEventActive($alarmclockcontroleventid, false); 
            SetValue($this->GetIDForIdent("AlarmCycle"), 0);
            SetValue($this->GetIDForIdent("AlarmClockVolume"), 0);
            SetValue($this->GetIDForIdent("AlarmRadioStation"), "");
        }
        $eventinfo = IPS_GetEvent($alarmclockcontroleventid);
        $eventtimestamp = ($eventinfo["NextRun"]); 
        if ($eventtimestamp == 0) {
            SetValue($this->GetIDForIdent("NextPowerOn"), "");
        }
        else {
            $day = date("l",$eventtimestamp);
            $date = date("d.m.Y",$eventtimestamp);
            $time = date("H:i:s",$eventtimestamp);
            $nextpoweron = ($day.", ".$date.", ".$time);
            SetValue($this->GetIDForIdent("NextPowerOn"), $nextpoweron);
        }      
        
    }


    // set sleep timer
    public function setSleepTimer()
    {
        $hour = GetValue($this->GetIDForIdent("TimerHour"));
        $minute = GetValue($this->GetIDForIdent("TimerMinute"));
        $sleeptimervariableid = $this->GetIDForIdent("SleepTimer");
        $sleeptimerstate = GetValue($sleeptimervariableid);
        $sleeptimerscriptid = IPS_GetScriptIDByName("SleepTimerControl", $sleeptimervariableid);
        $sleeptimereventid = @IPS_GetEventIDByName("SleepTimerEvent", $sleeptimerscriptid);
        $goodnightstate = GetValue($this->GetIDForIdent("GoodNight"));
        $volumefadeout =  GetValue($this->GetIDForIdent("VolumeFadeOut"));
        if ($sleeptimerstate == false AND $goodnightstate == false) {
            //toogle switch
            SetValue($this->GetIDForIdent("SleepTimer"), true);       
            // set event
            IPS_SetEventCyclicTimeFrom($sleeptimereventid, $hour, $minute, 0);
            IPS_SetEventActive($sleeptimereventid, true);
            // set next power off
            $eventinfo = IPS_GetEvent($sleeptimereventid);
            $eventtimestamp = ($eventinfo["NextRun"]); 
            $day = date("l",$eventtimestamp);
            $date = date("d.m.Y",$eventtimestamp);
            $time = date("H:i:s",$eventtimestamp);
            $nextpoweroff = ($day.", ".$date.", ".$time);
            SetValue($this->GetIDForIdent("NextPowerOff"), $nextpoweroff);
            // check volumefadeout time
            $timenow = ceil((time())/60)*60;
            $volumefadouttime = $eventtimestamp-$timenow;
            if ($volumefadouttime < 900) {
                SetValue($this->GetIDForIdent("VolumeFadeOut"), false);    
            }
            if ($volumefadeout == true ) {
                $fadeoutseconds = 900;
                $neweventtimestamp = strtotime('-'.$fadeoutseconds.' seconds', $eventtimestamp);
                $newhour = date("H",$neweventtimestamp);
                $newminute = date("i",$neweventtimestamp);
                // set new event time
                IPS_SetEventCyclicTimeFrom($sleeptimereventid, $newhour, $newminute, 0);
                // calculate cycle time 
                $actualvolume =  GetValue($this->GetIDForIdent("VolumeSlider"));
                $cycletime = floor(900/($actualvolume-1));
                SetValue($this->GetIDForIdent("SleepCycle"), $cycletime);
            }       
        }
        if ($sleeptimerstate == true AND $goodnightstate == false) {
            SetValue($this->GetIDForIdent("SleepTimer"), false);
            SetValue($this->GetIDForIdent("VolumeFadeOut"), false);         
            IPS_SetEventActive($sleeptimereventid, false); 
            SetValue($this->GetIDForIdent("SleepCycle"), 0);
            SetValue($this->GetIDForIdent("NextPowerOff"), "");
        }
    }
    

    public function toogleVolumeFadeOut()
    {
        $volumefadeoutstate = GetValue($this->GetIDForIdent("VolumeFadeOut")); 
        $sleeptimerstate = GetValue($this->GetIDForIdent("SleepTimer"));
        $goodnightstate = GetValue($this->GetIDForIdent("GoodNight"));
        if ($volumefadeoutstate == true) {
            if ($sleeptimerstate == false AND $goodnightstate == false) {
                 SetValue($this->GetIDForIdent("VolumeFadeOut"), false);             
            }
        } 
        if ($volumefadeoutstate == false) {
            if ($sleeptimerstate == false) {
                 SetValue($this->GetIDForIdent("VolumeFadeOut"), true);     
            }
        }         
    }


    // good night
    public function toogleGoodNight()
    {
        $goodnightstate = GetValue($this->GetIDForIdent("GoodNight"));
        $sleeptimerstate = GetValue($this->GetIDForIdent("SleepTimer"));
        $sleeptimervariableid = @$this->GetIDForIdent("SleepTimer"); 
        $sleeptimercontrolscriptid = @IPS_GetScriptIDByName("SleepTimerControl", $sleeptimervariableid);
        $sleeptimercontrolscripteventid = @IPS_GetEventIDByName("SleepTimerEvent", $sleeptimercontrolscriptid);
        if ($goodnightstate == false AND $sleeptimerstate == false) {
            SetValue($this->GetIDForIdent("GoodNight"), true);           
            SetValue($this->GetIDForIdent("VolumeFadeOut"), true);
            // set event
            $timenow = time();
            $timestamp =  strtotime('+900 seconds', $timenow);
            $nextpowerofftime = strtotime('+1800 seconds', $timenow);
            $hour = date("H",$timestamp);
            $minute = date("i",$timestamp);
            $second = date("s",$timestamp);
            IPS_SetEventCyclicTimeFrom($sleeptimercontrolscripteventid, $hour, $minute, $second);
            IPS_SetEventActive($sleeptimercontrolscripteventid, true);
            // set next power off
            $day = date("l",$nextpowerofftime);
            $date = date("d.m.Y",$nextpowerofftime);
            $time = date("H:i:s",$nextpowerofftime);
            $nextpoweroff = ($day.", ".$date.", ".$time);
            SetValue($this->GetIDForIdent("NextPowerOff"), $nextpoweroff);
            // calculate cycle time
            $fadeoutseconds = 900;
            $actualvolume =  GetValue($this->GetIDForIdent("VolumeSlider"));
            $cycletime = floor(900/($actualvolume-1));
            SetValue($this->GetIDForIdent("SleepCycle"), $cycletime);
                   
        }
        if ($goodnightstate == true AND $sleeptimerstate == false) {
            IPS_SetEventActive($sleeptimercontrolscripteventid, false);
            SetValue($this->GetIDForIdent("VolumeFadeOut"), false);   
            SetValue($this->GetIDForIdent("GoodNight"), false);       
            SetValue($this->GetIDForIdent("SleepCycle"), 0);
            SetValue($this->GetIDForIdent("NextPowerOff"), "");
        }
    }
 
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // start of request action
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    public function RequestAction($Ident,$Value)
    {
        switch($Ident) {
            case "DevicePower":
            $this->tooglePowerSwitch();
            break;
            case "VolumeSlider":
            SetValue($this->GetIDForIdent($Ident), $Value);
            $this->setVolume();
            break;
            case "PlayPause":
            $this->tooglePlayPause();
            break;
            case "JoinZone":
            SetValue($this->GetIDForIdent($Ident), $Value);
            $this->joinZone();
            break;
            case "Radio":
            SetValue($this->GetIDForIdent($Ident), $Value);
            $this->setRadio();
            break;
            case "TimerHour":
            SetValue($this->GetIDForIdent($Ident), $Value);
            break;
            case "TimerMinute":
            SetValue($this->GetIDForIdent($Ident), $Value);
            break;
            case "AlarmClock":
            SetValue($this->GetIDForIdent($Ident), $Value);
            $this->setAlarmClock();
            break;
            case "VolumeFadeIn":
            SetValue($this->GetIDForIdent($Ident), $Value);
            break;
            case "SleepTimer":
            $this->setSleepTimer();
            break;
            case "VolumeFadeOut":
            $this->toogleVolumeFadeOut();
            break;
            case "GoodNight":
            $this->toogleGoodNight();
            break;
            default:
            throw new Exception("Invalid ident");
        }
    }
}
?>