package Apache::Ocsinventory::Plugins::Wifi::Map;
 
use strict;
 
use Apache::Ocsinventory::Map;

$DATA_MAP{wifi} = {
   mask => 0,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'ID',
   writeDiff => 0,
   cache => 0,
   fields => {
       SSID => {},
       IP => {},
	   MAC => {}
   }
};
1;