<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Règle de validation personnalisée pour les ObjectIDs MongoDB.
 *
 * Elle garantit qu'une valeur donnée correspond au format hexadécimal de 24 caractères utilisé par MongoDB.
 */
class MongoObjectId implements ValidationRule
{
    /**
     * Exécuter la règle de validation.
     *
     * @param  string  $attribute  Le nom de l'attribut en cours de validation.
     * @param  mixed  $value  La valeur de l'attribut.
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Les ObjectIDs MongoDB sont des valeurs binaires de 12 octets, représentées par des chaînes hexadécimales de 24 caractères.
        if (! is_string($value) || ! preg_match('/^[a-f\d]{24}$/i', $value)) {
            $fail("Le champ {$attribute} doit être un identifiant MongoDB valide.");
        }
    }
}
