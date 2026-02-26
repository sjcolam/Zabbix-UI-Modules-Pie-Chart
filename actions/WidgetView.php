<?php declare(strict_types = 0);

namespace Modules\FirmwarePieChart\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

    private const VALID_INV_FIELDS = [
        'type', 'type_full', 'name', 'alias', 'os', 'os_full', 'os_short',
        'serialno_a', 'serialno_b', 'tag', 'asset_tag', 'macaddress_a', 'macaddress_b',
        'hardware', 'hardware_full', 'software', 'software_full',
        'software_app_a', 'software_app_b', 'software_app_c', 'software_app_d', 'software_app_e',
        'chassis', 'model', 'hw_arch', 'vendor', 'contract_number', 'installer_name',
        'deployment_status', 'location', 'site_address_a', 'site_address_b', 'site_address_c',
        'site_city', 'site_state', 'site_country', 'site_zip', 'site_rack',
        'poc_1_name', 'poc_2_name',
    ];

    protected function doAction(): void {
        $fields        = $this->fields_values;
        $groupids      = $fields['groupids']     ?? [];
        $host_patterns = $fields['hostids']      ?? [];
        $items         = $fields['items']        ?? [];
        $show_legend   = (bool) ($fields['show_legend'] ?? true);
        $host_tags     = $fields['host_tags']    ?? [];
        $tags_evaltype = $fields['host_tags_evaltype'] ?? TAG_EVAL_TYPE_AND_OR;

        $inv_field_1 = strtolower(trim($fields['inv_field_1'] ?? ''));
        $inv_value_1 = trim($fields['inv_value_1'] ?? '');
        $inv_field_2 = strtolower(trim($fields['inv_field_2'] ?? ''));
        $inv_value_2 = trim($fields['inv_value_2'] ?? '');

        $firmware_counts = [];
        $error = null;

        // Resolve groupids
        $resolved_groupids = [];
        foreach ($groupids as $g) {
            $resolved_groupids[] = is_array($g) ? $g['groupid'] : (string) $g;
        }

        // Build inventory filters
        $inv_filters = [];
        if ($inv_field_1 !== '' && $inv_value_1 !== '' && in_array($inv_field_1, self::VALID_INV_FIELDS, true)) {
            $inv_filters[$inv_field_1] = $inv_value_1;
        }
        if ($inv_field_2 !== '' && $inv_value_2 !== '' && in_array($inv_field_2, self::VALID_INV_FIELDS, true)) {
            $inv_filters[$inv_field_2] = $inv_value_2;
        }

        // Clean host patterns
        $host_patterns_clean = array_filter(array_map('trim', (array) $host_patterns));

        // Clean tags - remove empty ones
        $active_tags = array_values(array_filter($host_tags, fn($t) => $t['tag'] !== ''));

        $has_any_filter = !empty($resolved_groupids) || !empty($host_patterns_clean)
            || !empty($inv_filters) || !empty($active_tags);

        if (!$has_any_filter) {
            $error = 'No hosts, host groups, inventory filters or tags configured. Please edit the widget.';
        }
        else {
            $host_options = [
                'output'          => ['hostid'],
                'monitored_hosts' => true,
            ];

            if (!empty($resolved_groupids)) {
                $host_options['groupids'] = $resolved_groupids;
            }

            if (!empty($host_patterns_clean) && !in_array('*', $host_patterns_clean, true)) {
                $host_options['search']                 = ['name' => $host_patterns_clean];
                $host_options['searchWildcardsEnabled'] = true;
                $host_options['searchByAny']            = true;
            }

            if (!empty($inv_filters)) {
                $host_options['selectInventory'] = array_keys($inv_filters);
            }

            // Pass tags directly to the API
            if (!empty($active_tags)) {
                $host_options['evaltype'] = $tags_evaltype;
                $host_options['tags']     = $active_tags;
            }

            $hosts = API::Host()->get($host_options);

            // Apply inventory value filtering
            if (!empty($inv_filters)) {
                $resolved_hostids = [];
                foreach ($hosts as $host) {
                    $inv = is_array($host['inventory']) ? $host['inventory'] : [];
                    $match = true;
                    foreach ($inv_filters as $field => $value) {
                        $inv_val = strtolower(trim($inv[$field] ?? ''));
                        $pattern = strtolower($value);
                        if (strpos($pattern, '*') !== false) {
                            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
                            if (!preg_match($regex, $inv_val)) {
                                $match = false;
                                break;
                            }
                        }
                        elseif ($inv_val !== $pattern) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $resolved_hostids[] = $host['hostid'];
                    }
                }
            }
            else {
                $resolved_hostids = array_column($hosts, 'hostid');
            }

            if (empty($resolved_hostids)) {
                $filter_desc = [];
                foreach ($inv_filters as $f => $v) {
                    $filter_desc[] = "$f=$v";
                }
                $error = 'No monitored hosts found matching the configured filters'
                    . (!empty($filter_desc) ? ' (' . implode(', ', $filter_desc) . ')' : '')
                    . '. Note: inventory field names must be lowercase (e.g. vendor, model).';
            }
            else {
                $item_patterns = array_filter(array_map('trim', (array) $items));

                if (empty($item_patterns)) {
                    $error = 'No item pattern configured. Please edit the widget.';
                }
                else {
                    $matched_items = [];
                    foreach ($item_patterns as $pattern) {
                        foreach (['key_', 'name'] as $search_field) {
                            $batch = API::Item()->get([
                                'output'   => ['itemid', 'hostid', 'lastvalue', 'key_', 'name'],
                                'hostids'  => $resolved_hostids,
                                'search'   => [$search_field => $pattern],
                                'filter'   => ['status' => ITEM_STATUS_ACTIVE],
                                'webitems' => false,
                            ]);
                            foreach ($batch as $item) {
                                $matched_items[$item['itemid']] = $item;
                            }
                        }
                    }

                    if (empty($matched_items)) {
                        $error = 'No active items matching pattern(s) "' . implode(', ', $item_patterns) . '" found.';
                    }
                    else {
                        $seen_hosts = [];
                        foreach ($matched_items as $item) {
                            if (isset($seen_hosts[$item['hostid']])) continue;
                            $seen_hosts[$item['hostid']] = true;
                            $version = trim((string) $item['lastvalue']);
                            if ($version === '') $version = 'No data';
                            $firmware_counts[$version] = ($firmware_counts[$version] ?? 0) + 1;
                        }
                        arsort($firmware_counts);
                    }
                }
            }
        }

        $this->setResponse(new CControllerResponseData([
            'name'            => $this->getInput('name', $this->widget->getDefaultName()),
            'firmware_counts' => $firmware_counts,
            'show_legend'     => $show_legend,
            'error'           => $error,
            'user'            => ['debug_mode' => $this->getDebugMode()],
        ]));
    }
}
