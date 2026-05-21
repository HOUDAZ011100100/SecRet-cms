<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /**
     * Récupérer l'utilisateur authentifié avec un type concret pour les contrôleurs et services.
     */
    protected function actor(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    protected function actorId(Request $request): string
    {
        return $this->userId($this->actor($request));
    }

    protected function userId(User $user): string
    {
        $id = $user->getKey();
        if (! is_int($id) && ! is_string($id)) {
            abort(401);
        }

        return (string) $id;
    }

    protected function validatedNullableString(FormRequest $request, string $key): ?string
    {
        $value = $request->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function validatedString(FormRequest $request, string $key): string
    {
        $value = $request->validated($key);
        if (! is_string($value)) {
            abort(422);
        }

        return $value;
    }

    protected function validatedInt(FormRequest $request, string $key): int
    {
        $value = $request->validated($key);
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        abort(422);
    }
}
