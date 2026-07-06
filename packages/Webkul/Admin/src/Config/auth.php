<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Password Policy
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the minimum length enforced when an admin
    | SETS a new password (user create/edit, account change, password reset).
    | Server rules and the client-side VeeValidate rules both read this value,
    | so the policy can never drift between the two again.
    |
    | Note: this is NOT applied to the login form or to `current_password`
    | verification — an existing admin with a shorter password must still be
    | able to authenticate. Raising this only affects newly chosen passwords.
    |
    */
    'password_min' => 8,
];
