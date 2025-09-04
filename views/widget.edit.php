<?php declare(strict_types = 0);

/**
 *
 * @var CView $this
 * @var array $data
 */

use Modules\BasicWidget\Includes\WidgetForm;

$form = new CWidgetFormView($data);

$form->addField(
				new CWidgetFieldTextBoxView($data['fields']['description'])
			)
	->show();
