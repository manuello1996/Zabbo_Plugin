<?php
       
       /**
        * Gauge chart widget form view.
        *
        * @var CView $this
        * @var array $data
        */
       
       (new CWidgetFormView($data))
           ->addField(
               new CWidgetFieldTextBoxView($data['fields']['description'])
           )
           ->show();