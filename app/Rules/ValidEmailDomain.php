<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidEmailDomain implements Rule
{
    public function passes($attribute, $value)
    {
        $domain = substr(strrchr($value, "@"), 1);
        return checkdnsrr($domain, 'MX');
    }

    public function message()
    {
        return 'El dominio del correo electrónico no es válido.';
    }
}
    