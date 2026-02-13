<?php

declare(strict_types=1);

// Bad: hook_form_alter must take 3 parameters, this implementation has 2.
function my_module_form_alter(array &$form, $form_state)
{
    // ...
}

// Bad: hook_entity_presave takes exactly 1 parameter, this has 2.
function my_module_entity_presave($entity, $extra)
{
    // ...
}

// Bad: hook_theme expects 4 parameters, this has 0.
function my_module_theme()
{
    return [];
}
