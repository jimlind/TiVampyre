<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Working Directory
|--------------------------------------------------------------------------
|
| This should be where everything is going to be downloaded to and
| processed from.  Where the finished products will also be found.
| Everything will get stored here, nothing in the /tmp/ directory.
|
| Include trailing slash.
|
*/
$config['tivampyre']['working_directory'] = '/disk2/tivo/';

/*
|--------------------------------------------------------------------------
| Commercial Skip Executable Path
|--------------------------------------------------------------------------
|
| This should be where comskip.exe and compskip.ini are found on your
| server.  I like to keep that stuff in /opt/ and some people like to
| keep in is the /usr/bin/ directory.  Doesn't matter to me.
|
| Include trailing slash.
|
*/
$config['tivampyre']['comskip_path'] = '/opt/comskip/';

/*
|--------------------------------------------------------------------------
| Media Access Key
|--------------------------------------------------------------------------
|
| This is the magic ID that tells your TiVo that the person trying to
| interact with it is actually a person with physical access to the device.
|
| Find it via:
| TiVo Central -> Messages and Set Up -> Account and System Information
|
*/
$config['tivampyre']['mak'] = '7678113199';

