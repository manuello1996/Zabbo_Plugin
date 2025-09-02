<?php

        namespace Modules\TopPisk\Includes;

        use Zabbix\Widgets\CWidgetForm;
       
        use Zabbix\Widgets\Fields\CWidgetFieldTextBox;
       
        class WidgetForm extends CWidgetForm {
       
           public function addFields(): self {
               return $this
                   ->addField(
                      new CWidgetFieldTextBox('description', _('Description'))
                   );
          }
        }