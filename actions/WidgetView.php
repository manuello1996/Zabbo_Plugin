<?php declare(strict_types = 0);

namespace Modules\BasicWidget\Actions;

use API,
    CControllerDashboardWidgetView,
    CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        $groupids = array_map('intval', (array)($this->fields_values['groupids'] ?? []));
        $patterns = array_values(array_filter((array)($this->fields_values['items'] ?? []), 'strlen'));

        // 1) Fetch hosts in the selected groups
        $hosts = [];
        if ($groupids) {
            $hosts = API::Host()->get([
                'groupids'       => $groupids,
                'output'         => ['hostid', 'host', 'name'],
                'monitored_hosts'=> true,          // only monitored (optional; remove if you want all)
                'preservekeys'   => true
            ]);
        }

        // 2) For each pattern, fetch matching items for ALL those hosts in a single call per pattern
        $items_by_host = [];
        if ($hosts && $patterns) {
            $hostids = array_keys($hosts);

            foreach ($patterns as $pattern) {
                $items = API::Item()->get([
                    'hostids'                 => $hostids,
                    'search'                  => ['name' => $pattern], // match by item NAME
                    'searchWildcardsEnabled'  => true,                 // enable '*' wildcards in pattern
                    'output'                  => ['itemid','hostid','name','key_','lastvalue','lastclock','value_type','units'],
                    'sortfield'               => 'name'
                ]);

                foreach ($items as $it) {
                    $hid = (int)$it['hostid'];
                    if (!isset($items_by_host[$hid])) {
                        $items_by_host[$hid] = [];
                    }
                    $items_by_host[$hid][] = [
                        'itemid'    => (int)$it['itemid'],
                        'name'      => $it['name'],
                        'key'       => $it['key_'],
                        'lastvalue' => $it['lastvalue'],  // already the latest value from items table
                        'lastclock' => (int)$it['lastclock'],
                        'units'     => $it['units'] ?? '',
                        'value_type'=> isset($it['value_type']) ? (int)$it['value_type'] : null
                    ];
                }
            }
        }

        // 3) Build response payload: list of hosts with their matched items
        $result_hosts = [];
        foreach ($hosts as $hid => $h) {
            $result_hosts[] = [
                'hostid' => (int)$h['hostid'],
                'name'   => $h['name'] !== '' ? $h['name'] : $h['host'],
                'items'  => array_values($items_by_host[(int)$h['hostid']] ?? [])
            ];
        }

        $this->setResponse(new CControllerResponseData([
            'name'          => $this->getInput('name', $this->widget->getName()),
            'fields_values' => $this->fields_values,
            'groups'        => $groupids,
            'hosts'         => $result_hosts,
            // extras for troubleshooting
            'debug'         => [
                'patterns' => $patterns,
                'hosts_count' => count($result_hosts)
            ]
        ]));
    }
}
