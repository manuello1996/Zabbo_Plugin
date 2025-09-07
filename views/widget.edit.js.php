<?php declare(strict_types=1); // FIXED: Era =0, corretto a =1 per strict typing

use Modules\BasicWidget\Includes\WidgetForm; ?>

/**
 * Basic Widget Form JavaScript Handler
 * Manages color pickers and form interactions for the widget configuration dialog
 * 
 * CHANGES MADE:
 * 1. FIXED: Aggiunto strict_types=1
 * 2. IMPROVED: Aggiunta gestione errori e validazione
 * 3. IMPROVED: Separazione delle responsabilità in metodi dedicati
 * 4. IMPROVED: Documentazione completa
 * 5. IMPROVED: Gestione memoria e cleanup
 * 6. FIXED: Gestione più robusta degli eventi overlay
 */

window.basic_widget_form = new class {
    
    constructor() {
        // ADDED: Proprietà per tracking stato inizializzazione
        this.initialized = false;
        this.colorPickersActive = [];
        this.overlayEventsBound = false;
    }

    /**
     * Initialize the widget form with color picker functionality
     * IMPROVED: Aggiunta validazione parametri e gestione errori
     * 
     * @param {Object} config - Configuration object
     * @param {Array} config.color_palette - Array of hex colors for the palette
     */
    init(config) {
        try {
            // ADDED: Validazione parametri di input
            if (!config || !Array.isArray(config.color_palette)) {
                console.error('BasicWidget: Invalid configuration provided to init()');
                return false;
            }

            // ADDED: Prevenzione doppia inizializzazione
            if (this.initialized) {
                console.warn('BasicWidget: Form already initialized, skipping...');
                return true;
            }

            // IMPROVED: Separazione logica inizializzazione
            this.initializeColorPalette(config.color_palette);
            this.initializeColorPickers();
            this.bindOverlayEvents();

            // ADDED: Marcatura come inizializzato
            this.initialized = true;
            
            console.log('BasicWidget: Form initialized successfully');
            return true;

        } catch (error) {
            // ADDED: Gestione errori completa
            console.error('BasicWidget: Initialization failed:', error);
            return false;
        }
    }

    /**
     * Initialize the color palette for color pickers
     * ADDED: Metodo separato per gestione palette
     * 
     * @param {Array} palette - Array of hex color codes
     */
    initializeColorPalette(palette) {
        try {
            // ADDED: Validazione palette prima dell'uso
            const validPalette = this.validateColorPalette(palette);
            
            if (typeof colorPalette !== 'undefined' && colorPalette.setThemeColors) {
                colorPalette.setThemeColors(validPalette);
            } else {
                console.warn('BasicWidget: colorPalette object not available');
            }
        } catch (error) {
            console.error('BasicWidget: Failed to set color palette:', error);
        }
    }

    /**
     * Validate and sanitize color palette
     * ADDED: Validazione palette colori
     * 
     * @param {Array} palette - Raw palette array
     * @returns {Array} - Validated palette array
     */
    validateColorPalette(palette) {
        const hexColorRegex = /^[0-9A-Fa-f]{6}$/;
        const defaultColor = 'CCCCCC';
        
        return palette.map(color => {
            const cleanColor = String(color).replace('#', '').toUpperCase();
            return hexColorRegex.test(cleanColor) ? cleanColor : defaultColor;
        }).filter((color, index, arr) => arr.indexOf(color) === index); // Remove duplicates
    }

    /**
     * Initialize color picker inputs
     * IMPROVED: Gestione più robusta dei color picker
     */
    initializeColorPickers() {
        try {
            // IMPROVED: Selezione più specifica degli input color picker
            const colorInputs = document.querySelectorAll('.<?= ZBX_STYLE_COLOR_PICKER ?> input[type="text"]');
            
            if (colorInputs.length === 0) {
                console.log('BasicWidget: No color picker inputs found');
                return;
            }

            // ADDED: Cleanup eventuali picker precedenti
            this.cleanupColorPickers();

            // IMPROVED: Inizializzazione con gestione errori per singolo picker
            colorInputs.forEach((input, index) => {
                try {
                    if (jQuery && jQuery.fn.colorpicker) {
                        const $input = jQuery(input);
                        $input.colorpicker({
                            // ADDED: Opzioni personalizzate per migliore UX
                            showInput: true,
                            allowEmpty: false,
                            showInitial: true,
                            preferredFormat: "hex6",
                            showPalette: true,
                            hideAfterPaletteSelect: true
                        });
                        
                        // ADDED: Tracking dei picker attivi per cleanup
                        this.colorPickersActive.push($input);
                        
                    } else {
                        console.error('BasicWidget: jQuery colorpicker plugin not available');
                    }
                } catch (error) {
                    console.error(`BasicWidget: Failed to initialize color picker ${index}:`, error);
                }
            });

            console.log(`BasicWidget: Initialized ${this.colorPickersActive.length} color pickers`);

        } catch (error) {
            console.error('BasicWidget: Failed to initialize color pickers:', error);
        }
    }

    /**
     * Bind overlay events for cleanup
     * IMPROVED: Gestione eventi overlay più robusta
     */
    bindOverlayEvents() {
        try {
            // ADDED: Prevenzione binding multipli
            if (this.overlayEventsBound) {
                return;
            }

            // IMPROVED: Controllo esistenza overlays_stack
            if (typeof overlays_stack === 'undefined') {
                console.warn('BasicWidget: overlays_stack not available');
                return;
            }

            const overlay = overlays_stack.getById('widget_properties');
            
            if (!overlay || !overlay.$dialogue || !overlay.$dialogue[0]) {
                console.warn('BasicWidget: Widget properties overlay not found');
                return;
            }

            // IMPROVED: Gestione eventi con cleanup automatico
            const events = ['overlay.reload', 'overlay.close'];
            const cleanupHandler = () => this.cleanup();

            events.forEach(eventName => {
                overlay.$dialogue[0].addEventListener(eventName, cleanupHandler);
            });

            this.overlayEventsBound = true;
            console.log('BasicWidget: Overlay events bound successfully');

        } catch (error) {
            console.error('BasicWidget: Failed to bind overlay events:', error);
        }
    }

    /**
     * Cleanup color pickers and reset state
     * ADDED: Metodo dedicato per cleanup risorse
     */
    cleanup() {
        try {
            // ADDED: Cleanup color picker attivi
            this.cleanupColorPickers();
            
            // ADDED: Reset stato
            this.initialized = false;
            this.overlayEventsBound = false;
            
            console.log('BasicWidget: Cleanup completed');

        } catch (error) {
            console.error('BasicWidget: Cleanup failed:', error);
        }
    }

    /**
     * Clean up active color pickers
     * ADDED: Gestione cleanup color picker per evitare memory leaks
     */
    cleanupColorPickers() {
        try {
            if (jQuery && jQuery.colorpicker) {
                // ADDED: Nasconde tutti i picker aperti
                jQuery.colorpicker('hide');
                
                // ADDED: Distrugge i picker attivi
                this.colorPickersActive.forEach($picker => {
                    try {
                        if ($picker.data('colorpicker')) {
                            $picker.colorpicker('destroy');
                        }
                    } catch (error) {
                        console.warn('BasicWidget: Failed to destroy color picker:', error);
                    }
                });
            }

            // ADDED: Reset array picker attivi
            this.colorPickersActive = [];

        } catch (error) {
            console.error('BasicWidget: Failed to cleanup color pickers:', error);
        }
    }

    /**
     * Get current form state for debugging
     * ADDED: Metodo per debugging stato form
     */
    getState() {
        return {
            initialized: this.initialized,
            activeColorPickers: this.colorPickersActive.length,
            overlayEventsBound: this.overlayEventsBound
        };
    }
};