<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$rows = $data['rows'] ?? [];

// Thresholds & colors (colors are 6-hex without '#')
$warn = isset($data['fields_values']['threshold_warn']) && is_numeric($data['fields_values']['threshold_warn'])
    ? (float)$data['fields_values']['threshold_warn'] : 80.0;
$crit = isset($data['fields_values']['threshold_crit']) && is_numeric($data['fields_values']['threshold_crit'])
    ? (float)$data['fields_values']['threshold_crit'] : 90.0;

$warn = max(0.0, min(100.0, $warn));
$crit = max(0.0, min(100.0, $crit));
if ($warn > $crit) { [$warn, $crit] = [$crit, $warn]; }

$color_warn_hex = strtoupper(trim((string)($data['fields_values']['color_warn'] ?? ''))) ?: 'E67E22';
$color_crit_hex = strtoupper(trim((string)($data['fields_values']['color_crit'] ?? ''))) ?: 'E74C3C';
$color_ok_hex   = '5FB760';

// Indicate current order in the header (0 = ASC, 1 = DESC)
$sort_flag = isset($data['fields_values']['sortorder']) ? (int)$data['fields_values']['sortorder'] : 1;
$arrow = ($sort_flag === 0) ? ' ▲' : ' ▼';

$table = (new CTableInfo())
    ->setHeader([_('Host'), _('Item'), _('Last value').$arrow]);

foreach ($rows as $r) {
    $units = $r['units'] ?? '';
    $val   = $r['lastvalue'];

    // CHANGE: Prefer server-calculated normalized percentage (value_percent) for bar width.
    // If value_percent is present and numeric, render a bar using that width.
    // This allows us to show bars for all numeric items consistently.
    $p = null;
    if (isset($r['value_percent']) && is_numeric($r['value_percent'])) {
        $p = (int)$r['value_percent'];
    } else {
        // Fallback: if units are '%' and lastvalue numeric, use that percentage.
        if ($units === '%' && is_numeric($val)) {
            $tmp = (float)$val;
            $p = max(0, min(100, (int)round($tmp)));
        }
        // Otherwise leave $p as null; no safe global normalization available on front-end.
    }

    if ($p !== null) {
        // Determine bar color based on thresholds (thresholds are percentage-based).
        if ($p > $crit) $bar_hex = $color_crit_hex;
        elseif ($p > $warn) $bar_hex = $color_warn_hex;
        else $bar_hex = $color_ok_hex;

        $bar_outer = (new CDiv())
            ->setAttribute(
                'style',
                'width:120px;height:10px;border:1px solid #ccc;border-radius:2px;'.
                'overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px;'
            );

        // CHANGE: use server normalized width ($p) to draw bar_inner width
        $bar_inner = (new CDiv())
            ->setAttribute('style', 'height:100%;width:'.(int)$p.'%;background:#'.$bar_hex.';');

        $bar_outer->addItem($bar_inner);

        // Format the numeric display: if units == '%' show percent with 2 decimals,
        // otherwise show numeric value + units.
        if ($units === '%') {
            $value_cell = [
                $bar_outer,
                new CSpan(sprintf('%.2f%%', is_numeric($val) ? (float)$val : 0.0))
            ];
        } else {
            // Show raw value with units (if present) — keep original numeric precision
            $value_text = is_numeric($val) ? (string)$val : (string)$val;
            $value_text .= ($units !== '' ? ' '.$units : '');
            $value_cell = [$bar_outer, new CSpan($value_text)];
        }
    }
    else {
        // Non-numeric or no percentage available -> plain display (unchanged)
        $value_cell = (string)$val . ($units !== '' ? ' '.$units : '');
    }

    $table->addRow([$r['host'], $r['item'], $value_cell]);
}

(new CWidgetView($data))
    ->addItem($table)
    
    // ADDED (unchanged): show debug JSON to inspect row data during review
    // CHANGE: Commented to shut hide debug info
    //->addItem(new CTag('pre', true, json_encode($data, JSON_PRETTY_PRINT)))
    
    ->show();