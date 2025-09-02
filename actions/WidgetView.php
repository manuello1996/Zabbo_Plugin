<?php
       
       namespace Modules\TopPisk\Actions;
       
       use CControllerDashboardWidgetView,
           CControllerResponseData;
       
       class WidgetView extends CControllerDashboardWidgetView {
       
            protected function doAction(): void {
                $this->setResponse(new CControllerResponseData([
                    'name' => $this->getInput('name', $this->widget->getName()),
                    'description' => $this->fields_values['description'],
                    'test' => "test",
                    'user' => [
                        'debug_mode' => $this->getDebugMode()
                    ]
                ]));
            }
    }