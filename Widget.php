<?php declare(strict_types = 0);

namespace Modules\FirmwarePieChart;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

    public function getDefaultName(): string {
        return 'Firmware Version Pie Chart';
    }
}
