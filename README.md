# OCS Inventory Plugin - WiFi (macOS)

This plugin collects Wi-Fi information (SSID, IP, MAC address) on macOS devices for **OCS Inventory NG**.

## Why?

macOS creates a randomized MAC address per SSID. This makes DHCP reservations and inventory tracking more difficult when SSIDs change. This plugin helps track persistent MACs per SSID.

## Features

- Detects current Wi-Fi interface
- Retrieves:
  - SSID
  - IP address
  - MAC address
- Automatically stores data in the OCS Inventory database
- Cleanup system removes duplicates or invalid entries using MySQL events


## Installation

https://wiki.ocsinventory-ng.org/10.Plugin-engine/Using-plugins-installer/

### Agent Side (macOS)

1. Copy `Wifi.pm` to the agent plugin folder:
   ```
   /Applications/OCSNG.app/Contents/Resources/lib/Ocsinventory/Agent/Modules/Wifi.pm
   ```
2. Add to the /etc/ocsinventory-agent/modules.conf file:
   ```
   use Ocsinventory::Agent::Modules::Wifi;
   ```
   
3. Ensure required macOS CLI tools are available (default on macOS):
   - `networksetup`
   - `system_profiler`
   - `ipconfig`
   - `ifconfig`
   - `awk`
   

## Enable MySQL / MariaDB Event Scheduler (REQUIRED)

This plugin uses a scheduled event (`wifi_cleanup_event`) in **MySQL** or **MariaDB** to remove duplicate or invalid Wi-Fi records.

### Enable Temporarily (until the database restarts):

```
SET GLOBAL event_scheduler = ON;
```

### Enable Permanently (on every boot):

Edit your database server config file, which could be one of the following depending on your system and setup:

- `/etc/my.cnf`
- `/etc/mysql/my.cnf`
- `/etc/mysql/mysql.conf.d/mysqld.cnf`

Add the following under the `[mysqld]` section (works for both MySQL and MariaDB):

```
[mysqld]
event_scheduler=ON
```

### Restart your database server to apply changes:

For **MySQL**:

```
sudo systemctl restart mysql
# or
sudo service mysql restart
```

For **MariaDB** (sometimes the service name is `mariadb`):

```
sudo systemctl restart mariadb
# or
sudo service mariadb restart
```

### Verify the event scheduler is enabled:

```
SHOW VARIABLES LIKE 'event_scheduler';
```

Expected output:

| Variable_name   | Value |
|-----------------|-------|
| event_scheduler | ON    |

> If the value is `OFF`, the cleanup system in the plugin will **not** function correctly.

## License

MIT License. See `LICENSE`.

## Maintainer

- 4d000
