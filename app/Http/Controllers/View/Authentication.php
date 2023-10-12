<?php
declare(strict_types=1);

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function signOut(): RedirectResponse
    {
        Auth::guard()->logout();

        return redirect()->route('landing');
    }
}
