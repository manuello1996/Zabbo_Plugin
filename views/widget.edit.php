<?php declare(strict_types=1); // FIXED: Aggiunto strict typing mancante

/**
 * Basic Widget Configuration Form View
 * 
 * CHANGES MADE:
 * 1. ADDED: strict_types declaration
 * 2. IMPROVED: Organized field rendering with logical grouping
 * 3. IMPROVED: Enhanced error handling and validation
 * 4. ADDED: Configuration validation
 * 5. IMPROVED: Better structure and documentation
 * 6. FIXED: Consistent field ordering matching form definition
 * 
 * @var CView $this
 * @var array $data
 */

use Modules\BasicWidget\Includes\WidgetForm;

// ADDED: Validate required data structure
if (!isset($data['fields']) || !is_array($data['fields'])) {
    throw new InvalidArgumentException('Widget form data is missing or invalid');
}

try {
    // IMPROVED: Initialize form with error handling
    $form = new CWidgetFormView($data);

    // IMPROVED: Group fields logically for better UX
    
    // Basic Configuration Group
    $form->addField(new CWidgetFieldTextBoxView($data['fields']['description']));

    // Data Source Configuration Group  
    $form->addField(new CWidgetFieldMultiSelectGroupView($data['fields']['groupids']));
    $form->addField(new CWidgetFieldPatternSelectItemView($data['fields']['items']));

    // Display Configuration Group
    $form->addField(new CWidgetFieldNumericBoxView($data['fields']['max_items']));
    $form->addField(new CWidgetFieldSelectView($data['fields']['sortorder']));

    // Threshold Configuration Group
    // IMPROVED: Logical pairing of threshold and color fields
    $form->addField(new CWidgetFieldNumericBoxView($data['fields']['threshold_warn']));
    $form->addField(new CWidgetFieldColorView($data['fields']['color_warn']));
    
    $form->addField(new CWidgetFieldNumericBoxView($data['fields']['threshold_crit']));
    $form->addField(new CWidgetFieldColorView($data['fields']['color_crit']));

    // IMPROVED: Enhanced JavaScript initialization with validation
    $form->includeJsFile('widget.edit.js.php');
    
    // ADDED: Validate color palette before passing to JavaScript
    $colorPalette = WidgetForm::DEFAULT_COLOR_PALETTE;
    if (!is_array($colorPalette) || empty($colorPalette)) {
        // ADDED: Fallback palette se quella di default è corrotta
        $colorPalette = [
            'E67E22', 'E74C3C', '5FB760', '3498DB', '9B59B6', 
            'F1C40F', '1ABC9C', '2ECC71', '95A5A6', '34495E'
        ];
        error_log('BasicWidget: Using fallback color palette due to invalid default');
    }

    // IMPROVED: Safer JavaScript configuration generation
    $jsConfig = [
        'color_palette' => array_values($colorPalette) // Ensure indexed array
    ];

    // ADDED: Validate JSON encoding before output
    $jsonConfig = json_encode($jsConfig, JSON_UNESCAPED_SLASHES);
    if ($jsonConfig === false) {
        throw new RuntimeException('Failed to encode JavaScript configuration: ' . json_last_error_msg());
    }

    // IMPROVED: More robust JavaScript initialization
    $form->addJavaScript('
        try {
            if (typeof basic_widget_form !== "undefined") {
                const initResult = basic_widget_form.init(' . $jsonConfig . ');
                if (!initResult) {
                    console.error("BasicWidget: Form initialization failed");
                }
            } else {
                console.error("BasicWidget: basic_widget_form object not found");
            }
        } catch (error) {
            console.error("BasicWidget: JavaScript initialization error:", error);
        }
    ');

    // ADDED: Final validation before rendering
    if (!$form instanceof CWidgetFormView) {
        throw new RuntimeException('Form object is not properly initialized');
    }

    // Render the complete form
    $form->show();

} catch (Exception $e) {
    // ADDED: Comprehensive error handling
    error_log('BasicWidget Edit Form Error: ' . $e->getMessage());
    
    // ADDED: User-friendly error display
    echo (new CDiv([
        new CTag('h4', true, _('Widget Configuration Error')),
        new CTag('p', true, _('Unable to load widget configuration form. Please check the system logs for details.')),
        new CTag('pre', true, 'Error: ' . htmlspecialchars($e->getMessage()))
    ]))->addClass('msg-bad')->toString();
}