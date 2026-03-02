<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Sso_service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SsoController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|\Response
    {
        $response = (new Sso_service)->user_details($request);

        if (! $response->ok()) {
            return redirect()->back();
        }

        $userName = $response->json()['nom_utilisateur'];

        // user should log in with fortifying
        $user = User::where('name', $userName)->first();

        if (! $user) {

            $user = User::create([
                'name' => $userName,
                'email' => $userName,
                'password' => bcrypt('password'),
            ]);
        }

        Auth::login($user);

        Session::put('user_data_'.Auth::user()->individu, $response->json());

        // initiate the default role;
        // TODO  mlanage the roles later

        // Auth::user()->activeRole = Auth::user()->affectationAll[0]['id'];

        Session::regenerate();

        return redirect('/dashboard');
    }
}
