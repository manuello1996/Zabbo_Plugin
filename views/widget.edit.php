<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

use Modules\BasicWidget\Includes\WidgetForm;

$form = new CWidgetFormView($data);

$form
    ->addField(new CWidgetFieldTextBoxView($data['fields']['description']))
    ->addField(new CWidgetFieldMultiSelectGroupView($data['fields']['groupids']))
    ->addField(new CWidgetFieldPatternSelectItemView($data['fields']['items']))

    ->addField(new CWidgetFieldNumericBoxView($data['fields']['threshold_warn']))
    ->addField(new CWidgetFieldColorView($data['fields']['color_warn']))

    ->addField(new CWidgetFieldNumericBoxView($data['fields']['threshold_crit']))
    ->addField(new CWidgetFieldColorView($data['fields']['color_crit']))

    // Load the small JS helper to activate the color pickers and set the palette
    ->includeJsFile('widget.edit.js.php')
    ->addJavaScript('basic_widget_form.init('.json_encode([
        'color_palette' => WidgetForm::DEFAULT_COLOR_PALETTE
    ]).');')
    ->show();
