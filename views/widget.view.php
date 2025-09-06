<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

// Build table: HOST | Item name | Last value (bar for %)
$table = (new CTableInfo())
    ->setHeader([_('Host'), _('Item'), _('Last value')]);

$hosts = $data['hosts'] ?? [];

// Only show hosts that have matching items.
foreach ($hosts as $host) {
    $items = $host['items'] ?? [];
    if (empty($items)) {
        continue; // skip hosts without matches
    }

    $host_name = $host['name'] ?? ($host['host'] ?? '');

    foreach ($items as $it) {
        $val_raw = $it['lastvalue'] ?? null;
        $units   = $it['units'] ?? '';
        $val_txt = $val_raw;

        $value_cell = null;

        // If units are "%", render a small bar + numeric value.
        if ($units === '%') {
            // normalize to float and clamp to [0,100] to avoid broken bars
            $p = is_numeric($val_raw) ? (float)$val_raw : 0.0;
            if ($p < 0)   $p = 0.0;
            if ($p > 100) $p = 100.0;

            // Tiny inline progress bar using Zabbix HTML wrappers (CTag/CDiv/CSpan).
            // This uses standard HTML + Zabbix's tag helpers; no custom JS needed.
            $bar_outer = (new CDiv())
                ->addClass('progress')                // generic class name; Zabbix keeps it unstyled by default
                ->setAttribute('style', 'width:120px;height:10px;border:1px solid #ccc;border-radius:2px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px;');

            $bar_inner = (new CDiv())
                ->addClass('progress-bar')
                ->setAttribute('style', 'height:100%;width:'.(int)$p.'%;background:#5fb760;'); // green-ish fill

            $bar_outer->addItem($bar_inner);

            // Numeric label next to bar.
            $value_cell = new CSpan(sprintf('%.2f%%', is_numeric($val_raw) ? (float)$val_raw : 0.0));
            $value_cell = [$bar_outer, $value_cell];
        }
        else {
            // Non-% units: just show value + units.
            $value_cell = trim((string)$val_txt.' '.(string)$units);
        }

        $table->addRow([
            $host_name,
            $it['name'] ?? '',
            $value_cell
        ]);
    }
}

// Render inside the widget
(new CWidgetView($data))
    ->addItem($table)
	->addItem(new CTag('pre', true, json_encode($data, JSON_PRETTY_PRINT)))
    ->show();

