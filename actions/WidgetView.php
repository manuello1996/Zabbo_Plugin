<?php declare(strict_types = 0);

namespace Modules\BasicWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        $fv = $this->fields_values;

        $groupids  = array_map('intval', (array)($fv['groupids'] ?? []));
        $patterns  = array_values(array_filter((array)($fv['items'] ?? []), 'strlen'));

        // Limit (default 100; clamp 1..10000)
        $max_items = (int)($fv['max_items'] ?? 100);
        $max_items = max(1, min(10000, $max_items));

        // Map integer select to ASC/DESC (0 = ASC, 1 = DESC)
        $sort_flag = isset($fv['sortorder']) ? (int)$fv['sortorder'] : 1;
        $sortorder = ($sort_flag === 0) ? 'ASC' : 'DESC';

        // 1) Hosts
        $hosts = [];
        if ($groupids) {
            $hosts = API::Host()->get([
                'groupids'        => $groupids,
                'output'          => ['hostid','host','name'],
                'monitored_hosts' => true,
                'preservekeys'    => true
            ]);
        }

        // 2) Items (respect overall cap across patterns)
        $items_by_host = [];
        if ($hosts && $patterns) {
            $hostids   = array_keys($hosts);
            $remaining = $max_items;

            foreach ($patterns as $pattern) {
                if ($remaining <= 0) break;

                $items = API::Item()->get([
                    'hostids'                => $hostids,
                    'search'                 => ['name' => $pattern],
                    'searchWildcardsEnabled' => true,
                    'output'                 => ['itemid','hostid','name','key_','lastvalue','lastclock','value_type','units'],
                    'sortfield'              => 'name',
                    'limit'                  => $remaining
                ]);

                foreach ($items as $it) {
                    $items_by_host[(int)$it['hostid']][] = $it;
                }
                $remaining -= count($items);
            }
        }

        // 3) Flatten rows (skip hosts without matches)
        $rows = [];
        foreach ($hosts as $hid => $h) {
            $host_items = $items_by_host[(int)$hid] ?? [];
            if (!$host_items) {
                continue;
            }

            $host_name = $h['name'] !== '' ? $h['name'] : $h['host'];

            foreach ($host_items as $it) {
                $val_raw = $it['lastvalue'] ?? null;
                $units   = $it['units'] ?? '';

                // Derive numeric for sorting: numbers first, non-numeric last
                $sort_num = is_numeric($val_raw) ? (float)$val_raw : null;

                $rows[] = [
                    'host'      => $host_name,
                    'item'      => $it['name'],
                    'units'     => $units,
                    'lastvalue' => $val_raw,
                    'lastclock' => isset($it['lastclock']) ? (int)$it['lastclock'] : null,
                    'sort_num'  => $sort_num
                ];
            }
        }

        // 4) Sort by last value according to sortorder (always sort; it’s the only sortable col)
        usort($rows, function($a, $b) use ($sortorder) {
            $dir = ($sortorder === 'ASC') ? 1 : -1;
            $an = $a['sort_num']; $bn = $b['sort_num'];

            if ($an === null && $bn === null) {
                // stable tie-breakers
                $t = strnatcasecmp((string)$a['host'], (string)$b['host']);
                if ($t !== 0) return $dir * $t;
                return $dir * strnatcasecmp((string)$a['item'], (string)$b['item']);
            }
            if ($an === null) return 1;
            if ($bn === null) return -1;
            if ($an == $bn) {
                $t = strnatcasecmp((string)$a['host'], (string)$b['host']);
                if ($t !== 0) return $dir * $t;
                return $dir * strnatcasecmp((string)$a['item'], (string)$b['item']);
            }
            return $dir * (($an < $bn) ? -1 : 1);
        });

        // 5) Enforce final cap after sorting (defensive)
        if (count($rows) > $max_items) {
            $rows = array_slice($rows, 0, $max_items);
        }

        $this->setResponse(new CControllerResponseData([
            'name'          => $this->getInput('name', $this->widget->getName()),
            'fields_values' => $fv,
            'rows'          => $rows
        ]));
    }
}
