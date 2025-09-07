<?php declare(strict_types=1);

namespace Modules\BasicWidget\Includes;

use Zabbix\Widgets\{CWidgetField, CWidgetForm};
use Zabbix\Widgets\Fields\{
    CWidgetFieldTextBox,
    CWidgetFieldMultiSelectGroup,
    CWidgetFieldPatternSelectItem,
    CWidgetFieldNumericBox,
    CWidgetFieldColor,
    CWidgetFieldSelect
};

/**
 * Basic Widget configuration form
 */
class WidgetForm extends CWidgetForm
{
    // Default color palette for widget customization
    public const DEFAULT_COLOR_PALETTE = [
        'E67E22', 'E74C3C', '5FB760', '3498DB', '9B59B6', 
        'F1C40F', '1ABC9C', '2ECC71', 'E67E22', '95A5A6',
        '34495E', '16A085', '27AE60', '2980B9', '8E44AD', 
        'F39C12', 'D35400', 'C0392B', '7F8C8D', '2C3E50'
    ];

    // Configuration constants
    private const DEFAULT_MAX_ITEMS = 100;
    private const DEFAULT_WARN_THRESHOLD = 80;
    private const DEFAULT_CRIT_THRESHOLD = 90;
    private const DEFAULT_SORT_ORDER = 1; // Descending
    private const DEFAULT_WARN_COLOR = 'E67E22';
    private const DEFAULT_CRIT_COLOR = 'E74C3C';

    // Sort order options
    private const SORT_OPTIONS = [
        0 => 'Ascending',
        1 => 'Descending'
    ];

    public function addFields(): self
    {
        return $this
            ->addBasicFields()
            ->addDataFields()
            ->addDisplayFields()
            ->addThresholdFields()
            ->addColorFields();
    }

    /**
     * Add basic configuration fields
     */
    private function addBasicFields(): self
    {
        return $this->addField(
            new CWidgetFieldTextBox('description', _('Description'))
        );
    }

    /**
     * Add data source configuration fields
     */
    private function addDataFields(): self
    {
        return $this
            ->addField(
                (new CWidgetFieldMultiSelectGroup('groupids', _('Host group')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldPatternSelectItem('items', _('Item patterns')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            );
    }

    /**
     * Add display configuration fields
     */
    private function addDisplayFields(): self
    {
        return $this
            ->addField(
                (new CWidgetFieldNumericBox('max_items', _('Max items')))
                    ->setDefault(self::DEFAULT_MAX_ITEMS)
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldSelect(
                    'sortorder',
                    _('Last value order'),
                    [
                        0 => _('Ascending'),
                        1 => _('Descending')
                    ]
                ))->setDefault(self::DEFAULT_SORT_ORDER)
            );
    }

    /**
     * Add threshold configuration fields
     */
    private function addThresholdFields(): self
    {
        return $this
            ->addField(
                (new CWidgetFieldNumericBox('threshold_warn', _('Warning threshold (%)')))
                    ->setDefault(self::DEFAULT_WARN_THRESHOLD)
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldNumericBox('threshold_crit', _('Critical threshold (%)')))
                    ->setDefault(self::DEFAULT_CRIT_THRESHOLD)
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            );
    }

    /**
     * Add color configuration fields
     */
    private function addColorFields(): self
    {
        return $this
            ->addField(
                (new CWidgetFieldColor('color_warn', _('Warning color')))
                    ->setDefault(self::DEFAULT_WARN_COLOR)
            )
            ->addField(
                (new CWidgetFieldColor('color_crit', _('Critical color')))
                    ->setDefault(self::DEFAULT_CRIT_COLOR)
            );
    }
}