<?php declare(strict_types = 0);

namespace Modules\BasicWidget\Includes;

use Zabbix\Widgets\{
    CWidgetField,
    CWidgetForm
};

use Zabbix\Widgets\Fields\{
    CWidgetFieldTextBox,
    CWidgetFieldMultiSelectGroup,
    CWidgetFieldPatternSelectItem
};

class WidgetForm extends CWidgetForm {
    public function addFields(): self {
        return $this
            ->addField(new CWidgetFieldTextBox('description', _('Description')))
            ->addField(
                (new CWidgetFieldMultiSelectGroup('groupids', _('Host group')))
            )
            ->addField(
                (new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            );
    }
}
