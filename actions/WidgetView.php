<?php declare(strict_types = 0);

namespace Modules\BasicWidget\Actions;

use API,
	CControllerDashboardWidgetView,
	CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		
		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getName()),
			'fields_values' => $this->fields_values,
			'test' => "test",
			'random' => 12345,
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
