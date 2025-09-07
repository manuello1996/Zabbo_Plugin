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

    if ($units === '%') {
        $p = is_numeric($val) ? (float)$val : 0.0;
        $p = max(0.0, min(100.0, $p));

        if      ($p > $crit) $bar_hex = $color_crit_hex;
        elseif  ($p > $warn) $bar_hex = $color_warn_hex;
        else                 $bar_hex = $color_ok_hex;

        $bar_outer = (new CDiv())
            ->setAttribute(
                'style',
                'width:120px;height:10px;border:1px solid #ccc;border-radius:2px;'.
                'overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px;'
            );

        $bar_inner = (new CDiv())
            ->setAttribute('style', 'height:100%;width:'.(int)$p.'%;background:#'.$bar_hex.';');

        $bar_outer->addItem($bar_inner);

        $value_cell = [
            $bar_outer,
            new CSpan(sprintf('%.2f%%', is_numeric($val) ? (float)$val : 0.0))
        ];
    }
    else {
        $value_cell = (string)$val . ($units !== '' ? ' '.$units : '');
    }

    $table->addRow([$r['host'], $r['item'], $value_cell]);
}

(new CWidgetView($data))
    ->addItem($table)
    ->addItem(new CTag('pre', true, json_encode($data, JSON_PRETTY_PRINT)))  
    ->show();