# Symcon Bose SoundTouch

PHP Module for IP-Symcon to integrate Bose SoundTouch Devices  

[![license](https://i.creativecommons.org/l/by-nc/4.0/88x31.png "Creative Commons Attribution-NonCommercial 4.0 International License")](http://creativecommons.org/licenses/by-nc/4.0/)  

## Documentation

**Content**

1. [license](#1-license)
2. [purpose](#2-purpose) 
3. [environment](#3-environment) 
4. [functional range](#4-functional-range) 
5. [requirements](#5-requirements)
6. [installation & configuration](#6-installation--configuration)
7. [operation manual](#7-operation-manual)
8. [api functions](#8-api-functions)

## 1. license

SymconBoseSoundTouch by Ulrich Bittner is licensed under a [Creative Commons Attribution-NonCommercial 4.0 International License](http://creativecommons.org/licenses/by-nc/4.0/).  
Based on a work at https://github.com/ubittner/SymconBoseSoundTouch.  
Permissions beyond the scope of this license may be available at https://github.com/ubittner/SymconBoseSoundTouch.  

For more information see LICENSE.MD


## 2. purpose

The purpose of this repository is to integrate and control Bose SoundTouch devices in IP-Symcon. 


## 3. environment

The code was developed and tested under IP-Symcon Version 4.X in beta phase on a raspberry pi 2. 


## 4. functional range

This module integrates Bose SoundTouch devices in IP-Symcon.

The folowing functions are implemented:
-  Internet Radio (presets 1-6 + additional presets)
-  Multi Room
-  Alarm Clock
-  Sleep Timer

All other functions are not included right now. 


## 5. requirements

- IPS 4.x
- Bose SoundTouch Device


## 6. installation & configuration

- installation in IPS 4.x

- go to the core instance  
![Modules](/Screenshots/01-modules.jpg)

- use the add button, to add a new module  
![AddModule](/Screenshots/02-addmodule.jpg)

- add the following URL to 'Modul Control':  
`git://github.com/ubittner/SymconBoseSoundTouch.git`  
![Address](/Screenshots/03-githubaddress.jpg)

- add a new instance  
![AddInstance](/Screenshots/04-addinstance.jpg)  


- select Bose as a filter and click next  
![BoseInstance](/Screenshots/05-boseinstance.jpg)  

- you can rename the instance name now, or later  
![InstanceName](/Screenshots/06-instancename.jpg)

- input the device settings:  
![DeviceSettings](/Screenshots/07-devicesettings.jpg)
- Roomname, to identify the device and rename the instance
- IP-Address is the ip address of the device
- Device-ID is the id of the device, it is used for multiroom function  
Will be read out automatically
- Timeout is the timeout of the device in network environment


- use categories for a better overview  
![UseCategories](/Screenshots/08-categories.jpg)

## 7. operation manual


- overview of module  
![ShowOverview](/Screenshots/09-overview.jpg)  

- Device Power: power the device on / off
- Volume: change the volume of the device
- Play/Pause: play / pause the device
- Join Zone: select the multiroom master (you need more then one device)
- Radio: select the presets 1- 6  
	To add more radio stations use the DeviceInformationControl Script, edit the stations and execute the script manually.
	You can do it for each device seperatly.
- Device Mode: shows the device mode (standby, Internet Radio, etc.)
- Device State: shows the device state (play, pause, etc.)
- Now Playing: shows whats now playing
- Description: shows the discription
- Logo: shows the logo
- Timer Hour: is used for alarm clock and sleep timer, sets the hour
- Timer Minute: is used for alarm clock and sleep timer, sets the minute
- Alarm Clock: sets the alarm clock on / off and uses the timer hour and minute 
- Volume Fade In: the volume will be fade in over 15 minutes form 1 to the stored alarm clock volume
- Next Power On: shows the next alarm clock event
- Sleep Timer: sets the sleep timer on / off and uses the timer hour and minute
- Volume fade Out: the volume will be fadout over 15 minutes to volume 1 before sleep timer will switch device off
- Good Night: will power off the device in 30 minutes from now and will fade out the volume to 1 for the last 15 minutes
- Next Power Off: shows the next sleep timer event


## 8. api functions

get functions:  

- getDevicePresetsAPI
- getDeviceNowPlayingAPI
- getDeviceVolumeAPI

post functions: 

- powerDeviceAPI
- setDeviceRadioStationAPI    ($location)
- setDeviceVolumeAPI          ($volume)
- setDeviceZoneAPI            ($zonemasterip,$zonemasterid,$zonememberip,$zonememberid)
- removeDeviceZoneSlaveAPI    ($zonemasterid,$zonememberip,$zonememberid)
