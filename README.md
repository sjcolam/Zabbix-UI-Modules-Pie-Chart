# Firmware Version Pie Chart — Zabbix 7.4 Widget

A custom Zabbix dashboard widget that displays firmware version distribution across your monitored hosts as an interactive pie chart.

![Widget screenshot showing a pie chart with firmware version segments and a colour-coded legend](doc/screenshot-dashboard.png)

## Features

- **Pie chart** showing what proportion of your devices are running each firmware version
- **Flexible host filtering** — filter by host group, host name pattern (`pdu-*`, `*router*`), and/or inventory fields (vendor, model, type, OS, etc.)
- **Multiple item patterns** — handles environments where different device types use different SNMP OID keys for firmware (e.g. `deviceFirmware.0` and `firmwareVersion.0`)
- **Wildcard inventory matching** — filter by `vendor=Cisco*` or `model=*PDU*`
- **Optional legend** showing version, device count, and percentage
- **Responsive** — redraws automatically when the widget is resized
- **No external dependencies** — pure HTML5 Canvas rendering

## Requirements

- Zabbix 7.4 or later
- PHP 8.x
- Hosts must have SNMP items collecting firmware version as a text value
- Host inventory must be enabled and populated if using inventory filters

## Installation

### 1. Copy the module files

```bash
cp -r Zabbix-UI-Modules-Pie-Chart /usr/share/zabbix/ui/modules/
```

### 2. Set correct permissions

```bash
find /usr/share/zabbix/ui/modules/Zabbix-UI-Modules-Pie-Chart -type f -exec chmod 644 {} \;
find /usr/share/zabbix/ui/modules/Zabbix-UI-Modules-Pie-Chart -type d -exec chmod 755 {} \;
```

### 3. Enable the module in Zabbix

1. Log in to Zabbix as an Administrator
2. Go to **Administration → General → Modules**
3. Click **Scan directory**
4. Find **Firmware Version Pie Chart** in the list and click **Enable**

### 4. Add the widget to a dashboard

1. Open any dashboard and click **Edit dashboard**
2. Click **Add widget**
3. Select **Firmware Version Pie Chart** from the widget type list
4. Configure the widget (see below) and click **Apply**

## Configuration

| Field | Description |
|-------|-------------|
| **Host groups** | Filter to hosts in selected groups (optional) |
| **Hosts** | Filter by host name pattern, e.g. `pdu-*` or `*router*` (optional) |
| **Inventory field 1** | Inventory DB column name to filter by, e.g. `vendor` (optional) |
| **Inventory value 1** | Value to match, wildcards supported, e.g. `Cisco*` (optional) |
| **Inventory field 2** | Second inventory filter field (optional, AND'd with field 1) |
| **Inventory value 2** | Second inventory filter value (optional) |
| **Item patterns** *(required)* | SNMP item key or name patterns to find firmware values, e.g. `deviceFirmware.0` |
| **Show legend** | Toggle the colour-coded legend below the chart |

### Host filtering

At least one of Host groups, Hosts, or an Inventory filter must be configured. All configured filters are AND'd together — a host must match all specified filters to be included.

### Inventory field names

Inventory field names must match the database column names from the `host_inventory` table exactly (lowercase). Common useful fields:

| Field name | Description |
|-----------|-------------|
| `vendor` | Hardware vendor |
| `model` | Hardware model |
| `type` | Device type |
| `os` | Operating system |
| `hardware` | Hardware description |
| `serialno_a` | Serial number |
| `location` | Location |
| `deployment_status` | Deployment status |

### Item patterns

The widget searches for items matching the pattern against both the item **key** and item **name**. Multiple patterns can be entered — all matching items across all hosts are included. This allows a single widget to cover device types that use different SNMP OIDs:

- `deviceFirmware.0` — matches ICT PDU devices
- `firmwareVersion.0` — matches Tachyon FWA devices

One firmware version per host is counted (the first matching item found).

## Examples

### All Tachyon devices by firmware version
- **Inventory field 1:** `vendor`
- **Inventory value 1:** `Tachyon`
- **Item patterns:** `firmwareVersion.0`

### ICT PDUs in a specific host group
- **Host groups:** `ICT PDUs`
- **Item patterns:** `deviceFirmware.0`

### All access points by firmware, filtered by host name
- **Hosts:** `*-AP*`
- **Item patterns:** `firmwareVersion.0`, `deviceFirmware.0`

### Mixed vendor environment — two inventory filters
- **Inventory field 1:** `vendor`
- **Inventory value 1:** `Ubiquiti`
- **Inventory field 2:** `type`
- **Inventory value 2:** `Wave*`
- **Item patterns:** `firmwareVersion.0`

## Troubleshooting

**"No monitored hosts found matching the configured filters"**
- Ensure inventory field names are lowercase (e.g. `vendor` not `Vendor`)
- Check that host inventory is enabled and populated for your hosts
- Verify the value matches exactly (case-insensitive), or use `*` wildcards

**"No active items matching pattern(s) found"**
- Check the item key in Zabbix: go to a host → Items and search for the firmware item
- Copy the exact key value and paste it into the Item patterns field
- The item must be active (not disabled) and have a recent value

**Widget is blank with no error message**
- Hard-refresh the browser (Ctrl+Shift+R) to clear the JS cache
- Check Apache error log: `tail -50 /var/log/apache2/error.log`

## File structure

```
Zabbix-UI-Modules-Pie-Chart/
├── manifest.json                          # Module registration
├── Widget.php                             # Root widget class
├── actions/
│   ├── WidgetEdit.php                     # Edit form controller
│   └── WidgetView.php                     # Data fetching & response
├── includes/
│   └── WidgetForm.php                     # Form field definitions
├── views/
│   ├── widget.firmware_pie_chart.edit.php # Edit form view
│   └── widget.view.php                    # Widget body view
└── assets/
    ├── js/
    │   └── class.widget.js                # Frontend widget class
    └── css/
        └── widget.css                     # Widget styles
```

## Licence

MIT — free to use, modify and distribute.

## Contributing

Pull requests welcome. Tested on Zabbix 7.4 with PHP 8.2.
