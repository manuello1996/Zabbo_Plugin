<?php declare(strict_types = 0);

/**
 *
 * @var CView $this
 * @var array $data
 */

(new CWidgetView($data))
	->addItem(new CTag('pre', true, json_encode($data, JSON_PRETTY_PRINT)))
	->show();
