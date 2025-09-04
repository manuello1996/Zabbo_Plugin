<?php declare(strict_types = 0);


namespace Modules\BasicWidget\Includes;


use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};

use Zabbix\Widgets\Fields\{
	CWidgetFieldTextBox
};

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				new CWidgetFieldTextBox('description', _('Description'))
			);
	}
}
