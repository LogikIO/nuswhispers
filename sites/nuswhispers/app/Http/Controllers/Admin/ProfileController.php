<?php namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\User as User;
use App\Models\UserProfile as UserProfile;

class ProfileController extends AdminController {

    public function getIndex()
    {
        $allowedProviders = ['facebook' => 'Facebook'];

        return view('admin.profile.index', [
            'providers' => $allowedProviders,
            'user'      => \Auth::user(),
            'profiles'  => \Auth::user()->profiles()->get()->keyBy('provider_name'),
        ]);
    }

    public function postEdit()
    {
        $validationRules = [
            'email' => 'required|email',
            'name' => 'required|string',
            'new_password' => 'min:6|max:32|string',
            'repeat_password' => 'same:new_password',
        ];

        $validator = \Validator::make(\Input::all(), $validationRules);
        if ($validator->fails()) {
            return \Redirect::back()->withErrors($validator);
        }

        try {
            $res = \Auth::user()->update([
                'email' => \Input::get('email'),
                'name' => \Input::get('name'),
                'password' => \Input::get('new_password') ? \Hash::make(\Input::get('new_password')) : \Auth::user()->password,
            ]);

            return \Redirect::back()->withMessage('Profile successfully updated.')
                ->with('alert-class', 'alert-success');
        } catch (\Exception $e) {
            return \Redirect::back()->withMessage('Failed updating profile: ' . $e->getMessage())
                ->with('alert-class', 'alert-danger');
        }
    }

    public function getConnect($provider = 'facebook')
    {
        if (\Input::all()) {
            try {
                $this->addProfile($provider, \Auth::user(), \Socialize::with($provider)->user());
                $this->flashMessage('Sucessfully connected to ' . ucfirst($provider) . '.');
            } catch (\Exception $e) {
                $this->flashMessage('Error connecting to provider: ' . $e->getMessage(), 'alert-danger');
            }
            return redirect('/admin/profile');
        } else {
            $scopes = $provider == 'facebook' ? ['manage_pages', 'publish_pages'] : [];
            return \Socialize::with($provider)->scopes($scopes)->redirect();
        }
    }

    public function getDelete($provider = 'facebook')
    {
        $profile = UserProfile::where('user_id', '=', \Auth::user()->user_id)->where('provider_name', '=', $provider)->delete();
        $this->flashMessage('Sucessfully removed ' . ucfirst($provider) . '.');
        return redirect('/admin/profile');
    }

    /**
     * TODO: Refactor to somewehere better; somewhere like a UserRepository.
     * @param string $provider  provider name
     * @param \App\Models\User user model
     * @param \Laravel\Socialite\Two\User $oauthUser oAuth user object
     */
    protected function addProfile($provider, $user, $oauthUser)
    {
        $pageToken = $oauthUser->token;
        $token = $oauthUser->token;

        if ($provider == 'facebook') {
            // Extend current token to long-lived access token
            $response = \Facebook::get('/oauth/access_token?client_id=' . urlencode(env('FACEBOOK_APP_ID')) . '&client_secret=' . urlencode(env('FACEBOOK_APP_SECRET')) . '&grant_type=fb_exchange_token&fb_exchange_token=' . urlencode($oauthUser->token));

            if (!isset($response->getDecodedBody()['access_token']))
                throw new \Exception('User is not a page admin of Facebook page #' . env('FACEBOOK_PAGE_ID', '') . '.');

            $token = $response->getDecodedBody()['access_token'];

            // Get page token (never expires)
            try {
                $response = \Facebook::get('/' . env('FACEBOOK_PAGE_ID', '') .'?fields=access_token', $token)->getGraphObject();
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                throw new \Exception('User is not a page admin of Facebook page #' . env('FACEBOOK_PAGE_ID', '') . '.');
            }

            $pageToken = $response['access_token'];
        }

        $user->profiles()->save(new UserProfile([
            'provider_id' => $oauthUser->id,
            'provider_name' => $provider,
            'provider_token' => $oauthUser->token,
            'page_token' => $pageToken,
            'data' => json_encode($oauthUser->user)
        ]));
    }

}
