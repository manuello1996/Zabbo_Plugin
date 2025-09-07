<?php
// Evita "Undefined constant" fatale: usa fallback se la costante non è assente.
$zbx_color_picker_class = defined('ZBX_STYLE_COLOR_PICKER') ? ZBX_STYLE_COLOR_PICKER : 'zbx_colorpicker';
?>
// JavaScript per l'inizializzazione del form di modifica del widget.
// NOTA: non inserire tag <script> qui — il file viene incluso dall'infrastruttura Zabbix.
(function() {
    // BasicWidget form helper (parziale)
    const basic_widget_form = {
        /**
         * Validate a color palette array (6-hex values).
         * @returns {Array} - Validated palette array
         */
        validateColorPalette(palette) {
            const hexColorRegex = /^[0-9A-Fa-f]{6}$/;
            const defaultColor = 'CCCCCC';
            
            return palette.map(color => {
                const cleanColor = String(color).replace('#', '').toUpperCase();
                return hexColorRegex.test(cleanColor) ? cleanColor : defaultColor;
            }).filter((color, index, arr) => arr.indexOf(color) === index); // Remove duplicates
        },

        /**
         * Initialize color picker inputs
         * CHANGE: Use a fallback class name if ZBX_STYLE_COLOR_PICKER constant is not defined.
         */
        initializeColorPickers() {
            try {
                // Use the guarded PHP variable to select color picker inputs.
                // This avoids "Undefined constant" PHP fatal errors in environments where
                // ZBX_STYLE_COLOR_PICKER is not defined.
                const colorInputs = document.querySelectorAll('.<?= $zbx_color_picker_class ?> input[type="text"]');
                
                if (colorInputs.length === 0) {
                    console.log('BasicWidget: No color picker inputs found');
                    return;
                }

                // Cleanup any previous pickers (if applicable)
                this.cleanupColorPickers();

                // Initialize pickers for each input (pseudo-code; adattare alla libreria effettiva)
                colorInputs.forEach(input => {
                    try {
                        // Esempio: inizializzazione se si usa una libreria jQuery/plugin
                        // $(input).colorpicker(...);
                    } catch (e) {
                        console.warn('BasicWidget: Failed to initialize color picker for input', input, e);
                    }
                });

            } catch (err) {
                console.error('BasicWidget: initializeColorPickers error', err);
            }
        },

        cleanupColorPickers() {
            // Optional: remove/distruggi eventuali color picker precedenti
            // Implementazione dipende dalla libreria usata (se presente).
        },

        // Altri helper eventualmente necessari...
    };

    // Esponi l'oggetto per l'inizializzazione del form
    window.basic_widget_form = basic_widget_form;
})();