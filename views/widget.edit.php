<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

// No "use Zabbix\Widgets\Fields\CWidgetField*View" — view classes are global.

$form = new CWidgetFormView($data);

$form
    ->addField(new CWidgetFieldTextBoxView($data['fields']['description']))
    ->addField(new CWidgetFieldMultiSelectGroupView($data['fields']['groupids']))
    ->addField(new CWidgetFieldPatternSelectItemView($data['fields']['items']))
    ->show();
