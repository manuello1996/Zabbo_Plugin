<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

// Build table: HOST | Item | Last value
$table = (new CTableInfo())
    ->setHeader([_('Host'), _('Item'), _('Last value')]);

$hosts = $data['hosts'] ?? [];

// ---- Read settings (with safe defaults) ----
$warn = isset($data['fields_values']['threshold_warn']) && is_numeric($data['fields_values']['threshold_warn'])
    ? (float)$data['fields_values']['threshold_warn'] : 80.0;

$crit = isset($data['fields_values']['threshold_crit']) && is_numeric($data['fields_values']['threshold_crit'])
    ? (float)$data['fields_values']['threshold_crit'] : 90.0;

// Colors are saved as 6-hex (no '#') by CWidgetFieldColor.
$color_warn_hex = strtoupper(trim((string)($data['fields_values']['color_warn'] ?? ''))) ?: 'E67E22'; // orange
$color_crit_hex = strtoupper(trim((string)($data['fields_values']['color_crit'] ?? ''))) ?: 'E74C3C'; // red
$color_ok_hex   = '5FB760'; // green for "OK" (fixed default)

// Normalize thresholds to 0..100 and ensure $warn <= $crit.
$warn = max(0.0, min(100.0, $warn));
$crit = max(0.0, min(100.0, $crit));
if ($warn > $crit) {
    [$warn, $crit] = [$crit, $warn];
}

// ---- Table rows ----
foreach ($hosts as $host) {
    $items = $host['items'] ?? [];
    if (empty($items)) {
        // Skip hosts without matching items
        continue;
    }

    $host_name = $host['name'] ?? ($host['host'] ?? '');

    foreach ($items as $it) {
        $val_raw = $it['lastvalue'] ?? null;
        $units   = $it['units'] ?? '';

        // If units are %, show progress bar with thresholds; else plain value.
        if ($units === '%') {
            $p = is_numeric($val_raw) ? (float)$val_raw : 0.0;
            // Clamp for bar display
            if ($p < 0)   $p = 0.0;
            if ($p > 100) $p = 100.0;

            // Choose bar color by thresholds
            if ($p > $crit) {
                $bar_color_hex = $color_crit_hex;
            }
            elseif ($p > $warn) {
                $bar_color_hex = $color_warn_hex;
            }
            else {
                $bar_color_hex = $color_ok_hex;
            }

            // Bar container
            $bar_outer = (new CDiv())
                ->setAttribute('style',
                    'width:120px;height:10px;border:1px solid #ccc;border-radius:2px;'.
                    'overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px;'
                );

            // Bar fill
            $bar_inner = (new CDiv())
                ->setAttribute('style',
                    'height:100%;width:'.(int)$p.'%;background:#'.$bar_color_hex.';'
                );

            $bar_outer->addItem($bar_inner);

            $value_cell = [
                $bar_outer,
                new CSpan(sprintf('%.2f%%', is_numeric($val_raw) ? (float)$val_raw : 0.0))
            ];
        }
        else {
            // Non-%: just show value + units
            $txt = (string)$val_raw;
            if ($units !== '') {
                $txt .= ' '.$units;
            }
            $value_cell = $txt;
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
