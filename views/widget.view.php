<?php
       
       /**
        * Gauge chart widget view.
        *
        * @var CView $this
        * @var array $data
        */
       
       (new CWidgetView($data))
           ->addItem(
               new CTag('h1', true, json_encode($data))
            )
           ->show();