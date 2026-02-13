<?php

declare(strict_types=1);

// Good: 3 parameters as documented.
function my_module_form_alter(array &$form, $form_state, $form_id)
{
    // ...
}

// Good: hook_entity_presave with exactly one parameter.
function my_module_entity_presave($entity)
{
    // ...
}

// Good: hook_preprocess_HOOK with one parameter.
function my_module_preprocess_node(array &$variables)
{
    // ...
}

// Good: hook_node_access with the documented three parameters.
function my_module_node_access($node, $op, $account)
{
    // ...
}

// Not a hook — should be ignored entirely.
function helper_function_doing_random_things($a, $b, $c, $d, $e)
{
    return $a;
}
