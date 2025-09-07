<?php declare(strict_types=1);

namespace Modules\BasicWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

/**
 * Controller for BasicWidget dashboard widget view
 *
 * NOTE (CHANGE): This file was extended to annotate rows with 'display' => 'bar'
 * and a normalized 'value_percent' (0..100) so the front-end can render numeric items as bars.
 * All edits are labeled with "CHANGE" comments.
 */
class WidgetView extends CControllerDashboardWidgetView
{
    private const DEFAULT_MAX_ITEMS = 100;
    private const MIN_ITEMS = 1;
    private const MAX_ITEMS = 10000;
    private const SORT_ASC = 0;
    private const SORT_DESC = 1;

    protected function doAction(): void
    {
        $fields_values = $this->fields_values;

        // Extract and validate configuration
        $config = $this->extractConfiguration($fields_values);
        
        // Get hosts based on group selection
        $hosts = $this->getHosts($config['groupids']);
        
        // Get items for the hosts based on patterns
        $items_by_host = $this->getItems($hosts, $config['patterns'], $config['max_items']);
        
        // Build result rows
        $rows = $this->buildResultRows($hosts, $items_by_host);
        
        // Sort rows according to configuration
        $this->sortRows($rows, $config['sortorder']);
        
        // Apply final item limit
        if (count($rows) > $config['max_items']) {
            $rows = array_slice($rows, 0, $config['max_items']);
        }

        // CHANGE: Annotate rows with display metadata and compute normalized percentage
        // so front-end can render numeric items as bars.
        $this->applyBarDisplay($rows);

        $this->setResponse(new CControllerResponseData([
            'name' => $this->getInput('name', $this->widget->getName()),
            'fields_values' => $fields_values,
            'rows' => $rows
        ]));
    }

    /**
     * Extract and validate configuration from field values
     */
    private function extractConfiguration(array $fields_values): array
    {
        $groupids = array_map('intval', (array)($fields_values['groupids'] ?? []));
        $patterns = array_values(array_filter((array)($fields_values['items'] ?? []), 'strlen'));
        
        // Validate and clamp max_items
        $max_items = (int)($fields_values['max_items'] ?? self::DEFAULT_MAX_ITEMS);
        $max_items = max(self::MIN_ITEMS, min(self::MAX_ITEMS, $max_items));
        
        // Validate sort order
        $sort_flag = isset($fields_values['sortorder']) ? (int)$fields_values['sortorder'] : self::SORT_DESC;
        $sortorder = ($sort_flag === self::SORT_ASC) ? 'ASC' : 'DESC';

        return [
            'groupids' => $groupids,
            'patterns' => $patterns,
            'max_items' => $max_items,
            'sortorder' => $sortorder
        ];
    }

    /**
     * Get hosts from specified groups
     */
    private function getHosts(array $groupids): array
    {
        if (empty($groupids)) {
            return [];
        }

        try {
            return API::Host()->get([
                'groupids' => $groupids,
                'output' => ['hostid', 'host', 'name'],
                'monitored_hosts' => true,
                'preservekeys' => true
            ]) ?: [];
        } catch (\Exception $e) {
            error_log("Error fetching hosts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items for hosts based on patterns
     */
    private function getItems(array $hosts, array $patterns, int $max_items): array
    {
        if (empty($hosts) || empty($patterns)) {
            return [];
        }

        $hostids = array_keys($hosts);
        $items_by_host = [];
        $remaining = $max_items;

        foreach ($patterns as $pattern) {
            if ($remaining <= 0) {
                break;
            }

            try {
                $items = API::Item()->get([
                    'hostids' => $hostids,
                    'search' => ['name' => $pattern],
                    'searchWildcardsEnabled' => true,
                    'output' => ['itemid', 'hostid', 'name', 'key_', 'lastvalue', 'lastclock', 'value_type', 'units'],
                    'sortfield' => 'name',
                    'limit' => $remaining
                ]) ?: [];

                foreach ($items as $item) {
                    $hostid = (int)$item['hostid'];
                    $items_by_host[$hostid][] = $item;
                }
                
                $remaining -= count($items);
                
            } catch (\Exception $e) {
                error_log("Error fetching items for pattern '$pattern': " . $e->getMessage());
                continue;
            }
        }

        return $items_by_host;
    }

    /**
     * Build result rows from hosts and items
     */
    private function buildResultRows(array $hosts, array $items_by_host): array
    {
        $rows = [];

        foreach ($hosts as $host_id => $host) {
            $host_items = $items_by_host[(int)$host_id] ?? [];
            
            if (empty($host_items)) {
                continue;
            }

            $host_name = !empty($host['name']) ? $host['name'] : $host['host'];

            foreach ($host_items as $item) {
                $rows[] = $this->createRowData($host_name, $item);
            }
        }

        return $rows;
    }

    /**
     * Create row data structure
     *
     * CHANGE: Add 'display' and 'value_percent' fields.
     * - 'display' => 'bar' indicates the front-end should consider a bar layout for the row.
     * - 'value_percent' will be filled by applyBarDisplay() with integer 0..100 for numeric rows.
     */
    private function createRowData(string $host_name, array $item): array
    {
        $last_value = $item['lastvalue'] ?? null;
        $units = $item['units'] ?? '';
        
        // Convert numeric values for sorting
        $sort_num = is_numeric($last_value) ? (float)$last_value : null;

        return [
            'host' => $host_name,
            'item' => $item['name'],
            'units' => $units,
            'lastvalue' => $last_value,
            'lastclock' => isset($item['lastclock']) ? (int)$item['lastclock'] : null,
            'sort_num' => $sort_num,
            // CHANGE: default display type for front-end: 'bar'
            'display' => 'bar',
            // CHANGE: placeholder for normalized percentage (computed later)
            'value_percent' => null
        ];
    }

    /**
     * Sort rows by last value with stable tie-breaking
     */
    private function sortRows(array &$rows, string $sortorder): void
    {
        $direction = ($sortorder === 'ASC') ? 1 : -1;

        usort($rows, function ($a, $b) use ($direction) {
            $a_num = $a['sort_num'];
            $b_num = $b['sort_num'];

            // Both null - use tie breakers
            if ($a_num === null && $b_num === null) {
                return $this->compareByTieBreakers($a, $b, $direction);
            }

            // Null values always go last regardless of sort direction
            if ($a_num === null) return 1;
            if ($b_num === null) return -1;

            // Both numeric - compare values
            if ($a_num == $b_num) {
                return $this->compareByTieBreakers($a, $b, $direction);
            }

            return $direction * ($a_num <=> $b_num);
        });
    }

    /**
     * Compare rows by tie-breaker fields (host, then item name)
     */
    private function compareByTieBreakers(array $a, array $b, int $direction): int
    {
        // First tie-breaker: host name
        $host_comparison = strnatcasecmp((string)$a['host'], (string)$b['host']);
        if ($host_comparison !== 0) {
            return $direction * $host_comparison;
        }

        // Second tie-breaker: item name
        return $direction * strnatcasecmp((string)$a['item'], (string)$b['item']);
    }

    /**
     * CHANGE: New helper that annotates rows so they can be rendered as bars in the front-end.
     *
     * Steps:
     * - Find the numeric min and max among rows with numeric 'sort_num'.
     * - For each row with numeric 'sort_num', compute a 0..100 percentage (value_percent).
     *   If all numeric values are equal, set 100% so bars are visible.
     * - Non-numeric rows keep value_percent = null (front-end can display a placeholder).
     *
     * This function mutates $rows in-place.
     */
    private function applyBarDisplay(array &$rows): void
    {
        $min = null;
        $max = null;

        // Determine min and max numeric values across all rows
        foreach ($rows as $r) {
            if (!isset($r['sort_num']) || $r['sort_num'] === null) {
                continue;
            }
            $val = (float)$r['sort_num'];
            if ($min === null || $val < $min) {
                $min = $val;
            }
            if ($max === null || $val > $max) {
                $max = $val;
            }
        }

        // Compute normalized percentage for each row
        foreach ($rows as &$r) {
            // Ensure we keep display flag (already set in createRowData). Reinforce it here.
            $r['display'] = 'bar';

            if (!isset($r['sort_num']) || $r['sort_num'] === null) {
                // Non-numeric values: leave percent null so front-end can treat specially
                $r['value_percent'] = null;
                continue;
            }

            $value = (float)$r['sort_num'];

            if ($min === null || $max === null) {
                // Shouldn't happen: no numeric rows found. Set null for safety.
                $r['value_percent'] = null;
                continue;
            }

            if ($max === $min) {
                // All numeric values identical -> show full bars (100%) for visibility.
                $percent = 100;
            } else {
                // Normalize value to 0..100 range
                $percent = (($value - $min) / ($max - $min)) * 100;
            }

            // Clamp and cast to integer percentage for simpler rendering
            $percent = max(0, min(100, (int)round($percent)));
            $r['value_percent'] = $percent;
        }
        unset($r);
    }
}