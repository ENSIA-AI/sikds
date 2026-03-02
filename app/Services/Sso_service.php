<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class Sso_service
{
    public function login_sso(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('sso.redirect_uri'),
            'state' => $state,
        ]);

        return redirect(config('sso.server').'/oauth/authorize?'.$query);
    }

    /**
     * @return Redirector|RedirectResponse
     *
     * @throws Throwable
     * @throws ConnectionException
     */
    public function callback(Request $request)
    {

        $state = $request->session()->pull('state');

        throw_unless(strlen($state) > 0 && $state === $request->input('state'), InvalidArgumentException::class);

        $response = Http::asForm()
            ->withOptions([
                'verify' => true,
            ])
            ->post(config('sso.server').'/oauth/token', [

                'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
                'code' => $request->input('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('sso.redirect_uri'),
            ]);

        $request->session()->put('access_token', $response->json()['access_token']);

        return redirect('/user');
    }

    // fetch user information and redirect to the home page
    /**
     * @throws ConnectionException
     */
    public function user_details(Request $request): Response|PromiseInterface
    {
        $access_token = $request->session()->get('access_token');

        return Http::withOptions(['verify' => false])->withHeaders(
            [
                'Accept' => 'application/json',
                'Authorization' => "Bearer $access_token",
            ]
        )->get(config('sso.server').'/api/user');
    }
}
