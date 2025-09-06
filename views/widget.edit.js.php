<?php declare(strict_types = 0);

use Modules\BasicWidget\Includes\WidgetForm; ?>

window.basic_widget_form = new class {
  init({color_palette}) {
    // Set palette used by the built-in color picker
    colorPalette.setThemeColors(color_palette);

    // Activate the color pickers for all color fields in this form
    for (const input of jQuery('.<?= ZBX_STYLE_COLOR_PICKER ?> input')) {
      jQuery(input).colorpicker();
    }

    // Hide pickers when the widget dialog reloads/closes (prevents stuck overlays)
    const overlay = overlays_stack.getById('widget_properties');
    for (const ev of ['overlay.reload', 'overlay.close']) {
      overlay.$dialogue[0].addEventListener(ev, () => { jQuery.colorpicker('hide'); });
    }
  }
};
