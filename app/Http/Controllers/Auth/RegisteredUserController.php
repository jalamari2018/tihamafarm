<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DigitNormalizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isEmailOrSaudiPhone = function (string $value): bool {
            $normalizedPhone = DigitNormalizer::normalizeSaudiPhone($value);

            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                || preg_match('/^05\d{8}$/', $normalizedPhone) === 1;
        };

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'max:255',
                'unique:'.User::class,
                function ($attribute, $value, $fail) use ($isEmailOrSaudiPhone) {
                    if (! is_string($value) || ! $isEmailOrSaudiPhone($value)) {
                        $fail('حقل البريد الإلكتروني أو رقم الجوال يجب أن يكون بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد الإلكتروني أو رقم الجوال مطلوب.',
            'email.unique' => 'البريد الإلكتروني أو رقم الجوال مستخدم مسبقًا.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'terms.accepted' => 'يجب الموافقة على الشروط والأحكام للمتابعة.',
        ]);

        $emailOrPhone = (string) $request->input('email');
        $normalizedPhone = DigitNormalizer::normalizeSaudiPhone($emailOrPhone);
        $identifier = preg_match('/^05\d{8}$/', $normalizedPhone)
            ? $normalizedPhone
            : strtolower($emailOrPhone);

        $user = User::create([
            'name' => $request->name,
            'email' => $identifier,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
