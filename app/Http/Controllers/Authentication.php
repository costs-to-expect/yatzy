<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Account\DeleteAccount;
use App\Actions\Account\DeleteYatzyAccount;
use App\Api\Service;
use App\Models\PartialRegistration;
use App\Notifications\CreatePassword;
use App\Notifications\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Authentication extends Controller
{
    public function account(Request $request)
    {
        $this->bootstrap($request);

        $user = $this->api->getAuthUser();

        if ($user['status'] !== 200) {
            abort(404, 'Unable to fetch your account from the API');
        }

        $job = $request->query('job');
        if ($job !== null) {
            Auth::guard()->logout();
        }

        return view(
            'account',
            [
                'user' => $user['content'],
                'job' => $job
            ]
        );
    }

    public function confirmDeleteYatzyAccount(Request $request)
    {
        $this->bootstrap($request);

        $user = $this->api->getAuthUser();

        if ($user['status'] !== 200) {
            abort(404, 'Unable to fetch your account from the API');
        }

        return view(
            'confirm-delete-yatzy-account',
            [
                'user' => $user['content']
            ]
        );
    }

    public function confirmDeleteAccount(Request $request)
    {
        $this->bootstrap($request);

        $user = $this->api->getAuthUser();

        if ($user['status'] !== 200) {
            abort(404, 'Unable to fetch your account from the API');
        }

        return view(
            'confirm-delete-account',
            [
                'user' => $user['content']
            ]
        );
    }

    public function createPassword(Request $request)
    {
        $token = null;
        $email = null;

        if (session()->get('authentication.parameters') !== null) {
            $token = session()->get('authentication.parameters')['token'];
            $email = session()->get('authentication.parameters')['email'];
        }

        if ($request->input('token') !== null && $request->input('email') !== null) {
            $token = $request->input('token');
            $email = $request->input('email');
        }

        if ($token === null && $email === null) {
            abort(404, 'Password cannot be created, registration parameters not found');
        }

        return view(
            'create-password',
            [
                'token' => $token,
                'email' => $email,
                'errors' => session()->get('authentication.errors'),
                'failed' => session()->get('authentication.failed'),
            ]
        );
    }

    public function createPasswordProcess(Request $request)
    {
        $api = new Service();

        $response = $api->createPassword(
            $request->only(['token', 'email', 'password', 'password_confirmation'])
        );

        if ($response['status'] === 204) {

            PartialRegistration::query()
                ->where('token', '=', $request->input('token'))
                ->delete();

            Notification::route('mail', $request->input('email'))
                ->notify(new Registered());

            $credentials = $request->only(['email', 'password']);

            if (Auth::attempt($credentials, $request->input('remember_me') !== null)) {
                return redirect()->route('home');
            }

            return redirect()->route('registration-complete');
        }

        if ($response['status'] === 422) {
            return redirect()->route(
                'create-password.view',
                [
                    'email' => $request->input('token'),
                    'token' => $request->input('email')
                ])
                ->withInput()
                ->with('authentication.errors', $response['fields']);
        }

        return redirect()->route(
            'create-password.view',
            [
                'email' => $request->input('token'),
                'token' => $request->input('email')
            ])
            ->with('authentication.failed', $response['content']);
    }

    public function deleteYatzyAccount(Request $request, DeleteYatzyAccount $action): RedirectResponse
    {
        $this->bootstrap($request);

        $user = $this->api->getAuthUser();

        if ($user['status'] !== 200) {
            abort(404, 'Unable to fetch your account from the API');
        }

        $action(
            $request->cookie($this->config['cookie_bearer']),
            $this->resource_type_id,
            $this->resource_id,
            $user['content']['id'],
            $user['content']['email']
        );

        return redirect()->route('account', ['job'=>'delete-yatzy-account']);
    }

    public function deleteAccount(Request $request, DeleteAccount $action): RedirectResponse
    {
        $this->bootstrap($request);

        $user = $this->api->getAuthUser();

        if ($user['status'] !== 200) {
            abort(404, 'Unable to fetch your account from the API');
        }

        $action(
            $request->cookie($this->config['cookie_bearer']),
            $user['content']['id'],
            $user['content']['email']
        );

        return redirect()->route('account', ['job'=>'delete-account']);
    }

    public function register()
    {
        return view(
            'register',
            [
                'errors' => session()->get('authentication.errors'),
                'failed' => session()->get('authentication.failed'),
            ]
        );
    }

    public function registerProcess(Request $request)
    {
        $api = new Service();

        $response = $api->register(
            $request->only(['name','email'])
        );

        if ($response['status'] === 201) {
            $parameters = $response['content']['uris']['create-password']['parameters'];

            $model = new PartialRegistration();
            $model->token = $parameters['token'];
            $model->email = $parameters['email'];
            $model->save();

            Notification::route('mail', $request->input('email'))
                ->notify(
                    (new CreatePassword($parameters['email'], $parameters['token']))->delay(now()->addMinutes(5))
                );

            return redirect()->route('create-password.view')
                ->with('authentication.parameters', $parameters);
        }

        if ($response['status'] === 422) {
            return redirect()->route('register.view')
                ->withInput()
                ->with('authentication.errors', $response['fields']);
        }

        return redirect()->route('register.view')
            ->with('authentication.failed', $response['content']);
    }

    public function registrationComplete()
    {
        return view(
            'registration-complete',
            [
            ]
        );
    }

    public function signIn()
    {
        return view(
            'sign-in',
            [
                'errors' => session()->get('authentication.errors')
            ]
        );
    }

    public function signInProcess(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials, $request->input('remember_me') !== null)) {
            return redirect()->route('home');
        }

        return redirect()->route('sign-in.view')
            ->withInput()
            ->with(
                'authentication.errors',
                Auth::errors()
            );
    }

    public function signOut(): RedirectResponse
    {
        Auth::guard()->logout();

        return redirect()->route('landing');
    }
}
