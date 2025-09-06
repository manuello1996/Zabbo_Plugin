<?php declare(strict_types = 0);

namespace Modules\BasicWidget\Includes;

use Zabbix\Widgets\{ CWidgetField, CWidgetForm };
use Zabbix\Widgets\Fields\{
    CWidgetFieldTextBox,
    CWidgetFieldMultiSelectGroup,
    CWidgetFieldPatternSelectItem,
    CWidgetFieldNumericBox,
    CWidgetFieldColor
};

class WidgetForm extends CWidgetForm {
    // Default palette for the picker (you can tweak these).
    public const DEFAULT_COLOR_PALETTE = [
        'E67E22','E74C3C','5FB760','3498DB','9B59B6','F1C40F','1ABC9C','2ECC71','E67E22','95A5A6',
        '34495E','16A085','27AE60','2980B9','8E44AD','F39C12','D35400','C0392B','7F8C8D','2C3E50'
    ];

    public function addFields(): self {
        return $this
            ->addField(new CWidgetFieldTextBox('description', _('Description')))
            ->addField(new CWidgetFieldMultiSelectGroup('groupids', _('Host group')))
            ->addField(
                (new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField((new CWidgetFieldNumericBox('threshold_warn', _('Warning threshold (%)')))->setDefault(80))
            ->addField((new CWidgetFieldNumericBox('threshold_crit', _('Critical threshold (%)')))->setDefault(90))
            // IMPORTANT: defaults are 6-hex w/o '#'
            ->addField((new CWidgetFieldColor('color_warn', _('Warning color')))->setDefault('E67E22'))
            ->addField((new CWidgetFieldColor('color_crit', _('Critical color')))->setDefault('E74C3C'));
    }
}
